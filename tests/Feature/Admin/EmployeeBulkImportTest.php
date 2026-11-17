<?php

use App\Mail\EmployeeRegistrationMail;
use App\Models\User;
use App\Services\EmployeeRegistrationMailer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function employeeImportUpload(array $overrides = [], array $headers = [], array $numericCells = []): UploadedFile
{
    $headers = $headers ?: [
        'Employee ID', 'Full Name', 'Email', 'Mobile Phone', 'Residential Address',
        'Department', 'Employment Type', 'Join Date', 'Work Location', 'NIK',
        'Gender', 'Bank Name', 'Bank Account', 'Bank Account Holder', 'Daily Rate',
    ];
    $row = [
        'EMP-IMPORT-001',
        'Imported Employee',
        'imported.employee@example.com',
        '081234567890',
        'Jl. Merdeka No. 1, Jakarta',
        'Operasional',
        'organik',
        now()->subYear()->toDateString(),
        'office',
        '1234567890123456',
        'female',
        'BCA',
        '001234567890',
        'Imported Employee',
        '',
    ];
    $fieldIndexes = ['employment_type' => 6, 'daily_rate' => 14];
    foreach ($overrides as $field => $value) {
        $row[$fieldIndexes[$field]] = $value;
    }

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([$headers, $row], null, 'A1');
    foreach (['A2', 'J2', 'M2'] as $coordinate) {
        if (in_array($coordinate, $numericCells, true)) {
            $sheet->getCell($coordinate)->setValue((float) $sheet->getCell($coordinate)->getValue());
        } else {
            $sheet->getCell($coordinate)->setValueExplicit(
                (string) $sheet->getCell($coordinate)->getValue(),
                DataType::TYPE_STRING,
            );
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'employee-import-');
    (new Xlsx($spreadsheet))->save($path);
    $content = file_get_contents($path);
    unlink($path);
    $spreadsheet->disconnectWorksheets();

    return UploadedFile::fake()->createWithContent('employees.xlsx', $content);
}

it('previews an Excel file and only creates employee accounts after confirmation', function () {
    Mail::fake();
    config(['mail.default' => 'smtp']);
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.employees.import.preview'), [
            'employee_file' => employeeImportUpload(),
        ])
        ->assertRedirect(route('admin.absensi.approval.hrd', ['section' => 'employees']));

    expect(User::where('email', 'imported.employee@example.com')->exists())->toBeFalse();

    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee('1 baris siap dibuat')
        ->assertSee('Imported Employee');

    $this->post(route('admin.absensi.approval.employees.import.confirm'))
        ->assertRedirect(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertSessionHas('success');

    $employee = User::where('email', 'imported.employee@example.com')->firstOrFail();
    expect($employee->role)->toBe('user')
        ->and($employee->registration_status)->toBe('approved')
        ->and($employee->is_active)->toBeTrue()
        ->and($employee->employment_type)->toBe('organik')
        ->and($employee->work_location)->toBe('office')
        ->and($employee->nik)->toBe('1234567890123456')
        ->and($employee->bank_account_number)->toBe('001234567890')
        ->and($employee->employmentHistories()->sole()->start_date->toDateString())
        ->toBe(now()->subYear()->toDateString());

    Mail::assertSent(EmployeeRegistrationMail::class, fn (EmployeeRegistrationMail $mail) => $mail->hasTo($employee->email));
});

it('creates freelance salary history from the daily rate column', function () {
    Mail::fake();
    config(['mail.default' => 'smtp']);
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.employees.import.preview'), [
            'employee_file' => employeeImportUpload([
                'employment_type' => 'freelance',
                'daily_rate' => '135000',
            ]),
        ])
        ->assertRedirect();

    $this->post(route('admin.absensi.approval.employees.import.confirm'))
        ->assertSessionHas('success');

    $employee = User::where('email', 'imported.employee@example.com')->firstOrFail();
    expect($employee->employment_type)->toBe('freelance');
    expect($employee->salaryRates()->sole()->daily_rate)->toBe(135000);
});

it('blocks confirmation when any imported row is invalid and creates no partial accounts', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $invalidRow = [
        'EMP-IMPORT-002',
        'Invalid Employee',
        'not-an-email',
        '081234567890',
        'Jl. Merdeka No. 1, Jakarta',
        'Operasional',
        'organik',
        now()->subYear()->toDateString(),
        'office',
        '1234567890123456',
        'female',
        'BCA',
        '1234567890',
        'Invalid Employee',
        '',
    ];
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $headers = [
        'Employee ID', 'Full Name', 'Email', 'Mobile Phone', 'Residential Address',
        'Department', 'Employment Type', 'Join Date', 'Work Location', 'NIK',
        'Gender', 'Bank Name', 'Bank Account', 'Bank Account Holder', 'Daily Rate',
    ];
    $valid = [
        'EMP-IMPORT-001', 'Valid Employee', 'valid.employee@example.com', '081234567890',
        'Jl. Merdeka No. 1, Jakarta', 'Operasional', 'organik', now()->subYear()->toDateString(),
        'office', '1234567890123456', 'female', 'BCA', '1234567890', 'Valid Employee', '',
    ];
    $sheet->fromArray([$headers, $valid, $invalidRow], null, 'A1');
    foreach (['A2', 'J2', 'M2', 'A3', 'J3', 'M3'] as $coordinate) {
        $sheet->getCell($coordinate)->setValueExplicit(
            (string) $sheet->getCell($coordinate)->getValue(),
            DataType::TYPE_STRING,
        );
    }
    $path = tempnam(sys_get_temp_dir(), 'employee-import-');
    (new Xlsx($spreadsheet))->save($path);
    $content = file_get_contents($path);
    unlink($path);
    $spreadsheet->disconnectWorksheets();

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.employees.import.preview'), [
            'employee_file' => UploadedFile::fake()->createWithContent('employees.xlsx', $content),
        ])
        ->assertRedirect();

    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee('1 baris perlu diperbaiki')
        ->assertSee('Baris 3')
        ->assertSee('Format email tidak valid.');

    $this->post(route('admin.absensi.approval.employees.import.confirm'))
        ->assertSessionHasErrors('employee_import');

    expect(User::where('email', 'valid.employee@example.com')->exists())->toBeFalse();
});

it('requires HRD to confirm that Organization contains the department name', function () {
    Mail::fake();
    config(['mail.default' => 'smtp']);
    $hrd = User::factory()->create(['role' => 'hrd']);
    $headers = [
        'Employee ID', 'Full Name', 'Email', 'Mobile Phone', 'Residential Address',
        'Organization', 'Employment Type', 'Join Date', 'Work Location', 'NIK',
        'Gender', 'Bank Name', 'Bank Account', 'Bank Account Holder', 'Daily Rate',
    ];

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.employees.import.preview'), [
            'employee_file' => employeeImportUpload([], $headers),
        ])
        ->assertRedirect();

    $this->post(route('admin.absensi.approval.employees.import.confirm'))
        ->assertSessionHasErrors('confirm_organization_department');
    expect(User::where('email', 'imported.employee@example.com')->exists())->toBeFalse();

    $this->post(route('admin.absensi.approval.employees.import.confirm'), [
        'confirm_organization_department' => '1',
    ])->assertSessionHas('success');

    expect(User::where('email', 'imported.employee@example.com')->firstOrFail()->departemen)
        ->toBe('Operasional');
});

it('rejects numeric NIK cells that may have lost digits in Excel', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.employees.import.preview'), [
            'employee_file' => employeeImportUpload([], [], ['J2']),
        ])
        ->assertRedirect();

    $this->get(route('admin.absensi.approval.hrd', ['section' => 'employees']))
        ->assertOk()
        ->assertSee('NIK harus disimpan sebagai teks agar semua 16 digit tetap akurat.');

    expect(User::where('email', 'imported.employee@example.com')->exists())->toBeFalse();
});

it('restricts employee import endpoints to HRD accounts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.absensi.approval.employees.import.template'))
        ->assertForbidden();

    $this->post(route('admin.absensi.approval.employees.import.preview'), [
        'employee_file' => employeeImportUpload(),
    ])->assertForbidden();
});

it('does not log temporary passwords when the log mail driver is configured', function () {
    Log::spy();
    config(['mail.default' => 'log']);
    $employee = User::factory()->create(['role' => 'user']);

    expect(app(EmployeeRegistrationMailer::class)->sendTemporaryPassword($employee, 'secret'))->toBeFalse();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Employee credentials were not sent because the log mail driver is configured.', [
            'employee_id' => $employee->id,
        ]);
});
