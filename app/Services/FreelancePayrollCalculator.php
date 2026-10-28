<?php

namespace App\Services;

use Carbon\Carbon;

class FreelancePayrollCalculator
{
    public const DAILY_RATE = 120_000;

    public const LATE_BLOCK_MINUTES = 30;

    public const LATE_BLOCK_AMOUNT = 7_500;

    public static function lateMinutesFromCheckIn(Carbon $checkIn): int
    {
        $shiftStart = $checkIn->copy()->setTime(8, 0, 0);
        if (! $checkIn->greaterThan($shiftStart)) {
            return 0;
        }

        $breakStart = $checkIn->copy()->setTime(12, 0, 0);
        $breakEnd = $checkIn->copy()->setTime(13, 0, 0);
        $lateSeconds = $checkIn->getTimestamp() - $shiftStart->getTimestamp();
        $breakOverlapStart = max($shiftStart->getTimestamp(), $breakStart->getTimestamp());
        $breakOverlapEnd = min($checkIn->getTimestamp(), $breakEnd->getTimestamp());
        $breakSeconds = max(0, $breakOverlapEnd - $breakOverlapStart);

        return intdiv(max(0, $lateSeconds - $breakSeconds), 60);
    }

    /**
     * @return array{
     *     gross_pay: int,
     *     penalty_blocks: int,
     *     charged_late_minutes: int,
     *     late_deduction: int,
     *     net_pay: int
     * }
     */
    public static function calculateDay(int $lateMinutes): array
    {
        $penaltyBlocks = intdiv(max(0, $lateMinutes), self::LATE_BLOCK_MINUTES);
        $lateDeduction = $penaltyBlocks * self::LATE_BLOCK_AMOUNT;

        return [
            'gross_pay' => self::DAILY_RATE,
            'penalty_blocks' => $penaltyBlocks,
            'charged_late_minutes' => $penaltyBlocks * self::LATE_BLOCK_MINUTES,
            'late_deduction' => $lateDeduction,
            'net_pay' => max(0, self::DAILY_RATE - $lateDeduction),
        ];
    }
}
