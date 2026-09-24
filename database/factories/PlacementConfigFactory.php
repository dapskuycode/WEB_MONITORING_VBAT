<?php

namespace Database\Factories;

use App\Models\PlacementConfig;
use App\Models\SponsorTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlacementConfigFactory extends Factory
{
    protected $model = PlacementConfig::class;

    public function definition(): array
    {
        return [
            'placement_type' => fake()->randomElement(['hero_slider', 'horizontal_infeed', 'popup', 'best_deal', 'card_infeed']),
            'tier_id' => SponsorTier::inRandomOrder()->first()?->id,
            'max_slots' => fake()->numberBetween(1, 6),
            'target_probability' => round(fake()->randomFloat(0.1, 0.9), 3),
            'insertion_interval' => fake()->numberBetween(5, 20),
            'fallback_behavior' => fake()->randomElement(['hide', 'show_default']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
