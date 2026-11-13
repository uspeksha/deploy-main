<?php

namespace Tests\Unit;

use App\Models\FcmDevice;
use App\Models\Notification;
use App\Services\FirebaseMessagingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirebaseMessagingServiceTest extends TestCase
{
    public function test_it_authenticates_and_sends_a_notification_using_fcm_http_v1(): void
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $privateKey));

        $credentialsPath = tempnam(sys_get_temp_dir(), 'fcm-test-');
        $this->assertNotFalse($credentialsPath);
        file_put_contents($credentialsPath, json_encode([
            'client_email' => 'firebase-test@example.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_THROW_ON_ERROR));

        try {
            config([
                'services.firebase.project_id' => 'test-project',
                'services.firebase.credentials' => $credentialsPath,
            ]);
            Http::fake([
                'https://oauth2.googleapis.com/token' => Http::response([
                    'access_token' => 'test-access-token',
                ]),
                'https://fcm.googleapis.com/v1/projects/test-project/messages:send' => Http::response([
                    'name' => 'projects/test-project/messages/123',
                ]),
            ]);

            $notification = new Notification([
                'title' => 'Slip gaji tersedia',
                'message' => 'Slip gaji sudah tersedia.',
                'type' => 'payroll_slip',
                'target_page' => '/notifications',
                'target_id' => '42',
            ]);
            $device = new FcmDevice(['token' => 'test-device-token']);

            $this->assertTrue(app(FirebaseMessagingService::class)->send($notification, $device));

            Http::assertSent(function (Request $request): bool {
                return str_contains($request->url(), '/messages:send')
                    && $request->hasHeader('Authorization', 'Bearer test-access-token')
                    && $request['message']['token'] === 'test-device-token'
                    && $request['message']['notification']['title'] === 'Slip gaji tersedia'
                    && $request['message']['data']['target_id'] === '42';
            });
        } finally {
            unlink($credentialsPath);
        }
    }
}
