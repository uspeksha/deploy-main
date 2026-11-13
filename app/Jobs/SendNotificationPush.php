<?php

namespace App\Jobs;

use App\Models\FcmDevice;
use App\Models\Notification;
use App\Services\FirebaseMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120];

    public function __construct(public readonly int $notificationId) {}

    public function handle(FirebaseMessagingService $messaging): void
    {
        $notification = Notification::query()->findOrFail($this->notificationId);
        $devices = FcmDevice::query()
            ->where('user_id', $notification->user_id)
            ->get();

        if ($devices->isEmpty()) {
            return;
        }

        if (! $messaging->isConfigured()) {
            Log::error('Push notification was not sent because Firebase credentials are not configured.', [
                'notification_id' => $notification->id,
            ]);

            return;
        }

        foreach ($devices as $device) {
            if (! $messaging->send($notification, $device)) {
                $device->delete();
            }
        }
    }
}
