<?php

namespace Database\Factories;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entitlement>
 */
class EntitlementFactory extends Factory
{
    protected $model = Entitlement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_type' => Entitlement::PACKAGE_ANDROID,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => Entitlement::STATUS_ACTIVE,
            'source' => 'purchase',
            'transaction_reference' => null,
        ];
    }

    public function android(): static
    {
        return $this->state(fn () => ['package_type' => Entitlement::PACKAGE_ANDROID]);
    }

    public function iphone(): static
    {
        return $this->state(fn () => ['package_type' => Entitlement::PACKAGE_IPHONE]);
    }

    public function bundling(): static
    {
        return $this->state(fn () => ['package_type' => Entitlement::PACKAGE_BUNDLING]);
    }

    public function hardwareSolution(): static
    {
        return $this->state(fn () => ['package_type' => Entitlement::PACKAGE_HARDWARE_SOLUTION]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Entitlement::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => Entitlement::STATUS_REVOKED,
        ]);
    }
}