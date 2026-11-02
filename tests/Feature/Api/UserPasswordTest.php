<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('updates the authenticated user password and revokes only that user api tokens', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $otherUser = User::factory()->create();
    $currentToken = $user->createToken('current-device');
    $otherUserToken = $otherUser->createToken('other-user-device');
    $user->createToken('another-device');

    $this->withToken($currentToken->plainTextToken)
        ->putJson('/api/v1/user/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertOk()
        ->assertExactJson([
            'message' => 'Password berhasil diperbarui. Silakan login kembali.',
        ]);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0)
        ->and($otherUser->tokens()->count())->toBe(1)
        ->and($otherUserToken->accessToken->exists)->toBeTrue();
});

it('rejects a wrong current password without changing the password or revoking tokens', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $token = $user->createToken('current-device');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/user/password', [
            'current_password' => 'incorrect-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Data tidak valid.')
        ->assertJsonValidationErrors('current_password')
        ->assertJsonPath('errors.current_password.0', 'The password is incorrect.');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(1);
});

it('validates the new password length and confirmation', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $token = $user->createToken('current-device');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/user/password', [
            'current_password' => 'old-password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password')
        ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/user/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password')
        ->assertJsonPath('errors.password.0', 'The password field confirmation does not match.');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(1);
});
