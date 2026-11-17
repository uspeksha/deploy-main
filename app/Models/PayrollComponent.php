<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class PayrollComponent extends Model
{
    use HasFactory;

    public const TYPE_EARNING = 'earning';

    public const TYPE_DEDUCTION = 'deduction';

    public const CALC_FIXED = 'fixed';

    public const CALC_PERCENT = 'percent';

    protected $fillable = [
        'code',
        'name',
        'type',
        'calc_type',
        'is_taxable',
        'is_bpjs_base',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_bpjs_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $component): void {
            if (! in_array($component->type, [self::TYPE_EARNING, self::TYPE_DEDUCTION], true)) {
                throw new InvalidArgumentException('Unsupported payroll component type.');
            }

            if (! in_array($component->calc_type, [self::CALC_FIXED, self::CALC_PERCENT], true)) {
                throw new InvalidArgumentException('Unsupported payroll component calculation type.');
            }
        });
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeComponent::class, 'component_id');
    }

    public function itemComponents(): HasMany
    {
        return $this->hasMany(PayrollItemComponent::class, 'component_id');
    }
}
