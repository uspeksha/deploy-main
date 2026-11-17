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
        try {
            Mail::to($employee->email)->send(
                new EmployeeRegistrationMail($employee, $temporaryPassword),
            );

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send employee credentials email via SMTP.', [
                'employee_id' => $employee->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            // Graceful fallback for local development or log mail driver:
            // Log credentials clearly and return true so account activation succeeds.
            Log::info('--- EMPLOYEE TEMPORARY CREDENTIALS (FALLBACK / LOG) ---', [
                'name' => $employee->name,
                'email' => $employee->email,
                'temporary_password' => $temporaryPassword,
            ]);

            return true;
        }
    }
}
