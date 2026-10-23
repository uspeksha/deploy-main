<?php

namespace App\Exports;

use App\Models\PayrollPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollSummaryExport extends DefaultValueBinder implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly PayrollPeriod $period) {}

    public function collection()
    {
        $items = $this->period->items()
            ->with('employee:id,bank_name,bank_account_number,bank_account_name')
            ->orderBy('employee_name')
            ->get([
                'user_id',
                'employee_id',
                'employee_name',
                'employment_type',
                'department',
                'work_days',
                'daily_rate',
                'gross_pay',
                'late_occurrences',
                'total_late_minutes',
                'late_deduction',
                'other_deductions',
                'net_pay',
                'slip_number',
            ])
            ->map(fn ($item) => [
                $item->employee_id,
                $item->employee_name,
                $item->employee?->bank_name,
                $item->employee?->bank_account_number,
                $item->employee?->bank_account_name,
                $item->employment_type,
                $item->department,
                $item->work_days,
                $item->daily_rate,
                $item->gross_pay,
                $item->late_occurrences,
                $item->total_late_minutes,
                $item->late_deduction,
                $item->other_deductions,
                $item->net_pay,
                $item->slip_number,
                $this->period->label,
                $this->period->status,
            ]);

        $totals = $items->reduce(function (array $totals, array $row): array {
            $totals['work_days'] += $row[7];
            $totals['gross_pay'] += $row[9];
            $totals['late_occurrences'] += $row[10];
            $totals['late_minutes'] += $row[11];
            $totals['late_deduction'] += $row[12];
            $totals['other_deductions'] += $row[13];
            $totals['net_pay'] += $row[14];

            return $totals;
        }, [
            'work_days' => 0,
            'gross_pay' => 0,
            'late_occurrences' => 0,
            'late_minutes' => 0,
            'late_deduction' => 0,
            'other_deductions' => 0,
            'net_pay' => 0,
        ]);

        $items->push([
            null,
            'TOTAL',
            null,
            null,
            null,
            null,
            null,
            $totals['work_days'],
            null,
            $totals['gross_pay'],
            $totals['late_occurrences'],
            $totals['late_minutes'],
            $totals['late_deduction'],
            $totals['other_deductions'],
            $totals['net_pay'],
            null,
            $this->period->label,
            $this->period->status,
        ]);

        return $items;
    }

    public function headings(): array
    {
        return [
            'ID Karyawan',
            'Nama',
            'Bank',
            'Nomor Rekening',
            'Nama Pemilik Rekening',
            'Jenis Karyawan',
            'Departemen',
            'Hari Kerja',
            'Tarif Harian',
            'Gaji Kotor',
            'Kejadian Terlambat',
            'Total Menit Terlambat',
            'Potongan Terlambat',
            'Potongan Lainnya',
            'Gaji Bersih',
            'Nomor Slip',
            'Periode',
            'Status',
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getColumn() === 'D' && $cell->getRow() > 1 && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        $rupiahFormat = '"Rp" #,##0;[Red]-"Rp" #,##0';

        return [
            'I' => $rupiahFormat,
            'J' => $rupiahFormat,
            'M' => $rupiahFormat,
            'N' => $rupiahFormat,
            'O' => $rupiahFormat,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 28,
            'C' => 18,
            'D' => 22,
            'E' => 26,
            'F' => 18,
            'G' => 20,
            'H' => 12,
            'I' => 20,
            'J' => 20,
            'K' => 18,
            'L' => 20,
            'M' => 23,
            'N' => 22,
            'O' => 20,
            'P' => 25,
            'Q' => 44,
            'R' => 22,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->insertNewRowBefore(1, 3);
        $sheet->mergeCells('A1:R1');
        $sheet->mergeCells('A2:R2');
        $sheet->mergeCells('A3:R3');
        $sheet->setCellValue('A1', 'FOLLOWME | REKAP PAYROLL KARYAWAN FREELANCE');
        $sheet->setCellValue('A2', 'Periode: '.$this->period->label);
        $sheet->setCellValue('A3', 'Tarif Rp120.000 per hari | Potongan Rp7.500 per 30 menit penuh per tanggal | Status: '.strtoupper(str_replace('_', ' ', $this->period->status)));

        $lastDataRow = $sheet->getHighestRow();
        $totalRow = $lastDataRow;
        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:R'.($totalRow - 1));

        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:R3')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '374151']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A4:R4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle("A4:R{$totalRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ]);
        $sheet->getStyle("H5:O{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("P5:R{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A{$totalRow}:R{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(4)->setRowHeight(34);

        return [];
    }

    public function title(): string
    {
        return substr(sprintf('Payroll_%04d_%02d_W%d', $this->period->year, $this->period->month, $this->period->period_number), 0, 31);
    }
}
