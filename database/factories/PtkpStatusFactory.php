<?php

namespace Database\Factories;

use App\Models\PtkpStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PtkpStatus>
 */
class PtkpStatusFactory extends Factory
{
    protected $model = PtkpStatus::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('??/#'),
            'annual_amount' => fake()->numberBetween(54000000, 72000000),
            'effective_at' => fake()->date(),
        ];
    }
}
