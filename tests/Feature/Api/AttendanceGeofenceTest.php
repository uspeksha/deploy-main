<?php

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'attendance.geofence.radius_meters' => 100,
        'attendance.geofence.locations.office' => [
            'latitude' => -6.3357454,
            'longitude' => 106.9457406,
        ],
        'attendance.geofence.locations.produksi' => [
            'latitude' => -6.3357454,
            'longitude' => 106.9457406,
        ],
    ]);
    Storage::fake('public');
});

it('rejects check-in outside the assigned workplace geofence', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => -6.3457454,
            'lng' => 106.9457406,
            'accuracy_meters' => 5,
            'status' => 'hadir',
        ])
        ->assertForbidden()
        ->assertJsonPath('allowed_radius_meters', 100);

    expect(Absensi::where('user_id', $employee->id)->count())->toBe(0);
    expect(Storage::disk('public')->allFiles('absensi_foto'))->toBe([]);
});

it('allows check-in inside the workplace geofence', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'produksi',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => -6.3357454,
            'lng' => 106.9457406,
            'accuracy_meters' => 5,
            'status' => 'hadir',
        ])
        ->assertCreated();

    expect(Absensi::where('user_id', $employee->id)->where('status', 'hadir')->exists())->toBeTrue();
});

it('keeps check-in compatible with app versions that do not send GPS accuracy', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => -6.3357454,
            'lng' => 106.9457406,
            'status' => 'hadir',
        ])
        ->assertCreated();

    expect(Absensi::where('user_id', $employee->id)->where('status', 'hadir')->exists())->toBeTrue();
});

it('rejects check-out outside the workplace geofence', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);
    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'check_in_at' => now()->setTime(8, 0),
        'status_approval' => 'approved',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/pulang', [
            'foto' => UploadedFile::fake()->image('selfie-pulang.jpg'),
            'lat' => -6.3457454,
            'lng' => 106.9457406,
            'accuracy_meters' => 5,
        ])
        ->assertForbidden()
        ->assertJsonPath('allowed_radius_meters', 100);

    expect(Absensi::where('user_id', $employee->id)->firstOrFail()->check_out_at)->toBeNull();
});

it('keeps check-out compatible with app versions that do not send GPS accuracy', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);
    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'check_in_at' => now()->setTime(8, 0),
        'status_approval' => 'approved',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/pulang', [
            'foto' => UploadedFile::fake()->image('selfie-pulang.jpg'),
            'lat' => -6.3457454,
            'lng' => 106.9457406,
        ])
        ->assertForbidden()
        ->assertJsonPath('allowed_radius_meters', 100)
        ->assertJsonMissingValidationErrors('accuracy_meters');

    expect(Absensi::where('user_id', $employee->id)->firstOrFail()->check_out_at)->toBeNull();
});

it('rejects invalid GPS coordinate ranges before recording attendance', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => 120,
            'lng' => 106.9457406,
            'accuracy_meters' => 5,
            'status' => 'hadir',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lat');

    expect(Absensi::where('user_id', $employee->id)->count())->toBe(0);
});

it('rejects check-in when the device reports a mocked location', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => -6.3357454,
            'lng' => 106.9457406,
            'accuracy_meters' => 5,
            'status' => 'hadir',
            'is_mocked' => true,
        ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Absensi ditolak karena perangkat melaporkan lokasi palsu.');

    expect(Absensi::where('user_id', $employee->id)->count())->toBe(0);
});

it('requires check-out to be inside the workplace even for early leave', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);
    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'check_in_at' => now()->setTime(8, 0),
        'status_approval' => 'approved',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/pulang', [
            'foto' => UploadedFile::fake()->image('selfie-pulang.jpg'),
            'lat' => -6.3457454,
            'lng' => 106.9457406,
            'accuracy_meters' => 5,
            'tipe' => 'sakit',
        ])
        ->assertForbidden()
        ->assertJsonPath('allowed_radius_meters', 100);

    expect(Absensi::where('user_id', $employee->id)->firstOrFail()->check_out_at)->toBeNull();
});

it('rejects a location measurement whose accuracy could put it outside the geofence', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => -6.3362849,
            'lng' => 106.9457406,
            'accuracy_meters' => 50,
            'status' => 'hadir',
        ])
        ->assertForbidden()
        ->assertJsonPath('allowed_radius_meters', 100);

    expect(Absensi::where('user_id', $employee->id)->count())->toBe(0);
});

it('requires valid GPS accuracy for check-in and check-out', function () {
    $employee = User::factory()->create([
        'employment_type' => 'freelance',
        'work_location' => 'office',
    ]);

    $this->actingAs($employee, 'sanctum')
        ->postJson('/api/v1/absensi/masuk', [
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
            'lat' => -6.3357454,
            'lng' => 106.9457406,
            'accuracy_meters' => 0,
            'status' => 'hadir',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('accuracy_meters');
});
