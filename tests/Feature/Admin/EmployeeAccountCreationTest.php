<?php

use App\Mail\EmployeeRegistrationMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function employeeAccountPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'New Employee',
        'email' => 'new.employee@example.com',
        'id_karyawan' => 'EMP-2026-001',
        'departemen' => 'Operasional',
        'employment_type' => 'organik',
        'work_location' => 'office',
        'nik' => '1234567890123456',
        'bank_name' => 'Bank Contoh',
        'bank_account_number' => '1234567890',
        'bank_account_name' => 'New Employee',
    ], $overrides);
}

it('lets HRD create an active employee account and emails temporary credentials', function () {
    Mail::fake();
    config(['mail.default' => 'smtp']);
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.approval.hrd'))
        ->assertOk()
        ->assertSee('Buat akun karyawan baru')
        ->assertSee('kirim akses lewat email');

    $this->post(route('admin.absensi.approval.employees.store'), employeeAccountPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $employee = User::where('email', 'new.employee@example.com')->firstOrFail();
    expect($employee->role)->toBe('user')
        ->and($employee->registration_status)->toBe('approved')
        ->and($employee->is_active)->toBeTrue()
        ->and(Hash::check('password', $employee->password))->toBeFalse()
        ->and($employee->nik)->toBe('1234567890123456');

    expect($employee->getRawOriginal('nik'))->not->toBe('1234567890123456');

    $temporaryPassword = null;
    Mail::assertSent(EmployeeRegistrationMail::class, function (EmployeeRegistrationMail $mail) use ($employee, &$temporaryPassword) {
        $temporaryPassword = $mail->temporaryPassword;
        expect($mail->temporaryPassword)->not->toBeEmpty();
        expect(Hash::check($mail->temporaryPassword, $employee->password))->toBeTrue();

        return $mail->hasTo($employee->email);
    });

    $this->postJson('/api/v1/login', [
        'email' => $employee->email,
        'password' => $temporaryPassword,
    ])->assertOk()
        ->assertJsonPath('user.id', $employee->id)
        ->assertJsonMissingPath('user.nik')
        ->assertJsonMissingPath('user.bank_account_number');
});

it('keeps an account inactive if email fails and lets HRD resend credentials', function () {
    Mail::fake();
    config(['mail.default' => 'array']);
    $hrd = User::factory()->create(['role' => 'hrd']);

    $this->actingAs($hrd)
        ->post(route('admin.absensi.approval.employees.store'), employeeAccountPayload())
        ->assertRedirect()
        ->assertSessionHas('warning');

    $employee = User::where('email', 'new.employee@example.com')->firstOrFail();
    expect($employee->registration_status)->toBe('pending')
        ->and($employee->is_active)->toBeFalse();

    config(['mail.default' => 'smtp']);
    $this->post(route('admin.absensi.approval.employees.resend', $employee))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($employee->fresh()->registration_status)->toBe('approved')
        ->and($employee->fresh()->is_active)->toBeTrue();
    Mail::assertSent(EmployeeRegistrationMail::class, 1);
});

it('only allows exact HRD role to create accounts and keeps employees out of web admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->post(route('admin.absensi.approval.employees.store'), employeeAccountPayload())
        ->assertForbidden();

    $employee = User::factory()->create([
        'role' => 'user',
        'registration_status' => 'approved',
        'is_active' => true,
    ]);

    auth()->guard('web')->logout();
    $this->post('/login', [
        'email' => $employee->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

it('does not show employee account creation form to HRGA', function () {
    $hrga = User::factory()->create(['role' => 'hrga']);

    $this->actingAs($hrga)
        ->get(route('admin.absensi.approval.hrd'))
        ->assertForbidden();
});

it('does not flash NIK or bank details back into the session after validation failure', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $payload = employeeAccountPayload(['nik' => 'invalid']);

    $this->actingAs($hrd)
        ->from(route('admin.absensi.approval.hrd'))
        ->post(route('admin.absensi.approval.employees.store'), $payload)
        ->assertRedirect(route('admin.absensi.approval.hrd'))
        ->assertSessionHasErrors('nik')
        ->assertSessionHas('_old_input', fn (array $input) =>
            !array_key_exists('nik', $input)
            && !array_key_exists('bank_name', $input)
            && !array_key_exists('bank_account_number', $input)
            && !array_key_exists('bank_account_name', $input)
        );
});

it('rejects public web and mobile self-registration', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
    $this->postJson('/api/v1/register')->assertNotFound();
});
