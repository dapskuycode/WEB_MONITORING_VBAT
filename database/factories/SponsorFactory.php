<?php

namespace Database\Factories;

use App\Models\Province;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        $tier = SponsorTier::inRandomOrder()->first() ?? SponsorTier::factory()->create();

        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'logo_path' => null,
            'website_url' => fake()->optional(0.7)->url(),
            'tier_id' => $tier->id,
            'tier' => $tier->slug,
            'weight' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'contact_email' => fake()->optional(0.7)->companyEmail(),
            'phone' => fake()->optional(0.5)->phoneNumber(),
            'whatsapp' => fake()->optional(0.5)->phoneNumber(),
            'address' => fake()->optional(0.3)->address(),
            'province_id' => null,
            'city_id' => null,
        ];
    }
}
