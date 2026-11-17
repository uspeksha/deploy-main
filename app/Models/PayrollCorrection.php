<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollCorrection extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'recorded_by',
        'adjustment_amount',
        'reason',
        'status',
        'settled_by',
        'settled_at',
        'settlement_reference',
        'settlement_note',
    ];

    protected function casts(): array
    {
        return [
            'settled_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }
}
