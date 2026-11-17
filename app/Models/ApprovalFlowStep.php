<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ApprovalFlowStep extends Model
{
    use HasFactory;

    public const APPROVER_ROLE = 'role';

    protected $fillable = [
        'approval_flow_id',
        'level',
        'approver_type',
        'role_id',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $step): void {
            if (
                $step->approver_type !== self::APPROVER_ROLE
                || $step->level !== 1
                || $step->role_id === null
                || ! Role::query()->whereKey($step->role_id)->where('name', 'hrd')->exists()
            ) {
                throw new InvalidArgumentException('Approval steps must have exactly one level-1 HRD role approver.');
            }
        });
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class);
    }
}
