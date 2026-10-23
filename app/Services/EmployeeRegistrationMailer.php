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
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            Log::warning('Employee credentials email is not deliverable with the configured mail transport.', [
                'employee_id' => $employee->id,
                'mailer' => config('mail.default'),
            ]);

            return false;
        }

        try {
            Mail::to($employee->email)->send(
                new EmployeeRegistrationMail($employee, $temporaryPassword),
            );

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send employee credentials email.', [
                'employee_id' => $employee->id,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
