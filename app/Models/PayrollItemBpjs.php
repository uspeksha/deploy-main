<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemBpjs extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'bpjs_rate_id',
        'employee_amount',
        'employer_amount',
    ];

    protected function casts(): array
    {
        return [
            'employee_amount' => 'integer',
            'employer_amount' => 'integer',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function bpjsRate(): BelongsTo
    {
        return $this->belongsTo(BpjsRate::class);
    }
}
