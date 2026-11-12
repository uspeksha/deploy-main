<?php

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('rejects planned leave applications with less than seven calendar days notice', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);
    $startDate = now()->addDays(6)->toDateString();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/absensi/izin', [
            'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
            'keterangan_izin_sakit' => 'Pengajuan cuti',
            'catatan_admin' => 'cuti_tahunan',
            'start_date' => $startDate,
            'end_date' => $startDate,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath(
            'message',
            'Pengajuan izin/cuti harus diajukan minimal H-7. Tanggal mulai paling cepat yang dapat dipilih adalah '.now()->addDays(7)->format('d/m/Y').'.',
        );

    expect(Absensi::where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('bukti_sakit_izin'))->toBe([]);
});

it('accepts a planned leave application submitted exactly seven days before its start date', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);
    $startDate = now()->addDays(7)->toDateString();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/absensi/izin', [
            'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
            'keterangan_izin_sakit' => 'Pengajuan cuti',
            'catatan_admin' => 'cuti_melahirkan',
            'start_date' => $startDate,
            'end_date' => $startDate,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    expect(Absensi::where('user_id', $user->id)->where('status_approval', 'pending')->exists())->toBeTrue();
});

it('enforces the seven-day notice period when resubmitting a rejected leave application', function () {
    $user = User::factory()->create();
    $startDate = now()->addDays(6)->startOfDay();
    $absensi = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'check_in_at' => $startDate,
        'end_date' => $startDate,
        'status_approval' => 'rejected',
        'file_bukti' => 'bukti_sakit_izin/original.jpg',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/absensi/izin/{$absensi->id}/resubmit", [
            'file_bukti' => UploadedFile::fake()->image('bukti-baru.jpg'),
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->toDateString(),
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath(
            'message',
            'Pengajuan izin/cuti harus diajukan minimal H-7. Tanggal mulai paling cepat yang dapat dipilih adalah '.now()->addDays(7)->format('d/m/Y').'.',
        );

    expect($absensi->fresh()->status_approval)->toBe('rejected')
        ->and(Storage::disk('public')->allFiles('bukti_sakit_izin'))->toBe([]);
});

it('allows organic employees to submit a half-day request against todays approved check-in', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);
    $checkIn = Absensi::create([
        'user_id' => $user->id,
        'status' => 'hadir',
        'check_in_at' => today()->setTime(8, 0),
        'status_approval' => 'approved',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/absensi/izin', [
            'status' => 'izin',
            'catatan_admin' => 'izin_pulang_cepat',
            'jam_pulang_rencana' => '12:00',
            'keterangan_izin_sakit' => 'Ada keperluan keluarga',
            'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $checkIn->id)
        ->assertJsonPath('data.status', 'izin')
        ->assertJsonPath('data.submission_type', 'izin_pulang_cepat')
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.jam_pulang_rencana', '12:00');

    expect(Absensi::where('user_id', $user->id)->count())->toBe(1)
        ->and($checkIn->fresh()->check_in_at->toDateString())->toBe(today()->toDateString())
        ->and(Storage::disk('public')->allFiles('bukti_sakit_izin'))->toHaveCount(1);
});

it('requires a current approved check-in before submitting a half-day request', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/absensi/izin', [
            'status' => 'izin',
            'catatan_admin' => 'izin_pulang_cepat',
            'jam_pulang_rencana' => '12:00',
            'keterangan_izin_sakit' => 'Ada keperluan keluarga',
            'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath(
            'message',
            'Pengajuan setengah hari hanya dapat diajukan setelah Anda absen masuk hari ini.',
        );

    expect(Absensi::where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('bukti_sakit_izin'))->toBe([]);
});

it('still rejects a half-day request when another leave application conflicts on that date', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);
    Absensi::create([
        'user_id' => $user->id,
        'status' => 'hadir',
        'check_in_at' => today()->setTime(8, 0),
        'status_approval' => 'approved',
    ]);
    Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'check_in_at' => today(),
        'end_date' => today(),
        'status_approval' => 'pending',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/absensi/izin', [
            'status' => 'izin',
            'catatan_admin' => 'izin_pulang_cepat',
            'jam_pulang_rencana' => '12:00',
            'keterangan_izin_sakit' => 'Ada keperluan keluarga',
            'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])
        ->assertConflict()
        ->assertJsonPath('success', false);

    expect(Absensi::where('user_id', $user->id)->count())->toBe(2)
        ->and(Storage::disk('public')->allFiles('bukti_sakit_izin'))->toBe([]);
});
