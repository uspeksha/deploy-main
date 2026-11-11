<?php

use App\Models\Absensi;
use App\Models\Holiday;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

test('employee and HRD are notified for every Alfa from the third in a calendar month', function () {
    $this->travelTo(Carbon::parse('2026-11-09 23:59:00', 'Asia/Jakarta'));

    $employee = User::factory()->create([
        'name' => 'Test Employee',
        'registration_status' => 'approved',
        'is_active' => true,
        'role' => 'karyawan',
    ]);
    $hrd = User::factory()->create([
        'registration_status' => 'approved',
        'is_active' => true,
        'role' => 'hrd',
    ]);

    Absensi::factory()->create([
        'user_id' => $employee->id,
        'check_in_at' => '2026-11-02 08:00:00',
    ]);
    Absensi::factory()->create([
        'user_id' => $employee->id,
        'status' => 'izin',
        'check_in_at' => '2026-11-04 08:00:00',
    ]);
    Holiday::create([
        'holiday_date' => '2026-11-05',
        'name' => 'Test holiday',
        'is_national' => true,
    ]);

    $this->artisan('attendance:notify-alfa-calls')->assertSuccessful();

    $hrdIds = User::query()
        ->where('role', 'hrd')
        ->where('registration_status', 'approved')
        ->where('is_active', true)
        ->pluck('id');

    expect(Notification::query()->where('type', 'alfa_hrd_call_20261109')->where('target_id', $employee->id)->count())->toBe($hrdIds->count() + 1)
        ->and(Notification::query()->where('user_id', $employee->id)->where('target_id', $employee->id)->count())->toBe(1)
        ->and(Notification::query()->whereIn('user_id', $hrdIds)->where('target_id', $employee->id)->count())->toBe($hrdIds->count());

    $this->travelTo(Carbon::parse('2026-11-10 23:59:00', 'Asia/Jakarta'));
    $this->artisan('attendance:notify-alfa-calls')->assertSuccessful();

    expect(Notification::query()->where('type', 'alfa_hrd_call_20261110')->where('target_id', $employee->id)->count())->toBe($hrdIds->count() + 1);

    $this->artisan('attendance:notify-alfa-calls')->assertSuccessful();

    expect(Notification::query()->where('type', 'alfa_hrd_call_20261109')->where('target_id', $employee->id)->count())->toBe($hrdIds->count() + 1)
        ->and(Notification::query()->where('type', 'alfa_hrd_call_20261110')->where('target_id', $employee->id)->count())->toBe($hrdIds->count() + 1);
});

test('a pending sickness request does not count as a confirmed attendance exception', function () {
    $this->travelTo(Carbon::parse('2026-11-04 23:59:00', 'Asia/Jakarta'));

    $employee = User::factory()->create([
        'registration_status' => 'approved',
        'is_active' => true,
        'role' => 'karyawan',
    ]);

    Absensi::factory()->create([
        'user_id' => $employee->id,
        'status' => 'sakit',
        'status_approval' => 'pending',
        'check_in_at' => '2026-11-02 08:00:00',
    ]);

    $this->artisan('attendance:notify-alfa-calls')->assertSuccessful();

    $hrdCount = User::query()
        ->where('role', 'hrd')
        ->where('registration_status', 'approved')
        ->where('is_active', true)
        ->count();

    expect(Notification::query()->where('type', 'alfa_hrd_call_20261104')->where('target_id', $employee->id)->count())
        ->toBe($hrdCount + 1);
});
