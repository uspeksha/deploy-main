<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('logs out a stateful Sanctum session without trying to delete its transient token', function () {
    config(['sanctum.stateful' => ['localhost']]);

    $user = User::factory()->create(['role' => 'hrd']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->withHeader('Origin', 'http://localhost')
        ->postJson('/api/v1/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Successfully logged out');

    Auth::forgetGuards();
    $this->getJson('/api/v1/user')->assertUnauthorized();
});

it('revokes only the current bearer token when logging out', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');
    $otherToken = $user->createToken('other-token');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Successfully logged out');

    expect($user->tokens()->whereKey($token->accessToken->id)->exists())->toBeFalse()
        ->and($user->tokens()->whereKey($otherToken->accessToken->id)->exists())->toBeTrue();
});
