<?php

use App\Models\Absensi;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;

test('KPI endpoints authenticate against the configured API key', function () {
    config(['services.kpi.api_key' => 'test-kpi-key']);

    $this->getJson('/api/v1/kpi-active-employees')
        ->assertUnauthorized();

    $this->withHeader('X-API-KEY', 'test-kpi-key')
        ->getJson('/api/v1/kpi-active-employees')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

test('attendance summary counts lowercase statuses and missing workdays by calendar month', function () {
    config(['services.kpi.api_key' => 'test-kpi-key']);
    $this->travelTo(Carbon::parse('2026-11-09 12:00:00', 'Asia/Jakarta'));
    $user = User::factory()->create();
    $activeEmployeeWithoutAttendance = User::factory()->create([
        'registration_status' => 'approved',
        'is_active' => true,
    ]);

    foreach ([
        ['status' => 'hadir', 'status_approval' => 'approved', 'late_minutes' => 15, 'check_in_at' => '2026-11-02 08:15:00'],
        ['status' => 'hadir', 'status_approval' => 'approved', 'late_minutes' => 0, 'check_in_at' => '2026-11-09 08:00:00'],
        ['status' => 'izin', 'status_approval' => 'approved', 'late_minutes' => 0, 'check_in_at' => '2026-11-03 08:00:00'],
        ['status' => 'sakit', 'status_approval' => 'approved', 'late_minutes' => 0, 'check_in_at' => '2026-11-04 08:00:00'],
        ['status' => 'hadir', 'status_approval' => 'approved', 'late_minutes' => 0, 'check_in_at' => '2026-10-30 08:00:00'],
    ] as $attendance) {
        Absensi::create([
            'user_id' => $user->id,
            ...$attendance,
        ]);
    }

    Holiday::create([
        'holiday_date' => '2026-11-05',
        'name' => 'Test holiday',
        'is_national' => true,
    ]);

    $this->withHeader('X-API-KEY', 'test-kpi-key')
        ->getJson('/api/v1/kpi-attendance-summary?bulan=11&tahun=2026')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.0.user_id', $user->id)
        ->assertJsonPath('data.0.total_hadir', 2)
        ->assertJsonPath('data.0.total_telat', 1)
        ->assertJsonPath('data.0.total_izin', 1)
        ->assertJsonPath('data.0.total_sakit', 1)
        ->assertJsonPath('data.0.total_alfa', 1)
        ->assertJsonFragment([
            'user_id' => $activeEmployeeWithoutAttendance->id,
            'total_hadir' => 0,
            'total_telat' => 0,
            'total_izin' => 0,
            'total_sakit' => 0,
            'total_alfa' => 5,
        ]);
});
