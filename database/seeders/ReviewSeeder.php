<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create();
        echo "3. ReviewSeeder: Seeding 5,000 Multi-language Reviews...
";

        DB::table('reviews')->truncate();

        // Load 5000 reservations to write reviews against
        $reservations = DB::table('reservations')->select('uuid', 'property_id', 'source')->take(5000)->get();

        $reviewPool = [
            'en' => [
                ['rating' => 5.00, 'content' => 'Outstanding stay! The staff made us feel like family. Rooms were exceptionally clean.', 'response' => 'Thank you for the warm words! We look forward to welcoming you back.'],
                ['rating' => 4.00, 'content' => 'Nice pool area, breakfast was good but room was a bit small.', 'response' => 'We appreciate your feedback and are glad you liked our facilities.'],
                ['rating' => 3.00, 'content' => 'Great views, but the Wi-Fi was dropping connection frequently.', 'response' => 'Thank you. We are currently upgrading routers to fix the connectivity issues.'],
                ['rating' => 2.00, 'content' => 'Check-in was delayed by 1 hour. No welcome drink offered.', 'response' => 'Please accept our sincere apologies for the delay. We hope to improve next time.']
            ],
            'fr' => [
                ['rating' => 5.00, 'content' => 'Excellent séjour dans cet hôtel. Le personnel est très accueillant, la chambre était confortable.', 'response' => 'Merci beaucoup pour votre retour chaleureux! Au plaisir de vous revoir.'],
                ['rating' => 3.00, 'content' => 'L\'emplacement est idéal pour visiter la ville, mais la chambre était un peu bruyante le soir.', 'response' => 'Merci pour votre retour. Nous ferons au mieux pour insonoriser les chambres.']
            ],
            'de' => [
                ['rating' => 5.00, 'content' => 'Sehr schönes Hotel in guter Lage. Die Zimmer waren sauber und das Personal sehr aufmerksam.', 'response' => 'Vielen Dank für Ihre tolle Bewertung! Wir freuen uns auf Ihren nächsten Besuch.'],
                ['rating' => 4.00, 'content' => 'Gutes Preis-Leistungs-Verhältnis. Internetverbindung war stabil und schnell.', 'response' => 'Danke für Ihr Feedback. Wir freuen uns, dass Sie einen guten Aufenthalt hatten.']
            ],
            'es' => [
                ['rating' => 5.00, 'content' => 'Una experiencia fantástica. Las vistas al mar desde la villa eran espectaculares. Recomendado.', 'response' => '¡Muchas gracias por su reseña! Esperamos verle pronto de nuevo.'],
                ['rating' => 2.00, 'content' => 'El check-in fue lento y el personal de recepción parecía poco preparado.', 'response' => 'Lamentamos los inconvenientes. Estamos capacitando a nuestro equipo para mejorar.']
            ],
            'it' => [
                ['rating' => 5.00, 'content' => 'Soggiorno meraviglioso in questa dimora storica. Camere curate nei minimi dettagli.', 'response' => 'Grazie mille! Siamo felici che abbia apprezzato l\'attenzione ai dettagli.']
            ]
        ];

        $reviewsInsert = [];
        foreach ($reservations as $res) {
            // Select language representation
            $lang = $faker->randomElement(['en', 'en', 'en', 'fr', 'de', 'es', 'it']); // English biased
            $item = $faker->randomElement($reviewPool[$lang]);

            $reviewsInsert[] = [
                'uuid' => (string) Str::uuid(),
                'property_id' => $res->property_id,
                'reservation_id' => $res->uuid,
                'source' => $res->source,
                'rating_overall' => $item['rating'],
                'content' => $item['content'],
                'response' => $item['response'],
            ];

            if (count($reviewsInsert) >= 1000) {
                DB::table('reviews')->insert($reviewsInsert);
                $reviewsInsert = [];
            }
        }
        if (!empty($reviewsInsert)) {
            DB::table('reviews')->insert($reviewsInsert);
        }

        echo "ReviewSeeder complete. Created 5,000 multi-language reviews.
";
    }
}
