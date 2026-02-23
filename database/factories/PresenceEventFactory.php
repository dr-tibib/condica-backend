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
        $startAt = fake()->dateTimeBetween('-1 month', 'now');
        $endAt = (clone $startAt)->modify('+' . fake()->numberBetween(1, 8) . ' hours');

        return [
            'employee_id' => \App\Models\Employee::factory(),
            'workplace_id' => \App\Models\Workplace::factory(),
            'type' => fake()->randomElement(['presence', 'delegation']),
            'start_at' => $startAt,
            'end_at' => fake()->boolean(80) ? $endAt : null,
            'start_method' => fake()->randomElement(['auto', 'manual', 'kiosk']),
            'end_method' => fake()->boolean(80) ? fake()->randomElement(['auto', 'manual', 'kiosk']) : null,
            'start_latitude' => fake()->latitude(),
            'start_longitude' => fake()->longitude(),
            'start_accuracy' => fake()->numberBetween(5, 50),
            'end_latitude' => fake()->boolean(80) ? fake()->latitude() : null,
            'end_longitude' => fake()->boolean(80) ? fake()->longitude() : null,
            'end_accuracy' => fake()->boolean(80) ? fake()->numberBetween(5, 50) : null,
            'start_device_info' => ['user_agent' => fake()->userAgent()],
            'end_device_info' => fake()->boolean(80) ? ['user_agent' => fake()->userAgent()] : null,
            'app_version' => fake()->numerify('#.#.#'),
            'notes' => fake()->optional()->sentence(),
            'linkable_id' => null,
            'linkable_type' => null,
        ];
    }
}
