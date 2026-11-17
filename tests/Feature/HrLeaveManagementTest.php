<?php

use App\Models\Absensi;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceLog;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relates leave types, balances, logs, users, and attendance bidirectionally', function () {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create();
    $balance = LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'quota' => 12,
        'used' => 3.5,
    ]);
    $absensi = Absensi::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
    ]);
    $log = LeaveBalanceLog::factory()->create([
        'leave_balance_id' => $balance->id,
        'absensi_id' => $absensi->id,
        'amount' => -1,
    ]);

    expect($user->leaveBalances->first()->is($balance))->toBeTrue()
        ->and($leaveType->balances->first()->is($balance))->toBeTrue()
        ->and($balance->user->is($user))->toBeTrue()
        ->and($balance->leaveType->is($leaveType))->toBeTrue()
        ->and($balance->logs->first()->is($log))->toBeTrue()
        ->and($log->balance->is($balance))->toBeTrue()
        ->and($log->absensi->is($absensi))->toBeTrue()
        ->and($absensi->leaveType->is($leaveType))->toBeTrue()
        ->and($leaveType->absensis->first()->is($absensi))->toBeTrue()
        ->and($balance->remaining())->toBe(8.5);
});

it('enforces one leave balance per user, type, and year', function () {
    $balance = LeaveBalance::factory()->create();

    expect(fn () => LeaveBalance::factory()->create([
        'user_id' => $balance->user_id,
        'leave_type_id' => $balance->leave_type_id,
        'year' => $balance->year,
    ]))->toThrow(QueryException::class);
});

it('keeps leave balance logs append-only through the model', function () {
    $log = LeaveBalanceLog::factory()->create();
    $log->reason = 'Changed reason';

    expect(fn () => $log->save())->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});

it('seeds only configured leave types idempotently', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    expect(LeaveType::query()->count())->toBe(11)
        ->and(LeaveType::query()->where('name', 'cuti_tahunan')->value('default_quota'))->toBe(12)
        ->and(LeaveType::query()->where('name', 'unpaid_leave')->value('is_paid'))->toBeFalse()
        ->and(LeaveType::query()->where('name', 'cuti_melahirkan')->value('need_attachment'))->toBeTrue()
        ->and(LeaveType::query()->where('name', 'izin_pulang_cepat')->exists())->toBeFalse();
});

it('preserves leave type values edited after reference seeding', function () {
    $this->seed(ReferenceSeeder::class);
    LeaveType::query()->where('name', 'cuti_tahunan')->update([
        'default_quota' => 17,
        'is_paid' => false,
        'need_attachment' => false,
    ]);

    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    $leaveType = LeaveType::query()->where('name', 'cuti_tahunan')->firstOrFail();

    expect($leaveType->default_quota)->toBe(17)
        ->and($leaveType->is_paid)->toBeFalse()
        ->and($leaveType->need_attachment)->toBeFalse()
        ->and(LeaveType::query()->where('name', 'cuti_tahunan')->count())->toBe(1);
});

it('does not write leave balances in dry-run and creates matching-year balances idempotently', function () {
    $user = User::factory()->create([
        'sisa_cuti' => 5,
        'total_cuti_diambil' => 7,
        'tahun_cuti' => 2025,
    ]);
    $mismatchedUser = User::factory()->create([
        'sisa_cuti' => 12,
        'total_cuti_diambil' => 0,
        'tahun_cuti' => 2024,
    ]);
    $leaveType = LeaveType::factory()->create(['name' => 'cuti_tahunan']);

    $this->artisan('hr:backfill-leave-balances', [
        '--year' => 2025,
        '--leave-type' => $leaveType->name,
        '--dry-run' => true,
    ])->expectsOutputToContain('Would create user_id='.$user->id.', quota=12, used=7')
        ->assertExitCode(0);

    expect(LeaveBalance::query()->count())->toBe(0);

    $this->artisan('hr:backfill-leave-balances', [
        '--year' => 2025,
        '--leave-type' => $leaveType->name,
    ])->assertExitCode(0);
    $this->artisan('hr:backfill-leave-balances', [
        '--year' => 2025,
        '--leave-type' => $leaveType->name,
    ])->assertExitCode(0);

    $balance = LeaveBalance::query()->where('user_id', $user->id)->first();

    expect($balance)->not->toBeNull()
        ->and((float) $balance->quota)->toBe(12.0)
        ->and((float) $balance->used)->toBe(7.0)
        ->and($balance->remaining())->toBe(5.0)
        ->and(LeaveBalance::query()->where('user_id', $mismatchedUser->id)->exists())->toBeFalse()
        ->and(LeaveBalance::query()->count())->toBe(1);
});

it('requires explicit year and leave type for the manual backfill', function () {
    $this->artisan('hr:backfill-leave-balances')
        ->expectsOutputToContain('The year field is required.')
        ->assertExitCode(2);

    expect(LeaveBalance::query()->count())->toBe(0);
});
