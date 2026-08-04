<?php

namespace Database\Factories;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeDocumentFactory extends Factory
{
    protected $model = KnowledgeDocument::class;

    public function definition(): array
    {
        $docs = [
            ['title' => 'Wi-Fi Password & Network Access Info', 'content' => '# Wi-Fi Access

Our property offers high-speed Wi-Fi under the network SSID **QuickFlow-Guest**.

Password: `GuestSecure2026`

For premium speed upgrades, please contact reception.'],
            ['title' => 'Check-out Policy & Late Fees', 'content' => '# Check-out Information

Standard check-out time is strictly **11:00 AM**.

Late check-outs are available upon request:
- Up to 1:00 PM: Free of charge (based on availability)
- Up to 4:00 PM: 50% of daily rate
- After 4:00 PM: Full nightly rate'],
            ['title' => 'Swimming Pool & Fitness Regulations', 'content' => '# Facility Rules

- Pool hours: 7:00 AM - 10:00 PM.
- No lifeguard on duty. Children must be supervised at all times.
- Towels are provided at the pool deck kiosk.
- Glass containers are strictly prohibited.']
        ];

        $selected = $this->faker->randomElement($docs);

        return [
            'knowledge_base_id' => KnowledgeBase::factory(),
            'title' => $selected['title'],
            'content' => $selected['content'],
        ];
    }
}
