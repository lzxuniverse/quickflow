<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "Starting Global Production Seeding Pipeline...
";
        
        // Disable Foreign Key checks before resetting
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // Run structured sub-seeders sequentially
        $this->call([
            CoreSeeder::class,
            DemoSeeder::class,
            ReviewSeeder::class,
            AnalyticsSeeder::class,
            AISeeder::class,
        ]);

        // Re-enable Foreign Key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        echo "Global Production Dataset Seeding completed successfully!
";
    }
}
