<?php

use App\Models\BpjsRate;
use App\Models\EmployeeComponent;
use App\Models\PayrollComponent;
use App\Models\PayrollItemBpjs;
use App\Models\PayrollItemComponent;
use App\Models\User;
use Database\Factories\PayrollItemFactorySupport;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('relates payroll components, employee assignments, payroll snapshots, and BPJS records bidirectionally', function () {
    $component = PayrollComponent::factory()->create();
    $user = User::factory()->create();
    $assignment = EmployeeComponent::factory()->create([
        'component_id' => $component->id,
        'user_id' => $user->id,
        'effective_at' => '2026-01-01',
        'end_at' => '2026-12-31',
    ]);
    $itemId = PayrollItemFactorySupport::createId();
    $componentSnapshot = PayrollItemComponent::factory()->create([
        'payroll_item_id' => $itemId,
        'component_id' => $component->id,
        'amount' => 250000,
    ]);
    $rate = BpjsRate::factory()->create();
    $bpjsSnapshot = PayrollItemBpjs::factory()->create([
        'payroll_item_id' => $itemId,
        'bpjs_rate_id' => $rate->id,
        'employee_amount' => 20000,
        'employer_amount' => 40000,
    ]);

    expect($assignment->user->is($user))->toBeTrue()
        ->and($user->employeeComponents->contains($assignment))->toBeTrue()
        ->and($assignment->component->is($component))->toBeTrue()
        ->and($component->employeeComponents->contains($assignment))->toBeTrue()
        ->and($component->itemComponents->contains($componentSnapshot))->toBeTrue()
        ->and($componentSnapshot->component->is($component))->toBeTrue()
        ->and($componentSnapshot->payrollItem->componentSnapshots->contains($componentSnapshot))->toBeTrue()
        ->and($bpjsSnapshot->bpjsRate->is($rate))->toBeTrue()
        ->and($rate->payrollItemBpjs->contains($bpjsSnapshot))->toBeTrue()
        ->and($bpjsSnapshot->payrollItem->bpjsSnapshots->contains($bpjsSnapshot))->toBeTrue();
});

it('validates payroll component type and calculation type values', function () {
    expect(fn () => PayrollComponent::factory()->create(['type' => 'benefit']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => PayrollComponent::factory()->create(['calc_type' => 'formula']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => BpjsRate::factory()->create(['program' => 'jkp']))
        ->toThrow(InvalidArgumentException::class);
});

it('prevents overlapping employee component assignments and permits adjacent ranges', function () {
    $user = User::factory()->create();
    $component = PayrollComponent::factory()->create();
    EmployeeComponent::factory()->create([
        'user_id' => $user->id,
        'component_id' => $component->id,
        'effective_at' => '2026-01-01',
        'end_at' => '2026-06-30',
    ]);

    expect(fn () => EmployeeComponent::factory()->create([
        'user_id' => $user->id,
        'component_id' => $component->id,
        'effective_at' => '2026-06-30',
        'end_at' => null,
    ]))->toThrow(ValidationException::class);

    $nextPeriod = EmployeeComponent::factory()->create([
        'user_id' => $user->id,
        'component_id' => $component->id,
        'effective_at' => '2026-07-01',
        'end_at' => null,
    ]);

    expect($nextPeriod->exists)->toBeTrue();
});

it('copies component name and type into immutable payroll item snapshots', function () {
    $component = PayrollComponent::factory()->create([
        'name' => 'Late Deduction',
        'type' => PayrollComponent::TYPE_DEDUCTION,
    ]);
    $snapshot = PayrollItemComponent::factory()->create([
        'payroll_item_id' => PayrollItemFactorySupport::createId(),
        'component_id' => $component->id,
        'name_snapshot' => 'Tampered name',
        'type' => PayrollComponent::TYPE_EARNING,
    ]);
    $snapshot->amount = 999;

    expect($snapshot->name_snapshot)->toBe('Late Deduction')
        ->and($snapshot->type)->toBe(PayrollComponent::TYPE_DEDUCTION)
        ->and(fn () => $snapshot->save())->toThrow(LogicException::class)
        ->and(fn () => $snapshot->delete())->toThrow(LogicException::class);
});

it('enforces unique BPJS program effective dates', function () {
    $rate = BpjsRate::factory()->create([
        'program' => 'jht',
        'effective_at' => '2026-01-01',
    ]);

    expect(fn () => BpjsRate::factory()->create([
        'program' => 'jht',
        'effective_at' => $rate->effective_at,
    ]))->toThrow(QueryException::class);
});

it('preserves payroll snapshots when their source reference is deleted', function () {
    $component = PayrollComponent::factory()->create();
    $rate = BpjsRate::factory()->create();
    $payrollItemId = PayrollItemFactorySupport::createId();
    $componentSnapshot = PayrollItemComponent::factory()->create([
        'payroll_item_id' => $payrollItemId,
        'component_id' => $component->id,
    ]);
    $bpjsSnapshot = PayrollItemBpjs::factory()->create([
        'payroll_item_id' => $payrollItemId,
        'bpjs_rate_id' => $rate->id,
    ]);

    $component->delete();
    $rate->delete();

    expect($componentSnapshot->fresh())->not->toBeNull()
        ->and($componentSnapshot->fresh()->component_id)->toBeNull()
        ->and($componentSnapshot->fresh()->name_snapshot)->toBe($component->name)
        ->and($bpjsSnapshot->fresh())->not->toBeNull()
        ->and($bpjsSnapshot->fresh()->bpjs_rate_id)->toBeNull();
});

it('seeds only existing payroll items and official BPJS references idempotently', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    $health = BpjsRate::query()->where('program', 'kesehatan')->firstOrFail();
    $jht = BpjsRate::query()->where('program', 'jht')->firstOrFail();
    $jp = BpjsRate::query()->where('program', 'jp')->firstOrFail();
    $jkk = BpjsRate::query()->where('program', 'jkk')->firstOrFail();
    $jkm = BpjsRate::query()->where('program', 'jkm')->firstOrFail();
    $components = PayrollComponent::query()->orderBy('code')->get();

    expect($components->pluck('code')->all())->toBe([
        'gross_pay',
        'late_deduction',
        'other_deductions',
        'overtime',
    ])
        ->and($components->every(fn (PayrollComponent $component): bool => ! $component->is_active))->toBeTrue()
        ->and($components->every(fn (PayrollComponent $component): bool => $component->is_taxable === null))->toBeTrue()
        ->and($components->every(fn (PayrollComponent $component): bool => $component->is_bpjs_base === null))->toBeTrue()
        ->and(BpjsRate::query()->count())->toBe(5)
        ->and($health->employee_rate)->toBe('0.0100')
        ->and($health->employer_rate)->toBe('0.0400')
        ->and($health->salary_cap)->toBe(12000000)
        ->and($health->effective_at->toDateString())->toBe('2020-07-01')
        ->and($jht->employee_rate)->toBe('0.0200')
        ->and($jht->employer_rate)->toBe('0.0370')
        ->and($jht->effective_at->toDateString())->toBe('2015-07-01')
        ->and($jp->employee_rate)->toBe('0.0100')
        ->and($jp->employer_rate)->toBe('0.0200')
        ->and($jp->salary_cap)->toBeNull()
        ->and($jkk->employee_rate)->toBe('0.0000')
        ->and($jkk->employer_rate)->toBeNull()
        ->and($jkm->employee_rate)->toBe('0.0000')
        ->and($jkm->employer_rate)->toBe('0.0030')
        ->and(BpjsRate::query()->where('program', 'jkp')->exists())->toBeFalse();
});

it('preserves manually filled BPJS caps and rates when the reference seeder runs again', function () {
    $this->seed(ReferenceSeeder::class);
    BpjsRate::query()
        ->where('program', 'jp')
        ->update(['salary_cap' => 10500000]);
    BpjsRate::query()
        ->where('program', 'jkk')
        ->update(['employer_rate' => 0.0054]);

    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    expect(BpjsRate::query()->where('program', 'jp')->value('salary_cap'))->toBe(10500000)
        ->and(BpjsRate::query()->where('program', 'jkk')->firstOrFail()->employer_rate)->toBe('0.0054');
});
