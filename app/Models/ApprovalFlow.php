<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ApprovalFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $flow): void {
            if (! ApprovalSubjectType::tryFrom((string) $flow->subject_type)) {
                throw new InvalidArgumentException('Unsupported approval subject type.');
            }
        });
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class);
    }
}
