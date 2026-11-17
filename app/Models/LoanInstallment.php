<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property Carbon $due_date
 * @property Carbon|null $paid_at
 */
class LoanInstallment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'loan_id',
        'payroll_item_id',
        'installment_no',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'installment_no' => 'integer',
            'amount' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $installment): void {
            if (! in_array($installment->status, [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_PAID,
            ], true)) {
                throw new InvalidArgumentException('Unsupported loan installment status.');
            }
        });
    }

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
