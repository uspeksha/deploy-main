<?php

namespace Tests\Feature\Api;

use App\Models\FcmDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmDeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_and_remove_their_device_token(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/user/fcm-token', [
            'token' => 'first-device-token',
            'platform' => 'android',
        ])->assertOk()->assertJson(['success' => true]);

        $device = FcmDevice::query()->sole();
        $this->assertSame($user->id, $device->user_id);
        $this->assertSame(hash('sha256', 'first-device-token'), $device->token_hash);

        $this->deleteJson('/api/v1/user/fcm-token', [
            'token' => 'first-device-token',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('fcm_devices', ['id' => $device->id]);
    }

    public function test_registering_a_token_again_moves_it_to_the_current_account(): void
    {
        $firstUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        $secondUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        Sanctum::actingAs($firstUser);
        $this->putJson('/api/v1/user/fcm-token', [
            'token' => 'shared-device-token',
            'platform' => 'android',
        ])->assertOk();

        Sanctum::actingAs($secondUser);
        $this->putJson('/api/v1/user/fcm-token', [
            'token' => 'shared-device-token',
            'platform' => 'android',
        ])->assertOk();

        $this->assertSame(1, FcmDevice::query()->count());
        $this->assertSame($secondUser->id, FcmDevice::query()->sole()->user_id);

        $this->deleteJson('/api/v1/user/fcm-token', [
            'token' => 'shared-device-token',
        ])->assertOk();
        $this->assertDatabaseCount('fcm_devices', 0);
    }

    public function test_device_registration_validates_token_and_platform(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/user/fcm-token', [
            'token' => '',
            'platform' => 'desktop',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'platform']);
    }
}
