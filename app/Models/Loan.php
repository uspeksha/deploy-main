<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 */
class Loan extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'approved_by',
        'principal',
        'tenor_months',
        'purpose',
        'rejection_reason',
        'approved_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'integer',
            'tenor_months' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $loan): void {
            if (! in_array($loan->status, [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
            ], true)) {
                throw new InvalidArgumentException('Unsupported loan status.');
            }
        });
    }

    /**
     * @return HasMany<LoanInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function installmentsTotalMatchesPrincipal(): bool
    {
        return (int) $this->installments()->sum('amount') === (int) $this->principal;
    }
}
