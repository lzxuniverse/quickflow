<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $floor = $this->faker->numberBetween(1, 5);
        $roomNum = $floor * 100 + $this->faker->numberBetween(1, 20);

        return [
            'room_type_id' => RoomType::factory(),
            'room_number' => 'Room ' . $roomNum,
            'floor' => (string)$floor,
            'clean_status' => $this->faker->randomElement(['dirty', 'clean', 'inspected']),
            'service_status' => 'available',
        ];
    }
}
