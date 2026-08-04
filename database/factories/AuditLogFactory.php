<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['create', 'update', 'delete', 'export']),
            'table_name' => $this->faker->randomElement(['reservations', 'guests', 'folios', 'rooms']),
            'row_id' => $this->faker->uuid(),
            'changes_diff' => [
                'before' => ['status' => 'confirmed'],
                'after' => ['status' => 'checked_in']
            ],
        ];
    }
}
