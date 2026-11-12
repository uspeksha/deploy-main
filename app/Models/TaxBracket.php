<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TaxBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_income',
        'max_income',
        'rate',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'min_income' => 'integer',
            'max_income' => 'integer',
            'rate' => 'decimal:4',
            'effective_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $bracket): void {
            $minimumIncome = (float) $bracket->getAttribute('min_income');

            if ($minimumIncome < 0 || (float) $bracket->rate < 0 || (float) $bracket->rate > 1) {
                throw new InvalidArgumentException('Tax bracket income and rate are outside valid bounds.');
            }

            if ($bracket->max_income !== null && $bracket->max_income < $bracket->min_income) {
                throw new InvalidArgumentException('Tax bracket maximum income must not be less than its minimum.');
            }
        });
    }

    public function scopeEffectiveOn(Builder $query, DateTimeInterface|string $date): Builder
    {
        $effectiveDate = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : Carbon::parse($date)->toDateString();

        return $query->whereRaw(
            'DATE(tax_brackets.effective_at) = (SELECT MAX(DATE(effective_tax_brackets.effective_at))
                FROM tax_brackets AS effective_tax_brackets
                WHERE DATE(effective_tax_brackets.effective_at) <= DATE(?))',
            [$effectiveDate],
        );
    }
}
