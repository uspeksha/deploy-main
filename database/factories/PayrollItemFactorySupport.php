<?php

namespace Database\Factories;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;

final class PayrollItemFactorySupport
{
    public static function createId(): int
    {
        $user = User::factory()->create();
        $today = now();
        $revision = ((int) PayrollPeriod::query()
            ->where('year', $today->year)
            ->where('month', $today->month)
            ->where('period_number', 1)
            ->max('revision')) + 1;
        $period = PayrollPeriod::query()->create([
            'year' => $today->year,
            'month' => $today->month,
            'period_number' => 1,
            'revision' => $revision,
            'start_date' => $today->copy()->startOfMonth()->toDateString(),
            'end_date' => $today->copy()->startOfMonth()->addDays(14)->toDateString(),
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);

        return PayrollItem::query()->create([
            'payroll_period_id' => $period->id,
            'user_id' => $user->id,
            'employee_id' => $user->id_karyawan,
            'employee_name' => $user->name,
            'employment_type' => $user->employment_type ?? 'organik',
            'department' => $user->departemen,
            'work_days' => 0,
            'daily_rate' => 0,
            'gross_pay' => 0,
            'late_occurrences' => 0,
            'total_late_minutes' => 0,
            'late_deduction' => 0,
            'other_deductions' => 0,
            'net_pay' => 0,
            'attendance_snapshot' => [],
            'slip_number' => fake()->unique()->bothify('TEST-########'),
        ])->id;
    }
}
