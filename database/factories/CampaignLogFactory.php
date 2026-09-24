<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignLogFactory extends Factory
{
    protected $model = CampaignLog::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'sponsor_product_id' => null,
            'user_id' => null,
            'event_type' => fake()->randomElement(['impression', 'click', 'selection']),
            'placement_context' => fake()->randomElement(['hero_slider', 'horizontal_infeed', 'popup', 'card_infeed']),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
