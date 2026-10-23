<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'year',
        'month',
        'period_number',
        'revision',
        'revision_of_id',
        'correction_reason',
        'start_date',
        'end_date',
        'status',
        'generated_by',
        'approved_by',
        'submitted_at',
        'approved_at',
        'paid_by',
        'paid_at',
        'payment_reference',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function previousRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revision_of_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(PayrollCorrection::class);
    }

    public function getLabelAttribute(): string
    {
        $label = sprintf(
            '%s %d - Minggu %d (%s–%s)',
            Carbon::create($this->year, $this->month, 1)->translatedFormat('F'),
            $this->year,
            $this->period_number,
            $this->start_date->format('d M'),
            $this->end_date->format('d M'),
        );

        return $this->revision > 1 ? $label.' - Dibuat Ulang' : $label;
    }
}
