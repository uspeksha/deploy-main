<?php

use App\Exports\PayrollSummaryExport;
use App\Models\ActivityLog;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

function createPayrollAttendance(
    User $employee,
    string $date,
    int $lateMinutes = 0,
    string $approval = 'approved',
    bool $mocked = false,
    string $checkInTime = '08:00:00',
): void {
    DB::table('absensis')->insert([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'tipe' => null,
        'check_in_at' => "{$date} {$checkInTime}",
        'late_minutes' => $lateMinutes,
        'parent_id' => null,
        'is_mocked' => $mocked,
        'status_approval' => $approval,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('restricts payroll to HRD and generates an immutable snapshot from approved freelance attendance', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $unauthorized = User::factory()->create(['role' => 'admin']);

    $this->actingAs($unauthorized)
        ->get(route('admin.payroll.index'))
        ->assertForbidden();

    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
        'is_active' => true,
        'departemen' => 'Operasional',
    ]);
    $noAttendanceEmployee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    User::factory()->create([
        'role' => 'hrd',
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    foreach ([
        ['2026-10-01', 0, 'approved', false, '08:00:00'],
        ['2026-10-02', 29, 'approved', false, '08:00:00'],
        ['2026-10-03', 30, 'approved', false, '08:00:00'],
        ['2026-10-04', 59, 'approved', false, '08:00:00'],
        ['2026-10-05', 60, 'approved', false, '08:00:00'],
        ['2026-10-06', 90, 'approved', false, '23:59:59'],
        ['2026-10-07', 0, 'pending', false, '08:00:00'],
        ['2026-10-08', 0, 'approved', true, '08:00:00'],
        ['2026-10-16', 0, 'approved', false, '08:00:00'],
    ] as [$date, $late, $approval, $mocked, $checkInTime]) {
        createPayrollAttendance($employee, $date, $late, $approval, $mocked, $checkInTime);
    }
    createPayrollAttendance($employee, '2026-10-03', 60);

    $this->actingAs($hrd)
        ->post(route('admin.payroll.store'), [
            'year' => 2026,
            'month' => 10,
            'period_number' => 1,
        ])
        ->assertRedirect();

    $period = PayrollPeriod::query()->where('year', 2026)->where('month', 10)->where('period_number', 1)->firstOrFail();
    $item = PayrollItem::query()->where('payroll_period_id', $period->id)->where('user_id', $employee->id)->firstOrFail();
    $noAttendanceItem = PayrollItem::query()->where('payroll_period_id', $period->id)->where('user_id', $noAttendanceEmployee->id)->firstOrFail();

    expect($period->status)->toBe(PayrollPeriod::STATUS_DRAFT)
        ->and($period->start_date->toDateString())->toBe('2026-10-01')
        ->and($period->end_date->toDateString())->toBe('2026-10-15')
        ->and($item->work_days)->toBe(6)
        ->and($item->daily_rate)->toBe(120000)
        ->and($item->gross_pay)->toBe(720000)
        ->and($item->late_occurrences)->toBe(5)
        ->and($item->total_late_minutes)->toBe(268)
        ->and($item->late_deduction)->toBe(52500)
        ->and($item->other_deductions)->toBe(0)
        ->and($item->net_pay)->toBe(667500)
        ->and($item->latenessDetails()->count())->toBe(5)
        ->and(count($item->attendance_snapshot['work_dates']))->toBe(6)
        ->and($noAttendanceItem->work_days)->toBe(0)
        ->and($noAttendanceItem->gross_pay)->toBe(0)
        ->and($period->items()->count())->toBe(2)
        ->and(ActivityLog::query()->where('action', 'payroll.draft_created')->value('subject'))
        ->toBe("Payroll period ID: {$period->id}");

    $this->get(route('admin.payroll.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertSee('Rincian Payroll')
        ->assertSee($period->label);

    createPayrollAttendance($employee, '2026-10-09', 120);
    $this->actingAs($hrd)
        ->post(route('admin.payroll.store'), [
            'year' => 2026,
            'month' => 10,
            'period_number' => 1,
        ])
        ->assertSessionHas('warning');

    expect(PayrollPeriod::count())->toBe(1)
        ->and(PayrollItem::where('payroll_period_id', $period->id)->count())->toBe(2)
        ->and($item->fresh()->work_days)->toBe(6);
});

it('enforces one-way approval and manual payment transitions and records payment reference', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $period = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_DRAFT,
        'generated_by' => $hrd->id,
    ]);

    $this->actingAs($hrd)
        ->post(route('admin.payroll.submit', $period))
        ->assertRedirect();
    $this->post(route('admin.payroll.approve', $period))
        ->assertRedirect();
    $this->post(route('admin.payroll.paid', $period), ['payment_reference' => 'BUKTI-2026-001'])
        ->assertRedirect();
    $this->post(route('admin.payroll.paid', $period), ['payment_reference' => 'BUKTI-2026-001'])
        ->assertStatus(409);

    $this->post(route('admin.payroll.corrections.store', $period), [
        'adjustment_amount' => -25000,
        'reason' => 'Ada koreksi data kehadiran',
    ])->assertRedirect();
    $correction = $period->corrections()->firstOrFail();
    $this->post(route('admin.payroll.corrections.reconcile', [$period, $correction]), [
        'settlement_reference' => 'REKON-001',
        'settlement_note' => 'Diselesaikan manual',
    ])->assertRedirect();

    expect($period->fresh()->status)->toBe(PayrollPeriod::STATUS_PAID)
        ->and($period->fresh()->payment_reference)->toBe('BUKTI-2026-001')
        ->and($period->fresh()->paid_by)->toBe($hrd->id)
        ->and($period->fresh()->paid_at)->not->toBeNull()
        ->and($correction->fresh()->status)->toBe('reconciled')
        ->and($correction->fresh()->settlement_reference)->toBe('REKON-001');
});

it('creates a new payroll draft from a cancelled period without asking for a separate reason', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->post(route('admin.payroll.store'), [
            'year' => 2026,
            'month' => 10,
            'period_number' => 1,
        ])
        ->assertRedirect();
    $original = PayrollPeriod::query()->firstOrFail();

    $this->post(route('admin.payroll.cancel', $original), ['reason' => 'Kesalahan input periode'])
        ->assertRedirect();
    $this->get(route('admin.payroll.index', ['period_id' => $original->id]))
        ->assertOk()
        ->assertSee('Buat Ulang Periode Payroll')
        ->assertSee('Payroll lama tetap berstatus Dibatalkan untuk riwayat.')
        ->assertSee('Buat Payroll Baru')
        ->assertSee('Alasan pembatalan lama: Kesalahan input periode')
        ->assertSee('value="2026" selected', false)
        ->assertSee('value="10" selected', false)
        ->assertSee('value="1" selected', false)
        ->assertDontSee('name="correction_reason"', false);

    $this->post(route('admin.payroll.store'), [
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
    ])->assertRedirect();

    $revision = PayrollPeriod::query()->where('revision', 2)->firstOrFail();
    expect(PayrollPeriod::count())->toBe(2)
        ->and($revision->status)->toBe(PayrollPeriod::STATUS_DRAFT)
        ->and($revision->revision_of_id)->toBe($original->id)
        ->and($revision->correction_reason)->toBe("Dibuat ulang setelah payroll #{$original->id} dibatalkan")
        ->and($revision->label)->toContain('Dibuat Ulang')
        ->and($revision->label)->not->toContain('Revisi');
});

it('prevents a user from downloading another payroll slip', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $period = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_DRAFT,
        'generated_by' => $hrd->id,
    ]);
    $employee = User::factory()->create(['employment_type' => 'freelance', 'registration_status' => 'approved']);
    $item = PayrollItem::query()->create([
        'payroll_period_id' => $period->id,
        'user_id' => $employee->id,
        'employee_id' => $employee->id_karyawan,
        'employee_name' => $employee->name,
        'employment_type' => 'freelance',
        'work_days' => 0,
        'daily_rate' => 120000,
        'gross_pay' => 0,
        'late_occurrences' => 0,
        'total_late_minutes' => 0,
        'late_deduction' => 0,
        'other_deductions' => 0,
        'net_pay' => 0,
        'attendance_snapshot' => ['attendance_ids' => [], 'work_dates' => []],
        'slip_number' => 'FM-202610-W1-'.$employee->id,
    ]);

    $this->actingAs($employee)
        ->get(route('admin.payroll.slip', [$period, $item]))
        ->assertForbidden();
});

it('exports the saved payroll snapshot to Excel and a branded PDF slip', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
        'departemen' => 'Operasional',
        'bank_name' => 'Bank Contoh',
        'bank_account_number' => '001234567890',
        'bank_account_name' => 'Nama Penerima',
    ]);
    $period = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_DRAFT,
        'generated_by' => $hrd->id,
    ]);
    $item = PayrollItem::query()->create([
        'payroll_period_id' => $period->id,
        'user_id' => $employee->id,
        'employee_id' => $employee->id_karyawan,
        'employee_name' => $employee->name,
        'employment_type' => 'freelance',
        'department' => $employee->departemen,
        'work_days' => 10,
        'daily_rate' => 120000,
        'gross_pay' => 1200000,
        'late_occurrences' => 1,
        'total_late_minutes' => 30,
        'late_deduction' => 7500,
        'other_deductions' => 0,
        'net_pay' => 1192500,
        'attendance_snapshot' => ['attendance_ids' => [], 'work_dates' => []],
        'slip_number' => 'FM-202610-W1-'.$employee->id,
    ]);
    $item->latenessDetails()->create([
        'attendance_id' => 1,
        'work_date' => '2026-10-03',
        'late_minutes' => 30,
        'penalty_blocks' => 1,
        'deduction_amount' => 7500,
        'lateness_source' => 'stored_late_minutes_08_00_baseline',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.payroll.export', $period))
        ->assertOk()
        ->assertDownload('payroll-2026-10-minggu-1.xlsx');

    $temporaryFile = tempnam(sys_get_temp_dir(), 'payroll-export-test');
    try {
        file_put_contents($temporaryFile, Excel::raw(
            new PayrollSummaryExport($period),
            Maatwebsite\Excel\Excel::XLSX,
        ));
        $spreadsheet = IOFactory::load($temporaryFile);
        $worksheet = $spreadsheet->getActiveSheet();

        expect($worksheet->getCell('A1')->getValue())->toBe('FOLLOWME | REKAP PAYROLL KARYAWAN FREELANCE')
            ->and($worksheet->getCell('C5')->getValue())->toBe('Bank Contoh')
            ->and($worksheet->getCell('D5')->getValue())->toBe('001234567890')
            ->and($worksheet->getCell('D5')->getDataType())->toBe('s')
            ->and($worksheet->getCell('E5')->getValue())->toBe('Nama Penerima')
            ->and($worksheet->getCell('I5')->getValue())->toBe(120000)
            ->and($worksheet->getCell('I5')->getStyle()->getNumberFormat()->getFormatCode())->toBe('"Rp" #,##0;[Red]-"Rp" #,##0')
            ->and($worksheet->getCell('O5')->getValue())->toBe(1192500)
            ->and($worksheet->getCell('B6')->getValue())->toBe('TOTAL')
            ->and($worksheet->getCell('O6')->getValue())->toBe(1192500)
            ->and($worksheet->getFreezePane())->toBe('A5');

        $spreadsheet->disconnectWorksheets();
    } finally {
        unlink($temporaryFile);
    }

    $this->get(route('admin.payroll.slip.preview', [$period, $item]))
        ->assertOk()
        ->assertSee('FOLLOWME')
        ->assertSee('Gaji bersih diterima')
        ->assertSee('DRAFT - BELUM FINAL');

    $this->get(route('admin.payroll.slip', [$period, $item]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload($item->slip_number.'.pdf');
});
