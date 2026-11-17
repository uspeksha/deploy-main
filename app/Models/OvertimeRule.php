<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class OvertimeRule extends Model
{
    use HasFactory;

    public const DAY_TYPE_WEEKDAY = 'weekday';

    public const DAY_TYPE_WEEKEND = 'weekend';

    public const DAY_TYPE_HOLIDAY = 'holiday';

    protected $fillable = [
        'day_type',
        'hour_from',
        'hour_to',
        'multiplier',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'hour_from' => 'decimal:2',
            'hour_to' => 'decimal:2',
            'multiplier' => 'decimal:2',
            'effective_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $rule): void {
            if (! in_array($rule->day_type, [
                self::DAY_TYPE_WEEKDAY,
                self::DAY_TYPE_WEEKEND,
                self::DAY_TYPE_HOLIDAY,
            ], true)) {
                throw new InvalidArgumentException('Unsupported overtime day type.');
            }

            if ($rule->hour_to !== null && $rule->hour_to < $rule->hour_from) {
                throw new InvalidArgumentException('Overtime upper hour must not be less than its lower hour.');
            }
        });
    }
}
