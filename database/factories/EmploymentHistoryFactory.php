<?php

namespace Database\Factories;

use App\Models\EmploymentHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentHistory>
 */
class EmploymentHistoryFactory extends Factory
{
    protected $model = EmploymentHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employment_type' => fake()->randomElement(['organik', 'freelance', 'magang', 'borongan']),
            'start_date' => fake()->date(),
            'end_date' => null,
            'note' => null,
        ];
    }
}
