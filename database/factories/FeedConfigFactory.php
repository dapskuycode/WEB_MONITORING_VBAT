<?php

namespace Database\Factories;

use App\Models\FeedConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedConfigFactory extends Factory
{
    protected $model = FeedConfig::class;

    public function definition(): array
    {
        static $index = 0;
        $combinations = [
            ['home', 'hero'],
            ['home', 'horizontal'],
            ['shop', 'card'],
            ['search', 'popup'],
            ['profile', 'hero'],
            ['shop', 'hero'],
            ['search', 'horizontal'],
            ['profile', 'card'],
        ];
        $combo = $combinations[$index++ % count($combinations)];

        return [
            'feed_type' => $combo[0],
            'tier_id' => null,
            'banner_type' => $combo[1],
            'insertion_interval' => $this->faker->numberBetween(1, 20),
            'max_banners' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'sort_order' => 0,
            'metadata' => null,
        ];
    }
}
