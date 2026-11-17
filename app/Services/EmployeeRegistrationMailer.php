<?php

namespace App\Services;

use App\Mail\EmployeeRegistrationMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmployeeRegistrationMailer
{
    public function sendTemporaryPassword(User $employee, string $temporaryPassword): bool
    {
        if (config('mail.default') === 'log') {
            Log::warning('Employee credentials were not sent because the log mail driver is configured.', [
                'employee_id' => $employee->id,
            ]);

            return false;
        }

        try {
            Mail::to($employee->email)->send(
                new EmployeeRegistrationMail($employee, $temporaryPassword),
            );

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send employee credentials email via SMTP.', [
                'employee_id' => $employee->id,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
