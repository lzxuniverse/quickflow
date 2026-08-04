<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Property;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('now', '+30 days');
        $days = $this->faker->numberBetween(1, 7);
        $checkOut = (clone $checkIn)->modify('+' . $days . ' days');

        $sources = ['Booking.com', 'Airbnb', 'Agoda', 'Expedia', 'Direct Booking Engine', 'Corporate Walk-In'];

        return [
            'property_id' => Property::factory(),
            'guest_id' => Guest::factory(),
            'status' => $this->faker->randomElement(['draft', 'confirmed', 'checked_in', 'checked_out']),
            'source' => $this->faker->randomElement($sources),
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkOut->format('Y-m-d'),
            'total_amount' => $this->faker->randomFloat(2, 100, 1500),
        ];
    }
}
