<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class PayrollItemTax extends Model
{
    use HasFactory;

    public const METHOD_TER = 'ter';

    public const METHOD_PROGRESSIVE_ANNUAL = 'progressive_annual';

    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'payroll_item_id',
        'ptkp_status_id',
        'taxable_income',
        'pph21_amount',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'taxable_income' => 'integer',
            'pph21_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $tax): void {
            if (! in_array($tax->method, [
                self::METHOD_TER,
                self::METHOD_PROGRESSIVE_ANNUAL,
                self::METHOD_MANUAL,
            ], true)) {
                throw new InvalidArgumentException('Unsupported payroll tax method label.');
            }
        });
    }

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Payroll item tax snapshots are append-only and cannot be updated.');
    }

    protected function performDeleteOnModel(): void
    {
        throw new LogicException('Payroll item tax snapshots are append-only and cannot be deleted.');
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function ptkpStatus(): BelongsTo
    {
        return $this->belongsTo(PtkpStatus::class);
    }
}
