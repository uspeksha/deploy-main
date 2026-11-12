<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

it('issues a six digit OTP and invalidates it after a successful password reset', function () {
    $user = User::factory()->create();
    $user->createToken('existing-token');
    $sentOtp = null;
    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function (string $message) use (&$sentOtp): bool {
            preg_match('/adalah: ([0-9]{6})/', $message, $matches);
            $sentOtp = $matches[1] ?? null;

            return true;
        });

    $this->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', 'Jika email terdaftar, kode OTP akan dikirim.')
        ->assertJsonMissingPath('otp_debug');

    $otpRecord = DB::table('password_resets')->where('email', $user->email)->first();
    expect($otpRecord)->not->toBeNull()
        ->and($otpRecord->otp)->toMatch('/^[0-9a-z]{6}$/')
        ->and($otpRecord->otp)->not->toBe($sentOtp)
        ->and($sentOtp)->toMatch('/^[0-9]{6}$/');

    $this->postJson('/api/v1/forgot-password/verify-otp', [
        'email' => $user->email,
        'otp' => $sentOtp,
    ])->assertOk();

    $this->postJson('/api/v1/forgot-password/reset-password', [
        'email' => $user->email,
        'otp' => $sentOtp,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0)
        ->and(DB::table('password_resets')->where('email', $user->email)->exists())->toBeFalse();

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
        ->postJson('/api/v1/forgot-password/reset-password', [
            'email' => $user->email,
            'otp' => $sentOtp,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertUnprocessable();
});

it('rolls back password and OTP consumption when bearer token revocation fails', function () {
    $user = User::factory()->create();
    $token = $user->createToken('existing-token');
    $originalPasswordHash = $user->password;
    $sentOtp = null;
    $passwordWasUpdatedBeforeTokenDelete = false;

    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function (string $message) use (&$sentOtp): bool {
            preg_match('/adalah: ([0-9]{6})/', $message, $matches);
            $sentOtp = $matches[1] ?? null;

            return true;
        });

    $this->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
        ->assertOk();
    $otpBeforeReset = DB::table('password_resets')->where('email', $user->email)->first();

    DB::connection()->getPdo()->sqliteCreateFunction(
        'capture_reset_password_state',
        function (string $passwordHash) use (&$passwordWasUpdatedBeforeTokenDelete): int {
            $passwordWasUpdatedBeforeTokenDelete = Hash::check('new-password', $passwordHash);

            return 1;
        },
        1,
    );

    DB::unprepared(<<<'SQL'
        CREATE TRIGGER fail_personal_access_token_delete
        BEFORE DELETE ON personal_access_tokens
        BEGIN
            SELECT capture_reset_password_state(
                (SELECT password FROM users WHERE id = OLD.tokenable_id)
            );
            SELECT RAISE(ABORT, 'synthetic token revocation failure');
        END;
    SQL);

    $this->postJson('/api/v1/forgot-password/reset-password', [
        'email' => $user->email,
        'otp' => $sentOtp,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertInternalServerError();

    DB::unprepared('DROP TRIGGER fail_personal_access_token_delete');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue()
        ->and($passwordWasUpdatedBeforeTokenDelete)->toBeTrue()
        ->and($user->fresh()->password)->toBe($originalPasswordHash)
        ->and($user->tokens()->whereKey($token->accessToken->id)->exists())->toBeTrue()
        ->and(DB::table('password_resets')->where('email', $user->email)->value('otp'))->toBe($otpBeforeReset->otp);
});

it('expires an OTP at the ten minute boundary', function () {
    $user = User::factory()->create();
    $userWithNullTimestamp = User::factory()->create();
    $createdAt = now()->subMinutes(10);
    DB::table('password_resets')->insert([
        'email' => $user->email,
        'otp' => '123456',
        'created_at' => $createdAt,
    ]);
    DB::table('password_resets')->insert([
        'email' => $userWithNullTimestamp->email,
        'otp' => '654321',
        'created_at' => null,
    ]);

    $this->postJson('/api/v1/forgot-password/verify-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'Kode OTP sudah kadaluarsa. Silakan minta kode baru.');

    expect(DB::table('password_resets')->where('email', $user->email)->exists())->toBeFalse();

    $this->postJson('/api/v1/forgot-password/verify-otp', [
        'email' => $userWithNullTimestamp->email,
        'otp' => '654321',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'Kode OTP sudah kadaluarsa. Silakan minta kode baru.');
});

it('invalidates the previous OTP when a replacement is requested', function () {
    $user = User::factory()->create();
    $sentOtps = [];
    Mail::shouldReceive('raw')
        ->twice()
        ->withArgs(function (string $message) use (&$sentOtps): bool {
            preg_match('/adalah: ([0-9]{6})/', $message, $matches);
            $sentOtps[] = $matches[1] ?? null;

            return true;
        });

    $this->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])->assertOk();
    $previousOtp = $sentOtps[0];

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.30'])
        ->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
        ->assertOk();
    $currentOtp = $sentOtps[1];

    expect(DB::table('password_resets')->where('email', $user->email)->count())->toBe(1);

    if ($previousOtp !== $currentOtp) {
        $this->postJson('/api/v1/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $previousOtp,
        ])->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.31'])
        ->postJson('/api/v1/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $currentOtp,
        ])->assertOk();
});

it('limits failed OTP attempts across changing client IP addresses', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.{$attempt}"])
            ->postJson('/api/v1/forgot-password/verify-otp', [
                'email' => $user->email,
                'otp' => '000000',
            ])->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.6'])
        ->postJson('/api/v1/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => '000000',
        ])->assertTooManyRequests()
        ->assertJsonPath('message', 'Terlalu banyak percobaan OTP. Silakan minta kode baru beberapa saat lagi.');
});

it('limits failed password reset OTP attempts across changing client IP addresses', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$attempt}"])
            ->postJson('/api/v1/forgot-password/reset-password', [
                'email' => $user->email,
                'otp' => '000000',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.6'])
        ->postJson('/api/v1/forgot-password/reset-password', [
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertTooManyRequests()
        ->assertJsonPath('message', 'Terlalu banyak percobaan OTP. Silakan minta kode baru beberapa saat lagi.');
});

it('limits OTP resend requests per account across changing client IP addresses', function () {
    $user = User::factory()->create();
    Mail::shouldReceive('raw')->times(3);

    foreach (range(1, 4) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$attempt}"])
            ->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'Jika email terdaftar, kode OTP akan dikirim.');
    }

    expect(DB::table('password_resets')->where('email', $user->email)->count())->toBe(1);
});

it('returns a generic response and leaves no active OTP when email delivery fails', function () {
    $user = User::factory()->create();
    $deliveryAttempts = 0;
    Log::shouldReceive('error')
        ->once()
        ->with('Failed to send password reset OTP email.', ['exception_type' => RuntimeException::class]);
    Mail::shouldReceive('raw')
        ->twice()
        ->andReturnUsing(function () use (&$deliveryAttempts) {
            $deliveryAttempts++;
            if ($deliveryAttempts === 1) {
                throw new RuntimeException('SMTP unavailable');
            }
        });

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
        ->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', 'Jika email terdaftar, kode OTP akan dikirim.')
        ->assertJsonMissingPath('otp_debug');

    expect(DB::table('password_resets')->where('email', $user->email)->exists())->toBeFalse();

    $this->travel(2)->minutes();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
        ->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', 'Jika email terdaftar, kode OTP akan dikirim.');

    expect($deliveryAttempts)->toBe(1)
        ->and(DB::table('password_resets')->where('email', $user->email)->exists())->toBeFalse();

    $this->travel(9)->minutes();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.12'])
        ->postJson('/api/v1/forgot-password/send-otp', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', 'Jika email terdaftar, kode OTP akan dikirim.');

    expect($deliveryAttempts)->toBe(2)
        ->and(DB::table('password_resets')->where('email', $user->email)->exists())->toBeTrue();
});

it('returns the same response for unknown addresses without sending mail', function () {
    config(['app.env' => 'local']);
    Mail::shouldReceive('raw')->never();

    $this->postJson('/api/v1/forgot-password/send-otp', ['email' => 'missing@example.test'])
        ->assertOk()
        ->assertJsonPath('message', 'Jika email terdaftar, kode OTP akan dikirim.')
        ->assertJsonMissingPath('otp_debug');
});

it('accepts a still-valid plaintext OTP created before the verifier rollout', function () {
    $user = User::factory()->create();
    $plaintextOtp = '725184';
    DB::table('password_resets')->insert([
        'email' => $user->email,
        'otp' => $plaintextOtp,
        'created_at' => now()->subMinutes(2),
    ]);

    $this->postJson('/api/v1/forgot-password/reset-password', [
        'email' => $user->email,
        'otp' => $plaintextOtp,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('password_resets')->where('email', $user->email)->exists())->toBeFalse();
});
