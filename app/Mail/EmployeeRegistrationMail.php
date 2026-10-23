<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $employee,
        public string $temporaryPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akses akun absensi karyawan Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-registration',
            with: [
                'employee' => $this->employee,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
