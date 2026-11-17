<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $file_path Path relative to the private filesystem disk.
 */
class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'doc_type',
        'file_path',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
