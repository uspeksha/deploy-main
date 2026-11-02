<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// JADWAL AUTO CHECKOUT LUPA ABSEN (Setiap Hari Jam 23:59)
Schedule::call(function () {
    $today = \Carbon\Carbon::today();
    $forgottenAbsences = \App\Models\Absensi::where('status', 'hadir')
        ->whereNull('check_out_at')
        ->whereDate('check_in_at', $today)
        ->get();

    foreach ($forgottenAbsences as $absensi) {
        $updateData = [
            'check_out_at' => $today->copy()->setTime(17, 0, 0),
        ];

        // Hanya tandai untuk organik
        if ($absensi->user && $absensi->user->employment_status === 'organik') {
            $updateData['catatan_admin'] = 'AUTO_CHECKOUT_LUPA_ABSEN';
        }

        $absensi->update($updateData);
    }
})->dailyAt('23:59');

// JADWAL BACKUP OTOMATIS (Setiap Hari Jam 12 Malam)
Schedule::command('backup:run')->dailyAt('00:00');
// Bersihkan backup lama (Setiap Jam 1 Pagi)
Schedule::command('backup:clean')->dailyAt('01:00');
