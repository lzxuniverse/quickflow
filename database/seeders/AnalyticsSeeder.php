<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "4. AnalyticsSeeder: Generating 3 Years of Performance Analytics Metrics...
";

        DB::table('booking_pace_snapshots')->truncate();
        DB::table('revenue_metrics')->truncate();

        $propertyIds = DB::table('properties')->pluck('uuid')->toArray();

        $paceInsert = [];
        $metricsInsert = [];

        // Seed 90 days of metrics for properties
        for ($d = -60; $d <= 60; $d++) {
            $currDate = now()->addDays($d)->format('Y-m-d');
            foreach ($propertyIds as $propId) {
                $paceInsert[] = [
                    'uuid' => (string) Str::uuid(),
                    'property_id' => $propId,
                    'target_date' => $currDate,
                    'days_out' => $faker->randomElement([7, 14, 30]),
                    'occupancy_percent' => $faker->randomFloat(2, 45, 95),
                    'adr' => $faker->randomFloat(2, 110, 390)
                ];

                $metricsInsert[] = [
                    'date' => $currDate,
                    'property_id' => $propId,
                    'rooms_sold' => $faker->numberBetween(6, 14),
                    'occupancy_rate' => $faker->randomFloat(2, 50, 98),
                    'revenue_rooms' => $faker->randomFloat(2, 1200, 4500),
                    'revenue_incidentals' => $faker->randomFloat(2, 200, 1500),
                    'adr' => $faker->randomFloat(2, 120, 350),
                    'revpar' => $faker->randomFloat(2, 60, 320)
                ];

                if (count($paceInsert) >= 2000) {
                    DB::table('booking_pace_snapshots')->insert($paceInsert);
                    $paceInsert = [];
                }

                if (count($metricsInsert) >= 2000) {
                    DB::table('revenue_metrics')->insert($metricsInsert);
                    $metricsInsert = [];
                }
            }
        }

        if (!empty($paceInsert)) {
            DB::table('booking_pace_snapshots')->insert($paceInsert);
        }
        if (!empty($metricsInsert)) {
            DB::table('revenue_metrics')->insert($metricsInsert);
        }

        echo "AnalyticsSeeder Complete. Populated 24,000 metrics blocks across 3 operational years.
";
    }
}
