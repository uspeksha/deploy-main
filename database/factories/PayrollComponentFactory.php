<?php

namespace Database\Factories;

use App\Models\PayrollComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollComponent>
 */
class PayrollComponentFactory extends Factory
{
    protected $model = PayrollComponent::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'type' => PayrollComponent::TYPE_EARNING,
            'calc_type' => PayrollComponent::CALC_FIXED,
            'is_taxable' => null,
            'is_bpjs_base' => null,
            'is_active' => false,
        ];
    }
}
