<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalanceLog>
 */
class LeaveBalanceLogFactory extends Factory
{
    protected $model = LeaveBalanceLog::class;

    public function definition(): array
    {
        return [
            'leave_balance_id' => LeaveBalance::factory(),
            'absensi_id' => null,
            'amount' => -1,
            'reason' => fake()->sentence(),
        ];
    }

    public function forAbsensi(): static
    {
        return $this->state(fn () => [
            'absensi_id' => Absensi::factory(),
        ]);
    }
}
