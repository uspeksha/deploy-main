<?php

namespace Database\Factories;

use App\Models\BpjsRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BpjsRate>
 */
class BpjsRateFactory extends Factory
{
    protected $model = BpjsRate::class;

    public function definition(): array
    {
        return [
            'program' => 'jht',
            'employee_rate' => 0.0200,
            'employer_rate' => 0.0370,
            'salary_cap' => null,
            'effective_at' => fake()->date(),
        ];
    }
}
