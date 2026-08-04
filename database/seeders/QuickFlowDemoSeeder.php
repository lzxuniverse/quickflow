<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuickFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "Executing QuickFlowDemoSeeder (Fixed Deterministic Dataset)...
";

        // Disable Foreign Key checks before resetting
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // Clear existing tables to isolate this deterministic demo scenario
        $tablesToTruncate = [
            'tenants', 'users', 'properties', 'property_settings', 'tax_rates', 'services', 
            'room_types', 'rooms', 'bed_configurations', 'room_beds', 'inventory_grid', 
            'allotments', 'rate_plans', 'derived_rate_formulas', 'price_grid', 'restriction_grid', 
            'guests', 'guest_merges', 'guest_sentiments', 'reservations', 'reservation_versions', 
            'reservation_guests', 'reservation_rooms', 'folios', 'charges', 'payments', 
            'transaction_allocations', 'channels', 'channel_credentials', 'channel_room_mappings', 
            'channel_rate_mappings', 'channel_sync_jobs', 'ical_connections', 'pms_connections', 
            'pms_mappings', 'pms_sync_queue', 'reviews', 'audit_logs', 'api_keys', 'api_requests',
            'booking_pace_snapshots', 'revenue_metrics'
        ];

        foreach ($tablesToTruncate as $table) {
            DB::table($table)->truncate();
        }

        // 1. Seed Tenant
        $tenantId = 'tenant-demo-quickflow-master';
        DB::table('tenants')->insert([
            'uuid' => $tenantId,
            'name' => 'QuickFlow YouTube Demo Group',
            'plan_tier' => 'enterprise',
            'status' => 'active'
        ]);

        // 2. Seed Channels
        $channels = [
            ['id' => 'booking', 'name' => 'Booking.com', 'integration_mode' => 'xml_push'],
            ['id' => 'airbnb', 'name' => 'Airbnb', 'integration_mode' => 'json_rest'],
            ['id' => 'agoda', 'name' => 'Agoda', 'integration_mode' => 'xml_push'],
            ['id' => 'expedia', 'name' => 'Expedia', 'integration_mode' => 'xml_push'],
            ['id' => 'vrbo', 'name' => 'Vrbo', 'integration_mode' => 'xml_push']
        ];
        DB::table('channels')->insert($channels);

        // 3. Seed 10 Deterministic Properties
        $properties = [
            ['id' => 'prop-001', 'name' => 'Sunrise Beach Resort', 'tz' => 'Asia/Ho_Chi_Minh', 'cur' => 'USD', 'city' => 'Da Nang', 'country' => 'Vietnam'],
            ['id' => 'prop-002', 'name' => 'Alpine Mountain Cabin', 'tz' => 'Europe/Vienna', 'cur' => 'EUR', 'city' => 'Innsbruck', 'country' => 'Austria'],
            ['id' => 'prop-003', 'name' => 'Tokyo Luxury Oasis', 'tz' => 'Asia/Tokyo', 'cur' => 'JPY', 'city' => 'Tokyo', 'country' => 'Japan'],
            ['id' => 'prop-004', 'name' => 'Bali Cliffside Estate', 'tz' => 'Asia/Jakarta', 'cur' => 'USD', 'city' => 'Uluwatu', 'country' => 'Indonesia'],
            ['id' => 'prop-005', 'name' => 'Kyoto Bamboo Ryokan', 'tz' => 'Asia/Tokyo', 'cur' => 'JPY', 'city' => 'Kyoto', 'country' => 'Japan'],
            ['id' => 'prop-006', 'name' => 'Marina Bay Sky Loft', 'tz' => 'Asia/Singapore', 'cur' => 'SGD', 'city' => 'Singapore', 'country' => 'Singapore'],
            ['id' => 'prop-007', 'name' => 'Parisian Art Deco Inn', 'tz' => 'Europe/Paris', 'cur' => 'EUR', 'city' => 'Paris', 'country' => 'France'],
            ['id' => 'prop-008', 'name' => 'London Chic Townhouse', 'tz' => 'Europe/London', 'cur' => 'GBP', 'city' => 'London', 'country' => 'United Kingdom'],
            ['id' => 'prop-009', 'name' => 'Phuket Lagoon Cove', 'tz' => 'Asia/Bangkok', 'cur' => 'THB', 'city' => 'Phuket', 'country' => 'Thailand'],
            ['id' => 'prop-010', 'name' => 'New York Skyline Suite', 'tz' => 'America/New_York', 'cur' => 'USD', 'city' => 'New York', 'country' => 'United States']
        ];

        foreach ($properties as $p) {
            DB::table('properties')->insert([
                'uuid' => $p['id'],
                'tenant_id' => $tenantId,
                'name' => $p['name'],
                'timezone' => $p['tz'],
                'currency' => $p['cur'],
                'address_street' => '100 Main Demo Street',
                'address_city' => $p['city'],
                'address_state' => $p['city'],
                'address_postal_code' => '99999',
                'address_country' => $p['country'],
                'status' => 'active'
            ]);

            DB::table('property_settings')->insert([
                'property_id' => $p['id'],
                'check_in_time' => '14:00:00',
                'check_out_time' => '11:00:00',
                'tax_inclusive' => true,
                'overbooking_limit_percent' => 0.00
            ]);
        }

        // 4. Seed 3 Room Types per Property (30 total)
        // Mapped rooms layout: 10 rooms per property (5 Standard, 3 Deluxe, 2 Suite) = 100 rooms total
        $rtTemplates = [
            'std' => ['name' => 'Standard Room', 'suffix' => '-STD', 'rate' => 100.00, 'rooms' => 5],
            'dlx' => ['name' => 'Deluxe Room', 'suffix' => '-DLX', 'rate' => 180.00, 'rooms' => 3],
            'sui' => ['name' => 'Executive Suite', 'suffix' => '-SUI', 'rate' => 300.00, 'rooms' => 2]
        ];

        $roomsLookup = []; // Mapped by property_id and room index to allocate reservations cleanly
        $rtLookup = [];    // Mapped by property_id and type to get base rates

        foreach ($properties as $p) {
            $pId = $p['id'];
            $roomsLookup[$pId] = [];
            $rtLookup[$pId] = [];
            $roomIdx = 1;

            foreach ($rtTemplates as $key => $tpl) {
                $rtId = $pId . $tpl['suffix'];
                $rtLookup[$pId][$key] = [
                    'uuid' => $rtId,
                    'rate' => $tpl['rate']
                ];

                DB::table('room_types')->insert([
                    'uuid' => $rtId,
                    'property_id' => $pId,
                    'name' => $tpl['name'],
                    'code' => $pId . $tpl['suffix'] . '-CODE',
                    'max_adults' => 2,
                    'max_children' => 1,
                    'base_rate' => $tpl['rate'],
                    'inventory_mode' => 'physical'
                ]);

                $rpId = $rtId . '-RATEPLAN';
                DB::table('rate_plans')->insert([
                    'uuid' => $rpId,
                    'property_id' => $pId,
                    'room_type_id' => $rtId,
                    'name' => 'Standard Rate Plan',
                    'is_derived' => false,
                    'base_rate_plan_id' => null
                ]);

                // Create physical rooms
                for ($r = 0; $r < $tpl['rooms']; $r++) {
                    $roomId = $rtId . '-RM-' . $r;
                    $roomsLookup[$pId][] = [
                        'uuid' => $roomId,
                        'rt_uuid' => $rtId,
                        'rate_plan_id' => $rpId,
                        'rate' => $tpl['rate']
                    ];

                    DB::table('rooms')->insert([
                        'uuid' => $roomId,
                        'room_type_id' => $rtId,
                        'room_number' => '10' . $roomIdx,
                        'floor' => '1',
                        'clean_status' => 'clean',
                        'service_status' => 'available'
                    ]);
                    $roomIdx++;
                }
            }
        }

        // 5. Seed 50 Deterministic Guests
        $guestIds = [];
        for ($g = 1; $g <= 50; $g++) {
            $gId = 'guest-' . str_pad($g, 3, '0', STR_PAD_LEFT);
            $guestIds[] = $gId;
            DB::table('guests')->insert([
                'uuid' => $gId,
                'tenant_id' => $tenantId,
                'first_name' => 'GuestFirst' . $g,
                'last_name' => 'GuestLast' . $g,
                'email' => "guest-$g@quickflow-demo.com",
                'phone' => '+1 555 ' . str_pad($g, 4, '0', STR_PAD_LEFT),
                'passport_number' => 'PASS' . str_pad($g, 6, '0', STR_PAD_LEFT),
                'preferences' => json_encode(['vip' => ($g <= 5)]) // First 5 are VIPs
            ]);
        }

        // 6. Seed exactly 200 Reservations
        // Mapped evenly: 20 reservations per property
        $resCount = 1;
        $sources = ['Booking.com', 'Airbnb', 'Agoda', 'Expedia', 'Vrbo', 'Direct'];
        $statuses = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];
        
        $reservationIds = [];

        foreach ($properties as $p) {
            $pId = $p['id'];
            
            for ($r = 0; $r < 20; $r++) {
                $resId = 'QF-' . str_pad($resCount, 4, '0', STR_PAD_LEFT);
                $reservationIds[] = $resId;
                
                // Mapped deterministically to guest and room
                $guestId = $guestIds[($resCount - 1) % 50];
                $roomInfo = $roomsLookup[$pId][$r % 10]; // Rotate rooms 0-9
                
                $checkInDate = now()->addDays(($r * 2) - 10); // Spans from -10 to +30 days
                $stayNights = ($r % 3) + 1; // 1 to 3 nights stay
                $checkOutDate = (clone $checkInDate)->modify('+' . $stayNights . ' days');
                $total = $roomInfo['rate'] * $stayNights;

                DB::table('reservations')->insert([
                    'uuid' => $resId,
                    'property_id' => $pId,
                    'guest_id' => $guestId,
                    'status' => $statuses[$r % 4],
                    'source' => $sources[$r % 6],
                    'check_in_date' => $checkInDate->format('Y-m-d'),
                    'check_out_date' => $checkOutDate->format('Y-m-d'),
                    'total_amount' => $total
                ]);

                // Create daily stay allocation
                for ($d = 0; $d < $stayNights; $d++) {
                    $currDate = (clone $checkInDate)->modify('+' . $d . ' days');
                    DB::table('reservation_rooms')->insert([
                        'uuid' => $resId . '-DAY-' . $d,
                        'reservation_id' => $resId,
                        'room_type_id' => $roomInfo['rt_uuid'],
                        'room_id' => $roomInfo['uuid'],
                        'date' => $currDate->format('Y-m-d'),
                        'rate_plan_id' => $roomInfo['rate_plan_id'],
                        'price_charged' => $roomInfo['rate']
                    ]);
                }

                $resCount++;
            }
        }

        // 7. Seed exactly 100 Reviews
        // Mapped to the first 100 reservations
        $reviewPool = [
            ['rating' => 5.00, 'content' => 'Outstanding location! Extremely friendly staff, clean room.', 'response' => 'Thank you for your warm words!'],
            ['rating' => 4.00, 'content' => 'Nice pool area, breakfast was good but room was a bit small.', 'response' => 'We are glad you liked our facilities.'],
            ['rating' => 3.00, 'content' => 'Great views, but the Wi-Fi was dropping connection frequently.', 'response' => 'Sorry for the internet dropouts, we are upgrading.'],
            ['rating' => 5.00, 'content' => 'Perfect. Balcony view is breathtaking. Super clean.', 'response' => 'Thank you so much! We are glad you enjoyed the balcony view.']
        ];

        for ($rev = 0; $rev < 100; $rev++) {
            $resId = $reservationIds[$rev];
            $resDetails = DB::table('reservations')->where('uuid', $resId)->first();
            $item = $reviewPool[$rev % 4];

            DB::table('reviews')->insert([
                'uuid' => 'REV-' . str_pad($rev + 1, 4, '0', STR_PAD_LEFT),
                'property_id' => $resDetails->property_id,
                'reservation_id' => $resId,
                'source' => $resDetails->source,
                'rating_overall' => $item['rating'],
                'content' => $item['content'],
                'response' => $item['response']
            ]);
        }

        // 8. Seed Revenue Data ($25,000/month = ~$833.33/day over 30 days for each property)
        $dailyRevenue = 25000.00 / 30.00;
        foreach ($properties as $p) {
            for ($offset = -15; $offset <= 15; $offset++) {
                $targetDate = now()->addDays($offset)->format('Y-m-d');
                DB::table('revenue_metrics')->insert([
                    'date' => $targetDate,
                    'property_id' => $p['id'],
                    'rooms_sold' => 6,
                    'occupancy_rate' => 60.00, // 6 out of 10 rooms occupied
                    'revenue_rooms' => $dailyRevenue,
                    'revenue_incidentals' => 150.00,
                    'adr' => $dailyRevenue / 6,
                    'revpar' => $dailyRevenue / 10
                ]);
            }
        }

        // Re-enable Foreign Key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        echo "QuickFlowDemoSeeder finished successfully. Recreated 10 properties, 100 rooms, 50 guests, 200 reservations, and 100 reviews!
";
    }
}
