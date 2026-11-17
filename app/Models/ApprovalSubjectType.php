<?php

namespace App\Models;

enum ApprovalSubjectType: string
{
    case Absensi = 'absensi';
    case IzinKeluar = 'izin_keluar';
    case AttendanceCorrection = 'attendance_correction';
    case Loan = 'loan';
    case Reimbursement = 'reimbursement';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
