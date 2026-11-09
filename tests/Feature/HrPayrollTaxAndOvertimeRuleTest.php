<?php

use App\Models\OvertimeRule;
use App\Models\PayrollItemTax;
use App\Models\PtkpStatus;
use App\Models\TaxBracket;
use Database\Factories\PayrollItemFactorySupport;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relates payroll tax snapshots and creates global overtime rules', function () {
    $itemId = PayrollItemFactorySupport::createId();
    $ptkp = PtkpStatus::factory()->create([
        'code' => 'TK/0',
        'effective_at' => '2022-01-01',
    ]);
    $tax = PayrollItemTax::factory()->create([
        'payroll_item_id' => $itemId,
        'ptkp_status_id' => $ptkp->id,
    ]);
    $rule = OvertimeRule::factory()->create();

    expect($tax->payrollItem->tax->is($tax))->toBeTrue()
        ->and($tax->ptkpStatus->is($ptkp))->toBeTrue()
        ->and($ptkp->payrollItemTaxes->contains($tax))->toBeTrue()
        ->and($rule->day_type)->toBe(OvertimeRule::DAY_TYPE_WEEKDAY);
});

it('validates overtime day types and tax method labels', function () {
    expect(fn () => OvertimeRule::factory()->create(['day_type' => 'sunday']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => PayrollItemTax::factory()->create(['method' => 'monthly_ter']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => OvertimeRule::factory()->create([
            'hour_from' => 5.00,
            'hour_to' => 4.00,
        ]))->toThrow(InvalidArgumentException::class);
});

it('enforces a single tax snapshot per payroll item', function () {
    $itemId = PayrollItemFactorySupport::createId();
    PayrollItemTax::factory()->create(['payroll_item_id' => $itemId]);

    expect(fn () => PayrollItemTax::factory()->create(['payroll_item_id' => $itemId]))
        ->toThrow(QueryException::class);
});

it('enforces unique tax bracket minimum income per effective date', function () {
    TaxBracket::factory()->create([
        'min_income' => 60000000,
        'effective_at' => '2022-01-01',
    ]);

    expect(fn () => TaxBracket::factory()->create([
        'min_income' => 60000000,
        'effective_at' => '2022-01-01',
    ]))->toThrow(QueryException::class);
});

it('keeps payroll item tax snapshots append-only', function () {
    $tax = PayrollItemTax::factory()->create();
    $tax->pph21_amount = 1;

    expect(fn () => $tax->save())->toThrow(LogicException::class)
        ->and(fn () => $tax->delete())->toThrow(LogicException::class);
});

it('returns the active tax bracket set for a requested date', function () {
    $current = TaxBracket::factory()->create([
        'min_income' => 0,
        'max_income' => 60000000,
        'rate' => 0.0500,
        'effective_at' => '2022-01-01',
    ]);
    TaxBracket::factory()->create([
        'min_income' => 0,
        'max_income' => 60000000,
        'rate' => 0.0600,
        'effective_at' => '2027-01-01',
    ]);

    expect(TaxBracket::query()->effectiveOn('2026-12-31')->first()?->is($current))->toBeTrue()
        ->and(TaxBracket::query()->effectiveOn('2027-01-01')->first()?->rate)->toBe('0.0600');
});

it('seeds verified annual tax brackets idempotently and no overtime rules', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    $brackets = TaxBracket::query()->effectiveOn('2026-12-31')
        ->orderBy('min_income')
        ->get();

    expect($brackets)->toHaveCount(5)
        ->and($brackets->map(fn (TaxBracket $bracket): array => [
            $bracket->min_income,
            $bracket->max_income,
            $bracket->rate,
            $bracket->effective_at->toDateString(),
        ])->all())->toBe([
            [0, 60000000, '0.0500', '2022-01-01'],
            [60000001, 250000000, '0.1500', '2022-01-01'],
            [250000001, 500000000, '0.2500', '2022-01-01'],
            [500000001, 5000000000, '0.3000', '2022-01-01'],
            [5000000001, null, '0.3500', '2022-01-01'],
        ])
        ->and(TaxBracket::query()->count())->toBe(5)
        ->and(OvertimeRule::query()->count())->toBe(0);
});
