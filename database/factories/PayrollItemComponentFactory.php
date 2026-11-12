<?php

namespace Database\Factories;

use App\Models\PayrollComponent;
use App\Models\PayrollItemComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItemComponent>
 */
class PayrollItemComponentFactory extends Factory
{
    protected $model = PayrollItemComponent::class;

    public function definition(): array
    {
        return [
            'payroll_item_id' => fn () => PayrollItemFactorySupport::createId(),
            'component_id' => PayrollComponent::factory(),
            'name_snapshot' => fake()->words(2, true),
            'type' => PayrollComponent::TYPE_EARNING,
            'amount' => fake()->numberBetween(0, 10000000),
        ];
    }
}
