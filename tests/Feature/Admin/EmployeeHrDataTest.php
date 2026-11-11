<?php

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\EmployeeDocument;
use App\Models\EmployeeFamily;
use App\Models\EmployeeProfile;
use App\Models\EmploymentHistory;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\PtkpStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function hrDataEmployee(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'registration_status' => 'approved',
        'is_active' => true,
        'gender' => 'female',
        'phone' => '081234567890',
        'address' => 'Alamat lama',
        'departemen' => 'Departemen legacy',
        'employment_type' => 'organik',
        'work_location' => 'office',
        'nik' => '1234567890123456',
        'bank_name' => 'BCA',
        'bank_account_number' => '1234567890',
        'bank_account_name' => 'Nama Karyawan',
    ], $overrides));
}

function hrdForEmployeeHrData(): User
{
    return User::factory()->create(['role' => 'hrd']);
}

it('denies HR employee data routes to non-HRD users', function () {
    $employee = hrDataEmployee();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->put(route('admin.employees.hr-profile.update', $employee), [
            'npwp' => '12.345.678.9-000.000',
        ])
        ->assertForbidden();
});

it('shows HR profile secrets masked by default and exposes only approved profile fields', function () {
    $employee = hrDataEmployee();
    $profile = EmployeeProfile::factory()->for($employee)->create([
        'npwp' => '123456789012345',
        'bpjs_kesehatan_no' => 'BPJS-KESEHATAN-123',
        'bpjs_ketenagakerjaan_no' => 'BPJS-KERJA-456',
        'birth_date' => '1990-04-15',
        'nik_ktp' => 'SHOULD-NOT-BE-IN-THIS-FORM',
        'bank_name' => 'SHOULD-NOT-BE-IN-THIS-FORM',
        'bank_account_no' => 'SHOULD-NOT-BE-IN-THIS-FORM',
        'address' => 'SHOULD-NOT-BE-IN-THIS-FORM',
        'gender' => 'SHOULD-NOT-BE-IN-THIS-FORM',
    ]);
    $response = $this->actingAs(hrdForEmployeeHrData())
        ->get(route('admin.employees.edit', $employee))
        ->assertOk()
        ->assertSee('name="department_id"', false)
        ->assertSee('name="position_id"', false)
        ->assertSee('Legacy (teks lama', false)
        ->assertSee('Departemen (struktur baru')
        ->assertSee('type="password"', false)
        ->assertSee('value="'.$profile->npwp.'"', false)
        ->assertDontSee('name="nik_ktp"', false)
        ->assertDontSee('SHOULD-NOT-BE-IN-THIS-FORM');

    expect($response->getContent())->toContain('x-bind:type="visible ? \'text\' : \'password\'"');
});

it('updates only approved HR profile fields and never logs submitted sensitive values', function () {
    $employee = hrDataEmployee();
    $profile = EmployeeProfile::factory()->for($employee)->create([
        'nik_ktp' => 'OLD-NIK-KTP',
        'bank_name' => 'OLD-BANK',
        'bank_account_no' => 'OLD-ACCOUNT',
        'address' => 'OLD-ADDRESS',
        'gender' => 'OLD-GENDER',
    ]);
    $status = PtkpStatus::factory()->create(['effective_at' => today()]);
    $npwp = '987654321098765';
    $bpjsHealth = 'HEALTH-SECRET-9382';
    $bpjsEmployment = 'WORK-SECRET-2846';

    $this->actingAs(hrdForEmployeeHrData())
        ->put(route('admin.employees.hr-profile.update', $employee), [
            'birth_date' => '1991-02-03',
            'npwp' => $npwp,
            'bpjs_kesehatan_no' => $bpjsHealth,
            'bpjs_ketenagakerjaan_no' => $bpjsEmployment,
            'ptkp_status_id' => $status->id,
            'nik_ktp' => 'NEW-FORBIDDEN-NIK',
            'bank_name' => 'NEW-FORBIDDEN-BANK',
            'bank_account_no' => 'NEW-FORBIDDEN-ACCOUNT',
            'address' => 'NEW-FORBIDDEN-ADDRESS',
            'gender' => 'NEW-FORBIDDEN-GENDER',
        ])
        ->assertRedirect(route('admin.employees.edit', $employee))
        ->assertSessionHas('success');

    $log = ActivityLog::query()
        ->where('action', 'HR employee profile updated')
        ->latest('id')
        ->firstOrFail();
    $loggedDetails = $log->details;
    $profile->refresh();

    expect($profile->birth_date->toDateString())->toBe('1991-02-03')
        ->and($profile->npwp)->toBe($npwp)
        ->and($profile->bpjs_kesehatan_no)->toBe($bpjsHealth)
        ->and($profile->bpjs_ketenagakerjaan_no)->toBe($bpjsEmployment)
        ->and($profile->ptkp_status_id)->toBe($status->id)
        ->and($profile->nik_ktp)->toBe('OLD-NIK-KTP')
        ->and($profile->bank_name)->toBe('OLD-BANK')
        ->and($profile->bank_account_no)->toBe('OLD-ACCOUNT')
        ->and($profile->address)->toBe('OLD-ADDRESS')
        ->and($profile->gender)->toBe('OLD-GENDER')
        ->and($loggedDetails)->toContain('npwp')
        ->and($loggedDetails)->toContain('bpjs_kesehatan_no')
        ->and($loggedDetails)->not->toContain($npwp)
        ->and($loggedDetails)->not->toContain($bpjsHealth)
        ->and($loggedDetails)->not->toContain($bpjsEmployment);
});

it('manages family and employment history without changing the legacy employment type', function () {
    $employee = hrDataEmployee(['employment_type' => 'freelance']);
    $hrd = hrdForEmployeeHrData();

    $this->actingAs($hrd)
        ->post(route('admin.employees.families.store', $employee), [
            'name' => 'Nama Keluarga',
            'relationship' => 'Pasangan',
            'phone' => '0811222333',
            'is_emergency_contact' => '1',
        ])
        ->assertSessionHas('success');

    $family = EmployeeFamily::query()->where('user_id', $employee->id)->firstOrFail();
    $this->put(route('admin.employees.families.update', [$employee, $family]), [
        'name' => 'Nama Diperbarui',
        'relationship' => 'Pasangan',
        'phone' => '0811999888',
    ])->assertSessionHas('success');

    $this->post(route('admin.employees.employment-histories.store', $employee), [
        'employment_type' => 'kontrak',
        'start_date' => '2020-01-01',
        'end_date' => '2021-12-31',
        'note' => 'Riwayat manual',
    ])->assertSessionHas('success');

    $history = EmploymentHistory::query()->where('user_id', $employee->id)->firstOrFail();
    $this->put(route('admin.employees.employment-histories.update', [$employee, $history]), [
        'employment_type' => 'tetap',
        'start_date' => '2020-01-01',
        'end_date' => null,
        'note' => 'Catatan diperbarui',
    ])->assertSessionHas('success');

    expect($family->fresh()->name)->toBe('Nama Diperbarui')
        ->and($history->fresh()->employment_type)->toBe('tetap')
        ->and($employee->fresh()->employment_type)->toBe('freelance');

    $this->delete(route('admin.employees.families.destroy', [$employee, $family]))
        ->assertSessionHas('success');
    $this->delete(route('admin.employees.employment-histories.destroy', [$employee, $history]))
        ->assertSessionHas('success');

    expect(EmployeeFamily::query()->whereKey($family->id)->exists())->toBeFalse()
        ->and(EmploymentHistory::query()->whereKey($history->id)->exists())->toBeFalse();
});

it('stores documents privately with random names, authorizes downloads, and removes file on deletion', function () {
    Storage::fake('local');
    Storage::fake('public');
    $employee = hrDataEmployee();
    $hrd = hrdForEmployeeHrData();

    $this->actingAs($hrd)
        ->post(route('admin.employees.documents.store', $employee), [
            'doc_type' => 'Kontrak kerja',
            'file' => UploadedFile::fake()->createWithContent('kontrak-asli.pdf', "%PDF-1.4\nHR employee document\n"),
            'expired_at' => today()->addMonth()->toDateString(),
        ])
        ->assertSessionHas('success');

    $document = EmployeeDocument::query()->where('user_id', $employee->id)->firstOrFail();
    $privateDisk = Storage::disk('local');

    expect($privateDisk->exists($document->file_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($document->file_path))->toBeFalse()
        ->and(basename($document->file_path))->not->toBe('kontrak-asli.pdf');

    $this->get(route('admin.employees.documents.download', [$employee, $document]))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.employees.documents.download', [$employee, $document]))
        ->assertForbidden();

    $this->actingAs($hrd)
        ->delete(route('admin.employees.documents.destroy', [$employee, $document]))
        ->assertSessionHas('success');

    expect($privateDisk->exists($document->file_path))->toBeFalse()
        ->and(EmployeeDocument::query()->whereKey($document->id)->exists())->toBeFalse();
});

it('keeps the existing employee update form working when placement fields are omitted', function () {
    $employee = hrDataEmployee();

    $this->actingAs(hrdForEmployeeHrData())
        ->put(route('admin.employees.update', $employee), [
            'name' => 'Data Legacy Tetap',
            'email' => $employee->email,
            'id_karyawan' => $employee->id_karyawan,
            'gender' => 'female',
            'phone' => '081234567899',
            'address' => 'Alamat diperbarui',
            'departemen' => 'Departemen legacy baru',
            'employment_type' => 'organik',
            'work_location' => 'office',
            'nik' => '1234567890123456',
            'bank_name' => 'BRI',
            'bank_account_number' => '99887766',
            'bank_account_name' => 'Nama Karyawan',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($employee->fresh()->departemen)->toBe('Departemen legacy baru')
        ->and($employee->fresh()->department_id)->toBeNull()
        ->and($employee->fresh()->employment_type)->toBe('organik');
});

it('saves structured department and position independently of the legacy department string', function () {
    $employee = hrDataEmployee(['departemen' => 'Legacy department']);
    $department = Department::factory()->create();
    $position = Position::factory()->create([
        'department_id' => $department->id,
        'job_level_id' => JobLevel::factory()->create()->id,
    ]);

    $this->actingAs(hrdForEmployeeHrData())
        ->put(route('admin.employees.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'id_karyawan' => $employee->id_karyawan,
            'gender' => 'female',
            'phone' => '081234567899',
            'address' => 'Alamat',
            'departemen' => 'Legacy department',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_type' => 'organik',
            'work_location' => 'office',
            'nik' => '1234567890123456',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Nama Karyawan',
        ])
        ->assertSessionHas('success');

    expect($employee->fresh()->department_id)->toBe($department->id)
        ->and($employee->fresh()->position_id)->toBe($position->id)
        ->and($employee->fresh()->departemen)->toBe('Legacy department');
});

it('offers and accepts only PTKP references effective today', function () {
    $employee = hrDataEmployee();
    $currentStatus = PtkpStatus::factory()->create([
        'code' => 'CURRENT-TEST',
        'effective_at' => today(),
    ]);
    $futureStatus = PtkpStatus::factory()->create([
        'code' => 'FUTURE-TEST',
        'effective_at' => today()->addDay(),
    ]);

    $this->actingAs(hrdForEmployeeHrData())
        ->get(route('admin.employees.edit', $employee))
        ->assertOk()
        ->assertSee('value="'.$currentStatus->id.'"', false)
        ->assertDontSee('value="'.$futureStatus->id.'"', false);

    $this->put(route('admin.employees.hr-profile.update', $employee), [
        'ptkp_status_id' => $futureStatus->id,
    ])->assertSessionHasErrors('ptkp_status_id');
});
