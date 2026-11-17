<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'approved_by' => null,
            'principal' => 1000000,
            'tenor_months' => 5,
            'status' => Loan::STATUS_PENDING,
        ];
    }
}
