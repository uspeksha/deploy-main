<?php

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows HRD to review requests, create equal interest-free loan installments, and rejects other roles', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create();
    $loan = Loan::query()->create([
        'user_id' => $employee->id,
        'principal' => 1000001,
        'tenor_months' => 3,
        'purpose' => 'Biaya pendidikan',
        'status' => Loan::STATUS_PENDING,
    ]);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.hr-requests.index'))
        ->assertForbidden();

    $this->actingAs($hrd)
        ->get(route('admin.hr-requests.index'))
        ->assertOk()
        ->assertSee('Biaya pendidikan');

    $this->post(route('admin.hr-requests.loans.action', [$loan, 'approve']))
        ->assertSessionHas('success');

    $installments = LoanInstallment::query()
        ->where('loan_id', $loan->id)
        ->orderBy('installment_no')
        ->get();

    expect($installments)->toHaveCount(3)
        ->and($installments->sum('amount'))->toBe(1000001)
        ->and($installments->pluck('amount')->all())->toBe([333333, 333333, 333335])
        ->and($loan->fresh()->status)->toBe(Loan::STATUS_APPROVED);
});

it('only allows approved reimbursements to be marked paid and requires a payment reference', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create();
    $type = ReimbursementType::query()->create([
        'name' => 'Transport',
        'monthly_limit' => 100000,
    ]);
    $reimbursement = Reimbursement::query()->create([
        'user_id' => $employee->id,
        'type_id' => $type->id,
        'amount' => 25000,
        'receipt_path' => 'reimbursements/receipt.pdf',
        'status' => Reimbursement::STATUS_PENDING,
    ]);

    $this->actingAs($hrd)
        ->post(route('admin.hr-requests.reimbursements.action', [$reimbursement, 'paid']), [])
        ->assertSessionHasErrors('payment_reference');

    $reimbursement->update(['status' => Reimbursement::STATUS_APPROVED]);
    $this->post(route('admin.hr-requests.reimbursements.action', [$reimbursement, 'paid']), [
        'payment_reference' => 'TRF-2026-001',
    ])->assertSessionHas('success');

    expect($reimbursement->fresh()->status)->toBe(Reimbursement::STATUS_PAID)
        ->and($reimbursement->fresh()->payment_reference)->toBe('TRF-2026-001');
});

it('records confirmed loan installment payments with a required reference', function () {
    $hrd = User::factory()->create(['role' => 'hrd']);
    $employee = User::factory()->create();
    $loan = Loan::query()->create([
        'user_id' => $employee->id,
        'principal' => 100000,
        'tenor_months' => 1,
        'purpose' => 'Keperluan keluarga',
        'status' => Loan::STATUS_APPROVED,
    ]);
    $installment = LoanInstallment::query()->create([
        'loan_id' => $loan->id,
        'installment_no' => 1,
        'amount' => 100000,
        'due_date' => now()->addMonth()->endOfMonth()->toDateString(),
        'status' => LoanInstallment::STATUS_APPROVED,
    ]);

    $this->actingAs($hrd)
        ->post(route('admin.hr-requests.installments.paid', $installment), [])
        ->assertSessionHasErrors('payment_reference');

    $this->post(route('admin.hr-requests.installments.paid', $installment), [
        'payment_reference' => 'PAYROLL-2026-001',
    ])->assertSessionHas('success');

    expect($installment->fresh()->status)->toBe(LoanInstallment::STATUS_PAID)
        ->and($installment->fresh()->payment_reference)->toBe('PAYROLL-2026-001')
        ->and($installment->fresh()->paid_at)->not->toBeNull();
});
