<?php

use App\Services\PayrollGenerationService;

it('creates non-overlapping first and second half-month payroll periods', function () {
    $service = new PayrollGenerationService;

    [$firstStart, $firstEnd] = $service->periodBounds(2026, 10, 1);
    [$secondStart, $secondEnd] = $service->periodBounds(2026, 10, 2);

    expect($firstStart->toDateString())->toBe('2026-10-01')
        ->and($firstEnd->toDateString())->toBe('2026-10-15')
        ->and($secondStart->toDateString())->toBe('2026-10-16')
        ->and($secondEnd->toDateString())->toBe('2026-10-31')
        ->and($firstEnd->lt($secondStart))->toBeTrue();
});

it('uses the actual last day for February and 30-day months', function () {
    $service = new PayrollGenerationService;

    [, $februaryEnd] = $service->periodBounds(2027, 2, 2);
    [, $leapFebruaryEnd] = $service->periodBounds(2028, 2, 2);
    [, $aprilEnd] = $service->periodBounds(2026, 4, 2);

    expect($februaryEnd->toDateString())->toBe('2027-02-28')
        ->and($leapFebruaryEnd->toDateString())->toBe('2028-02-29')
        ->and($aprilEnd->toDateString())->toBe('2026-04-30');
});

it('rejects invalid payroll periods', function () {
    (new PayrollGenerationService)->periodBounds(2026, 13, 1);
})->throws(InvalidArgumentException::class);

it('calculates daily pay and full 30-minute late blocks on the backend', function () {
    $service = new PayrollGenerationService;
    $tenDays = $service->calculateTotals(10, []);
    $twelveDays = $service->calculateTotals(12, []);
    $lateBoundaries = $service->calculateTotals(5, [29, 30, 59, 60, 90]);

    expect($tenDays['gross_pay'])->toBe(1_200_000)
        ->and($twelveDays['gross_pay'])->toBe(1_440_000)
        ->and($lateBoundaries['total_penalty_blocks'])->toBe(7)
        ->and($lateBoundaries['late_deduction'])->toBe(52_500)
        ->and($lateBoundaries['net_pay'])->toBe(547_500);
});
