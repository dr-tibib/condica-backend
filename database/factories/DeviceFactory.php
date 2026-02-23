<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\Employee::factory(),
            'device_token' => fake()->unique()->uuid(),
            'device_name' => fake()->randomElement([
                'iPhone 14 Pro',
                'iPhone 15',
                'Samsung Galaxy S23',
                'Google Pixel 8',
                'iPad Pro',
            ]),
            'platform' => fake()->randomElement(['ios', 'android']),
            'app_version' => fake()->numerify('#.#.#'),
            'os_version' => fake()->numerify('##.#'),
            'last_active_at' => now(),
        ];
    }
}
