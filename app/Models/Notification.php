<?php

namespace App\Models;

use App\Jobs\SendNotificationPush;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (self $notification): void {
            SendNotificationPush::dispatch($notification->id)->afterCommit();
        });
    }

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'target_page',
        'target_id',
        'is_read',
    ];

    // Relasi ke user (optional tapi bagus ditambah)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
