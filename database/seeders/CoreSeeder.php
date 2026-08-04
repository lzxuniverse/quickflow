<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "1. CoreSeeder: Setting up Global Infrastructure...
";

        // Clear existing tables
        $tables = ['tenants', 'users', 'properties', 'property_settings', 'tax_rates', 'services', 'room_types', 'rooms', 'bed_configurations', 'room_beds', 'channels'];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        // Channels (8 channels)
        $channels = [
            ['id' => 'booking', 'name' => 'Booking.com', 'integration_mode' => 'xml_push'],
            ['id' => 'airbnb', 'name' => 'Airbnb', 'integration_mode' => 'json_rest'],
            ['id' => 'agoda', 'name' => 'Agoda', 'integration_mode' => 'xml_push'],
            ['id' => 'expedia', 'name' => 'Expedia', 'integration_mode' => 'xml_push'],
            ['id' => 'vrbo', 'name' => 'VRBO', 'integration_mode' => 'xml_push'],
            ['id' => 'direct', 'name' => 'Direct Website', 'integration_mode' => 'json_rest'],
            ['id' => 'google', 'name' => 'Google Hotel Ads', 'integration_mode' => 'xml_push'],
            ['id' => 'trip', 'name' => 'Trip.com', 'integration_mode' => 'xml_push']
        ];
        DB::table('channels')->insert($channels);

        // Tenants (5 tenants)
        $tenantIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $uuid = (string) Str::uuid();
            $tenantIds[] = $uuid;
            DB::table('tenants')->insert([
                'uuid' => $uuid,
                'name' => "Global Hospitality Group LLC $i",
                'plan_tier' => 'enterprise',
                'status' => 'active'
            ]);
        }

        // Static geographic metadata covering North America, Europe, Oceania, Asia, Middle East, South America, Africa
        $locations = [
            // North America
            ['city' => 'New York', 'country' => 'United States', 'tz' => 'America/New_York', 'cur' => 'USD'],
            ['city' => 'Los Angeles', 'country' => 'United States', 'tz' => 'America/Los_Angeles', 'cur' => 'USD'],
            ['city' => 'Vancouver', 'country' => 'Canada', 'tz' => 'America/Vancouver', 'cur' => 'CAD'],
            ['city' => 'Cancun', 'country' => 'Mexico', 'tz' => 'America/Cancun', 'cur' => 'MXN'],
            // Europe
            ['city' => 'London', 'country' => 'United Kingdom', 'tz' => 'Europe/London', 'cur' => 'GBP'],
            ['city' => 'Edinburgh', 'country' => 'United Kingdom', 'tz' => 'Europe/London', 'cur' => 'GBP'],
            ['city' => 'Dublin', 'country' => 'Ireland', 'tz' => 'Europe/Dublin', 'cur' => 'EUR'],
            ['city' => 'Paris', 'country' => 'France', 'tz' => 'Europe/Paris', 'cur' => 'EUR'],
            ['city' => 'Nice', 'country' => 'France', 'tz' => 'Europe/Paris', 'cur' => 'EUR'],
            ['city' => 'Berlin', 'country' => 'Germany', 'tz' => 'Europe/Berlin', 'cur' => 'EUR'],
            ['city' => 'Munich', 'country' => 'Germany', 'tz' => 'Europe/Berlin', 'cur' => 'EUR'],
            ['city' => 'Madrid', 'country' => 'Spain', 'tz' => 'Europe/Madrid', 'cur' => 'EUR'],
            ['city' => 'Barcelona', 'country' => 'Spain', 'tz' => 'Europe/Madrid', 'cur' => 'EUR'],
            ['city' => 'Lisbon', 'country' => 'Portugal', 'tz' => 'Europe/Lisbon', 'cur' => 'EUR'],
            ['city' => 'Rome', 'country' => 'Italy', 'tz' => 'Europe/Rome', 'cur' => 'EUR'],
            ['city' => 'Amsterdam', 'country' => 'Netherlands', 'tz' => 'Europe/Amsterdam', 'cur' => 'EUR'],
            ['city' => 'Brussels', 'country' => 'Belgium', 'tz' => 'Europe/Brussels', 'cur' => 'EUR'],
            ['city' => 'Geneva', 'country' => 'Switzerland', 'tz' => 'Europe/Zurich', 'cur' => 'CHF'],
            ['city' => 'Vienna', 'country' => 'Austria', 'tz' => 'Europe/Vienna', 'cur' => 'EUR'],
            ['city' => 'Copenhagen', 'country' => 'Denmark', 'tz' => 'Europe/Copenhagen', 'cur' => 'DKK'],
            ['city' => 'Stockholm', 'country' => 'Sweden', 'tz' => 'Europe/Stockholm', 'cur' => 'SEK'],
            ['city' => 'Oslo', 'country' => 'Norway', 'tz' => 'Europe/Oslo', 'cur' => 'NOK'],
            ['city' => 'Helsinki', 'country' => 'Finland', 'tz' => 'Europe/Helsinki', 'cur' => 'EUR'],
            ['city' => 'Prague', 'country' => 'Czech Republic', 'tz' => 'Europe/Prague', 'cur' => 'CZK'],
            // Oceania
            ['city' => 'Sydney', 'country' => 'Australia', 'tz' => 'Australia/Sydney', 'cur' => 'AUD'],
            ['city' => 'Auckland', 'country' => 'New Zealand', 'tz' => 'Pacific/Auckland', 'cur' => 'NZD'],
            // Asia
            ['city' => 'Tokyo', 'country' => 'Japan', 'tz' => 'Asia/Tokyo', 'cur' => 'JPY'],
            ['city' => 'Kyoto', 'country' => 'Japan', 'tz' => 'Asia/Tokyo', 'cur' => 'JPY'],
            ['city' => 'Singapore', 'country' => 'Singapore', 'tz' => 'Asia/Singapore', 'cur' => 'SGD'],
            ['city' => 'Bangkok', 'country' => 'Thailand', 'tz' => 'Asia/Bangkok', 'cur' => 'THB'],
            ['city' => 'Da Nang', 'country' => 'Vietnam', 'tz' => 'Asia/Ho_Chi_Minh', 'cur' => 'VND'],
            ['city' => 'Bali', 'country' => 'Indonesia', 'tz' => 'Asia/Jakarta', 'cur' => 'IDR'],
            ['city' => 'Manila', 'country' => 'Philippines', 'tz' => 'Asia/Manila', 'cur' => 'PHP'],
            ['city' => 'Seoul', 'country' => 'South Korea', 'tz' => 'Asia/Seoul', 'cur' => 'KRW'],
            // Middle East
            ['city' => 'Dubai', 'country' => 'UAE', 'tz' => 'Asia/Dubai', 'cur' => 'AED'],
            ['city' => 'Riyadh', 'country' => 'Saudi Arabia', 'tz' => 'Asia/Riyadh', 'cur' => 'SAR'],
            // South America
            ['city' => 'Rio de Janeiro', 'country' => 'Brazil', 'tz' => 'America/Sao_Paulo', 'cur' => 'BRL'],
            ['city' => 'Buenos Aires', 'country' => 'Argentina', 'tz' => 'America/Argentina/Buenos_Aires', 'cur' => 'ARS'],
            // Africa
            ['city' => 'Cape Town', 'country' => 'South Africa', 'tz' => 'Africa/Johannesburg', 'cur' => 'ZAR'],
            ['city' => 'Marrakech', 'country' => 'Morocco', 'tz' => 'Africa/Casablanca', 'cur' => 'MAD']
        ];

        $prefixes = ['Grand', 'Royal', 'Boutique', 'Emerald', 'Sunset', 'Metropolitan', 'Riverside', 'Alpine', 'Coastal', 'Marina Bay', 'Seaside', 'Summit', 'Urban'];
        $midfixes = ['Harbor', 'Vista', 'Sands', 'Oakwood', 'Heritage', 'Skyview', 'Park', 'Lagoon', 'Garden', 'Cove', 'Cliffside', 'Terrace', 'Springs'];
        $types = ['Hotel', 'Resort', 'Villa', 'Apartment', 'Serviced Apartment', 'Boutique Hotel', 'Guest House', 'Vacation Rental', 'Cabin', 'Lodge', 'Hostel'];

        $propertyIds = [];
        $settings = [];

        for ($p = 0; $p < 200; $p++) {
            $uuid = (string) Str::uuid();
            $propertyIds[] = $uuid;
            $loc = $faker->randomElement($locations);
            
            // Build a production-grade realistic name
            $name = $faker->randomElement($prefixes) . ' ' . $faker->randomElement($midfixes) . ' ' . $faker->randomElement($types);

            DB::table('properties')->insert([
                'uuid' => $uuid,
                'tenant_id' => $faker->randomElement($tenantIds),
                'name' => $name,
                'timezone' => $loc['tz'],
                'currency' => $loc['cur'],
                'address_street' => $faker->streetAddress(),
                'address_city' => $loc['city'],
                'address_state' => $loc['city'],
                'address_postal_code' => $faker->postcode(),
                'address_country' => $loc['country'],
                'lat' => $faker->latitude(-60, 60),
                'lng' => $faker->longitude(-120, 120),
                'contact_phone' => $faker->phoneNumber(),
                'contact_email' => strtolower(str_replace(' ', '', $name)) . '@global-hospitality.com',
                'status' => 'active'
            ]);

            $settings[] = [
                'property_id' => $uuid,
                'check_in_time' => '14:00:00',
                'check_out_time' => '11:00:00',
                'tax_inclusive' => $faker->boolean(),
                'overbooking_limit_percent' => $faker->randomElement([0.00, 5.00, 10.00])
            ];
        }
        DB::table('property_settings')->insert($settings);

        // Room Types & Rates setup
        $roomTemplates = [
            ['name' => 'Standard Double Room', 'code' => 'STD-DBL', 'adults' => 2, 'children' => 0, 'rate' => 110.00],
            ['name' => 'Deluxe King Room', 'code' => 'DLX-KNG', 'adults' => 2, 'children' => 1, 'rate' => 180.00],
            ['name' => 'Superior Twin Room', 'code' => 'SUP-TWN', 'adults' => 2, 'children' => 1, 'rate' => 140.00],
            ['name' => 'Executive Suite', 'code' => 'EXE-SUI', 'adults' => 3, 'children' => 2, 'rate' => 290.00],
            ['name' => 'Family Suite', 'code' => 'FAM-SUI', 'adults' => 4, 'children' => 3, 'rate' => 340.00],
            ['name' => 'Ocean View Suite', 'code' => 'OCN-SUI', 'adults' => 2, 'children' => 1, 'rate' => 320.00],
            ['name' => 'Garden Villa', 'code' => 'GRD-VIL', 'adults' => 4, 'children' => 4, 'rate' => 450.00],
            ['name' => 'Pool Villa', 'code' => 'POL-VIL', 'adults' => 4, 'children' => 4, 'rate' => 550.00],
            ['name' => 'Penthouse Suite', 'code' => 'PNT-SUI', 'adults' => 6, 'children' => 4, 'rate' => 950.00],
            ['name' => 'Studio Apartment', 'code' => 'STD-APT', 'adults' => 2, 'children' => 0, 'rate' => 125.00]
        ];

        $ratePlansInsert = [];
        $roomsInsert = [];

        foreach ($propertyIds as $propId) {
            // Pick 3 random room types for this property to simulate realistic inventory diversity
            $selectedTypes = $faker->randomElements($roomTemplates, 3);
            
            foreach ($selectedTypes as $tpl) {
                $rtUuid = (string) Str::uuid();
                DB::table('room_types')->insert([
                    'uuid' => $rtUuid,
                    'property_id' => $propId,
                    'name' => $tpl['name'],
                    'code' => $tpl['code'] . '-' . $propId,
                    'max_adults' => $tpl['adults'],
                    'max_children' => $tpl['children'],
                    'base_rate' => $tpl['rate'],
                    'inventory_mode' => 'physical'
                ]);

                // Rate plan
                $rpUuid = (string) Str::uuid();
                $ratePlansInsert[] = [
                    'uuid' => $rpUuid,
                    'property_id' => $propId,
                    'room_type_id' => $rtUuid,
                    'name' => 'Standard Rate Plan',
                    'is_derived' => false,
                    'base_rate_plan_id' => null
                ];

                // Physical rooms (Exactly 15 rooms total per property -> 5 rooms per room type)
                for ($r = 1; $r <= 5; $r++) {
                    $floor = $faker->numberBetween(1, 4);
                    $roomsInsert[] = [
                        'uuid' => (string) Str::uuid(),
                        'room_type_id' => $rtUuid,
                        'room_number' => 'Room ' . ($floor * 100 + $r),
                        'floor' => (string) $floor,
                        'clean_status' => $faker->randomElement(['clean', 'dirty', 'inspected']),
                        'service_status' => 'available'
                    ];
                }
            }
        }

        foreach (array_chunk($ratePlansInsert, 200) as $chunk) {
            DB::table('rate_plans')->insert($chunk);
        }

        foreach (array_chunk($roomsInsert, 500) as $chunk) {
            DB::table('rooms')->insert($chunk);
        }

        echo "CoreSeeder Complete. Created 200 properties, 600 room types, and 3000 physical rooms.
";
    }
}
