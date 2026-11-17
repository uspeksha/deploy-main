<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset link uses the configured application host', function () {
    Notification::fake();

    $user = User::factory()->create();
    $canonicalHost = parse_url(config('app.url'), PHP_URL_HOST);

    $this->withServerVariables(['HTTP_HOST' => 'attacker.example'])
        ->withHeader('X-Forwarded-Host', 'attacker.example')
        ->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $canonicalHost) {
        $resetUrl = $notification->toMail($user)->actionUrl;

        expect(parse_url($resetUrl, PHP_URL_HOST))->toBe($canonicalHost);

        return true;
    });
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
