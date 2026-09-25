<?php

namespace Database\Factories;

use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorProductFactory extends Factory
{
    protected $model = SponsorProduct::class;

    public function definition(): array
    {
        return [
            'sponsor_id' => Sponsor::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10000, 5000000),
            'discount_price' => fake()->optional(0.3)->randomFloat(2, 5000, 4500000),
            'rating' => fake()->optional(0.7)->randomFloat(2, 3.0, 5.0),
            'sold_count' => fake()->optional(0.7)->numberBetween(0, 5000),
            'image_path' => null,
            'shopee_url' => fake()->optional(0.7)->url(),
            'tokopedia_url' => fake()->optional(0.5)->url(),
            'is_featured' => fake()->boolean(30),
            'is_active' => true,
            'order' => fake()->numberBetween(0, 100),
        ];
    }
}