<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);

        return [
            'price' => fake()->numberBetween(1000, 10000),
            'expired_on' => fake()->date(),
            'quantity' => $quantity,
            'stock' => $quantity,
        ];
    }
}
