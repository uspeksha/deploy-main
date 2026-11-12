<?php

use App\Models\EmployeeDocument;
use App\Models\EmployeeFamily;
use App\Models\EmployeeProfile;
use App\Models\EmploymentHistory;
use App\Models\PtkpStatus;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('relates employee data and PTKP status bidirectionally', function () {
    $user = User::factory()->create();
    $ptkpStatus = PtkpStatus::factory()->create();
    $profile = EmployeeProfile::factory()->for($user)->create([
        'ptkp_status_id' => $ptkpStatus->id,
        'gender' => null,
    ]);
    $history = EmploymentHistory::factory()->for($user)->create();
    $family = EmployeeFamily::factory()->for($user)->create();
    $document = EmployeeDocument::factory()->for($user)->create();

    expect($user->profile->is($profile))->toBeTrue()
        ->and($profile->user->is($user))->toBeTrue()
        ->and($profile->ptkpStatus->is($ptkpStatus))->toBeTrue()
        ->and($ptkpStatus->employeeProfiles->contains($profile))->toBeTrue()
        ->and($user->employmentHistories->contains($history))->toBeTrue()
        ->and($history->user->is($user))->toBeTrue()
        ->and($user->families->contains($family))->toBeTrue()
        ->and($family->user->is($user))->toBeTrue()
        ->and($user->documents->contains($document))->toBeTrue()
        ->and($document->user->is($user))->toBeTrue()
        ->and($profile->gender)->toBeNull()
        ->and($user->gender)->toBeNull();
});

it('encrypts sensitive profile attributes in storage and hides them from serialization', function () {
    $plaintext = [
        'nik_ktp' => '3201010101010001',
        'npwp' => '123456789012345',
        'bpjs_kesehatan_no' => '000123456789',
        'bpjs_ketenagakerjaan_no' => '123456789012',
        'bank_account_no' => '987654321012345678901234567890',
    ];
    $profile = EmployeeProfile::factory()->create($plaintext);
    $stored = DB::table('employee_profiles')->where('id', $profile->id)->first();
    $serialized = $profile->toArray();

    foreach ($plaintext as $attribute => $value) {
        expect($stored->{$attribute})->not->toBe($value)
            ->and(Crypt::decryptString($stored->{$attribute}))->toBe($value)
            ->and($profile->{$attribute})->toBe($value)
            ->and(array_key_exists($attribute, $serialized))->toBeFalse();
    }
});

it('seeds current PTKP references idempotently', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    expect(PtkpStatus::query()->count())->toBe(8)
        ->and(PtkpStatus::query()->where('code', 'TK/0')->value('annual_amount'))->toBe(54000000)
        ->and(PtkpStatus::query()->whereDate('effective_at', '2016-01-01')->where('code', 'TK/0')->exists())->toBeTrue()
        ->and(PtkpStatus::query()->where('code', 'K/3')->value('annual_amount'))->toBe(72000000);
});

it('only backfills employment history when explicitly run with a start date', function () {
    $user = User::factory()->create(['employment_type' => 'organik']);

    $this->artisan('hr:backfill-employment-histories')
        ->assertExitCode(Command::INVALID);

    expect($user->employmentHistories()->exists())->toBeFalse();

    $this->artisan('hr:backfill-employment-histories', ['--start-date' => '2020-03-01'])
        ->assertSuccessful();
    $this->artisan('hr:backfill-employment-histories', ['--start-date' => '2020-03-01'])
        ->assertSuccessful();

    expect($user->employmentHistories()->count())->toBe(1)
        ->and($user->employmentHistories()->first()->employment_type)->toBe('organik')
        ->and($user->employmentHistories()->first()->start_date->toDateString())->toBe('2020-03-01');
});
