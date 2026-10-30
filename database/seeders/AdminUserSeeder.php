<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'hrd@anselmudaberkarya.com';
        $password = 'Followmesuksesselalu';

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'HRD Manager',
                'role' => 'hrd',
                'is_admin' => true,
                'id_karyawan' => 'HRD001',
                'departemen' => 'HRD',
                'employment_type' => 'organik',
                'password' => Hash::make($password),
                'registration_status' => 'approved',
                'is_active' => true,
            ]
        );
    }
}
