<?php

use App\Models\Absensi;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function createAttendanceDetailRecord(User $user, string $date, bool $withCheckOut = true): Absensi
{
    return Absensi::create([
        'user_id' => $user->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => "{$date} 08:00:00",
        'check_out_at' => $withCheckOut ? "{$date} 17:00:00" : null,
        'late_minutes' => 0,
        'rounded_late_minutes' => 0,
        'overtime_minutes' => 0,
        'base_salary' => 150000,
        'late_penalty' => 0,
        'final_salary' => 150000,
        'overtime_pay' => 0,
        'parent_id' => null,
        'is_mocked' => false,
    ]);
}

it('shows only freelance employees on the monthly attendance recap', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $freelance = User::factory()->create([
        'name' => 'Freelance Recap Test',
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    User::factory()->create([
        'name' => 'Organik Recap Test',
        'employment_type' => 'organik',
        'registration_status' => 'approved',
    ]);
    User::factory()->create([
        'name' => 'Borongan Recap Test',
        'id_karyawan' => 'CS-AMB-RECAP',
        'employment_type' => 'organik',
        'registration_status' => 'approved',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.recap'))
        ->assertOk()
        ->assertSee('Karyawan Freelance')
        ->assertSee('Freelance Recap Test')
        ->assertDontSee('Organik Recap Test')
        ->assertDontSee('Borongan Recap Test')
        ->assertDontSee('Karyawan Organik')
        ->assertDontSee('Karyawan Borongan')
        ->assertDontSee('Karyawan Magang');
});

it('applies a valid custom date range to attendance details and their summary', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);

    $before = createAttendanceDetailRecord($employee, '2026-10-09');
    $includedStart = createAttendanceDetailRecord($employee, '2026-10-10');
    $includedEnd = createAttendanceDetailRecord($employee, '2026-10-12');
    $after = createAttendanceDetailRecord($employee, '2026-10-13');

    $this->actingAs($hrd)
        ->get(route('admin.absensi.user', [
            'user' => $employee->id,
            'filter_type' => 'custom',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
        ]))
        ->assertOk()
        ->assertViewHas('absensi', fn ($absensi) => $absensi->modelKeys() === [$includedEnd->id, $includedStart->id])
        ->assertViewHas('absensiStats', fn ($stats) => $stats['total_absensi'] === 2);

    expect($before->exists)->toBeTrue()
        ->and($after->exists)->toBeTrue();
});

it('defaults a missing checkout edit to the attendance date', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    createAttendanceDetailRecord($employee, '2026-10-12', false);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.user', $employee))
        ->assertOk()
        ->assertSee('data-checkout-time="2026-10-12 17:00:00"', false);
});

it('rejects incomplete or reversed custom date ranges on attendance details', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.user', [
            'user' => $employee->id,
            'filter_type' => 'custom',
            'start_date' => '2026-10-10',
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors('end_date');

    $this->get(route('admin.absensi.user', [
        'user' => $employee->id,
        'filter_type' => 'custom',
        'end_date' => '2026-10-12',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors('start_date');

    $this->get(route('admin.absensi.user', [
        'user' => $employee->id,
        'filter_type' => 'custom',
        'start_date' => '2026-10-13',
        'end_date' => '2026-10-12',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors('end_date');
});

it('rejects malformed dates and unsupported detail filters', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.user', [
            'user' => $employee->id,
            'filter_type' => 'custom',
            'start_date' => 'not-a-date',
            'end_date' => '2026-10-12',
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors('start_date');

    $this->get(route('admin.absensi.user', [
        'user' => $employee->id,
        'filter_type' => 'unknown',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors('filter_type');
});

it('rejects edited check-in or check-out timestamps that reverse the attendance order', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    $attendance = createAttendanceDetailRecord($employee, '2026-10-12');

    $this->actingAs($hrd)
        ->post(route('admin.absensi.updateCheckIn', $attendance), [
            'new_check_in' => '2026-10-12T18:00',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('new_check_in');

    $this->post(route('admin.absensi.updateCheckOut', $attendance), [
        'new_check_out' => '2026-10-12T07:00',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('new_check_out');

    expect($attendance->fresh()->check_in_at->format('Y-m-d H:i'))->toBe('2026-10-12 08:00')
        ->and($attendance->fresh()->check_out_at->format('Y-m-d H:i'))->toBe('2026-10-12 17:00');
});

it('allows edited check-in and check-out timestamps that remain chronological', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    $attendance = createAttendanceDetailRecord($employee, '2026-10-12');

    $this->actingAs($hrd)
        ->post(route('admin.absensi.updateCheckIn', $attendance), [
            'new_check_in' => '2026-10-12T08:15',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->post(route('admin.absensi.updateCheckOut', $attendance), [
        'new_check_out' => '2026-10-12T17:30',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($attendance->fresh()->check_in_at->format('Y-m-d H:i'))->toBe('2026-10-12 08:15')
        ->and($attendance->fresh()->check_out_at->format('Y-m-d H:i'))->toBe('2026-10-12 17:30');
});

it('documents that admin checkout edits do not recalculate stored salary fields', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);
    $attendance = createAttendanceDetailRecord($employee, '2026-10-12');
    $storedSalary = $attendance->final_salary;

    $this->actingAs($hrd)
        ->post(route('admin.absensi.updateCheckOut', $attendance), [
            'new_check_out' => '2026-10-12T12:00',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedAttendance = $attendance->fresh();
    $salaryFromSharedCalculator = Absensi::calculateSalary(
        (int) $updatedAttendance->late_minutes,
        $updatedAttendance->status,
        $updatedAttendance->tipe,
        Absensi::isWeekend($updatedAttendance->check_in_at),
        $updatedAttendance->check_in_at,
        $updatedAttendance->check_out_at,
        $employee->kategori_absensi,
    );

    expect($updatedAttendance->check_out_at->format('Y-m-d H:i'))->toBe('2026-10-12 12:00')
        ->and($updatedAttendance->final_salary)->toBe($storedSalary)
        ->and($salaryFromSharedCalculator['final_salary'])->not->toBe((float) $storedSalary);
});

it('rejects incomplete custom date ranges on attendance and payroll exports', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
    ]);

    $this->actingAs($hrd);

    $exportRoutes = [
        route('admin.absensi.user.export', [
            'id' => $employee->id,
            'filter_type' => 'custom',
            'start_date' => '2026-10-10',
        ]),
        route('admin.absensi.user.export-slip', [
            'user' => $employee->id,
            'filter_type' => 'custom',
            'start_date' => '2026-10-10',
        ]),
        route('admin.absensi.user.export-slip-pdf', [
            'id' => $employee->id,
            'filter_type' => 'custom',
            'start_date' => '2026-10-10',
        ]),
    ];

    foreach ($exportRoutes as $route) {
        $response = $this->get($route);
        expect($response->status())->toBe(302, $route);
        $response->assertSessionHasErrors('end_date');
    }

    $bulkExportRoutes = [
        route('admin.absensi.bulk-export-detail'),
        route('admin.absensi.bulk-export-simple'),
        route('admin.absensi.bulk-export-pdf'),
    ];

    foreach ($bulkExportRoutes as $route) {
        $this->post($route, [
            'user_ids' => [$employee->id],
            'start_date' => '2026-10-12 00:00:00',
            'end_date' => '2026-10-10 23:59:59',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }
});

it('rejects incomplete or unsupported filters on attendance recap exports', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.recap.export', [
            'range' => 'custom',
            'start_date' => '2026-10-10',
        ]))
        ->assertRedirect()
        ->assertSessionHasErrors('end_date');

    $this->get(route('admin.absensi.recap.export', [
        'type' => 'unrecognized',
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors('type');

    $this->get(route('admin.absensi.recap.export', [
        'range' => 'weekly',
        'week' => 0,
    ]))
        ->assertRedirect()
        ->assertSessionHasErrors('week');
});

it('excludes pending attendance from the recap export total like the recap page', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'registration_status' => 'approved',
        'id_karyawan' => 'AMB-RECAP-01',
    ]);

    createAttendanceDetailRecord($employee, '2026-10-10');
    $rejected = createAttendanceDetailRecord($employee, '2026-10-11');
    $rejected->update(['status_approval' => 'rejected']);
    $pending = createAttendanceDetailRecord($employee, '2026-10-12');
    $pending->update(['status_approval' => 'pending']);

    $response = $this->actingAs($hrd)->get(route('admin.absensi.recap.export', [
        'month' => 10,
        'year' => 2026,
        'type' => 'freelance',
        'range' => 'monthly',
    ]));

    $response->assertOk();
    $exportPath = $response->baseResponse->getFile()->getPathname();
    $worksheet = IOFactory::load($exportPath)->getActiveSheet();

    expect((int) $worksheet->getCell('R4')->getValue())->toBe(2);
});
