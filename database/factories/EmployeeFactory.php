<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => fake()->unique()->numerify('EMP-#####'),
            'national_id_number' => fake()->numerify('################'),
            'name' => fake()->name(),
            'staff_type' => fake()->randomElement(['tendik', 'admin', 'staf_tu', 'laboran', 'other']),
            'position_title' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female']),
            'status' => 'active',
        ];
    }
}
