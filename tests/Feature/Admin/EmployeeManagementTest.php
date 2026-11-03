<?php

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
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

    $this->get(route('admin.employees.edit', [
        'user' => $employee,
        'employee_status' => 'active',
        'employee_search' => $employee->name,
        'employee_page' => 2,
    ]))
        ->assertOk()
        ->assertSee(route('admin.absensi.approval.hrd', [
            'section' => 'employees',
            'employee_status' => 'active',
            'employee_search' => $employee->name,
            'employee_page' => 2,
        ]));

    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee('Data Karyawan')
        ->assertSee($employee->name)
        ->assertSee($employee->email)
        ->assertSee('Aktif')
        ->assertSee($inactiveEmployee->name)
        ->assertSee('Nonaktif')
        ->assertSee('Hapus permanen')
        ->assertSee('Nonaktifkan dulu untuk menghapus')
        ->assertSee('Buat akun karyawan');

    $this->get(route('admin.absensi.approval.hrd', [
        'section' => 'employees',
        'employee_status' => 'inactive',
    ]))
        ->assertOk()
        ->assertSee($inactiveEmployee->name)
        ->assertSee('Hapus permanen');

    $this->get(route('admin.absensi.approval.hrd', [
        'section' => 'employees',
        'employee_status' => 'active',
    ]))
        ->assertOk()
        ->assertSee($employee->name)
        ->assertDontSee($inactiveEmployee->name);
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
        ->get(route('admin.employees.edit', [
            'user' => $employee,
            'employee_status' => 'inactive',
            'employee_search' => $employee->name,
            'employee_page' => 2,
        ]))
        ->assertOk()
        ->assertSee($employee->email)
        ->assertSee(route('admin.employees.update', [
            'user' => $employee,
            'section' => 'employees',
            'employee_status' => 'inactive',
            'employee_search' => $employee->name,
            'employee_page' => 2,
        ]))
        ->assertDontSee('name="profile_photo"', false);

    $this->put(route('admin.employees.update', [
        'user' => $employee,
        'section' => 'employees',
        'employee_status' => 'inactive',
        'employee_search' => $employee->name,
        'employee_page' => 2,
    ]), [
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
    ])->assertRedirect(route('admin.absensi.approval.hrd', [
        'section' => 'employees',
        'employee_status' => 'inactive',
        'employee_search' => $employee->name,
        'employee_page' => 2,
    ]));

    expect($employee->fresh()->name)->toBe('Nama Diperbarui')
        ->and($employee->fresh()->phone)->toBe('081111111111')
        ->and($employee->fresh()->bank_name)->toBe('BRI')
        ->and($employee->fresh()->is_active)->toBeFalse();

    $this->patch(route('admin.employees.activate', [
        'user' => $employee,
        'section' => 'employees',
        'employee_status' => 'inactive',
        'employee_search' => 'Nama Diperbarui',
        'employee_page' => 2,
    ]))
        ->assertRedirect(route('admin.absensi.approval.hrd', [
            'section' => 'employees',
            'employee_status' => 'inactive',
            'employee_search' => 'Nama Diperbarui',
            'employee_page' => 2,
        ]))
        ->assertSessionHas('success');

    expect($employee->fresh()->is_active)->toBeTrue();
});

it('deactivates without deleting employee data or attendance history', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = employeeManagementUser();
    $token = $employee->createToken('test-device');

    $this->actingAs($hrd)
        ->delete(route('admin.employees.deactivate', [
            'user' => $employee,
            'section' => 'employees',
            'employee_status' => 'all',
            'employee_search' => $employee->name,
            'employee_page' => 3,
        ]))
        ->assertRedirect(route('admin.absensi.approval.hrd', [
            'section' => 'employees',
            'employee_status' => 'all',
            'employee_search' => $employee->name,
            'employee_page' => 3,
        ]))
        ->assertSessionHas('success');

    expect($employee->fresh())->not->toBeNull()
        ->and($employee->fresh()->is_active)->toBeFalse()
        ->and($employee->fresh()->email)->toBe($employee->email)
        ->and($employee->tokens()->count())->toBe(1);

    $this->postJson('/api/v1/login', [
        'email' => $employee->email,
        'password' => 'password',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Maaf, akun Anda sudah dinonaktifkan dan tidak dapat melakukan absensi. Silakan hubungi HRD jika merasa ini keliru.');

    Holiday::create([
        'holiday_date' => today()->toDateString(),
        'name' => 'Hari Libur Uji',
        'is_national' => true,
    ]);

    $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/absensi/masuk')
        ->assertForbidden()
        ->assertJsonPath('message', 'Maaf, akun Anda sudah dinonaktifkan dan tidak dapat melakukan absensi. Silakan hubungi HRD jika merasa ini keliru.');

    expect(DB::table('absensis')->where('user_id', $employee->id)->exists())->toBeFalse();

    $this->actingAs($employee->fresh())
        ->get('/profile')
        ->assertRedirect(route('login'));

    $this->actingAs($hrd)
        ->patch(route('admin.employees.activate', [
            'user' => $employee,
            'section' => 'employees',
        ]))
        ->assertRedirect(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertSessionHas('success');

    expect($employee->fresh()->is_active)->toBeTrue()
        ->and($employee->tokens()->count())->toBe(0);

    Auth::forgetGuards();
    $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/user')
        ->assertUnauthorized();
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

it('shows the employee selected profile photo in the HRD list', function () {
    Storage::fake('public');
    $hrd = User::factory()->create(['role' => 'hrd']);
    $oldPhotoPath = 'profile-photos/old-photo.jpg';
    Storage::disk('public')->put($oldPhotoPath, 'old photo');
    $employee = employeeManagementUser(['profile_photo_path' => $oldPhotoPath]);

    Sanctum::actingAs($employee);
    $this->postJson('/api/v1/user/profile', [
        'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
    ])->assertOk()
        ->assertJsonPath('message', 'Profil berhasil diperbarui.');

    $newPhotoPath = $employee->fresh()->profile_photo_path;
    expect($newPhotoPath)->not->toBe($oldPhotoPath);
    expect($employee->fresh()->name)->toBe($employee->name)
        ->and($employee->fresh()->id_karyawan)->toBe($employee->id_karyawan)
        ->and($employee->fresh()->departemen)->toBe($employee->departemen);
    $photoUrl = route('profile-photos.show', ['filename' => basename($newPhotoPath)]);
    expect($employee->fresh()->profile_photo_url)->toBe($photoUrl);
    Storage::disk('public')->assertExists($newPhotoPath);
    Storage::disk('public')->assertMissing($oldPhotoPath);

    $this->get($photoUrl)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    Auth::forgetGuards();
    Auth::shouldUse('web');
    $this->postJson('/api/v1/login', [
        'email' => $employee->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('user.profile_photo_url', $photoUrl);

    $this->actingAs($hrd);
    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee(route('admin.storage.file', ['path' => $newPhotoPath]), false);

    Sanctum::actingAs($employee->fresh());
    $this->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('profile_photo_path', $newPhotoPath)
        ->assertJsonPath('profile_photo_url', $photoUrl);

    $this->getJson('/api/v1/user/profile')
        ->assertOk()
        ->assertJsonPath('profile_photo_url', $photoUrl);
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
        ->assertRedirect(route('admin.absensi.approval.hrd', ['section' => 'employees']));

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
