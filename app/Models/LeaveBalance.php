<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'quota',
        'used',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quota' => 'decimal:2',
            'used' => 'decimal:2',
            'expired_at' => 'date',
        ];
    }

    public function remaining(): float
    {
        return (float) $this->quota - (float) $this->used;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeaveBalanceLog::class);
    }
}
