<?php

namespace App\Services;

use App\Models\FcmDevice;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class FirebaseMessagingService
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private ?string $accessToken = null;

    public function isConfigured(): bool
    {
        $path = config('services.firebase.credentials');

        return is_string($path)
            && $path !== ''
            && is_file($path)
            && is_readable($path)
            && is_string(config('services.firebase.project_id'))
            && config('services.firebase.project_id') !== '';
    }

    public function send(Notification $notification, FcmDevice $device): bool
    {
        $credentials = $this->credentials();
        $projectId = (string) config('services.firebase.project_id');
        if ($projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        $response = Http::timeout(15)
            ->withToken($this->accessToken($credentials))
            ->post(sprintf(
                'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                rawurlencode($projectId),
            ), [
                'message' => [
                    'token' => $device->token,
                    'notification' => [
                        'title' => $notification->title,
                        'body' => $notification->message,
                    ],
                    'data' => [
                        'type' => (string) ($notification->type ?? ''),
                        'target_page' => (string) ($notification->target_page ?? ''),
                        'target_id' => (string) ($notification->target_id ?? ''),
                    ],
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'channel_id' => 'high_importance_channel',
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        if ($response->json('error.status') === 'UNREGISTERED') {
            return false;
        }

        throw new RuntimeException(sprintf(
            'Firebase Cloud Messaging request failed with HTTP %d.',
            $response->status(),
        ));
    }

    /**
     * @return array{client_email: string, private_key: string, token_uri: string}
     */
    private function credentials(): array
    {
        $path = config('services.firebase.credentials');
        if (! is_string($path) || $path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Firebase service-account credentials are unavailable.');
        }

        try {
            $credentials = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Firebase service-account credentials are invalid JSON.', previous: $exception);
        }

        if (! is_array($credentials)
            || ! is_string($credentials['client_email'] ?? null)
            || ! is_string($credentials['private_key'] ?? null)
            || ! is_string($credentials['token_uri'] ?? null)
            || ! hash_equals(self::TOKEN_URI, $credentials['token_uri'])) {
            throw new RuntimeException('Firebase service-account credentials are missing required fields.');
        }

        return [
            'client_email' => $credentials['client_email'],
            'private_key' => $credentials['private_key'],
            'token_uri' => $credentials['token_uri'],
        ];
    }

    /**
     * @param  array{client_email: string, private_key: string, token_uri: string}  $credentials
     */
    private function accessToken(array $credentials): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $now = time();
        $assertion = $this->jwtAssertion($credentials, $now);
        $response = Http::asForm()
            ->timeout(15)
            ->post($credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new RuntimeException(sprintf(
                'Firebase OAuth token request failed with HTTP %d.',
                $response->status(),
            ));
        }

        return $this->accessToken = $response->json('access_token');
    }

    /**
     * @param  array{client_email: string, private_key: string, token_uri: string}  $credentials
     */
    private function jwtAssertion(array $credentials, int $now): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $signingInput = "{$header}.{$claims}";
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false || ! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Firebase service-account private key could not sign an OAuth assertion.');
        }

        return "{$signingInput}.".$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
