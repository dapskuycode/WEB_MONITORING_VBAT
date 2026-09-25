<?php

namespace Database\Factories;

use App\Models\PaymentPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentPackageFactory extends Factory
{
    protected $model = PaymentPackage::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 500000, 3000000),
            'type' => 'one_time',
            'entitlements' => ['android' => true],
            'is_active' => true,
            'sort_order' => $this->faker->randomDigit(),
        ];
    }
}
