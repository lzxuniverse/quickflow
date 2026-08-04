<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Property;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $reviews = [
            [
                'rating' => 5.00,
                'content' => 'Outstanding stay! The staff made us feel like family. Rooms were exceptionally clean.',
                'response' => 'Thank you so much for the glowing review! We look forward to welcoming you back soon.'
            ],
            [
                'rating' => 4.00,
                'content' => 'Very good location, walk distance to everything. Breakfast was decent but lacked vegan choices.',
                'response' => 'Thank you for your feedback. We are currently working on adding more vegan selections to our menu.'
            ],
            [
                'rating' => 3.00,
                'content' => 'The property looks beautiful, but the Wi-Fi signal in room 304 was extremely weak.',
                'response' => 'We apologize for the internet issue. We are scheduled to replace our routers on the third floor next week.'
            ],
            [
                'rating' => 4.50,
                'content' => 'Excellent view of the ocean! Beds were super comfortable. The lobby smells amazing.',
                'response' => 'Delighted you enjoyed the balcony view and our custom scent profiles! See you next time!'
            ]
        ];

        $selected = $this->faker->randomElement($reviews);
        $sources = ['Booking.com', 'Airbnb', 'Agoda', 'Expedia'];

        return [
            'property_id' => Property::factory(),
            'reservation_id' => Reservation::factory(),
            'source' => $this->faker->randomElement($sources),
            'rating_overall' => $selected['rating'],
            'content' => $selected['content'],
            'response' => $selected['response'],
        ];
    }
}
