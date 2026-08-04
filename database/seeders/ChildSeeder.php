<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class ChildSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "3. Executing ChildSeeder (Child tables: 100-500 rows)...
";

        // Clear existing Child tables
        DB::table('room_types')->truncate();
        DB::table('rooms')->truncate();
        DB::table('rate_plans')->truncate();
        DB::table('reviews')->truncate();

        $propertyIds = DB::table('properties')->pluck('uuid')->toArray();

        // Seed Room Types (300 rows, exactly 3 per Property)
        $roomTemplates = [
            ['name' => 'Deluxe King Suite', 'code' => 'DLX-KNG', 'adults' => 2, 'children' => 1, 'rate' => 195.00],
            ['name' => 'Standard Queen Room', 'code' => 'STD-QUN', 'adults' => 2, 'children' => 0, 'rate' => 110.00],
            ['name' => 'Family Garden Villa', 'code' => 'FAM-VLA', 'adults' => 4, 'children' => 3, 'rate' => 380.00]
        ];

        $ratePlansInsert = [];
        $roomsInsert = [];

        foreach ($propertyIds as $propId) {
            foreach ($roomTemplates as $tpl) {
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

                // Rate plans
                $rpUuid = (string) Str::uuid();
                $ratePlansInsert[] = [
                    'uuid' => $rpUuid,
                    'property_id' => $propId,
                    'room_type_id' => $rtUuid,
                    'name' => 'Standard Rate Plan',
                    'is_derived' => false,
                    'base_rate_plan_id' => null
                ];

                // Physical Rooms (1500 rooms total, exactly 5 per Room Type)
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

        foreach (array_chunk($ratePlansInsert, 100) as $chunk) {
            DB::table('rate_plans')->insert($chunk);
        }

        foreach (array_chunk($roomsInsert, 500) as $chunk) {
            DB::table('rooms')->insert($chunk);
        }

        echo "ChildSeeder complete. Created 300 room types, 1500 rooms, 300 rate plans.
";
    }
}
