<?php

namespace Database\Factories;

use App\Models\PayrollItemTax;
use App\Models\PtkpStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItemTax>
 */
class PayrollItemTaxFactory extends Factory
{
    protected $model = PayrollItemTax::class;

    public function definition(): array
    {
        return [
            'payroll_item_id' => fn () => PayrollItemFactorySupport::createId(),
            'ptkp_status_id' => PtkpStatus::factory(),
            'taxable_income' => 0,
            'pph21_amount' => 0,
            'method' => PayrollItemTax::METHOD_MANUAL,
        ];
    }
}
