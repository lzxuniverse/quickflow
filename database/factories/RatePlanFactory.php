<?php

namespace Database\Factories;

use App\Models\RatePlan;
use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatePlanFactory extends Factory
{
    protected $model = RatePlan::class;

    public function definition(): array
    {
        $plans = [
            'Standard Flexible Rate', 'Non-Refundable Promotion',
            'Bed & Breakfast Package', 'Last Minute Discount'
        ];

        return [
            'property_id' => Property::factory(),
            'room_type_id' => RoomType::factory(),
            'name' => $this->faker->randomElement($plans),
            'is_derived' => false,
            'base_rate_plan_id' => null,
        ];
    }
}
