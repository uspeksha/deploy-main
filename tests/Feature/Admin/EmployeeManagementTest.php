<?php

use App\Models\User;

function employeeManagementUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'registration_status' => 'approved',
        'is_active' => true,
        'phone' => '081234567890',
        'address' => 'Jl. Merdeka No. 1',
        'employment_type' => 'organik',
        'work_location' => 'office',
        'nik' => '1234567890123456',
        'bank_name' => 'BCA',
        'bank_account_number' => '1234567890',
        'bank_account_name' => 'Nama Karyawan',
    ], $overrides));
}

it('shows employee data in the HRD page data employees section', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser();

    $this->actingAs($hrd)
        ->get(route('admin.employees.index'))
        ->assertRedirect(route('admin.absensi.approval.hrd', ['section' => 'employees']));

    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee('Data Karyawan')
        ->assertSee($employee->name)
        ->assertSee($employee->email)
        ->assertSee('Aktif')
        ->assertSee('Buat akun karyawan');
});

it('lets HRD edit employee details and reactivate a deactivated employee', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser(['is_active' => false]);

    $this->actingAs($hrd)
        ->get(route('admin.employees.edit', $employee))
        ->assertOk()
        ->assertSee($employee->email);

    $this->put(route('admin.employees.update', $employee), [
        'name' => 'Nama Diperbarui',
        'email' => $employee->email,
        'id_karyawan' => $employee->id_karyawan,
        'phone' => '081111111111',
        'address' => 'Alamat baru',
        'departemen' => 'Operasional',
        'employment_type' => 'organik',
        'work_location' => 'produksi',
        'nik' => '1234567890123456',
        'bank_name' => 'BRI',
        'bank_account_number' => '999888777',
        'bank_account_name' => 'Nama Diperbarui',
    ])->assertRedirect(route('admin.employees.index'));

    expect($employee->fresh()->name)->toBe('Nama Diperbarui')
        ->and($employee->fresh()->phone)->toBe('081111111111')
        ->and($employee->fresh()->bank_name)->toBe('BRI')
        ->and($employee->fresh()->is_active)->toBeFalse();

    $this->patch(route('admin.employees.activate', $employee))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($employee->fresh()->is_active)->toBeTrue();
});

it('deactivates without deleting employee data or attendance history', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser();

    $this->actingAs($hrd)
        ->delete(route('admin.employees.deactivate', $employee))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($employee->fresh())->not->toBeNull()
        ->and($employee->fresh()->is_active)->toBeFalse()
        ->and($employee->fresh()->email)->toBe($employee->email);
});

it('records freelance rate changes from the employee edit form', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser([
        'employment_type' => 'freelance',
        'id_karyawan' => 'FL-EDIT-01',
    ]);
    $employee->salaryRates()->create([
        'daily_rate' => 135000,
        'effective_at' => now()->subDay(),
        'recorded_by' => $hrd->id,
    ]);

    $effectiveAt = now()->addDay()->format('Y-m-d\TH:i');
    $this->actingAs($hrd)
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'id_karyawan' => $employee->id_karyawan,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'departemen' => 'Operasional',
            'employment_type' => 'freelance',
            'work_location' => 'office',
            'nik' => $employee->nik,
            'bank_name' => 'BCA',
            'bank_account_number' => $employee->bank_account_number,
            'bank_account_name' => $employee->bank_account_name,
            'daily_rate' => 150000,
            'effective_at' => $effectiveAt,
        ])
        ->assertRedirect(route('admin.employees.index'));

    expect($employee->salaryRates()->count())->toBe(2)
        ->and($employee->dailyRateAt($effectiveAt))->toBe(150000);
});

it('restricts employee management to HRD and rejects unsupported bank names', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = employeeManagementUser();

    $this->actingAs($admin)
        ->get(route('admin.employees.index'))
        ->assertForbidden();

    $hrd = User::factory()->create(['role' => 'hrd']);
    $this->actingAs($hrd)
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'id_karyawan' => $employee->id_karyawan,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'departemen' => $employee->departemen,
            'employment_type' => 'organik',
            'work_location' => 'office',
            'nik' => $employee->nik,
            'bank_name' => 'Mandiri',
            'bank_account_number' => $employee->bank_account_number,
            'bank_account_name' => $employee->bank_account_name,
        ])
        ->assertSessionHasErrors('bank_name')
        ->assertSessionHas('_old_input', fn (array $input) => ! array_key_exists('nik', $input)
            && ! array_key_exists('phone', $input)
            && ! array_key_exists('address', $input)
            && ! array_key_exists('bank_name', $input)
            && ! array_key_exists('bank_account_number', $input)
            && ! array_key_exists('bank_account_name', $input)
        );
});
