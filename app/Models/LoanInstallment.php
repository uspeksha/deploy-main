<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class LoanInstallment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'loan_id',
        'payroll_item_id',
        'installment_no',
        'amount',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'installment_no' => 'integer',
            'amount' => 'integer',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $installment): void {
            if (! in_array($installment->status, [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
            ], true)) {
                throw new InvalidArgumentException('Unsupported loan installment status.');
            }
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
