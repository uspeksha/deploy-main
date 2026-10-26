<?php

namespace App\Exports;

use App\Models\Absensi;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollDailyDetailSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly PayrollPeriod $period) {}

    public function collection(): Collection
    {
        $items = $this->period->items()
            ->with('latenessDetails')
            ->orderBy('employee_name')
            ->get([
                'id',
                'user_id',
                'employee_id',
                'employee_name',
                'department',
                'work_days',
                'daily_rate',
                'attendance_snapshot',
            ]);

        $legacyItems = $items->filter(fn ($item) => ! array_key_exists('daily_details', $item->attendance_snapshot ?? []));
        $attendanceIds = $legacyItems
            ->flatMap(fn ($item) => $item->attendance_snapshot['attendance_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        $attendances = $attendanceIds->isEmpty()
            ? collect()
            : Absensi::query()
                ->with('children')
                ->whereIn('id', $attendanceIds)
                ->whereNull('parent_id')
                ->whereNotNull('check_in_at')
                ->get()
                ->keyBy(fn (Absensi $attendance) => $attendance->user_id.'|'.Carbon::parse($attendance->check_in_at)->toDateString());

        return $items->flatMap(function ($item) use ($attendances): Collection {
            $snapshot = $item->attendance_snapshot ?? [];
            $latenessByDate = $item->latenessDetails->keyBy(fn ($detail) => Carbon::parse($detail->work_date)->toDateString());
            if (array_key_exists('daily_details', $snapshot)) {
                $dailyDetails = collect($snapshot['daily_details']);
            } else {
                $workDates = collect($snapshot['work_dates'] ?? []);
                if ($workDates->isEmpty()) {
                    $workDates = $attendances
                        ->filter(fn (Absensi $attendance) => (int) $attendance->user_id === (int) $item->user_id)
                        ->map(fn (Absensi $attendance) => Carbon::parse($attendance->check_in_at)->toDateString());
                }

                $dailyDetails = $workDates
                    ->map(fn ($date) => Carbon::parse($date)->toDateString())
                    ->unique()
                    ->sort()
                    ->map(function (string $date) use ($item, $attendances): array {
                        $attendance = $attendances->get($item->user_id.'|'.$date);
                        $overtimeRecords = $attendance?->children
                            ->where('tipe', 'lembur')
                            ->values() ?? collect();
                        $approvedOvertime = $overtimeRecords
                            ->where('status_approval', 'approved')
                            ->values();

                        return [
                            'work_date' => $date,
                            'attendance_status' => $attendance?->status,
                            'check_in_at' => $attendance?->check_in_at?->toDateTimeString(),
                            'check_out_at' => $attendance?->check_out_at?->toDateTimeString(),
                            'late_minutes' => $attendance?->late_minutes ?? 0,
                            'overtime_status' => $approvedOvertime->isNotEmpty()
                                ? 'approved'
                                : ($overtimeRecords->isNotEmpty() ? 'pending' : null),
                            'overtime_start' => $approvedOvertime->pluck('lembur_start')->filter()->all(),
                            'overtime_end' => $approvedOvertime->pluck('lembur_end')->filter()->all(),
                            'overtime_rest' => $approvedOvertime->contains(fn ($record) => (bool) $record->lembur_rest),
                            'overtime_minutes' => $approvedOvertime->isNotEmpty()
                                ? $approvedOvertime->sum('overtime_minutes')
                                : ($overtimeRecords->isNotEmpty() ? 0 : ($attendance?->overtime_minutes ?? 0)),
                            'overtime_pay' => $approvedOvertime->isNotEmpty()
                                ? $approvedOvertime->sum('overtime_pay')
                                : ($overtimeRecords->isNotEmpty() ? 0 : ($attendance?->overtime_pay ?? 0)),
                        ];
                    });
            }

            return $dailyDetails->map(function (array $detail) use ($item, $latenessByDate): array {
                $date = Carbon::parse($detail['work_date'])->toDateString();
                $lateness = $latenessByDate->get($date);
                $lateDeduction = (int) ($lateness?->deduction_amount ?? 0);
                $dailyRate = (int) $item->daily_rate;
                $overtimeStatus = match ($detail['overtime_status'] ?? null) {
                    'approved' => 'Disetujui',
                    'pending' => 'Belum disetujui',
                    default => null,
                };

                return [
                    $item->employee_id,
                    $item->employee_name,
                    $item->department,
                    $date,
                    $detail['attendance_status'] ?? 'Data absensi tidak tersedia',
                    ! empty($detail['check_in_at']) ? Carbon::parse($detail['check_in_at'])->format('H:i') : null,
                    ! empty($detail['check_out_at']) ? Carbon::parse($detail['check_out_at'])->format('H:i') : null,
                    $lateness?->late_minutes ?? $detail['late_minutes'] ?? 0,
                    $lateness?->penalty_blocks ?? 0,
                    $lateDeduction,
                    $overtimeStatus,
                    $this->formatTimes($detail['overtime_start'] ?? []),
                    $this->formatTimes($detail['overtime_end'] ?? []),
                    $overtimeStatus === 'Disetujui' ? (! empty($detail['overtime_rest']) ? 'Ya' : 'Tidak') : null,
                    $detail['overtime_minutes'] ?? 0,
                    $detail['overtime_pay'] ?? 0,
                    $dailyRate,
                    $dailyRate - $lateDeduction,
                ];
            });
        })->values();
    }

    private function formatTimes(array $times): string
    {
        return collect($times)
            ->filter()
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->implode(', ');
    }

    public function headings(): array
    {
        return [
            'ID Karyawan',
            'Nama',
            'Departemen',
            'Tanggal',
            'Status Kehadiran',
            'Check-in',
            'Check-out',
            'Menit Terlambat',
            'Blok Potongan',
            'Potongan Terlambat',
            'Status Lembur',
            'Mulai Lembur',
            'Selesai Lembur',
            'Istirahat Lembur',
            'Menit Lembur',
            'Bayaran Lembur',
            'Gaji Harian Kotor',
            'Gaji Harian Setelah Potongan',
        ];
    }

    public function columnFormats(): array
    {
        $rupiahFormat = '"Rp" #,##0;[Red]-"Rp" #,##0';

        return [
            'J' => $rupiahFormat,
            'P' => $rupiahFormat,
            'Q' => $rupiahFormat,
            'R' => $rupiahFormat,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(1, $sheet->getHighestRow());
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:R{$lastRow}");
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ]);
        if ($lastRow > 1) {
            $sheet->getStyle("A2:R{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Rincian Harian';
    }
}
