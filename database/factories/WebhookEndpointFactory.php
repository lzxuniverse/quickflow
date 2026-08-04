<?php

namespace Database\Factories;

use App\Models\WebhookEndpoint;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'target_url' => $this->faker->url() . '/webhooks/quickflow',
            'secret_key' => 'whsec_' . $this->faker->sha256(),
            'subscribed_events' => ['reservation.created', 'reservation.updated', 'guest.checked_in'],
        ];
    }
}
