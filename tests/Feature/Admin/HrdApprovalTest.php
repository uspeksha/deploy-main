<?php

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

it('shows only freelance attendance on the dashboard and attendance overview', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->get('/dashboard')
        ->assertRedirect(route('admin.absensi.index'));

    $this->get(route('admin.absensi.index'))
        ->assertOk()
        ->assertSee('Dashboard Absensi Karyawan Freelance')
        ->assertSee('Status Karyawan Freelance Hari Ini')
        ->assertDontSee('id="tabel-organik"')
        ->assertDontSee('id="tabel-borongan"')
        ->assertDontSee('id="tabel-magang"')
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
