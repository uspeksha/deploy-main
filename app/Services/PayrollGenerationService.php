<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollGenerationService
{
    public const DAILY_RATE = FreelancePayrollCalculator::DAILY_RATE;

    public const LATE_BLOCK_MINUTES = FreelancePayrollCalculator::LATE_BLOCK_MINUTES;

    public const LATE_BLOCK_AMOUNT = FreelancePayrollCalculator::LATE_BLOCK_AMOUNT;

    public function createPeriod(
        int $year,
        int $month,
        int $periodNumber,
        int $actorId,
        int $revision = 1,
        ?int $revisionOfId = null,
        ?string $correctionReason = null,
    ): PayrollPeriod {
        [$startDate, $endDate] = $this->periodBounds($year, $month, $periodNumber);

        return DB::transaction(function () use ($year, $month, $periodNumber, $startDate, $endDate, $actorId, $revision, $revisionOfId, $correctionReason) {
            $period = PayrollPeriod::create([
                'year' => $year,
                'month' => $month,
                'period_number' => $periodNumber,
                'revision' => $revision,
                'revision_of_id' => $revisionOfId,
                'correction_reason' => $correctionReason,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => PayrollPeriod::STATUS_DRAFT,
                'generated_by' => $actorId,
            ]);

            User::query()
                ->where('employment_type', 'freelance')
                ->where('registration_status', 'approved')
                ->whereNotIn('role', ['hrd', 'admin', 'super_admin', 'manager', 'supervisor', 'hrga', 'pkl'])
                ->orderBy('id')
                ->chunkById(100, function (Collection $employees) use ($period): void {
                    foreach ($employees as $employee) {
                        $this->createItemSnapshot($period, $employee);
                    }
                });

            return $period->load('items');
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodBounds(int $year, int $month, int $periodNumber): array
    {
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12 || ! in_array($periodNumber, [1, 2], true)) {
            throw new \InvalidArgumentException('Payroll period is invalid.');
        }

        $start = Carbon::create($year, $month, $periodNumber === 1 ? 1 : 16)->startOfDay();
        $end = $periodNumber === 1
            ? Carbon::create($year, $month, 15)->endOfDay()
            : Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    /**
     * @param  array<int, int>  $lateMinutesByWorkday
     * @return array{
     *     gross_pay: int,
     *     late_occurrences: int,
     *     total_late_minutes: int,
     *     total_penalty_blocks: int,
     *     late_deduction: int,
     *     other_deductions: int,
     *     net_pay: int
     * }
     */
    public function calculateTotals(int $workDays, array $lateMinutesByWorkday): array
    {
        $lateMinutesByWorkday = array_map(fn (int $minutes): int => max(0, $minutes), $lateMinutesByWorkday);
        $lateOccurrences = count(array_filter($lateMinutesByWorkday, fn (int $minutes): bool => $minutes > 0));
        $totalLateMinutes = array_sum($lateMinutesByWorkday);
        $totalPenaltyBlocks = array_sum(array_map(
            fn (int $minutes): int => FreelancePayrollCalculator::calculateDay($minutes)['penalty_blocks'],
            $lateMinutesByWorkday,
        ));
        $grossPay = $workDays * self::DAILY_RATE;
        $lateDeduction = $totalPenaltyBlocks * self::LATE_BLOCK_AMOUNT;

        return [
            'gross_pay' => $grossPay,
            'late_occurrences' => $lateOccurrences,
            'total_late_minutes' => $totalLateMinutes,
            'total_penalty_blocks' => $totalPenaltyBlocks,
            'late_deduction' => $lateDeduction,
            'other_deductions' => 0,
            'net_pay' => $grossPay - $lateDeduction,
        ];
    }

    private function createItemSnapshot(PayrollPeriod $period, User $employee): void
    {
        $attendances = Absensi::query()
            ->with('children')
            ->where('user_id', $employee->id)
            ->where('status', 'hadir')
            ->where('status_approval', 'approved')
            ->whereNull('parent_id')
            ->where('is_mocked', false)
            ->whereDate('check_in_at', '>=', $period->start_date->format('Y-m-d'))
            ->whereDate('check_in_at', '<=', $period->end_date->format('Y-m-d'))
            ->whereNotNull('check_in_at')
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get()
            ->unique(fn (Absensi $attendance): string => Carbon::parse($attendance->check_in_at)
                ->timezone(config('app.timezone'))
                ->toDateString())
            ->values();

        $attendanceDates = [];
        $dailyDetails = [];
        $lateRows = [];
        $lateMinutesByWorkday = [];

        foreach ($attendances as $attendance) {
            $workDate = Carbon::parse($attendance->check_in_at)
                ->timezone(config('app.timezone'))
                ->toDateString();
            $lateMinutes = max(0, (int) ($attendance->late_minutes ?? 0));

            $attendanceDates[] = $workDate;
            $overtimeRecords = $attendance->children
                ->where('tipe', 'lembur');
            $approvedOvertime = $overtimeRecords
                ->where('status_approval', 'approved')
                ->values();
            $dailyDetails[] = [
                'work_date' => $workDate,
                'attendance_status' => $attendance->status,
                'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
                'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
                'late_minutes' => $lateMinutes,
                'overtime_status' => $approvedOvertime->isNotEmpty()
                    ? 'approved'
                    : ($overtimeRecords->isNotEmpty() ? 'pending' : null),
                'overtime_start' => $approvedOvertime->pluck('lembur_start')->filter()->map(fn ($time) => Carbon::parse($time)->toDateTimeString())->all(),
                'overtime_end' => $approvedOvertime->pluck('lembur_end')->filter()->map(fn ($time) => Carbon::parse($time)->toDateTimeString())->all(),
                'overtime_rest' => $approvedOvertime->contains(fn (Absensi $record): bool => (bool) $record->lembur_rest),
                'overtime_minutes' => $approvedOvertime->isNotEmpty()
                    ? (int) $approvedOvertime->sum('overtime_minutes')
                    : ($overtimeRecords->isNotEmpty() ? 0 : (int) ($attendance->overtime_minutes ?? 0)),
                'overtime_pay' => $approvedOvertime->isNotEmpty()
                    ? (float) $approvedOvertime->sum('overtime_pay')
                    : ($overtimeRecords->isNotEmpty() ? 0 : (float) ($attendance->overtime_pay ?? 0)),
            ];

            if ($lateMinutes === 0) {
                continue;
            }

            $penaltyBlocks = intdiv($lateMinutes, self::LATE_BLOCK_MINUTES);
            $lateMinutesByWorkday[] = $lateMinutes;
            $lateRows[] = [
                'attendance_id' => $attendance->id,
                'work_date' => $workDate,
                'late_minutes' => $lateMinutes,
                'penalty_blocks' => $penaltyBlocks,
                'deduction_amount' => $penaltyBlocks * self::LATE_BLOCK_AMOUNT,
                'lateness_source' => 'stored_late_minutes_08_00_baseline',
            ];
        }

        $workDays = count($attendanceDates);
        $totals = $this->calculateTotals($workDays, $lateMinutesByWorkday);
        $slipNumber = sprintf(
            'FM-%04d%02d-W%d-%06d',
            $period->year,
            $period->month,
            $period->period_number,
            $employee->id,
        );
        if ($period->revision > 1) {
            $slipNumber .= sprintf('-R%d', $period->revision);
        }

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'user_id' => $employee->id,
            'employee_id' => $employee->id_karyawan,
            'employee_name' => $employee->name,
            'employment_type' => $employee->employment_type,
            'department' => $employee->departemen,
            'work_days' => $workDays,
            'daily_rate' => self::DAILY_RATE,
            'gross_pay' => $totals['gross_pay'],
            'late_occurrences' => $totals['late_occurrences'],
            'total_late_minutes' => $totals['total_late_minutes'],
            'late_deduction' => $totals['late_deduction'],
            'other_deductions' => $totals['other_deductions'],
            'net_pay' => $totals['net_pay'],
            'attendance_snapshot' => [
                'attendance_ids' => $attendances->pluck('id')->all(),
                'work_dates' => $attendanceDates,
                'daily_details' => $dailyDetails,
                'late_minutes_source' => 'stored_late_minutes_08_00_baseline',
            ],
            'slip_number' => $slipNumber,
        ]);

        foreach ($lateRows as $lateRow) {
            $item->latenessDetails()->create($lateRow);
        }
    }
}
