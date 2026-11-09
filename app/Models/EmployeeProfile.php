<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ptkp_status_id',
        'nik_ktp',
        'npwp',
        'bpjs_kesehatan_no',
        'bpjs_ketenagakerjaan_no',
        'birth_date',
        'gender',
        'address',
        'bank_name',
        'bank_account_no',
    ];

    protected $hidden = [
        'nik_ktp',
        'npwp',
        'bpjs_kesehatan_no',
        'bpjs_ketenagakerjaan_no',
        'bank_account_no',
    ];

    protected function casts(): array
    {
        return [
            'nik_ktp' => 'encrypted',
            'npwp' => 'encrypted',
            'bpjs_kesehatan_no' => 'encrypted',
            'bpjs_ketenagakerjaan_no' => 'encrypted',
            'bank_account_no' => 'encrypted',
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ptkpStatus(): BelongsTo
    {
        return $this->belongsTo(PtkpStatus::class);
    }
}
