<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Services\AttendanceAlfaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyRepeatedAlfaCommand extends Command
{
    protected $signature = 'attendance:notify-alfa-calls';

    protected $description = 'Notify employees and HRD for each Alfa from the third working-day absence in a month';

    public function handle(AttendanceAlfaService $alfaService): int
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $workDates = $alfaService->workDatesBetween($monthStart, $today);

        if ($workDates === []) {
            $this->info('No workdays to process today.');

            return self::SUCCESS;
        }

        $employees = User::query()
            ->where('registration_status', 'approved')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $attendanceDates = $alfaService->confirmedAttendanceDatesByUser($employees->modelKeys(), $monthStart, $today);
        $hrdUsers = User::query()
            ->where('role', 'hrd')
            ->where('registration_status', 'approved')
            ->where('is_active', true)
            ->get(['id']);
        $createdNotifications = 0;

        foreach ($employees as $employee) {
            $alfaDates = $alfaService->missingWorkDates($workDates, $attendanceDates->get($employee->id, []));

            foreach ($alfaDates as $index => $date) {
                $alfaNumber = $index + 1;

                if ($alfaNumber < 3) {
                    continue;
                }

                $eventType = 'alfa_hrd_call_'.Carbon::parse($date)->format('Ymd');
                $employeeMessage = "Anda mencapai Alfa ke-{$alfaNumber} bulan ini pada {$date}. HRD akan menghubungi Anda.";
                $hrdMessage = "Karyawan {$employee->name} mencapai Alfa ke-{$alfaNumber} pada {$date}. Mohon tindak lanjuti.";
                $recipientMessages = [$employee->id => $employeeMessage];

                foreach ($hrdUsers as $hrdUser) {
                    $recipientMessages[$hrdUser->id] = $hrdMessage;
                }

                foreach ($recipientMessages as $recipientId => $message) {
                    $alreadyNotified = Notification::query()
                        ->where('user_id', $recipientId)
                        ->where('type', $eventType)
                        ->where('target_id', $employee->id)
                        ->exists();

                    if ($alreadyNotified) {
                        continue;
                    }

                    Notification::query()->create([
                        'user_id' => $recipientId,
                        'title' => 'Tindak lanjut Alfa',
                        'message' => $message,
                        'type' => $eventType,
                        'target_id' => $employee->id,
                    ]);

                    $createdNotifications++;
                }
            }
        }

        $this->info("Created {$createdNotifications} Alfa notification(s).");

        return self::SUCCESS;
    }
}
