<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "1. Executing LookupSeeder (Lookup tables: 5-20 rows)...
";

        // Clear existing Lookup tables
        DB::table('channels')->truncate();
        DB::table('tenants')->truncate();
        DB::table('users')->truncate();

        // Seed Channels (5 rows)
        $channels = [
            ['id' => 'booking', 'name' => 'Booking.com', 'integration_mode' => 'xml_push'],
            ['id' => 'airbnb', 'name' => 'Airbnb', 'integration_mode' => 'json_rest'],
            ['id' => 'agoda', 'name' => 'Agoda', 'integration_mode' => 'xml_push'],
            ['id' => 'expedia', 'name' => 'Expedia', 'integration_mode' => 'xml_push'],
            ['id' => 'vrbo', 'name' => 'Vrbo', 'integration_mode' => 'xml_push']
        ];
        DB::table('channels')->insert($channels);

        // Seed Tenants (5 rows)
        $tenantIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $uuid = (string) Str::uuid();
            $tenantIds[] = $uuid;
            DB::table('tenants')->insert([
                'uuid' => $uuid,
                'name' => "SaaS Hospitality Partner Group $i",
                'plan_tier' => $faker->randomElement(['basic', 'professional', 'enterprise']),
                'status' => 'active'
            ]);
        }

        // Seed Users (10 rows)
        for ($u = 1; $u <= 10; $u++) {
            DB::table('users')->insert([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $faker->randomElement($tenantIds),
                'email' => "operator-$u@example-hospitality.com",
                'password_hash' => bcrypt('password123'),
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'role' => $faker->randomElement(['admin', 'manager', 'receptionist']),
                'status' => 'active'
            ]);
        }

        echo "LookupSeeder complete. Created 5 channels, 5 tenants, 10 users.
";
    }
}
