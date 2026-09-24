<?php

namespace Database\Factories;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminAuditLogFactory extends Factory
{
    protected $model = AdminAuditLog::class;

    public function definition(): array
    {
        return [
            'action' => fake()->randomElement(['benefit.overridden', 'sponsor.tier_changed', 'campaign.paused', 'product.deleted', 'best_deal.created']),
            'actor_type' => 'admin',
            'actor_id' => fake()->numberBetween(1, 10),
            'target_type' => fake()->randomElement(['sponsor', 'campaign', 'product', 'best_deal']),
            'target_id' => fake()->numberBetween(1, 100),
            'before_state' => fake()->optional()->randomElements(['status' => 'active']),
            'after_state' => fake()->optional()->randomElements(['status' => 'paused']),
            'reason' => fake()->optional()->sentence(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
