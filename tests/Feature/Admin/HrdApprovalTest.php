<?php

use App\Models\User;

it('shows one HRD approval page to HRGA and redirects legacy approval URLs', function () {
    $hrd = User::factory()->create(['role' => 'hrga']);

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

it('does not allow non-HRD admin roles to access the HRD approval page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.absensi.approval.hrd'))
        ->assertForbidden();
});
