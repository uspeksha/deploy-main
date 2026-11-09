<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class LeaveBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_balance_id',
        'absensi_id',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Leave balance logs are append-only and cannot be updated.');
    }

    protected function performDeleteOnModel(): void
    {
        throw new LogicException('Leave balance logs are append-only and cannot be deleted.');
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class, 'leave_balance_id');
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }
}
