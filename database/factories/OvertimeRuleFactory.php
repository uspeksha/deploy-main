<?php

namespace Database\Factories;

use App\Models\OvertimeRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRule>
 */
class OvertimeRuleFactory extends Factory
{
    protected $model = OvertimeRule::class;

    public function definition(): array
    {
        return [
            'day_type' => OvertimeRule::DAY_TYPE_WEEKDAY,
            'hour_from' => 0,
            'hour_to' => null,
            'multiplier' => 1.00,
            'effective_at' => fake()->date(),
        ];
    }
}
