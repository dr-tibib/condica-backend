<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PresenceEvent>
 */
class PresenceEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'workplace_id' => \App\Models\Workplace::factory(),
            'event_type' => fake()->randomElement(['check_in', 'check_out']),
            'event_time' => now(),
            'method' => fake()->randomElement(['auto', 'manual']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'accuracy' => fake()->randomFloat(2, 5, 50),
            'device_info' => fake()->userAgent(),
            'app_version' => fake()->numerify('#.#.#'),
            'notes' => fake()->optional()->sentence(),
            'pair_event_id' => null,
        ];
    }
}
