<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "2. Executing MasterSeeder (Master tables: ~100 rows)...
";

        // Clear existing Master tables
        DB::table('properties')->truncate();
        DB::table('property_settings')->truncate();
        DB::table('guests')->truncate();
        DB::table('knowledge_bases')->truncate();
        DB::table('knowledge_documents')->truncate();

        $tenantIds = DB::table('tenants')->pluck('uuid')->toArray();

        // Seed Properties (100 rows)
        $propertyIds = [];
        $propertySettings = [];
        $hotelNames = [
            'Grand Horizon Resort', 'Shoreline Villas & Suites', 'The Alpine Retreat',
            'Urban Edge Boutique Hotel', 'Sunny Palms Cabin Lodge', 'Emerald Lake Lodge',
            'The Sanctuary Boutique Resort', 'Vista Blue Hotel', 'Oakwood Country Inn',
            'Marina Bay Penthouse', 'Ubud Jungle Villa', 'Kyoto Gardens Ryokan'
        ];

        for ($p = 0; $p < 100; $p++) {
            $uuid = (string) Str::uuid();
            $propertyIds[] = $uuid;
            $name = $faker->randomElement($hotelNames) . ' #' . ($p + 1);

            DB::table('properties')->insert([
                'uuid' => $uuid,
                'tenant_id' => $faker->randomElement($tenantIds),
                'name' => $name,
                'timezone' => $faker->randomElement(['America/New_York', 'Europe/London', 'Asia/Bangkok', 'Europe/Paris']),
                'currency' => $faker->randomElement(['USD', 'EUR', 'GBP']),
                'address_street' => $faker->streetAddress(),
                'address_city' => $faker->city(),
                'address_state' => $faker->state(),
                'address_postal_code' => $faker->postcode(),
                'address_country' => $faker->country(),
                'lat' => $faker->latitude(-90, 90),
                'lng' => $faker->longitude(-180, 180),
                'contact_phone' => $faker->phoneNumber(),
                'contact_email' => strtolower(str_replace(' ', '', $name)) . '@demo.com',
                'status' => 'active'
            ]);

            $propertySettings[] = [
                'property_id' => $uuid,
                'check_in_time' => '14:00:00',
                'check_out_time' => '11:00:00',
                'tax_inclusive' => $faker->boolean(),
                'overbooking_limit_percent' => $faker->randomElement([0.00, 5.00, 10.00])
            ];
        }
        DB::table('property_settings')->insert($propertySettings);

        // Seed Guests (1000 rows)
        $guestsBatch = [];
        for ($g = 0; $g < 1000; $g++) {
            $uuid = (string) Str::uuid();
            $guestsBatch[] = [
                'uuid' => $uuid,
                'tenant_id' => $faker->randomElement($tenantIds),
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => "guest-$g-" . $faker->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'passport_number' => strtoupper($faker->bothify('??######')),
                'preferences' => json_encode(['pillow' => 'feather'])
            ];

            if (count($guestsBatch) >= 500) {
                DB::table('guests')->insert($guestsBatch);
                $guestsBatch = [];
            }
        }
        if (!empty($guestsBatch)) {
            DB::table('guests')->insert($guestsBatch);
        }

        // Seed Knowledge Documents (100 rows)
        $kbInsert = [];
        foreach ($tenantIds as $tId) {
            $kbUuid = (string) Str::uuid();
            $kbInsert[] = [
                'uuid' => $kbUuid,
                'tenant_id' => $tId,
                'name' => 'Reception Operations FAQ'
            ];

            for ($d = 1; $d <= 20; $d++) {
                DB::table('knowledge_documents')->insert([
                    'uuid' => (string) Str::uuid(),
                    'knowledge_base_id' => $kbUuid,
                    'title' => "Property Policy Document #$d",
                    'content' => "Standard check-out time is strictly 11:00 AM. Late check-out requests must be authorized by reception before 10:00 AM."
                ]);
            }
        }
        DB::table('knowledge_bases')->insert($kbInsert);

        echo "MasterSeeder complete. Created 100 properties, 1000 guests, 100 knowledge documents.
";
    }
}
