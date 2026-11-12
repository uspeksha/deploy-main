<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'late_tolerance_min' => 0,
            'is_overnight' => false,
        ];
    }
}
