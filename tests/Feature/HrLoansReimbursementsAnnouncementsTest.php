<?php

use App\Models\Announcement;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\PayrollItem;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Models\User;
use Database\Factories\PayrollItemFactorySupport;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relates loans, installments, reimbursements, types, and announcements bidirectionally', function () {
    $employee = User::factory()->create();
    $approver = User::factory()->create();
    $loan = Loan::factory()->create([
        'user_id' => $employee->id,
        'approved_by' => $approver->id,
    ]);
    $payrollItemId = PayrollItemFactorySupport::createId();
    $installment = LoanInstallment::factory()->create([
        'loan_id' => $loan->id,
        'payroll_item_id' => $payrollItemId,
    ]);
    $type = ReimbursementType::factory()->create();
    $reimbursement = Reimbursement::factory()->create([
        'user_id' => $employee->id,
        'type_id' => $type->id,
        'payroll_item_id' => $payrollItemId,
        'approved_by' => $approver->id,
    ]);
    $announcement = Announcement::factory()->create(['created_by' => $employee->id]);

    expect($loan->user->is($employee))->toBeTrue()
        ->and($loan->approvedBy->is($approver))->toBeTrue()
        ->and($loan->installments->contains($installment))->toBeTrue()
        ->and($installment->loan->is($loan))->toBeTrue()
        ->and($installment->payrollItem->id)->toBe($payrollItemId)
        ->and($employee->loans->contains($loan))->toBeTrue()
        ->and($approver->approvedLoans->contains($loan))->toBeTrue()
        ->and($reimbursement->user->is($employee))->toBeTrue()
        ->and($reimbursement->type->is($type))->toBeTrue()
        ->and($reimbursement->approvedBy->is($approver))->toBeTrue()
        ->and($reimbursement->payrollItem->id)->toBe($payrollItemId)
        ->and($type->reimbursements->contains($reimbursement))->toBeTrue()
        ->and($employee->reimbursements->contains($reimbursement))->toBeTrue()
        ->and($approver->approvedReimbursements->contains($reimbursement))->toBeTrue()
        ->and($employee->announcements->contains($announcement))->toBeTrue()
        ->and($announcement->createdBy->is($employee))->toBeTrue()
        ->and(PayrollItem::findOrFail($payrollItemId)->loanInstallments->contains($installment))->toBeTrue()
        ->and(PayrollItem::findOrFail($payrollItemId)->reimbursements->contains($reimbursement))->toBeTrue();
});

it('enforces unique installment numbers within each loan', function () {
    $loan = Loan::factory()->create();
    LoanInstallment::factory()->create([
        'loan_id' => $loan->id,
        'installment_no' => 1,
    ]);

    expect(fn () => LoanInstallment::factory()->create([
        'loan_id' => $loan->id,
        'installment_no' => 1,
    ]))->toThrow(QueryException::class);
});

it('checks installment totals against principal without changing data', function () {
    $loan = Loan::factory()->create(['principal' => 300000]);
    LoanInstallment::factory()->create([
        'loan_id' => $loan->id,
        'installment_no' => 1,
        'amount' => 100000,
    ]);
    LoanInstallment::factory()->create([
        'loan_id' => $loan->id,
        'installment_no' => 2,
        'amount' => 200000,
    ]);

    expect($loan->installmentsTotalMatchesPrincipal())->toBeTrue()
        ->and($loan->principal)->toBe(300000)
        ->and($loan->installments()->count())->toBe(2);

    $loan->principal = 300001;

    expect($loan->installmentsTotalMatchesPrincipal())->toBeFalse()
        ->and($loan->installments()->count())->toBe(2);
});

it('validates loan, installment, and reimbursement statuses', function () {
    expect(fn () => Loan::factory()->create(['status' => 'paid']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => LoanInstallment::factory()->create(['status' => 'settled']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => Reimbursement::factory()->create(['status' => 'paid']))
        ->toThrow(InvalidArgumentException::class);
});

it('prevents deleting reimbursement types that have reimbursement records', function () {
    $type = ReimbursementType::factory()->create();
    Reimbursement::factory()->create(['type_id' => $type->id]);

    expect(fn () => $type->delete())->toThrow(QueryException::class)
        ->and(ReimbursementType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('does not seed reimbursement types', function () {
    $this->seed(ReferenceSeeder::class);

    expect(ReimbursementType::query()->count())->toBe(0);
});
