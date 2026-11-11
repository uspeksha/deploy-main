<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtkpStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'annual_amount',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'annual_amount' => 'integer',
            'effective_at' => 'date',
        ];
    }

    public function scopeEffectiveOn(Builder $query, DateTimeInterface|string $date): Builder
    {
        $effectiveDate = $date instanceof DateTimeInterface
            ? $date->format('Y-m-d')
            : Carbon::parse($date)->toDateString();

        return $query->whereRaw(
            'DATE(ptkp_statuses.effective_at) = (SELECT MAX(DATE(ptkp_effective.effective_at))
                FROM ptkp_statuses AS ptkp_effective
                WHERE ptkp_effective.code = ptkp_statuses.code
                    AND DATE(ptkp_effective.effective_at) <= DATE(?))',
            [$effectiveDate],
        );
    }

    public function employeeProfiles(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function payrollItemTaxes(): HasMany
    {
        return $this->hasMany(PayrollItemTax::class);
    }
}
