<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "2. DemoSeeder: Setting up 10,000 Global Guests & 50,000 Reservation Records...
";

        $tables = ['guests', 'guest_merges', 'guest_sentiments', 'reservations', 'reservation_versions', 'reservation_guests', 'reservation_rooms', 'folios', 'charges', 'payments', 'transaction_allocations', 'pms_connections', 'ical_connections'];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        $tenantIds = DB::table('tenants')->pluck('uuid')->toArray();
        $propertyIds = DB::table('properties')->pluck('uuid')->toArray();
        
        $roomTypes = DB::table('room_types')->select('uuid', 'property_id', 'base_rate')->get()->groupBy('property_id');
        $rooms = DB::table('rooms')->select('uuid', 'room_type_id')->get()->groupBy('room_type_id');
        $ratePlans = DB::table('rate_plans')->select('uuid', 'room_type_id')->get()->groupBy('room_type_id');

        // Multi-country Guest profiles
        $countries = ['United States', 'Canada', 'United Kingdom', 'Australia', 'Germany', 'France', 'Spain', 'Italy', 'Netherlands', 'Sweden', 'Norway', 'Denmark', 'Japan', 'Singapore'];

        // Guests (10,000)
        $guestIds = [];
        $guestsBatch = [];
        for ($g = 0; $g < 10000; $g++) {
            $uuid = (string) Str::uuid();
            $guestIds[] = $uuid;
            
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $country = $faker->randomElement($countries);

            $guestsBatch[] = [
                'uuid' => $uuid,
                'tenant_id' => $faker->randomElement($tenantIds),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName . '.' . $lastName . '-' . $g . '@example-guest.com'),
                'phone' => $faker->phoneNumber(),
                'passport_number' => strtoupper($faker->bothify('??######')),
                'preferences' => json_encode(['country' => $country, 'vip' => ($g % 20 === 0)])
            ];

            if (count($guestsBatch) >= 2000) {
                DB::table('guests')->insert($guestsBatch);
                $guestsBatch = [];
            }
        }
        if (!empty($guestsBatch)) {
            DB::table('guests')->insert($guestsBatch);
        }

        // Reservations (50,000)
        $reservationsInsert = [];
        $reservationRoomsInsert = [];
        $sources = ['Booking.com', 'Airbnb', 'Agoda', 'Expedia', 'Direct Website', 'Google Hotel Ads', 'Trip.com', 'VRBO'];
        $statuses = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];
        
        $bookingPrefixes = [
            'Booking.com' => 'BK-',
            'Airbnb' => 'AIR-',
            'Agoda' => 'AGD-',
            'Expedia' => 'EXP-',
            'VRBO' => 'VRB-',
            'Direct Website' => 'DIR-',
            'Google Hotel Ads' => 'GGL-',
            'Trip.com' => 'TRP-'
        ];

        for ($resCount = 0; $resCount < 50000; $resCount++) {
            $propId = $faker->randomElement($propertyIds);
            
            if (!isset($roomTypes[$propId])) continue;

            $selectedRt = $faker->randomElement($roomTypes[$propId]);
            $rtId = $selectedRt->uuid;
            
            if (!isset($rooms[$rtId])) continue;
            $selectedRoomId = $faker->randomElement($rooms[$rtId])->uuid;

            $checkIn = $faker->dateTimeBetween('-90 days', '+120 days');
            $stayNights = $faker->numberBetween(1, 5);
            $checkOut = (clone $checkIn)->modify('+' . $stayNights . ' days');
            $total = $selectedRt->base_rate * $stayNights;
            $src = $faker->randomElement($sources);

            // Realistic Booking Code used as the UUID primary key (100% unique using $resCount)
            $resUuid = $bookingPrefixes[$src] . '2026-' . (100000 + $resCount);

            $reservationsInsert[] = [
                'uuid' => $resUuid,
                'property_id' => $propId,
                'guest_id' => $faker->randomElement($guestIds),
                'status' => $faker->randomElement($statuses),
                'source' => $src,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'total_amount' => $total,
            ];

            $ratePlanId = isset($ratePlans[$rtId]) ? $ratePlans[$rtId][0]->uuid : null;
            for ($d = 0; $d < $stayNights; $d++) {
                $currDate = (clone $checkIn)->modify('+' . $d . ' days');
                $reservationRoomsInsert[] = [
                    'uuid' => $resUuid . '-DAY-' . $d,
                    'reservation_id' => $resUuid,
                    'room_type_id' => $rtId,
                    'room_id' => $selectedRoomId,
                    'date' => $currDate->format('Y-m-d'),
                    'rate_plan_id' => $ratePlanId,
                    'price_charged' => $selectedRt->base_rate
                ];
            }

            if (count($reservationsInsert) >= 2000) {
                DB::table('reservations')->insert($reservationsInsert);
                $reservationsInsert = [];
            }
            if (count($reservationRoomsInsert) >= 2000) {
                DB::table('reservation_rooms')->insert($reservationRoomsInsert);
                $reservationRoomsInsert = [];
            }
        }

        if (!empty($reservationsInsert)) {
            DB::table('reservations')->insert($reservationsInsert);
        }
        if (!empty($reservationRoomsInsert)) {
            DB::table('reservation_rooms')->insert($reservationRoomsInsert);
        }

        // PMS Connections (200 PMS Links, one per property)
        $pmsProviders = ['cloudbeds', 'mews', 'opera', 'custom'];
        $pmsInsert = [];
        foreach ($propertyIds as $pId) {
            $pmsInsert[] = [
                'uuid' => (string) Str::uuid(),
                'property_id' => $pId,
                'pms_provider' => $faker->randomElement($pmsProviders),
                'pms_access_token' => 'prod_access_' . Str::random(32),
                'pms_refresh_token' => 'prod_refresh_' . Str::random(32),
                'token_expires_at' => now()->addDays(60)
            ];
        }
        DB::table('pms_connections')->insert($pmsInsert);

        echo "DemoSeeder complete. Created 10,000 guests, 50,000 reservations, and 200 PMS integration logs.
";
    }
}
