<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReimbursementType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'monthly_limit',
    ];

    protected function casts(): array
    {
        return [
            'monthly_limit' => 'integer',
        ];
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class, 'type_id');
    }
}
