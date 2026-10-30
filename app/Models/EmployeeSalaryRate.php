<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryRate extends Model
{
    public const LEGACY_DAILY_RATE = 120000;

    protected $fillable = [
        'user_id',
        'daily_rate',
        'effective_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
            'effective_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
