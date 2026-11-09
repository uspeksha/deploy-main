<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('services.hrd_admin.email');
        if (! is_string($email) || trim($email) === '') {
            throw new RuntimeException('Set HRD_ADMIN_EMAIL before creating the HRD account.');
        }

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = config('services.hrd_admin.password');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set a unique HRD_ADMIN_PASSWORD of at least 12 characters.');
        }

        User::create([
            'name' => 'HRD Manager',
            'email' => $email,
            'role' => 'hrd',
            'is_admin' => true,
            'id_karyawan' => 'HRD001',
            'departemen' => 'HRD',
            'employment_type' => 'organik',
            'password' => Hash::make($password),
            'registration_status' => 'approved',
            'is_active' => true,
        ]);
    }
}
