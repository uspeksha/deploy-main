<?php

use App\Models\Loan;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('returns only categories with a monthly limit and the authenticated employee request data', function () {
    $employee = User::factory()->create(['role' => 'user']);
    $anotherEmployee = User::factory()->create(['role' => 'user']);
    $type = ReimbursementType::query()->create([
        'name' => 'Transport',
        'monthly_limit' => 500000,
    ]);
    ReimbursementType::query()->create([
        'name' => 'No limit',
        'monthly_limit' => null,
    ]);
    $ownRequest = Reimbursement::query()->create([
        'user_id' => $employee->id,
        'type_id' => $type->id,
        'amount' => 10000,
        'receipt_path' => 'reimbursements/receipt.pdf',
        'status' => Reimbursement::STATUS_PENDING,
    ]);
    Reimbursement::query()->create([
        'user_id' => $anotherEmployee->id,
        'type_id' => $type->id,
        'amount' => 20000,
        'receipt_path' => 'reimbursements/another.pdf',
        'status' => Reimbursement::STATUS_PENDING,
    ]);

    Sanctum::actingAs($employee);

    $this->getJson('/api/v1/hr-requests')
        ->assertOk()
        ->assertJsonPath('data.reimbursement_types.0.name', 'Transport')
        ->assertJsonPath('data.reimbursement_types.0.used_this_month', 10000)
        ->assertJsonPath('data.reimbursements.0.id', $ownRequest->id)
        ->assertJsonMissing(['amount' => 20000])
        ->assertJsonPath('data.loan_policy.max_principal', 5000000);
});

it('stores a reimbursement receipt and blocks an amount above the remaining monthly category limit', function () {
    Storage::fake('local');
    $employee = User::factory()->create(['role' => 'user']);
    $type = ReimbursementType::query()->create([
        'name' => 'Transport',
        'monthly_limit' => 100000,
    ]);
    Reimbursement::query()->create([
        'user_id' => $employee->id,
        'type_id' => $type->id,
        'amount' => 60000,
        'receipt_path' => 'reimbursements/existing.pdf',
        'status' => Reimbursement::STATUS_APPROVED,
    ]);
    Sanctum::actingAs($employee);

    $this->postJson('/api/v1/hr-requests/reimbursements', [
        'type_id' => $type->id,
        'amount' => 50000,
        'description' => 'Transport ke kantor',
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    $this->postJson('/api/v1/hr-requests/reimbursements', [
        'type_id' => $type->id,
        'amount' => 40000,
        'description' => 'Transport ke kantor',
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertCreated()
        ->assertJsonPath('data.status', Reimbursement::STATUS_PENDING);

    $saved = Reimbursement::query()
        ->where('user_id', $employee->id)
        ->where('amount', 40000)
        ->firstOrFail();
    Storage::disk('local')->assertExists($saved->receipt_path);
});

it('validates loan policy limits and permits only one pending loan per employee', function () {
    $employee = User::factory()->create(['role' => 'user']);
    Sanctum::actingAs($employee);

    $this->postJson('/api/v1/hr-requests/loans', [
        'principal' => 5000001,
        'tenor_months' => 13,
        'purpose' => 'Biaya pendidikan',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['principal', 'tenor_months']);

    $this->postJson('/api/v1/hr-requests/loans', [
        'principal' => 1200000,
        'tenor_months' => 6,
        'purpose' => 'Biaya pendidikan anak',
    ])->assertCreated()
        ->assertJsonPath('data.status', Loan::STATUS_PENDING);

    $this->postJson('/api/v1/hr-requests/loans', [
        'principal' => 1000000,
        'tenor_months' => 5,
        'purpose' => 'Perbaikan rumah',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('principal');

    expect(Loan::query()->where('user_id', $employee->id)->count())->toBe(1);
});

it('does not expose employee request APIs to HRD accounts', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'hrd']));

    $this->getJson('/api/v1/hr-requests')->assertForbidden();
    $this->postJson('/api/v1/hr-requests/loans', [
        'principal' => 100000,
        'tenor_months' => 1,
        'purpose' => 'Keperluan keluarga',
    ])->assertForbidden();
});
