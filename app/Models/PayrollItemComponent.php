<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class PayrollItemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'component_id',
        'name_snapshot',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $snapshot): void {
            if ($snapshot->component_id !== null) {
                $component = PayrollComponent::query()->findOrFail($snapshot->component_id);
                $snapshot->name_snapshot = $component->name;
                $snapshot->type = $component->type;
            }

            if (! in_array($snapshot->type, [PayrollComponent::TYPE_EARNING, PayrollComponent::TYPE_DEDUCTION], true)) {
                throw new InvalidArgumentException('Unsupported payroll component snapshot type.');
            }
        });
    }

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Payroll item component snapshots are append-only and cannot be updated.');
    }

    protected function performDeleteOnModel(): void
    {
        throw new LogicException('Payroll item component snapshots are append-only and cannot be deleted.');
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'component_id');
    }
}
