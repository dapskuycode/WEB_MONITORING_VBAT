<?php

namespace Database\Factories;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminNotificationFactory extends Factory
{
    protected $model = AdminNotification::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            'product.uploaded',
            'campaign.paused',
            'benefit.overridden',
            'sponsor.tier_changed',
        ]);

        $actorType = fake()->randomElement(['sponsor', 'admin', 'system']);

        return [
            'type' => $type,
            'title' => fake()->sentence(6),
            'body' => fake()->optional(0.7)->paragraph(),
            'actor_type' => $actorType,
            'actor_id' => $actorType === 'system' ? null : fake()->numberBetween(1, 100),
            'target_type' => fake()->optional(0.6)->randomElement(['product', 'campaign', 'sponsor']),
            'target_id' => fake()->optional(0.6)->numberBetween(1, 100),
            'metadata' => null,
            'deep_link' => fake()->optional(0.3)->url(),
            'is_read' => false,
            'read_at' => null,
            'recipient_admin_id' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}