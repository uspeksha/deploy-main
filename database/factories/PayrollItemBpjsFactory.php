<?php

namespace Database\Factories;

use App\Models\BpjsRate;
use App\Models\PayrollItemBpjs;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItemBpjs>
 */
class PayrollItemBpjsFactory extends Factory
{
    protected $model = PayrollItemBpjs::class;

    public function definition(): array
    {
        return [
            'payroll_item_id' => fn () => PayrollItemFactorySupport::createId(),
            'bpjs_rate_id' => BpjsRate::factory(),
            'employee_amount' => 0,
            'employer_amount' => 0,
        ];
    }
}
