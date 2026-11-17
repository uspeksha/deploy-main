<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanInstallment>
 */
class LoanInstallmentFactory extends Factory
{
    protected $model = LoanInstallment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'payroll_item_id' => null,
            'installment_no' => 1,
            'amount' => 200000,
            'due_date' => fake()->date(),
            'status' => LoanInstallment::STATUS_PENDING,
        ];
    }
}
