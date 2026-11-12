<?php

namespace Database\Factories;

use App\Models\EmployeeFamily;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeFamily>
 */
class EmployeeFamilyFactory extends Factory
{
    protected $model = EmployeeFamily::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['spouse', 'child', 'parent', 'sibling']),
            'is_emergency_contact' => false,
            'phone' => fake()->phoneNumber(),
        ];
    }
}
