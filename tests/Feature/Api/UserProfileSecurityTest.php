<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects self-service employment type changes', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);

    Sanctum::actingAs($user);

    $this->putJson('/api/v1/user/update-employment-status', [
        'employment_type' => 'freelance',
    ])->assertForbidden();

    expect($user->fresh()->employment_type)->toBe('organik');
});

it('rejects employee id changes through self-service profile updates', function () {
    $user = User::factory()->create(['id_karyawan' => 'EMP-ORIGINAL']);

    Sanctum::actingAs($user);

    $this->putJson('/api/v1/user/profile', [
        'id_karyawan' => 'EMP-CHANGED',
        'name' => 'Attempted Profile Update',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_karyawan');

    expect($user->fresh()->id_karyawan)->toBe('EMP-ORIGINAL')
        ->and($user->fresh()->name)->not->toBe('Attempted Profile Update');
});

it('allows ordinary self-service profile updates', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'departemen' => 'Original Department',
    ]);

    Sanctum::actingAs($user);

    $this->putJson('/api/v1/user/profile', [
        'name' => 'Updated Name',
        'departemen' => 'Updated Department',
    ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Updated Name')
        ->assertJsonPath('user.departemen', 'Updated Department');

    expect($user->fresh()->name)->toBe('Updated Name')
        ->and($user->fresh()->departemen)->toBe('Updated Department');
});
