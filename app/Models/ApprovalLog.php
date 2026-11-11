<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class ApprovalLog extends Model
{
    use HasFactory;

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    protected $fillable = [
        'approval_flow_step_id',
        'approver_id',
        'subject_type',
        'subject_id',
        'action',
        'note',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $log): void {
            if (! ApprovalSubjectType::tryFrom((string) $log->subject_type)) {
                throw new InvalidArgumentException('Unsupported approval subject type.');
            }

            if (! in_array($log->action, [self::ACTION_APPROVED, self::ACTION_REJECTED], true)) {
                throw new InvalidArgumentException('Unsupported approval action.');
            }
        });
    }

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Approval logs are append-only and cannot be updated.');
    }

    protected function performDeleteOnModel(): void
    {
        throw new LogicException('Approval logs are append-only and cannot be deleted.');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlowStep::class, 'approval_flow_step_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
