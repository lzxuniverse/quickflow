<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        echo "Executing ScenarioSeeder (Generating 5 Specialized Demo Properties)...\n";

        // Disable Foreign Key checks before inserting scenario overrides
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // 1. Create a dedicated Demo Tenant
        $tenantId = (string) Str::uuid();
        DB::table('tenants')->insert([
            'uuid' => $tenantId,
            'name' => 'Global Luxury Properties Group',
            'plan_tier' => 'enterprise',
            'status' => 'active'
        ]);

        // 2. Define the 5 Specialized Scenarios
        $scenarios = [
            // Scenario 1: Luxury Hotel
            'luxury_hotel' => [
                'property' => [
                    'name' => 'The Royal Grand Palace & Spa',
                    'timezone' => 'Asia/Tokyo',
                    'currency' => 'JPY',
                    'city' => 'Tokyo',
                    'country' => 'Japan',
                    'phone' => '+81 3-5555-0199',
                    'email' => 'reservations@royalgrandpalace.jp'
                ],
                'room_types' => [
                    ['name' => 'Imperial Presidential Suite', 'code' => 'SUITE-PRES', 'adults' => 4, 'children' => 2, 'rate' => 150000.00], // ~ $1,000 USD
                    ['name' => 'Club Executive Room', 'code' => 'ROOM-EXEC', 'adults' => 2, 'children' => 1, 'rate' => 75000.00],    // ~ $500 USD
                    ['name' => 'Deluxe Court Room', 'code' => 'ROOM-DLX', 'adults' => 2, 'children' => 0, 'rate' => 45000.00]      // ~ $300 USD
                ],
                'rooms' => [
                    'SUITE-PRES' => ['Suite 501'],
                    'ROOM-EXEC' => ['Room 401', 'Room 402', 'Room 403'],
                    'ROOM-DLX' => ['Room 301', 'Room 302', 'Room 303', 'Room 304', 'Room 305']
                ]
            ],
            // Scenario 2: Beach Resort
            'beach_resort' => [
                'property' => [
                    'name' => 'Emerald Bay Sands Beach Resort & Spa',
                    'timezone' => 'Asia/Phuket',
                    'currency' => 'THB',
                    'city' => 'Phuket',
                    'country' => 'Thailand',
                    'phone' => '+66 76 555 123',
                    'email' => 'stay@emeraldbaysands.com'
                ],
                'room_types' => [
                    ['name' => 'Beachfront Pool Villa', 'code' => 'VILLA-BEACH', 'adults' => 4, 'children' => 4, 'rate' => 28000.00], // ~ $800 USD
                    ['name' => 'Ocean View Family Suite', 'code' => 'SUITE-OCEAN', 'adults' => 4, 'children' => 2, 'rate' => 15000.00], // ~ $430 USD
                    ['name' => 'Lagoon Access Room', 'code' => 'ROOM-LAGOON', 'adults' => 2, 'children' => 1, 'rate' => 7000.00]     // ~ $200 USD
                ],
                'rooms' => [
                    'VILLA-BEACH' => ['Villa 101', 'Villa 102'],
                    'SUITE-OCEAN' => ['Bungalow 201', 'Bungalow 202', 'Bungalow 203'],
                    'ROOM-LAGOON' => ['Room 110', 'Room 111', 'Room 112', 'Room 113', 'Room 114']
                ]
            ],
            // Scenario 3: Boutique Hotel
            'boutique_hotel' => [
                'property' => [
                    'name' => 'The Art Deco Heritage Hotel',
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'currency' => 'VND',
                    'city' => 'Da Nang',
                    'country' => 'Vietnam',
                    'phone' => '+84 236 555 0122',
                    'email' => 'hello@artdecoheritage.vn'
                ],
                'room_types' => [
                    ['name' => 'Artist Loft Suite', 'code' => 'LOFT-ARTIST', 'adults' => 2, 'children' => 0, 'rate' => 6000000.00], // ~ $240 USD
                    ['name' => 'Vintage Classic Double', 'code' => 'DBL-VINTAGE', 'adults' => 2, 'children' => 1, 'rate' => 3800000.00], // ~ $150 USD
                    ['name' => 'Heritage Single Room', 'code' => 'SGL-HERITAGE', 'adults' => 1, 'children' => 0, 'rate' => 2200000.00] // ~ $88 USD
                ],
                'rooms' => [
                    'LOFT-ARTIST' => ['Loft 401', 'Loft 402'],
                    'DBL-VINTAGE' => ['Room 201', 'Room 202', 'Room 203', 'Room 204'],
                    'SGL-HERITAGE' => ['Room 101', 'Room 102', 'Room 103']
                ]
            ],
            // Scenario 4: City Apartment
            'city_apartment' => [
                'property' => [
                    'name' => 'Marina Bay Skyview Penthouse & Lofts',
                    'timezone' => 'Asia/Singapore',
                    'currency' => 'SGD',
                    'city' => 'Singapore',
                    'country' => 'Singapore',
                    'phone' => '+65 6555 8989',
                    'email' => 'concierge@marinabayskyview.sg'
                ],
                'room_types' => [
                    ['name' => '3-Bedroom Sky Penthouse', 'code' => 'PENT-SKY', 'adults' => 6, 'children' => 4, 'rate' => 1200.00], // ~ $900 USD
                    ['name' => '2-Bedroom Executive Apartment', 'code' => 'APT-EXEC', 'adults' => 4, 'children' => 2, 'rate' => 750.00], // ~ $560 USD
                    ['name' => '1-Bedroom Sky Loft', 'code' => 'LOFT-SKY', 'adults' => 2, 'children' => 0, 'rate' => 400.00]        // ~ $300 USD
                ],
                'rooms' => [
                    'PENT-SKY' => ['Apt 4801'],
                    'APT-EXEC' => ['Apt 3201', 'Apt 3202', 'Apt 3203'],
                    'LOFT-SKY' => ['Apt 1501', 'Apt 1502', 'Apt 1503', 'Apt 1504']
                ]
            ],
            // Scenario 5: Villa Rental
            'villa_rental' => [
                'property' => [
                    'name' => 'Luxe Cliffside Infinity Villa',
                    'timezone' => 'Asia/Jakarta',
                    'currency' => 'IDR',
                    'city' => 'Bali',
                    'country' => 'Indonesia',
                    'phone' => '+62 361 555 0890',
                    'email' => 'butler@luxecliffsidebali.com'
                ],
                'room_types' => [
                    ['name' => '4-Bedroom Private Pool Villa', 'code' => 'VILLA-CLIFF', 'adults' => 8, 'children' => 6, 'rate' => 22000000.00] // ~ $1,400 USD
                ],
                'rooms' => [
                    'VILLA-CLIFF' => ['Villa Cliffside']
                ]
            ]
        ];

        // 3. Setup static demo Guests
        $demoGuests = [
            ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@gmail.com', 'phone' => '+1 555 1234567', 'passport' => 'US987654'],
            ['first_name' => 'Emily', 'last_name' => 'Smith', 'email' => 'emily.smith@yahoo.com', 'phone' => '+44 20 7946 0958', 'passport' => 'UK567890'],
            ['first_name' => 'Nguyen', 'last_name' => 'An', 'email' => 'an.nguyen@gmail.com', 'phone' => '+84 90 1234567', 'passport' => 'VN123456'],
            ['first_name' => 'Yuki', 'last_name' => 'Tanaka', 'email' => 'yuki.t@docomo.ne.jp', 'phone' => '+81 90 5555 0122', 'passport' => 'JP654321'],
            ['first_name' => 'Budi', 'last_name' => 'Hartono', 'email' => 'budi.h@gmail.com', 'phone' => '+62 812 3456 7890', 'passport' => 'ID246810']
        ];

        $guestUuids = [];
        foreach ($demoGuests as $g) {
            $uuid = (string) Str::uuid();
            $guestUuids[] = $uuid;
            DB::table('guests')->insert([
                'uuid' => $uuid,
                'tenant_id' => $tenantId,
                'first_name' => $g['first_name'],
                'last_name' => $g['last_name'],
                'email' => $g['email'],
                'phone' => $g['phone'],
                'passport_number' => $g['passport'],
                'preferences' => json_encode(['vip' => true])
            ]);
        }

        // 4. Loop to insert Properties, Room Types, Rooms
        foreach ($scenarios as $key => $data) {
            $propId = (string) Str::uuid();
            
            // Insert property
            DB::table('properties')->insert([
                'uuid' => $propId,
                'tenant_id' => $tenantId,
                'name' => $data['property']['name'],
                'timezone' => $data['property']['timezone'],
                'currency' => $data['property']['currency'],
                'address_street' => 'Luxury Cliffside Boulevard #1',
                'address_city' => $data['property']['city'],
                'address_state' => $data['property']['country'],
                'address_postal_code' => '80361',
                'address_country' => $data['property']['country'],
                'lat' => -8.7909,
                'lng' => 115.1666,
                'contact_phone' => $data['property']['phone'],
                'contact_email' => $data['property']['email'],
                'status' => 'active'
            ]);

            // Settings
            DB::table('property_settings')->insert([
                'property_id' => $propId,
                'check_in_time' => '14:00:00',
                'check_out_time' => '11:00:00',
                'tax_inclusive' => true,
                'overbooking_limit_percent' => 0.00
            ]);

            // Mapped room types & rooms
            $rtLookup = [];
            foreach ($data['room_types'] as $rt) {
                $rtId = (string) Str::uuid();
                $rtLookup[$rt['code']] = [
                    'uuid' => $rtId,
                    'rate' => $rt['rate']
                ];

                DB::table('room_types')->insert([
                    'uuid' => $rtId,
                    'property_id' => $propId,
                    'name' => $rt['name'],
                    'code' => $rt['code'],
                    'max_adults' => $rt['adults'],
                    'max_children' => $rt['children'],
                    'base_rate' => $rt['rate'],
                    'inventory_mode' => 'physical'
                ]);

                // Rate plan
                $rpId = (string) Str::uuid();
                DB::table('rate_plans')->insert([
                    'uuid' => $rpId,
                    'property_id' => $propId,
                    'room_type_id' => $rtId,
                    'name' => 'Standard Rate Plan',
                    'is_derived' => false,
                    'base_rate_plan_id' => null
                ]);

                // Physical rooms
                $roomList = $data['rooms'][$rt['code']] ?? [];
                foreach ($roomList as $roomNum) {
                    $roomId = (string) Str::uuid();
                    DB::table('rooms')->insert([
                        'uuid' => $roomId,
                        'room_type_id' => $rtId,
                        'room_number' => $roomNum,
                        'floor' => '1',
                        'clean_status' => 'clean',
                        'service_status' => 'available'
                    ]);

                    // Seed some sample bookings for this specific physical room
                    $resId = (string) Str::uuid();
                    $guestId = $guestUuids[rand(0, 4)];
                    
                    $checkIn = now()->addDays(rand(1, 10));
                    $checkOut = (clone $checkIn)->addDays(3);

                    DB::table('reservations')->insert([
                        'uuid' => $resId,
                        'property_id' => $propId,
                        'guest_id' => $guestId,
                        'status' => 'confirmed',
                        'source' => 'Booking.com',
                        'check_in_date' => $checkIn->format('Y-m-d'),
                        'check_out_date' => $checkOut->format('Y-m-d'),
                        'total_amount' => $rt['rate'] * 3
                    ]);

                    // allocation
                    for ($d = 0; $d < 3; $d++) {
                        DB::table('reservation_rooms')->insert([
                            'uuid' => (string) Str::uuid(),
                            'reservation_id' => $resId,
                            'room_type_id' => $rtId,
                            'room_id' => $roomId,
                            'date' => $checkIn->copy()->addDays($d)->format('Y-m-d'),
                            'rate_plan_id' => $rpId,
                            'price_charged' => $rt['rate']
                        ]);
                    }

                    // Seed Review for this reservation
                    DB::table('reviews')->insert([
                        'uuid' => (string) Str::uuid(),
                        'property_id' => $propId,
                        'reservation_id' => $resId,
                        'source' => 'Booking.com',
                        'rating_overall' => 5.00,
                        'content' => "Perfect experience at {$data['property']['name']}. Highly recommend the {$rt['name']}!",
                        'response' => 'Thank you for choosing us! Looking forward to your next visit.'
                    ]);
                }
            }

            // Seed historical metrics
            for ($dayOffset = -15; $dayOffset <= 15; $dayOffset++) {
                $targetDate = now()->addDays($dayOffset)->format('Y-m-d');
                DB::table('revenue_metrics')->insert([
                    'date' => $targetDate,
                    'property_id' => $propId,
                    'rooms_sold' => rand(2, 6),
                    'occupancy_rate' => 65.00,
                    'revenue_rooms' => 1200.00,
                    'revenue_incidentals' => 150.00,
                    'adr' => 200.00,
                    'revpar' => 130.00
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        echo "ScenarioSeeder finished. Generated specialized luxury, beach, boutique, city and villa properties.\n";
    }
}
