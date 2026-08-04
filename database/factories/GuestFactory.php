<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        $prefOptions = [
            ['pillow_type' => 'feather', 'room_location' => 'high_floor', 'dietary' => 'vegan'],
            ['pillow_type' => 'memory_foam', 'quiet_room' => true, 'dietary' => 'gluten_free'],
            ['late_checkout_preferred' => true, 'dietary' => 'none'],
            ['accessibility_needs' => false, 'high_floor' => false]
        ];

        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'passport_number' => strtoupper($this->faker->bothify('??######')),
            'preferences' => $this->faker->randomElement($prefOptions),
        ];
    }
}
