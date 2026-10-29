<?php

use App\Models\Absensi;
use App\Models\User;

it('shows the HRD approval page only to HRD and redirects legacy approval URLs', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.approval.hrd'))
        ->assertOk()
        ->assertSee('Approval HRD')
        ->assertDontSee('Supervisor (Freelance)')
        ->assertDontSee('Manager (Freelance/Org)')
        ->assertDontSee('HRGA (Final)');

    $this->get('/admin/approval/supervisor')->assertRedirect('/admin/approval/hrd');
    $this->get('/admin/approval/manager')->assertRedirect('/admin/approval/hrd');
    $this->get('/admin/approval/hrga')->assertRedirect('/admin/approval/hrd');
});

it('does not allow other roles to access the HRD approval page', function () {
    foreach (['admin', 'super_admin', 'manager', 'supervisor', 'hrga', 'pkl', 'user'] as $role) {
        $account = User::factory()->create(['role' => $role]);

        $this->actingAs($account)
            ->get(route('admin.absensi.approval.hrd'))
            ->assertForbidden();
    }
});

it('shows pending late-excuse and leave requests in the HRD approval queue', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'name' => 'Karyawan Pengajuan',
        'registration_status' => 'approved',
        'is_active' => true,
    ]);

    $attendance = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'tipe' => null,
        'check_in_at' => '2026-10-28 09:00:00',
        'status_approval' => 'approved',
        'late_minutes' => 60,
        'base_salary' => 120000,
        'late_penalty' => 15000,
        'final_salary' => 105000,
        'is_mocked' => false,
    ]);
    Absensi::create([
        'user_id' => $employee->id,
        'parent_id' => $attendance->id,
        'status' => 'hadir',
        'tipe' => 'telat',
        'check_in_at' => $attendance->check_in_at,
        'status_approval' => 'pending',
        'late_minutes' => 60,
        'keterangan_izin_sakit' => 'Kendala transportasi',
        'file_bukti' => 'bukti_telat/bukti.png',
    ]);
    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'check_in_at' => '2026-10-29 00:00:00',
        'end_date' => '2026-10-30',
        'total_days' => 2,
        'status_approval' => 'pending',
        'keterangan_izin_sakit' => 'Keperluan keluarga',
        'file_bukti' => 'bukti_sakit_izin/surat.png',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.approval.hrd'))
        ->assertOk()
        ->assertSee('Karyawan Pengajuan')
        ->assertSee('telat')
        ->assertSee('Kendala transportasi')
        ->assertSee('cuti tahunan')
        ->assertSee('Durasi izin/cuti')
        ->assertSee('2 hari')
        ->assertSee('Setujui')
        ->assertSee('Tolak');
});

it('lets HRD approve leave and reject a late-excuse request', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create([
        'registration_status' => 'approved',
        'is_active' => true,
        'sisa_cuti' => 12,
        'total_cuti_diambil' => 0,
    ]);
    $leave = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'check_in_at' => '2026-10-29 00:00:00',
        'end_date' => '2026-10-29',
        'total_days' => 1,
        'hari_potong_cuti' => 1,
        'status_approval' => 'pending',
        'keterangan_izin_sakit' => 'Keperluan keluarga',
    ]);
    $attendance = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'check_in_at' => '2026-10-28 09:00:00',
        'status_approval' => 'approved',
        'late_minutes' => 60,
        'base_salary' => 120000,
        'late_penalty' => 15000,
        'final_salary' => 105000,
    ]);
    $lateExcuse = Absensi::create([
        'user_id' => $employee->id,
        'parent_id' => $attendance->id,
        'status' => 'hadir',
        'tipe' => 'telat',
        'check_in_at' => $attendance->check_in_at,
        'status_approval' => 'pending',
        'late_minutes' => 60,
    ]);

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.action', [
            'absensi' => $leave->id,
            'action' => 'approve',
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->post(route('admin.absensi.approval.action', [
        'absensi' => $lateExcuse->id,
        'action' => 'reject',
    ]), ['catatan_admin' => 'Bukti tidak memenuhi ketentuan.'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($leave->fresh()->status_approval)->toBe('approved')
        ->and($employee->fresh()->sisa_cuti)->toBe(11)
        ->and($employee->fresh()->total_cuti_diambil)->toBe(1)
        ->and($lateExcuse->fresh()->status_approval)->toBe('rejected')
        ->and($lateExcuse->fresh()->catatan_admin)->toBe('Bukti tidak memenuhi ketentuan.');
});

it('shows only freelance attendance on the dashboard and attendance overview', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->get('/dashboard')
        ->assertRedirect(route('admin.absensi.index'));

    $this->get(route('admin.absensi.index'))
        ->assertOk()
        ->assertSee(route('admin.absensi.index', absolute: false), false)
        ->assertDontSee('href="' . route('admin.absensi.freelance', absolute: false) . '"', false)
        ->assertSee('Dashboard Absensi Karyawan Freelance')
        ->assertSee('Status Absen Karyawan Hari Ini')
        ->assertDontSee('Status Karyawan Freelance Hari Ini')
        ->assertDontSee('id="tabel-organik"')
        ->assertDontSee('id="tabel-borongan"')
        ->assertDontSee('id="tabel-magang"')
        ->assertDontSee('id="tabel-freelance"')
        ->assertDontSee('Semua Status');
});

it('rejects non-HRD roles from web login', function () {
    foreach (['admin', 'super_admin', 'manager', 'supervisor', 'hrga', 'pkl', 'user'] as $role) {
        $account = User::factory()->create(['role' => $role]);

        $this->post('/login', [
            'email' => $account->email,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertGuest();
    }
});
