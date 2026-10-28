<?php

use App\Exports\AbsensiRekapExport;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;

test('organic salary calculation preserves the auto-checkout adjustment without static instance access', function () {
    $checkIn = Carbon::parse('2026-10-18 08:00:00');
    $checkOut = Carbon::parse('2026-10-18 17:00:00');

    $salary = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        true,
        $checkIn,
        $checkOut,
        'organik',
    );

    $autoCheckout = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        true,
        $checkIn,
        $checkOut,
        'organik',
        true,
    );

    expect($salary['worked_minutes'])->toBe(480)
        ->and($salary['base_salary'])->toBe(300000.0)
        ->and($autoCheckout['worked_minutes'])->toBe(0)
        ->and($autoCheckout['base_salary'])->toBe(0.0);
});

it('pays freelance employees the fixed daily rate and deducts completed 30-minute blocks', function () {
    $checkIn = Carbon::parse('2026-10-11 08:00:00');
    $checkOut = Carbon::parse('2026-10-11 12:00:00');

    $onTime = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        false,
        $checkIn,
        $checkOut,
        'freelance',
    );

    expect($onTime['base_salary'])->toBe(120000.0)
        ->and($onTime['late_penalty'])->toBe(0.0)
        ->and($onTime['final_salary'])->toBe(120000.0)
        ->and($onTime['worked_minutes'])->toBe(240);

    foreach ([
        29 => [0, 0.0],
        30 => [30, 7500.0],
        59 => [30, 7500.0],
        60 => [60, 15000.0],
        90 => [90, 22500.0],
    ] as $lateMinutes => [$chargedMinutes, $deduction]) {
        $salary = Absensi::calculateSalary(
            $lateMinutes,
            'hadir',
            null,
            false,
            $checkIn,
            $checkOut,
            'freelance',
        );

        expect($salary['base_salary'])->toBe(120000.0)
            ->and($salary['rounded_late_minutes'])->toBe($chargedMinutes)
            ->and($salary['late_penalty'])->toBe($deduction)
            ->and($salary['final_salary'])->toBe(120000.0 - $deduction);
    }
});

test('recap export includes the payroll totals and overtime values from the prepared row', function () {
    $row = [
        'user' => new User([
            'name' => 'Test Employee',
            'id_karyawan' => 'TEST-000001',
            'departemen' => 'QA',
        ]),
        'kategori' => 'organik',
        'total_hadir' => 1,
        'total_izin' => 0,
        'total_sakit' => 0,
        'total_lembur' => 1,
        'total_lembur_pending' => 0,
        'total_lembur_ditolak' => 0,
        'total_kurang_8_jam' => 0,
        'total_telat' => 0,
        'total_gaji_pokok' => 150000,
        'total_gaji_lembur' => 5000,
        'total_potongan' => 0,
        'keterangan_adj' => '',
        'total_gaji' => 155000,
        'total_absensi' => 1,
    ];

    $export = new AbsensiRekapExport([$row], 10, 2026);
    $exportedRow = $export->collection()->first();

    expect($exportedRow['gaji_pokok'])->toBe('Rp 150.000')
        ->and($exportedRow['gaji_lembur'])->toBe('Rp 5.000')
        ->and($exportedRow['total_diterima'])->toBe('Rp 155.000');
});

test('overtime below thirty minutes is excluded and exactly thirty minutes remains eligible', function () {
    $start = Carbon::parse('2026-10-18 17:00:00');

    $belowThreshold = Absensi::calculateOvertimeFromInput(
        $start,
        $start->copy()->addMinutes(29),
        false,
        true,
    );
    $atThreshold = Absensi::calculateOvertimeFromInput(
        $start,
        $start->copy()->addMinutes(30),
        false,
        true,
    );

    expect($belowThreshold['minutes'])->toBe(0)
        ->and($belowThreshold['pay'])->toBe(0.0)
        ->and($atThreshold['minutes'])->toBe(30)
        ->and($atThreshold['pay'])->toBeGreaterThan(0);
});
