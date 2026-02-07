<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Workplace;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'workplace_enter_code' => fake()->unique()->numerify('####'),
            'workplace_id' => Workplace::factory(),
        ];
    }
}
