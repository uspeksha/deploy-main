<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rank_order',
    ];

    protected function casts(): array
    {
        return [
            'rank_order' => 'integer',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
