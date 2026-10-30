<?php

use App\Exports\PayrollSummaryExport;
use App\Models\ActivityLog;
use App\Models\PayrollCorrection;
use App\Models\PayrollItem;
use App\Models\PayrollLatenessDetail;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollGenerationService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

function createPayrollAttendance(
    User $employee,
    string $date,
    int $lateMinutes = 0,
    string $approval = 'approved',
    bool $mocked = false,
    ?string $checkInTime = null,
): void {
    $checkInTime ??= Carbon::parse('08:00:00')
        ->addMinutes($lateMinutes)
        ->format('H:i:s');

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
        ['2026-10-02', 29, 'approved', false, '08:29:00'],
        ['2026-10-03', 30, 'approved', false, '08:30:00'],
        ['2026-10-04', 59, 'approved', false, '08:59:00'],
        ['2026-10-05', 60, 'approved', false, '09:00:00'],
        ['2026-10-06', 90, 'approved', false, '09:30:00'],
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
        ->and(count($item->attendance_snapshot['daily_details']))->toBe(6)
        ->and($item->attendance_snapshot['daily_details'][0]['check_in_at'])->toContain('08:00:00')
        ->and($noAttendanceItem->work_days)->toBe(0)
        ->and($noAttendanceItem->gross_pay)->toBe(0)
        ->and($period->items()->count())->toBe(2)
        ->and(ActivityLog::query()->where('action', 'payroll.draft_created')->value('subject'))
        ->toBe("Payroll period ID: {$period->id}");

    $this->get(route('admin.payroll.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertSee('min-w-0 flex-1 lg:ml-80', false)
        ->assertSee('Rincian Payroll')
        ->assertSee(route('admin.payroll.index', ['period_id' => $period->id]).'#rincian-payroll')
        ->assertSee('Unduh Excel')
        ->assertDontSee('Ajukan Persetujuan')
        ->assertSee('Opsi lainnya: batalkan payroll')
        ->assertSee($period->label);

    $this->actingAs($unauthorized)
        ->get(route('admin.payroll.export', $period))
        ->assertForbidden();

    $this->actingAs($hrd)
        ->get(route('admin.payroll.export', $period))
        ->assertOk()
        ->assertDownload('payroll-2026-10-minggu-1.xlsx');

    $this->actingAs($unauthorized)
        ->post(route('admin.payroll.cancel', $period), ['reason' => 'Tidak berwenang'])
        ->assertForbidden();

    expect($period->fresh()->status)->toBe(PayrollPeriod::STATUS_EXPORTED);

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

it('uses each attendance date rate in payroll snapshots and leaves generated payroll unchanged after later rate updates', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
        'is_active' => true,
        'id_karyawan' => 'AMB-RATE-001',
    ]);
    $employee->salaryRates()->create([
        'daily_rate' => 100000,
        'effective_at' => '2026-10-01 00:00:00',
        'recorded_by' => $hrd->id,
    ]);
    $employee->salaryRates()->create([
        'daily_rate' => 150000,
        'effective_at' => '2026-10-03 00:00:00',
        'recorded_by' => $hrd->id,
    ]);
    createPayrollAttendance($employee, '2026-10-01');
    createPayrollAttendance($employee, '2026-10-03');

    $this->actingAs($hrd)
        ->post(route('admin.payroll.store'), [
            'year' => 2026,
            'month' => 10,
            'period_number' => 1,
        ])
        ->assertRedirect();

    $period = PayrollPeriod::query()->where('year', 2026)->where('month', 10)->where('period_number', 1)->firstOrFail();
    $item = $period->items()->where('user_id', $employee->id)->firstOrFail();
    $originalSnapshot = $item->attendance_snapshot;

    expect($item->gross_pay)->toBe(250000)
        ->and($item->daily_rate_summary)->toBe('Bervariasi')
        ->and(array_column($item->attendance_snapshot['daily_details'], 'daily_rate'))->toBe([100000, 150000]);

    $this->post(route('admin.absensi.approval.employees.salary-rates.store', $employee), [
        'daily_rate' => 175000,
        'effective_at' => '2026-10-05T00:00',
    ])->assertSessionHas('success');

    expect($item->fresh()->gross_pay)->toBe(250000)
        ->and($item->fresh()->attendance_snapshot)->toBe($originalSnapshot);
});

it('excludes the lunch break from payroll lateness even for older stored attendance minutes', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
        'is_active' => true,
    ]);
    createPayrollAttendance($employee, '2026-10-12', 270, checkInTime: '12:30:00');

    $period = (new PayrollGenerationService)->createPeriod(2026, 10, 1, $hrd->id);
    $item = PayrollItem::query()
        ->where('payroll_period_id', $period->id)
        ->where('user_id', $employee->id)
        ->firstOrFail();
    $lateness = $item->latenessDetails()->firstOrFail();

    expect($item->total_late_minutes)->toBe(240)
        ->and($item->late_deduction)->toBe(60_000)
        ->and($item->net_pay)->toBe(60_000)
        ->and($lateness->late_minutes)->toBe(240)
        ->and($lateness->penalty_blocks)->toBe(8)
        ->and($lateness->deduction_amount)->toBe(60_000);
});

it('denies the non-HRD admin role across all protected payroll actions', function () {
    $nonHrdAdmin = User::factory()->create(['role' => 'admin']);
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
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    $item = $period->items()->create([
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
    $correction = PayrollCorrection::query()->create([
        'payroll_period_id' => $period->id,
        'recorded_by' => $hrd->id,
        'adjustment_amount' => -1000,
        'reason' => 'Synthetic authorization fixture',
        'status' => 'open',
    ]);

    $requests = [
        fn () => $this->get(route('admin.payroll.index')),
        fn () => $this->post(route('admin.payroll.store'), [
            'year' => 2026,
            'month' => 10,
            'period_number' => 1,
        ]),
        fn () => $this->post(route('admin.payroll.submit', $period)),
        fn () => $this->post(route('admin.payroll.approve', $period)),
        fn () => $this->post(route('admin.payroll.paid', $period), ['payment_reference' => 'SYNTHETIC']),
        fn () => $this->post(route('admin.payroll.cancel', $period), ['reason' => 'Synthetic']),
        fn () => $this->delete(route('admin.payroll.destroy', $period)),
        fn () => $this->post(route('admin.payroll.corrections.store', $period), [
            'adjustment_amount' => -1000,
            'reason' => 'Synthetic',
        ]),
        fn () => $this->post(route('admin.payroll.corrections.reconcile', [$period, $correction]), [
            'settlement_reference' => 'SYNTHETIC',
            'settlement_note' => 'Synthetic',
        ]),
        fn () => $this->get(route('admin.payroll.export', $period)),
        fn () => $this->get(route('admin.payroll.slip.preview', [$period, $item])),
        fn () => $this->get(route('admin.payroll.slip', [$period, $item])),
    ];

    $this->actingAs($nonHrdAdmin);
    foreach ($requests as $request) {
        $request()->assertForbidden();
    }

    expect($period->fresh()->status)->toBe(PayrollPeriod::STATUS_DRAFT)
        ->and($period->corrections()->count())->toBe(1)
        ->and($period->items()->count())->toBe(1)
        ->and($correction->fresh()->status)->toBe('open')
        ->and($item->fresh()->net_pay)->toBe(0);
});

it('allows HRD to permanently delete payrolls in any status and their related records', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $draftPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_DRAFT,
        'generated_by' => $hrd->id,
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    $item = $draftPeriod->items()->create([
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
    $exportedPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 2,
        'start_date' => '2026-10-16',
        'end_date' => '2026-10-31',
        'status' => PayrollPeriod::STATUS_EXPORTED,
        'generated_by' => $hrd->id,
    ]);
    $approvedPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 9,
        'period_number' => 1,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-15',
        'status' => PayrollPeriod::STATUS_APPROVED,
        'generated_by' => $hrd->id,
    ]);
    $paidPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 8,
        'period_number' => 1,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-15',
        'status' => PayrollPeriod::STATUS_PAID,
        'generated_by' => $hrd->id,
    ]);
    $correction = PayrollCorrection::query()->create([
        'payroll_period_id' => $paidPeriod->id,
        'recorded_by' => $hrd->id,
        'adjustment_amount' => 1000,
        'reason' => 'Catatan untuk uji penghapusan payroll',
        'status' => 'open',
    ]);
    $pendingPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 7,
        'period_number' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-15',
        'status' => PayrollPeriod::STATUS_PENDING_APPROVAL,
        'generated_by' => $hrd->id,
    ]);
    $cancelledPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 6,
        'period_number' => 1,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-15',
        'status' => PayrollPeriod::STATUS_CANCELLED,
        'generated_by' => $hrd->id,
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.payroll.index'))
        ->assertOk()
        ->assertSee(route('admin.payroll.destroy', $draftPeriod))
        ->assertSee(route('admin.payroll.destroy', $exportedPeriod))
        ->assertSee(route('admin.payroll.destroy', $approvedPeriod))
        ->assertSee(route('admin.payroll.destroy', $paidPeriod))
        ->assertSee(route('admin.payroll.destroy', $pendingPeriod))
        ->assertSee(route('admin.payroll.destroy', $cancelledPeriod));

    $this->actingAs($hrd)
        ->delete(route('admin.payroll.destroy', $draftPeriod))
        ->assertRedirect(route('admin.payroll.index'))
        ->assertSessionHas('success');

    expect(PayrollPeriod::query()->find($draftPeriod->id))->toBeNull()
        ->and(PayrollItem::query()->find($item->id))->toBeNull()
        ->and(ActivityLog::query()->where('action', 'payroll.deleted')->count())->toBe(1);

    $this->delete(route('admin.payroll.destroy', $exportedPeriod))
        ->assertRedirect(route('admin.payroll.index'));
    expect(PayrollPeriod::query()->find($exportedPeriod->id))->toBeNull();

    $this->delete(route('admin.payroll.destroy', $approvedPeriod))
        ->assertRedirect(route('admin.payroll.index'));
    $this->delete(route('admin.payroll.destroy', $paidPeriod))
        ->assertRedirect(route('admin.payroll.index'));
    $this->delete(route('admin.payroll.destroy', $pendingPeriod))
        ->assertRedirect(route('admin.payroll.index'));
    $this->delete(route('admin.payroll.destroy', $cancelledPeriod))
        ->assertRedirect(route('admin.payroll.index'));

    expect(PayrollPeriod::query()->find($approvedPeriod->id))->toBeNull()
        ->and(PayrollPeriod::query()->find($paidPeriod->id))->toBeNull()
        ->and(PayrollPeriod::query()->find($pendingPeriod->id))->toBeNull()
        ->and(PayrollPeriod::query()->find($cancelledPeriod->id))->toBeNull()
        ->and(PayrollCorrection::query()->find($correction->id))->toBeNull()
        ->and(ActivityLog::query()->where('action', 'payroll.deleted')->count())->toBe(6);
});

it('allows direct Excel export and preserves manual payroll transitions', function () {
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
        ->get(route('admin.payroll.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertSee('Unduh Excel')
        ->assertDontSee('Ajukan Persetujuan');
    $this->get(route('admin.payroll.export', $period))
        ->assertOk()
        ->assertDownload('payroll-2026-10-minggu-1.xlsx');
    expect($period->fresh()->status)->toBe(PayrollPeriod::STATUS_EXPORTED);

    $this->get(route('admin.payroll.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertSee('Unduh Excel')
        ->assertSee('Konfirmasi Dibayar')
        ->assertDontSee('Setujui Payroll');

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

it('rejects repeated approval and manual payment transitions', function () {
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
    $this->post(route('admin.payroll.approve', $period))
        ->assertStatus(409);
    $this->post(route('admin.payroll.paid', $period), ['payment_reference' => 'BUKTI-2026-001'])
        ->assertRedirect();
    $this->post(route('admin.payroll.paid', $period), ['payment_reference' => 'BUKTI-2026-001'])
        ->assertStatus(409);
});

it('creates a new payroll draft from a cancelled period without asking for a separate reason', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    createPayrollAttendance($employee, '2026-10-03');

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

    $originalItem = $original->items()->where('user_id', $employee->id)->firstOrFail();
    $originalSlipNumber = sprintf(
        'FM-%04d%02d-W%d-%06d',
        $original->year,
        $original->month,
        $original->period_number,
        $employee->id,
    );

    $this->post(route('admin.payroll.store'), [
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
    ])->assertRedirect();

    $revision = PayrollPeriod::query()->where('revision', 2)->firstOrFail();
    $revisionItem = $revision->items()->where('user_id', $employee->id)->firstOrFail();
    expect(PayrollPeriod::count())->toBe(2)
        ->and($revision->status)->toBe(PayrollPeriod::STATUS_DRAFT)
        ->and($revision->revision_of_id)->toBe($original->id)
        ->and($revision->correction_reason)->toBe("Dibuat ulang setelah payroll #{$original->id} dibatalkan")
        ->and($revision->label)->toContain('Dibuat Ulang')
        ->and($revision->label)->not->toContain('Revisi')
        ->and($originalItem->fresh()->slip_number)->toBe($originalSlipNumber)
        ->and($revisionItem->slip_number)->toBe($originalSlipNumber.'-R2')
        ->and($revisionItem->slip_number)->not->toBe($originalItem->slip_number)
        ->and(PayrollItem::query()->where('slip_number', $originalSlipNumber)->count())->toBe(1)
        ->and(PayrollItem::query()->where('slip_number', $originalSlipNumber.'-R2')->count())->toBe(1);

    $this->post(route('admin.payroll.cancel', $revision), ['reason' => 'Koreksi lanjutan'])
        ->assertRedirect();
    $revisionTwoSnapshot = $revisionItem->fresh()->attendance_snapshot;
    createPayrollAttendance($employee, '2026-10-04', 30);

    $this->post(route('admin.payroll.store'), [
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
    ])->assertRedirect();

    $revisionThree = PayrollPeriod::query()->where('revision', 3)->firstOrFail();
    $revisionThreeItem = $revisionThree->items()->where('user_id', $employee->id)->firstOrFail();
    expect(PayrollPeriod::query()->count())->toBe(3)
        ->and($revisionThree->revision_of_id)->toBe($revision->id)
        ->and($revisionThree->correction_reason)->toBe("Dibuat ulang setelah payroll #{$revision->id} dibatalkan")
        ->and($revisionThreeItem->slip_number)->toBe($originalSlipNumber.'-R3')
        ->and($revisionThreeItem->slip_number)->not->toBe($originalItem->slip_number)
        ->and($revisionThreeItem->slip_number)->not->toBe($revisionItem->slip_number)
        ->and($original->fresh()->items()->where('user_id', $employee->id)->value('slip_number'))->toBe($originalSlipNumber)
        ->and($revision->fresh()->items()->where('user_id', $employee->id)->value('slip_number'))->toBe($originalSlipNumber.'-R2')
        ->and($revision->fresh()->items()->where('user_id', $employee->id)->firstOrFail()->attendance_snapshot)->toBe($revisionTwoSnapshot)
        ->and(PayrollItem::query()->where('slip_number', $originalSlipNumber.'-R3')->count())->toBe(1);
});

it('rethrows an unrelated unique constraint failure instead of redirecting to an older period', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $cancelledPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'revision' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_CANCELLED,
        'generated_by' => $hrd->id,
    ]);
    $exception = new UniqueConstraintViolationException(
        'sqlite',
        'insert into payroll_items (...) values (...)',
        [],
        new PDOException('Synthetic unique constraint violation', 19),
    );
    $generationService = Mockery::mock(PayrollGenerationService::class);
    $generationService->shouldReceive('createPeriod')->once()->andThrow($exception);
    $this->instance(PayrollGenerationService::class, $generationService);

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($hrd)->post(route('admin.payroll.store'), [
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect(PayrollPeriod::query()->count())->toBe(1)
        ->and(PayrollPeriod::query()->where('revision', 2)->exists())->toBeFalse();
});

it('rolls back the period and payroll item when creating a lateness detail fails', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    createPayrollAttendance($employee, '2026-10-03', 30);

    PayrollLatenessDetail::creating(function (): void {
        throw new RuntimeException('Synthetic lateness detail failure');
    });

    expect(fn () => app(PayrollGenerationService::class)->createPeriod(
        2026,
        10,
        1,
        $hrd->id,
    ))->toThrow(RuntimeException::class, 'Synthetic lateness detail failure');

    expect(PayrollPeriod::query()->count())->toBe(0)
        ->and(PayrollItem::query()->count())->toBe(0)
        ->and(PayrollLatenessDetail::query()->count())->toBe(0);
});

it('rolls back a new period and item when creating a payroll item fails without changing existing payroll', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    createPayrollAttendance($employee, '2026-09-03', 30);
    $existingPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 9,
        'period_number' => 1,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-15',
        'status' => PayrollPeriod::STATUS_PAID,
        'generated_by' => $hrd->id,
    ]);
    $existingItem = PayrollItem::query()->create([
        'payroll_period_id' => $existingPeriod->id,
        'user_id' => $employee->id,
        'employee_id' => $employee->id_karyawan,
        'employee_name' => $employee->name,
        'employment_type' => 'freelance',
        'work_days' => 1,
        'daily_rate' => 120000,
        'gross_pay' => 120000,
        'late_occurrences' => 1,
        'total_late_minutes' => 30,
        'late_deduction' => 7500,
        'other_deductions' => 0,
        'net_pay' => 112500,
        'attendance_snapshot' => ['attendance_ids' => [], 'work_dates' => []],
        'slip_number' => 'FM-202609-W1-'.$employee->id,
    ]);
    $existingDetail = $existingItem->latenessDetails()->create([
        'attendance_id' => 1,
        'work_date' => '2026-09-03',
        'late_minutes' => 30,
        'penalty_blocks' => 1,
        'deduction_amount' => 7500,
        'lateness_source' => 'stored_late_minutes_08_00_baseline',
    ]);
    $existingPayrollSnapshot = [
        'period' => (array) DB::table('payroll_periods')->where('id', $existingPeriod->id)->first(),
        'item' => (array) DB::table('payroll_items')->where('id', $existingItem->id)->first(),
        'lateness_details' => DB::table('payroll_lateness_details')
            ->where('payroll_item_id', $existingItem->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all(),
    ];

    PayrollItem::creating(function (): void {
        throw new RuntimeException('Synthetic payroll item failure');
    });

    expect(fn () => app(PayrollGenerationService::class)->createPeriod(
        2026,
        10,
        1,
        $hrd->id,
    ))->toThrow(RuntimeException::class, 'Synthetic payroll item failure');

    $existingPayrollAfter = [
        'period' => (array) DB::table('payroll_periods')->where('id', $existingPeriod->id)->first(),
        'item' => (array) DB::table('payroll_items')->where('id', $existingItem->id)->first(),
        'lateness_details' => DB::table('payroll_lateness_details')
            ->where('payroll_item_id', $existingItem->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all(),
    ];

    expect(PayrollPeriod::query()->count())->toBe(1)
        ->and(PayrollPeriod::query()->where('month', 10)->exists())->toBeFalse()
        ->and(PayrollItem::query()->count())->toBe(1)
        ->and(PayrollLatenessDetail::query()->count())->toBe(1)
        ->and($existingPayrollAfter)->toEqual($existingPayrollSnapshot);
});

it('redirects with a conflict warning when a matching competing payroll period exists', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $previousPeriod = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'revision' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_CANCELLED,
        'generated_by' => $hrd->id,
    ]);
    $competingPeriod = null;
    $exception = new UniqueConstraintViolationException(
        'sqlite',
        'insert into payroll_periods (...) values (...)',
        [],
        new PDOException('Synthetic unique constraint violation', 19),
    );
    $generationService = Mockery::mock(PayrollGenerationService::class);
    $generationService->shouldReceive('createPeriod')
        ->once()
        ->andReturnUsing(function (
            int $year,
            int $month,
            int $periodNumber,
            int $actorId,
            int $revision,
            ?int $revisionOfId,
            ?string $correctionReason,
        ) use (&$competingPeriod, $exception): never {
            $competingPeriod = PayrollPeriod::query()->create([
                'year' => $year,
                'month' => $month,
                'period_number' => $periodNumber,
                'revision' => $revision,
                'revision_of_id' => $revisionOfId,
                'correction_reason' => $correctionReason,
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-15',
                'status' => PayrollPeriod::STATUS_DRAFT,
                'generated_by' => $actorId,
            ]);

            throw $exception;
        });
    $this->instance(PayrollGenerationService::class, $generationService);
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (Closure $callback) => $callback());

    $response = $this->actingAs($hrd)->post(route('admin.payroll.store'), [
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
    ]);

    $response->assertRedirect(route('admin.payroll.index', ['period_id' => $competingPeriod->id]))
        ->assertSessionHas('warning', 'Payroll periode ini baru saja dibuat oleh pengguna lain; data lama tidak dihitung ulang.');

    expect($competingPeriod)->not->toBeNull()
        ->and($competingPeriod->year)->toBe(2026)
        ->and($competingPeriod->month)->toBe(10)
        ->and($competingPeriod->period_number)->toBe(1)
        ->and($competingPeriod->revision)->toBe(2)
        ->and($competingPeriod->revision_of_id)->toBe($previousPeriod->id)
        ->and($competingPeriod->correction_reason)->toBe("Dibuat ulang setelah payroll #{$previousPeriod->id} dibatalkan")
        ->and(PayrollPeriod::query()->count())->toBe(2)
        ->and(PayrollPeriod::query()->where('month', 10)->where('revision', 2)->count())->toBe(1)
        ->and($previousPeriod->fresh()->status)->toBe(PayrollPeriod::STATUS_CANCELLED);
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
        'name' => 'Zoe Zulu',
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
        'departemen' => 'Operasional',
        'bank_name' => 'BCA',
        'bank_account_number' => '001234567890',
        'bank_account_name' => 'Nama Penerima',
    ]);
    $period = PayrollPeriod::query()->create([
        'year' => 2026,
        'month' => 10,
        'period_number' => 1,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-15',
        'status' => PayrollPeriod::STATUS_APPROVED,
        'generated_by' => $hrd->id,
        'approved_by' => $hrd->id,
        'approved_at' => now(),
    ]);
    createPayrollAttendance($employee, '2026-10-03', 30);
    $attendanceId = DB::table('absensis')
        ->where('user_id', $employee->id)
        ->whereDate('check_in_at', '2026-10-03')
        ->value('id');
    DB::table('absensis')->where('id', $attendanceId)->update([
        'check_out_at' => '2026-10-03 17:00:00',
    ]);
    DB::table('absensis')->insert([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'tipe' => 'lembur',
        'parent_id' => $attendanceId,
        'check_in_at' => '2026-10-03 17:00:00',
        'lembur_start' => '2026-10-03 17:00:00',
        'lembur_end' => '2026-10-03 19:00:00',
        'lembur_rest' => false,
        'overtime_minutes' => 120,
        'overtime_pay' => 30000.0,
        'status_approval' => 'approved',
        'created_at' => now(),
        'updated_at' => now(),
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
        'attendance_snapshot' => [
            'attendance_ids' => [$attendanceId],
            'work_dates' => ['2026-10-03'],
            'daily_details' => [
                [
                    'work_date' => '2026-10-03',
                    'attendance_status' => 'hadir',
                    'check_in_at' => '2026-10-03 08:30:00',
                    'check_out_at' => '2026-10-03 17:00:00',
                    'late_minutes' => 30,
                    'overtime_status' => 'approved',
                    'overtime_start' => ['2026-10-03 17:00:00'],
                    'overtime_end' => ['2026-10-03 19:00:00'],
                    'overtime_rest' => false,
                    'overtime_minutes' => 120,
                    'overtime_pay' => 30000,
                ],
            ],
        ],
        'slip_number' => 'FM-202610-W1-'.$employee->id,
    ]);
    foreach ([
        ['name' => 'Ada Alpha', 'bank_name' => 'Bank Central Asia (BCA)', 'bank_account_number' => '000000000001'],
        ['name' => 'Budi Beta', 'bank_name' => 'BRI', 'bank_account_number' => '000000000002'],
    ] as $employeeAttributes) {
        $groupedEmployee = User::factory()->create($employeeAttributes + [
            'employment_type' => 'freelance',
            'registration_status' => 'approved',
        ]);
        $period->items()->create([
            'user_id' => $groupedEmployee->id,
            'employee_id' => $groupedEmployee->id_karyawan,
            'employee_name' => $groupedEmployee->name,
            'employment_type' => 'freelance',
            'work_days' => 1,
            'daily_rate' => 120000,
            'gross_pay' => 120000,
            'late_occurrences' => 0,
            'total_late_minutes' => 0,
            'late_deduction' => 0,
            'other_deductions' => 0,
            'net_pay' => 120000,
            'attendance_snapshot' => ['attendance_ids' => [], 'work_dates' => [], 'daily_details' => []],
            'slip_number' => 'FM-202610-W1-'.$groupedEmployee->id,
        ]);
    }
    $item->latenessDetails()->create([
        'attendance_id' => $attendanceId,
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

    expect($period->fresh()->status)->toBe(PayrollPeriod::STATUS_APPROVED);

    $this->get(route('admin.payroll.index', ['period_id' => $period->id]))
        ->assertOk()
        ->assertSee('Siap Dibayar')
        ->assertDontSee('Ajukan Persetujuan');

    $temporaryFile = tempnam(sys_get_temp_dir(), 'payroll-export-test');
    try {
        file_put_contents($temporaryFile, Excel::raw(
            new PayrollSummaryExport($period),
            Maatwebsite\Excel\Excel::XLSX,
        ));
        $spreadsheet = IOFactory::load($temporaryFile);
        $worksheet = $spreadsheet->getActiveSheet();

        expect($worksheet->getCell('A1')->getValue())->toBe('PT. Followme Indonesia')
            ->and($worksheet->getCell('A2')->getValue())->toBe('Salary Report (1-15 Oktober 2026)')
            ->and($worksheet->getCell('D3')->getValue())->toBe('Allowance')
            ->and($worksheet->getCell('A4')->getValue())->toBe('Employee ID')
            ->and($worksheet->getCell('B4')->getValue())->toBe('Full Name')
            ->and($worksheet->getCell('C4')->getValue())->toBe('No. Rekening')
            ->and($worksheet->getCell('A5')->getValue())->toBe('REKENING BCA')
            ->and($worksheet->getCell('B6')->getValue())->toBe('Ada Alpha')
            ->and($worksheet->getCell('C6')->getValue())->toBe('000000000001')
            ->and($worksheet->getCell('C6')->getDataType())->toBe('s')
            ->and($worksheet->getCell('B7')->getValue())->toBe('Zoe Zulu')
            ->and($worksheet->getCell('C7')->getValue())->toBe('001234567890')
            ->and($worksheet->getCell('C7')->getDataType())->toBe('s')
            ->and($worksheet->getCell('D7')->getValue())->toBe(120000)
            ->and($worksheet->getCell('D7')->getStyle()->getNumberFormat()->getFormatCode())->toBe('"Rp" #,##0;[Red]-"Rp" #,##0;"Rp" -')
            ->and($worksheet->getCell('G7')->getValue())->toBe(1200000)
            ->and($worksheet->getCell('J7')->getValue())->toBe(30000.0)
            ->and($worksheet->getCell('K7')->getValue())->toBe(1230000.0)
            ->and($worksheet->getCell('M7')->getValue())->toBe(1222500.0)
            ->and($worksheet->getCell('A8')->getValue())->toBe('REKENING BRI')
            ->and($worksheet->getCell('B9')->getValue())->toBe('Budi Beta')
            ->and($worksheet->getCell('B10')->getValue())->toBe('GRAND TOTAL')
            ->and($worksheet->getCell('K10')->getValue())->toBe(1470000.0)
            ->and($worksheet->getCell('M10')->getValue())->toBe(1462500.0)
            ->and($worksheet->getStyle('A5')->getFill()->getStartColor()->getRGB())->toBe('5B9BD5')
            ->and($worksheet->getFreezePane())->toBe('A5')
            ->and($worksheet->getAutoFilter()->getRange())->toBe('')
            ->and($spreadsheet->getSheetCount())->toBe(1)
            ->and($spreadsheet->getSheetByName('Rincian Harian'))->toBeNull();

        $spreadsheet->disconnectWorksheets();
    } finally {
        unlink($temporaryFile);
    }

    $this->get(route('admin.payroll.slip.preview', [$period, $item]))
        ->assertOk()
        ->assertSee('FOLLOWME')
        ->assertSee('Gaji bersih diterima')
        ->assertSee('DISETUJUI - BELUM DIBAYARKAN')
        ->assertSee('Disetujui');

    $this->get(route('admin.payroll.slip', [$period, $item]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload($item->slip_number.'.pdf');
});
