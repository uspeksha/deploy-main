<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the initial HRD login only with explicitly configured credentials', function () {
    config([
        'services.hrd_admin.email' => 'hrd-test@example.com',
        'services.hrd_admin.password' => 'unique-test-password',
    ]);

    $this->seed(AdminUserSeeder::class);

    $hrd = User::where('email', 'hrd-test@example.com')->firstOrFail();

    expect($hrd->name)->toBe('HRD Manager')
        ->and($hrd->role)->toBe('hrd')
        ->and($hrd->is_admin)->toBeTrue()
        ->and(Hash::check('unique-test-password', $hrd->password))->toBeTrue();
});

it('does not overwrite an existing HRD login when the seeder is rerun', function () {
    config([
        'services.hrd_admin.email' => 'existing-hrd@example.com',
        'services.hrd_admin.password' => null,
    ]);
    $existing = User::factory()->create([
        'email' => 'existing-hrd@example.com',
        'name' => 'HRD Updated in Admin',
        'password' => Hash::make('existing-password'),
        'role' => 'hrd',
    ]);

    $this->seed(AdminUserSeeder::class);

    expect($existing->fresh()->name)->toBe('HRD Updated in Admin')
        ->and(Hash::check('existing-password', $existing->fresh()->password))->toBeTrue()
        ->and(User::where('email', 'existing-hrd@example.com')->count())->toBe(1);
});

it('requires explicit bootstrap credentials before creating an HRD login', function () {
    config([
        'services.hrd_admin.email' => null,
        'services.hrd_admin.password' => null,
    ]);

    expect(fn () => $this->seed(AdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'Set HRD_ADMIN_EMAIL');
});
