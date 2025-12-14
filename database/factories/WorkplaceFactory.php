<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workplace>
 */
class WorkplaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Office',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'radius' => fake()->numberBetween(50, 200),
            'timezone' => 'UTC',
            'is_active' => true,
        ];
    }
}
