<?php

namespace Database\Factories;

use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reimbursement>
 */
class ReimbursementFactory extends Factory
{
    protected $model = Reimbursement::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type_id' => ReimbursementType::factory(),
            'payroll_item_id' => null,
            'approved_by' => null,
            'amount' => 10000,
            'receipt_path' => 'reimbursements/private/receipt.pdf',
            'status' => Reimbursement::STATUS_PENDING,
        ];
    }
}
