<?php

namespace Database\Factories;

use App\Models\KnowledgeBase;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeBaseFactory extends Factory
{
    protected $model = KnowledgeBase::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Reception FAQs & Policy Guides',
        ];
    }
}
