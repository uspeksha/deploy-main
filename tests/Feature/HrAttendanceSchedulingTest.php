<?php

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\EmployeeSchedule;
use App\Models\PtkpStatus;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('relates shifts to attendance and schedules bidirectionally', function () {
    $shift = Shift::factory()->create();
    $user = User::factory()->create();
    $absensi = Absensi::factory()->create([
        'user_id' => $user->id,
        'shift_id' => $shift->id,
        'check_in_lat' => -6.2000000,
        'check_in_lng' => 106.8166667,
    ]);
    $schedule = EmployeeSchedule::factory()->create([
        'user_id' => $user->id,
        'shift_id' => $shift->id,
        'work_date' => '2026-11-09',
    ]);
    $approver = User::factory()->create();
    $correction = AttendanceCorrection::factory()->create([
        'user_id' => $user->id,
        'absensi_id' => $absensi->id,
        'approved_by' => $approver->id,
        'requested_check_in' => '2026-11-09 09:00:00',
        'requested_check_out' => null,
        'status' => 'pending',
    ]);

    expect($absensi->shift->is($shift))->toBeTrue()
        ->and($shift->absensis->contains($absensi))->toBeTrue()
        ->and($schedule->user->is($user))->toBeTrue()
        ->and($schedule->shift->is($shift))->toBeTrue()
        ->and($user->schedules->contains($schedule))->toBeTrue()
        ->and($shift->schedules->contains($schedule))->toBeTrue()
        ->and($correction->user->is($user))->toBeTrue()
        ->and($correction->absensi->is($absensi))->toBeTrue()
        ->and($absensi->corrections->contains($correction))->toBeTrue()
        ->and($correction->approver->is($approver))->toBeTrue()
        ->and($user->attendanceCorrections->contains($correction))->toBeTrue()
        ->and($absensi->check_in_lat)->toBe('-6.2000000')
        ->and($absensi->check_in_lng)->toBe('106.8166667');
});

it('enforces one schedule per user and work date', function () {
    $user = User::factory()->create();
    $firstShift = Shift::factory()->create();
    $secondShift = Shift::factory()->create();
    EmployeeSchedule::factory()->create([
        'user_id' => $user->id,
        'shift_id' => $firstShift->id,
        'work_date' => '2026-11-09',
    ]);

    expect(fn () => EmployeeSchedule::factory()->create([
        'user_id' => $user->id,
        'shift_id' => $secondShift->id,
        'work_date' => '2026-11-09',
    ]))->toThrow(QueryException::class);
});

it('returns the latest PTKP status effective on a requested date', function () {
    $current = PtkpStatus::factory()->create([
        'code' => 'TK/0',
        'annual_amount' => 54000000,
        'effective_at' => '2016-01-01',
    ]);
    $future = PtkpStatus::factory()->create([
        'code' => 'TK/0',
        'annual_amount' => 60000000,
        'effective_at' => '2027-01-01',
    ]);

    expect(PtkpStatus::effectiveOn('2026-12-31')->where('code', 'TK/0')->first()?->is($current))->toBeTrue()
        ->and(PtkpStatus::effectiveOn('2027-01-01')->where('code', 'TK/0')->first()?->is($future))->toBeTrue();
});

it('seeds example shifts idempotently and keeps profile encrypted fields as text', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    expect(Shift::query()->count())->toBe(2)
        ->and(Shift::query()->where('name', 'Malam')->value('is_overnight'))->toBeTrue()
        ->and(Schema::getColumnType('employee_profiles', 'nik_ktp'))->toBe('text')
        ->and(Schema::getColumnType('employee_profiles', 'npwp'))->toBe('text')
        ->and(Schema::getColumnType('employee_profiles', 'bpjs_kesehatan_no'))->toBe('text')
        ->and(Schema::getColumnType('employee_profiles', 'bpjs_ketenagakerjaan_no'))->toBe('text')
        ->and(Schema::getColumnType('employee_profiles', 'bank_account_no'))->toBe('text');
});

it('preserves shift values edited after reference seeding', function () {
    $this->seed(ReferenceSeeder::class);
    Shift::query()->where('name', 'Reguler')->update([
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'late_tolerance_min' => 10,
    ]);

    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    $shift = Shift::query()->where('name', 'Reguler')->firstOrFail();

    expect($shift->start_time)->toBe('08:00:00')
        ->and($shift->end_time)->toBe('17:00:00')
        ->and($shift->late_tolerance_min)->toBe(10)
        ->and(Shift::query()->where('name', 'Reguler')->count())->toBe(1);
});
