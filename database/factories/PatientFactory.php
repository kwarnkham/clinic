<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'age' => fake()->numberBetween(1, 100),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'gender' => fake()->numberBetween(0, 1),
            'code' => Patient::generateCode(),
        ];
    }
}
