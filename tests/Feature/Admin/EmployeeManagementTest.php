<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

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
    $inactiveEmployee = employeeManagementUser([
        'name' => 'Karyawan Nonaktif',
        'email' => 'nonaktif@example.com',
        'id_karyawan' => 'EMP-INACTIVE',
        'is_active' => false,
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.employees.index'))
        ->assertRedirect(route('admin.absensi.approval.hrd', ['section' => 'employees']));

    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee('Data Karyawan')
        ->assertSee($employee->name)
        ->assertSee($employee->email)
        ->assertSee('Aktif')
        ->assertDontSee($inactiveEmployee->name)
        ->assertSee('Buat akun karyawan');

    $this->get(route('admin.absensi.approval.hrd', [
        'section' => 'employees',
        'employee_status' => 'inactive',
    ]))
        ->assertOk()
        ->assertSee($inactiveEmployee->name)
        ->assertSee('Hapus permanen');
});

it('shows employee profile photos in the HRD employee list', function () {
    Storage::fake('public');
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser([
        'name' => 'Karyawan Dengan Foto',
        'profile_photo_path' => 'profile-photos/employee.jpg',
    ]);
    Storage::disk('public')->put($employee->profile_photo_path, 'test image');

    $this->actingAs($hrd)
        ->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee(route('admin.storage.file', ['path' => $employee->profile_photo_path]), false)
        ->assertSee('alt="Foto profil Karyawan Dengan Foto"', false);
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
    $employee->createToken('test-device');

    $this->actingAs($hrd)
        ->delete(route('admin.employees.deactivate', $employee))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($employee->fresh())->not->toBeNull()
        ->and($employee->fresh()->is_active)->toBeFalse()
        ->and($employee->fresh()->email)->toBe($employee->email)
        ->and($employee->tokens()->count())->toBe(0);

    $this->postJson('/api/v1/login', [
        'email' => $employee->email,
        'password' => 'password',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Akun Anda nonaktif. Silakan hubungi HRD.');

    Sanctum::actingAs($employee->fresh());
    $this->postJson('/api/v1/absensi/masuk')->assertForbidden();

    $this->actingAs($employee->fresh())
        ->get('/profile')
        ->assertRedirect(route('login'));
});

it('permanently deletes inactive employees and their attendance and payroll history', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser(['is_active' => false]);
    $employee->salaryRates()->create([
        'daily_rate' => 135000,
        'effective_at' => now()->subDay(),
        'recorded_by' => $hrd->id,
    ]);

    $attendanceId = DB::table('absensis')->insertGetId([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'check_in_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $periodId = DB::table('payroll_periods')->insertGetId([
        'year' => now()->year,
        'month' => now()->month,
        'period_number' => 1,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $payrollItemId = DB::table('payroll_items')->insertGetId([
        'payroll_period_id' => $periodId,
        'user_id' => $employee->id,
        'employee_name' => $employee->name,
        'employment_type' => 'organik',
        'daily_rate' => 150000,
        'gross_pay' => 150000,
        'net_pay' => 150000,
        'attendance_snapshot' => json_encode([]),
        'slip_number' => 'TEST-'.$employee->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('payroll_lateness_details')->insert([
        'payroll_item_id' => $payrollItemId,
        'attendance_id' => $attendanceId,
        'work_date' => now()->toDateString(),
        'late_minutes' => 15,
        'penalty_blocks' => 1,
        'deduction_amount' => 1000,
        'lateness_source' => 'test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $employee->createToken('test-device');

    $this->actingAs($hrd)
        ->delete(route('admin.employees.destroy', $employee))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(User::find($employee->id))->toBeNull()
        ->and(DB::table('absensis')->where('id', $attendanceId)->exists())->toBeFalse()
        ->and(DB::table('payroll_items')->where('id', $payrollItemId)->exists())->toBeFalse()
        ->and(DB::table('payroll_lateness_details')->where('payroll_item_id', $payrollItemId)->exists())->toBeFalse()
        ->and(DB::table('employee_salary_rates')->where('user_id', $employee->id)->exists())->toBeFalse()
        ->and(DB::table('personal_access_tokens')->where('tokenable_id', $employee->id)->exists())->toBeFalse();
});

it('requires an employee to be deactivated before permanent deletion', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser();

    $this->actingAs($hrd)
        ->delete(route('admin.employees.destroy', $employee))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($employee->fresh())->not->toBeNull()
        ->and($employee->fresh()->is_active)->toBeTrue();
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
