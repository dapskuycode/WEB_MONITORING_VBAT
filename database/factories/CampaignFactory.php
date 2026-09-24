<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'sponsor_id' => Sponsor::factory(),
            'title' => fake()->sentence(3),
            'placement_type' => fake()->randomElement(['hero_slider', 'horizontal_infeed', 'popup', 'card_infeed']),
            'media_path' => 'sponsor/campaigns/hero/' . fake()->uuid() . '.jpg',
            'media_type' => 'image',
            'target_url' => fake()->url(),
            'description' => fake()->paragraph(),
            'daily_limit' => 1000,
            'weight' => fake()->numberBetween(1, 10),
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ];
    }

    public function hero(): static
    {
        return $this->state(fn (array $attributes) => [
            'placement_type' => 'hero_slider',
        ]);
    }
}
