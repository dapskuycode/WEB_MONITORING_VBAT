<?php

namespace Database\Factories;

use App\Models\FeedConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedConfigFactory extends Factory
{
    protected $model = FeedConfig::class;

    public function definition(): array
    {
        return [
            'feed_type' => $this->faker->randomElement(['home', 'shop', 'search', 'profile']),
            'tier_id' => null,
            'banner_type' => $this->faker->randomElement(['hero', 'horizontal', 'popup', 'card']),
            'insertion_interval' => $this->faker->numberBetween(1, 20),
            'max_banners' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'sort_order' => 0,
            'metadata' => null,
        ];
    }
}
