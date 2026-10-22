<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Absensi;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jangan membuat user dummy tanpa id_karyawan karyawan yang valid.

        // 2. Buat beberapa user dummy tambahan (opsional)
        // User::factory(5)->create();

        // 3. Buat data absensi hanya sekali agar db:seed aman diulang.
        $users = User::all();
        if ($users->isNotEmpty() && Absensi::doesntExist()) {
            for ($i = 0; $i < 30; $i++) {
                $randomUser = $users->random();
                $status = ['hadir', 'izin', 'sakit'][$i % 3];
                $tipe = null;
                if ($status === 'hadir') {
                    $tipe = ['masuk', 'lembur', null][$i % 3];
                }

                Absensi::create([
                    'user_id' => $randomUser->id,
                    'check_in_at' => Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                    'status' => $status,
                    'tipe' => $tipe,
                    // 'keterangan' => ($status !== 'hadir' && rand(0,1) == 1) ? 'Keterangan ' . $status : null, // <-- BARIS INI DIHAPUS ATAU DIKOMENTARI
                ]);
            }

            // Contoh spesifik untuk Test User dan Admin jika Anda ingin memastikan mereka punya data
            // $testUserFromDb = User::where('email', 'test@example.com')->first();
            // if ($testUserFromDb) {
            //     Absensi::create([
            //         'user_id' => $testUserFromDb->id,
            //         'waktu' => Carbon::now()->subDays(1),
            //         'status' => 'hadir',
            //         'tipe' => 'masuk',
            //         // 'keterangan' => null, // Tidak perlu lagi jika kolom dihapus dari seeder
            //     ]);
            //     Absensi::create([
            //         'user_id' => $testUserFromDb->id,
            //         'waktu' => Carbon::now()->subDays(2),
            //         'status' => 'izin',
            //         // 'keterangan' => 'Demam', // Tidak perlu lagi jika kolom dihapus dari seeder
            //     ]);
            // }

            // $adminUserFromDb = User::where('email', 'admin@example.com')->first();
            // if ($adminUserFromDb) {
            //     Absensi::create([
            //         'user_id' => $adminUserFromDb->id,
            //         'waktu' => Carbon::now()->subDays(0),
            //         'status' => 'hadir',
            //         'tipe' => 'lembur',
            //         // 'keterangan' => null, // Tidak perlu lagi jika kolom dihapus dari seeder
            //     ]);
            // }
        }
    }
}
