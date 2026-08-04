<?php

namespace Database\Factories;

use App\Models\ReservationRoom;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Room;
use App\Models\RatePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationRoomFactory extends Factory
{
    protected $model = ReservationRoom::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'room_type_id' => RoomType::factory(),
            'room_id' => Room::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'rate_plan_id' => RatePlan::factory(),
            'price_charged' => $this->faker->randomFloat(2, 80, 400),
        ];
    }
}
