<?php

namespace Database\Factories;

use App\Models\SponsorTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorTierFactory extends Factory
{
    protected $model = SponsorTier::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->company(),
            'badge_label' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
