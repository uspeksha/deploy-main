<?php

namespace Database\Factories;

use App\Models\ReimbursementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReimbursementType>
 */
class ReimbursementTypeFactory extends Factory
{
    protected $model = ReimbursementType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'monthly_limit' => null,
        ];
    }
}
