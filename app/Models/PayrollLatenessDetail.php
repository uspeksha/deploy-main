<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLatenessDetail extends Model
{
    protected $fillable = [
        'payroll_item_id',
        'attendance_id',
        'work_date',
        'late_minutes',
        'penalty_blocks',
        'deduction_amount',
        'lateness_source',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
