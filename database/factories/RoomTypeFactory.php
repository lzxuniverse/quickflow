<?php

namespace Database\Factories;

use App\Models\RoomType;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        $roomTypes = [
            ['name' => 'Deluxe King Room', 'code' => 'DLX-KNG', 'adults' => 2, 'children' => 0, 'rate' => 180.00],
            ['name' => 'Standard Queen Room', 'code' => 'STD-QUN', 'adults' => 2, 'children' => 1, 'rate' => 120.00],
            ['name' => 'Executive Ocean Suite', 'code' => 'EXEC-OCN', 'adults' => 4, 'children' => 2, 'rate' => 350.00],
            ['name' => 'Cozy Single Studio', 'code' => 'COZY-SGL', 'adults' => 1, 'children' => 0, 'rate' => 85.00],
            ['name' => 'Family Bungalow', 'code' => 'FAM-BGL', 'adults' => 4, 'children' => 3, 'rate' => 220.00]
        ];

        $selected = $this->faker->randomElement($roomTypes);

        return [
            'property_id' => Property::factory(),
            'name' => $selected['name'],
            'code' => $selected['code'],
            'max_adults' => $selected['adults'],
            'max_children' => $selected['children'],
            'base_rate' => $selected['rate'],
            'inventory_mode' => 'physical',
        ];
    }
}
