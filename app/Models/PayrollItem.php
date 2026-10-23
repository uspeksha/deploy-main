<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function latenessDetails(): HasMany
    {
        return $this->hasMany(PayrollLatenessDetail::class);
    }
}
