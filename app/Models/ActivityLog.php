<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $user_id
 * @property-read User|null $user
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject',
        'details',
        'ip_address',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper log function
     */
    public static function log($action, $subject = null, $details = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject' => $subject,
            'details' => is_array($details) ? json_encode($details) : $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
