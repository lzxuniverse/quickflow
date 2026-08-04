<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "4. Executing TransactionSeeder (Transaction tables: 1000+ rows)...
";

        // Clear existing transaction tables
        DB::table('reservations')->truncate();
        DB::table('reservation_rooms')->truncate();
        DB::table('folios')->truncate();
        DB::table('charges')->truncate();
        DB::table('payments')->truncate();
        DB::table('transaction_allocations')->truncate();
        DB::table('webhook_endpoints')->truncate();
        DB::table('webhook_delivery_attempts')->truncate();
        DB::table('audit_logs')->truncate();

        $tenantIds = DB::table('tenants')->pluck('uuid')->toArray();
        $propertyIds = DB::table('properties')->pluck('uuid')->toArray();
        $guestIds = DB::table('guests')->pluck('uuid')->toArray();
        
        $roomTypes = DB::table('room_types')->select('uuid', 'property_id', 'base_rate')->get()->groupBy('property_id');
        $rooms = DB::table('rooms')->select('uuid', 'room_type_id')->get()->groupBy('room_type_id');
        $ratePlans = DB::table('rate_plans')->select('uuid', 'room_type_id')->get()->groupBy('room_type_id');

        // Reservations (2000 rows) & Reservation Rooms Daily Grid
        $reservationsInsert = [];
        $reservationRoomsInsert = [];
        $reservationIds = [];
        $sources = ['Booking.com', 'Airbnb', 'Agoda', 'Expedia', 'Vrbo', 'Direct'];
        $statuses = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];

        for ($resCount = 0; $resCount < 2000; $resCount++) {
            $resUuid = (string) Str::uuid();
            $reservationIds[] = $resUuid;
            $propId = $faker->randomElement($propertyIds);
            
            if (!isset($roomTypes[$propId])) continue;

            $selectedRt = $faker->randomElement($roomTypes[$propId]);
            $rtId = $selectedRt->uuid;
            
            if (!isset($rooms[$rtId])) continue;
            $selectedRoomId = $faker->randomElement($rooms[$rtId])->uuid;

            $checkIn = $faker->dateTimeBetween('-15 days', '+30 days');
            $stayNights = $faker->numberBetween(1, 4);
            $checkOut = (clone $checkIn)->modify('+' . $stayNights . ' days');
            $total = $selectedRt->base_rate * $stayNights;

            $reservationsInsert[] = [
                'uuid' => $resUuid,
                'property_id' => $propId,
                'guest_id' => $faker->randomElement($guestIds),
                'status' => $faker->randomElement($statuses),
                'source' => $faker->randomElement($sources),
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'total_amount' => $total,
            ];

            $ratePlanId = isset($ratePlans[$rtId]) ? $ratePlans[$rtId][0]->uuid : null;
            for ($d = 0; $d < $stayNights; $d++) {
                $currDate = (clone $checkIn)->modify('+' . $d . ' days');
                $reservationRoomsInsert[] = [
                    'uuid' => (string) Str::uuid(),
                    'reservation_id' => $resUuid,
                    'room_type_id' => $rtId,
                    'room_id' => $selectedRoomId,
                    'date' => $currDate->format('Y-m-d'),
                    'rate_plan_id' => $ratePlanId,
                    'price_charged' => $selectedRt->base_rate
                ];
            }

            if (count($reservationsInsert) >= 500) {
                DB::table('reservations')->insert($reservationsInsert);
                $reservationsInsert = [];
            }
            if (count($reservationRoomsInsert) >= 1000) {
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

        // Fetch freshly created reservation list for folio setup
        $dbReservations = DB::table('reservations')->select('uuid', 'guest_id', 'total_amount')->get();

        // Folios, Charges, Payments (2000+ rows)
        $foliosInsert = [];
        $chargesInsert = [];
        $paymentsInsert = [];
        $allocationsInsert = [];

        foreach ($dbReservations as $res) {
            $folioUuid = (string) Str::uuid();
            $foliosInsert[] = [
                'uuid' => $folioUuid,
                'reservation_id' => $res->uuid,
                'guest_id' => $res->guest_id,
                'status' => 'closed',
                'balance' => 0.00
            ];

            // Charge
            $chargeUuid = (string) Str::uuid();
            $chargesInsert[] = [
                'uuid' => $chargeUuid,
                'folio_id' => $folioUuid,
                'service_id' => null,
                'description' => 'Room Accommodation Charge',
                'base_amount' => $res->total_amount,
                'tax_amount' => 0.00,
                'is_void' => false
            ];

            // Payment
            $paymentUuid = (string) Str::uuid();
            $paymentsInsert[] = [
                'uuid' => $paymentUuid,
                'folio_id' => $folioUuid,
                'payment_method' => $faker->randomElement(['credit_card', 'cash']),
                'amount' => $res->total_amount,
                'gateway_transaction_id' => 'tx_' . Str::random(10)
            ];

            // Allocation
            $allocationsInsert[] = [
                'uuid' => (string) Str::uuid(),
                'payment_id' => $paymentUuid,
                'charge_id' => $chargeUuid,
                'amount_allocated' => $res->total_amount
            ];
        }

        foreach (array_chunk($foliosInsert, 500) as $chunk) DB::table('folios')->insert($chunk);
        foreach (array_chunk($chargesInsert, 500) as $chunk) DB::table('charges')->insert($chunk);
        foreach (array_chunk($paymentsInsert, 500) as $chunk) DB::table('payments')->insert($chunk);
        foreach (array_chunk($allocationsInsert, 500) as $chunk) DB::table('transaction_allocations')->insert($chunk);

        // Seed Webhook Endpoint & Delivery Attempts (1000+ rows)
        $endpointUuid = (string) Str::uuid();
        DB::table('webhook_endpoints')->insert([
            'uuid' => $endpointUuid,
            'tenant_id' => $tenantIds[0],
            'target_url' => 'https://example.com/webhooks',
            'secret_key' => 'whsec_' . Str::random(24),
            'subscribed_events' => json_encode(['reservation.created'])
        ]);

        $webhooksInsert = [];
        for ($w = 0; $w < 1000; $w++) {
            $webhooksInsert[] = [
                'uuid' => (string) Str::uuid(),
                'endpoint_id' => $endpointUuid,
                'event_type' => 'reservation.created',
                'payload' => json_encode(['res_id' => 'test']),
                'response_code' => 200,
                'status' => 'success'
            ];
        }
        foreach (array_chunk($webhooksInsert, 500) as $chunk) DB::table('webhook_delivery_attempts')->insert($chunk);

        // Seed Audit Logs (1000+ rows)
        $auditInsert = [];
        for ($a = 0; $a < 1000; $a++) {
            $auditInsert[] = [
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $faker->randomElement($tenantIds),
                'user_id' => null,
                'action' => 'update',
                'table_name' => 'reservations',
                'row_id' => $faker->randomElement($reservationIds),
                'changes_diff' => json_encode(['before' => ['status' => 'confirmed'], 'after' => ['status' => 'checked_in']])
            ];
        }
        foreach (array_chunk($auditInsert, 500) as $chunk) DB::table('audit_logs')->insert($chunk);

        // Seed Reviews (500 rows)
        $reviewsInsert = [];
        $reviewsPool = [
            ['rating' => 5.00, 'content' => 'Outstanding location! Extremely friendly staff, clean room.', 'response' => 'Thank you for your warm words!'],
            ['rating' => 4.00, 'content' => 'Nice pool area, breakfast was good but room was a bit small.', 'response' => 'We are glad you liked our facilities.'],
            ['rating' => 3.00, 'content' => 'Great views, but the Wi-Fi was dropping connection frequently.', 'response' => 'Sorry for the internet dropouts, we are upgrading.']
        ];
        
        $takeReservations = array_slice($reservationIds, 0, 500);
        foreach ($takeReservations as $resId) {
            $resDetails = DB::table('reservations')->where('uuid', $resId)->first();
            if (!$resDetails) continue;
            $item = $faker->randomElement($reviewsPool);
            $reviewsInsert[] = [
                'uuid' => (string) Str::uuid(),
                'property_id' => $resDetails->property_id,
                'reservation_id' => $resId,
                'source' => $resDetails->source,
                'rating_overall' => $item['rating'],
                'content' => $item['content'],
                'response' => $item['response'],
            ];
        }
        DB::table('reviews')->insert($reviewsInsert);

        echo "TransactionSeeder complete. Created 2000 reservations, 2000 folios/charges/payments, 1000 webhooks, 1000 audits, 500 reviews.
";
    }
}
