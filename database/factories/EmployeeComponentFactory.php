<?php

namespace Database\Factories;

use App\Models\EmployeeComponent;
use App\Models\PayrollComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeComponent>
 */
class EmployeeComponentFactory extends Factory
{
    protected $model = EmployeeComponent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'component_id' => PayrollComponent::factory(),
            'amount' => fake()->numberBetween(1000, 10000000),
            'effective_at' => fake()->date(),
            'end_at' => null,
        ];
    }
}
