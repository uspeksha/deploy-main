<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property Carbon|null $effective_at
 * @property Carbon|null $end_at
 */
class EmployeeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'component_id',
        'amount',
        'effective_at',
        'end_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'effective_at' => 'date',
            'end_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            if ($assignment->effective_at === null) {
                throw ValidationException::withMessages([
                    'effective_at' => 'An effective date is required for employee components.',
                ]);
            }

            if ($assignment->end_at !== null && $assignment->end_at->lt($assignment->effective_at)) {
                throw ValidationException::withMessages([
                    'end_at' => 'The end date must be on or after the effective date.',
                ]);
            }

            $overlaps = static::query()
                ->where('user_id', $assignment->user_id)
                ->where('component_id', $assignment->component_id)
                ->whereDate('effective_at', '<=', $assignment->end_at?->toDateString() ?? '9999-12-31')
                ->where(function ($query) use ($assignment): void {
                    $query->whereNull('end_at')
                        ->orWhereDate('end_at', '>=', $assignment->effective_at->toDateString());
                })
                ->when($assignment->exists, fn ($query) => $query->whereKeyNot($assignment->getKey()))
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'effective_at' => 'This employee already has an overlapping assignment for the component.',
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'component_id');
    }
}
