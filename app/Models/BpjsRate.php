<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class BpjsRate extends Model
{
    use HasFactory;

    public const PROGRAMS = [
        'kesehatan',
        'jht',
        'jp',
        'jkk',
        'jkm',
    ];

    protected $fillable = [
        'program',
        'employee_rate',
        'employer_rate',
        'salary_cap',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'salary_cap' => 'integer',
            'effective_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $rate): void {
            if (! in_array($rate->program, self::PROGRAMS, true)) {
                throw new InvalidArgumentException('Unsupported BPJS program.');
            }
        });
    }

    public function payrollItemBpjs(): HasMany
    {
        return $this->hasMany(PayrollItemBpjs::class);
    }
}
