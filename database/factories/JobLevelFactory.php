<?php

namespace Database\Factories;

use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobLevel>
 */
class JobLevelFactory extends Factory
{
    protected $model = JobLevel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'rank_order' => fake()->numberBetween(1, 10),
        ];
    }
}
