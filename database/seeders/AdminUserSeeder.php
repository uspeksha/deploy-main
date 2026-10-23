<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('HRD_ADMIN_EMAIL', 'hrd@anselmudaberkarya.com');
        $password = env('HRD_ADMIN_PASSWORD');

        $user = User::firstOrNew(['email' => $email]);
        if (!$user->exists && (!is_string($password) || strlen($password) < 12)) {
            throw new InvalidArgumentException('Set HRD_ADMIN_PASSWORD to a value with at least 12 characters before seeding the HRD account.');
        }

        $user->fill([
            'name' => 'HRD',
            'role' => 'hrd',
            'is_admin' => true,
            'id_karyawan' => $user->id_karyawan ?: 'HRD001',
            'departemen' => 'HRD',
            'employment_type' => 'organik',
        ]);
        if (!$user->exists) {
            $user->password = Hash::make($password);
        }
        $user->save();
    }
}