<?php

namespace Database\Factories;

use App\Models\WebhookDeliveryAttempt;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookDeliveryAttemptFactory extends Factory
{
    protected $model = WebhookDeliveryAttempt::class;

    public function definition(): array
    {
        $events = ['reservation.created', 'reservation.updated', 'guest.checked_in'];
        $status = $this->faker->randomElement(['success', 'failed']);

        return [
            'endpoint_id' => WebhookEndpoint::factory(),
            'event_type' => $this->faker->randomElement($events),
            'payload' => json_encode(['event' => 'test', 'data' => ['id' => $this->faker->uuid()]]),
            'response_code' => $status === 'success' ? 200 : 500,
            'status' => $status,
        ];
    }
}
