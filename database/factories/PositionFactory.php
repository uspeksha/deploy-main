<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'job_level_id' => JobLevel::factory(),
            'name' => fake()->unique()->jobTitle(),
        ];
    }
}
