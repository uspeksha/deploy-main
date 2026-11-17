<?php

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createEmployeePayrollItem(PayrollPeriod $period, User $employee, string $slipNumber): PayrollItem
{
    return PayrollItem::query()->create([
        'payroll_period_id' => $period->id,
        'user_id' => $employee->id,
        'employee_id' => $employee->id_karyawan,
        'employee_name' => $employee->name,
        'employment_type' => $employee->employment_type ?? 'organik',
        'department' => $employee->departemen,
        'work_days' => 1,
        'daily_rate' => 120000,
        'gross_pay' => 120000,
        'late_occurrences' => 0,
        'total_late_minutes' => 0,
        'late_deduction' => 0,
        'other_deductions' => 0,
        'net_pay' => 120000,
        'attendance_snapshot' => [],
        'slip_number' => $slipNumber,
    ]);
}

function createEmployeePayrollPeriod(string $status, int $month): PayrollPeriod
{
    return PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => $month,
        'period_number' => 1,
        'revision' => 1,
        'start_date' => sprintf('2026-%02d-01', $month),
        'end_date' => sprintf('2026-%02d-15', $month),
        'status' => $status,
    ]);
}

it('returns only the authenticated employees paid payroll slips', function () {
    $employee = User::factory()->create();
    $anotherEmployee = User::factory()->create();
    $paid = createEmployeePayrollPeriod(PayrollPeriod::STATUS_PAID, 1);
    $approved = createEmployeePayrollPeriod(PayrollPeriod::STATUS_APPROVED, 2);

    $ownSlip = createEmployeePayrollItem($paid, $employee, 'SLIP-OWN');
    createEmployeePayrollItem($paid, $anotherEmployee, 'SLIP-OTHER');
    createEmployeePayrollItem($approved, $employee, 'SLIP-UNPAID');

    Sanctum::actingAs($employee);

    $this->getJson('/api/v1/payroll/slips')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ownSlip->id)
        ->assertJsonPath('data.0.slip_number', 'SLIP-OWN')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonMissing(['slip_number' => 'SLIP-OTHER'])
        ->assertJsonMissing(['slip_number' => 'SLIP-UNPAID']);
});

it('does not allow employees to download another employee or unpaid payroll slips', function () {
    $employee = User::factory()->create();
    $anotherEmployee = User::factory()->create();
    $paid = createEmployeePayrollPeriod(PayrollPeriod::STATUS_PAID, 1);
    $approved = createEmployeePayrollPeriod(PayrollPeriod::STATUS_APPROVED, 2);
    $otherSlip = createEmployeePayrollItem($paid, $anotherEmployee, 'SLIP-OTHER');
    $unpaidSlip = createEmployeePayrollItem($approved, $employee, 'SLIP-UNPAID');

    Sanctum::actingAs($employee);

    $this->get("/api/v1/payroll/slips/{$otherSlip->id}/download")->assertNotFound();
    $this->get("/api/v1/payroll/slips/{$unpaidSlip->id}/download")->assertNotFound();
});
