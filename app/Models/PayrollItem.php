<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $employee_id
 * @property string $employee_name
 * @property string|null $department
 * @property int $daily_rate
 * @property int $gross_pay
 * @property int $late_deduction
 * @property int $other_deductions
 * @property array<string, mixed> $attendance_snapshot
 * @property-read int|string $daily_rate_summary
 * @property-read User|null $employee
 * @property-read Collection<int, PayrollLatenessDetail> $latenessDetails
 */
class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'user_id',
        'employee_id',
        'employee_name',
        'employment_type',
        'department',
        'work_days',
        'daily_rate',
        'gross_pay',
        'late_occurrences',
        'total_late_minutes',
        'late_deduction',
        'other_deductions',
        'net_pay',
        'attendance_snapshot',
        'slip_number',
    ];

    protected function casts(): array
    {
        return [
            'attendance_snapshot' => 'array',
        ];
    }

    public function getDailyRateSummaryAttribute(): int|string
    {
        $rates = collect($this->attendance_snapshot['daily_details'] ?? [])
            ->pluck('daily_rate')
            ->filter(fn ($rate): bool => $rate !== null)
            ->unique()
            ->values();

        return match ($rates->count()) {
            0 => (int) $this->daily_rate,
            1 => (int) $rates->first(),
            default => 'Tarif',
        };
    }

    public function currentDailyRate(): int|string
    {
        if ($this->employment_type !== 'freelance') {
            return $this->daily_rate_summary;
        }

        $this->loadMissing('period:id,status');
        if ($this->period?->status === PayrollPeriod::STATUS_PAID) {
            return $this->daily_rate_summary;
        }

        $this->loadMissing('employee.salaryRates');

        return $this->employee?->dailyRateAt() ?? $this->daily_rate_summary;
    }

    public function currentGrossPay(): int
    {
        if ($this->employment_type !== 'freelance') {
            return (int) $this->gross_pay;
        }

        $this->loadMissing('period:id,status');
        if ($this->period?->status === PayrollPeriod::STATUS_PAID) {
            return (int) $this->gross_pay;
        }

        $this->loadMissing('employee.salaryRates');
        if (! $this->employee) {
            return (int) $this->gross_pay;
        }

        return $this->employee->dailyRateAt() * (int) $this->work_days;
    }

    public function currentNetPay(): int
    {
        if ($this->employment_type !== 'freelance') {
            return (int) $this->net_pay;
        }

        $this->loadMissing('period:id,status');
        if ($this->period?->status === PayrollPeriod::STATUS_PAID) {
            return (int) $this->net_pay;
        }

        $this->loadMissing('employee.salaryRates');
        if (! $this->employee) {
            return (int) $this->net_pay;
        }

        return max(
            0,
            $this->currentGrossPay() - (int) $this->late_deduction - (int) $this->other_deductions,
        );
    }

    /**
     * @return BelongsTo<PayrollPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<PayrollLatenessDetail, $this>
     */
    public function latenessDetails(): HasMany
    {
        return $this->hasMany(PayrollLatenessDetail::class);
    }

    public function componentSnapshots(): HasMany
    {
        return $this->hasMany(PayrollItemComponent::class);
    }

    public function bpjsSnapshots(): HasMany
    {
        return $this->hasMany(PayrollItemBpjs::class);
    }

    public function tax(): HasOne
    {
        return $this->hasOne(PayrollItemTax::class);
    }

    public function loanInstallments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }
}
