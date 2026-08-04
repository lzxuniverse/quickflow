<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $hotelNames = [
            'Grand Horizon Resort', 'Shoreline Villas & Suites', 'The Alpine Retreat',
            'Urban Edge Boutique Hotel', 'Sunny Palms Cabin Lodge', 'Emerald Lake Lodge',
            'The Sanctuary Boutique Resort', 'Vista Blue Hotel', 'Oakwood Country Inn'
        ];

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->randomElement($hotelNames),
            'timezone' => $this->faker->randomElement(['America/New_York', 'Europe/London', 'Asia/Bangkok', 'Europe/Paris']),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'address_street' => $this->faker->streetAddress(),
            'address_city' => $this->faker->city(),
            'address_state' => $this->faker->state(),
            'address_postal_code' => $this->faker->postcode(),
            'address_country' => $this->faker->country(),
            'lat' => $this->faker->latitude(-90, 90),
            'lng' => $this->faker->longitude(-180, 180),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->safeEmail(),
            'status' => 'active',
        ];
    }
}
