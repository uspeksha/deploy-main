<?php

namespace App\Services;

class FreelancePayrollCalculator
{
    public const DAILY_RATE = 120_000;

    public const LATE_BLOCK_MINUTES = 30;

    public const LATE_BLOCK_AMOUNT = 7_500;

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
