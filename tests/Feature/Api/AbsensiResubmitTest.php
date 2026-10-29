<?php

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function createRejectedResubmission(User $user, array $attributes = []): Absensi
{
    $absensi = Absensi::create(array_merge([
        'user_id' => $user->id,
        'status' => 'sakit',
        'tipe' => 'sakit',
        'check_in_at' => now()->startOfDay(),
        'status_approval' => 'rejected',
        'file_bukti' => 'bukti_sakit_izin/original.jpg',
        'keterangan_izin_sakit' => 'Bukti awal',
        'workflow_status' => ['hrd' => 'rejected'],
        'current_approval_level' => 1,
    ], $attributes));

    DB::table('absensis')->where('id', $absensi->id)->update([
        'rejected_by' => 'HRD',
        'rejected_at' => now(),
        'catatan_admin' => 'Mohon perbaiki bukti',
    ]);

    return $absensi;
}

it('resubmits a rejected sickness application on the same record', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $absensi = createRejectedResubmission($user);

    $response = $this->actingAs($user, 'sanctum')->postJson(
        "/api/v1/absensi/sakit/{$absensi->id}/resubmit",
        [
            'file_bukti' => UploadedFile::fake()->image('bukti-baru.jpg'),
            'keterangan_izin_sakit' => 'Keterangan yang sudah diperbaiki',
        ],
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.keterangan_izin_sakit', 'Keterangan yang sudah diperbaiki');

    expect(Absensi::where('user_id', $user->id)->count())->toBe(1)
        ->and($absensi->fresh()->status_approval)->toBe('pending')
        ->and($absensi->fresh()->rejected_by)->toBeNull()
        ->and($absensi->fresh()->rejected_at)->toBeNull()
        ->and($absensi->fresh()->catatan_admin)->toBeNull();

    $this->postJson("/api/v1/absensi/sakit/{$absensi->id}/resubmit", [
        'file_bukti' => UploadedFile::fake()->image('bukti-kedua.jpg'),
    ])->assertStatus(409);
});

it('resubmits a rejected leave application on the same record', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $absensi = createRejectedResubmission($user, [
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson(
        "/api/v1/absensi/izin/{$absensi->id}/resubmit",
        [
            'file_bukti' => UploadedFile::fake()->image('bukti-baru.jpg'),
            'keterangan_izin_sakit' => 'Alasan izin yang sudah diperbaiki',
        ],
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.keterangan_izin_sakit', 'Alasan izin yang sudah diperbaiki');

    expect(Absensi::where('user_id', $user->id)->count())->toBe(1)
        ->and($absensi->fresh()->status_approval)->toBe('pending')
        ->and($absensi->fresh()->rejected_by)->toBeNull()
        ->and($absensi->fresh()->rejected_at)->toBeNull()
        ->and($absensi->fresh()->catatan_admin)->toBeNull();
});

it('resubmits a rejected overtime application on the same record', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $absensi = createRejectedResubmission($user, [
        'status' => 'hadir',
        'tipe' => 'lembur',
        'check_out_at' => now()->setTime(17, 0),
        'foto_pulang' => 'absensi_foto/original.jpg',
        'lembur_start' => now()->setTime(17, 0),
        'lembur_end' => now()->setTime(18, 0),
        'lembur_keterangan' => 'Keterangan awal',
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson(
        "/api/v1/absensi/lembur/{$absensi->id}/resubmit",
        [
            'foto' => UploadedFile::fake()->image('foto-baru.jpg'),
            'lat' => '-6.2',
            'lng' => '106.8',
            'jam_mulai' => '17:00',
            'jam_selesai' => '18:00',
            'istirahat' => false,
            'keterangan' => 'Keterangan lembur yang sudah diperbaiki',
        ],
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.lembur_keterangan', 'Keterangan lembur yang sudah diperbaiki');

    expect(Absensi::where('user_id', $user->id)->count())->toBe(1)
        ->and($absensi->fresh()->status_approval)->toBe('pending');
});
