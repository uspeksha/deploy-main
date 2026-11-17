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

class PayrollSummarySheet extends DefaultValueBinder implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly PayrollPeriod $period) {}

    public function collection()
    {
        $employeeRows = $this->period->items()
            ->with('employee:id,bank_name,bank_account_number')
            ->get([
                'user_id',
                'employee_id',
                'employee_name',
                'daily_rate',
                'gross_pay',
                'late_deduction',
                'other_deductions',
                'attendance_snapshot',
            ])
            ->map(function ($item): array {
                $overtimePay = collect($item->attendance_snapshot['daily_details'] ?? [])
                    ->sum(fn (array $day): float => (float) ($day['overtime_pay'] ?? 0));
                $productionAllowance = 0;
                $weeklyAllowance = 0;
                $incompleteAttendanceDeduction = 0;
                $totalAllowance = (float) $item->gross_pay
                    + $productionAllowance
                    + $weeklyAllowance
                    + $overtimePay;

                return [
                    'bank' => $this->bankGroup($item->employee?->bank_name),
                    'name' => $item->employee_name,
                    'values' => [
                        $item->employee_id,
                        $item->employee_name,
                        $item->employee?->bank_account_number,
                        $item->daily_rate_summary,
                        $productionAllowance,
                        $weeklyAllowance,
                        $item->gross_pay,
                        $item->late_deduction,
                        $incompleteAttendanceDeduction,
                        $overtimePay,
                        $totalAllowance,
                        $item->other_deductions,
                        $totalAllowance
                            - (float) $item->late_deduction
                            - $incompleteAttendanceDeduction
                            - (float) $item->other_deductions,
                    ],
                ];
            });

        $totals = $employeeRows->reduce(function (array $totals, array $employee): array {
            $row = $employee['values'];
            foreach ([4, 5, 6, 7, 8, 9, 10, 11, 12] as $column) {
                $totals[$column] += (float) ($row[$column] ?? 0);
            }

            return $totals;
        }, array_fill(0, 13, 0));

        $items = collect();
        $bankGroups = $employeeRows
            ->groupBy('bank')
            ->sortKeysUsing(fn (string $left, string $right): int => $this->compareBanks($left, $right));

        foreach ($bankGroups as $bank => $employees) {
            $items->push(array_merge(["REKENING {$bank}"], array_fill(0, 12, null)));

            foreach ($employees->sort(fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name'])) as $employee) {
                $items->push($employee['values']);
            }
        }

        $items->push([
            null,
            'GRAND TOTAL',
            null,
            null,
            $totals[4],
            $totals[5],
            $totals[6],
            $totals[7],
            $totals[8],
            $totals[9],
            $totals[10],
            $totals[11],
            $totals[12],
        ]);

        return $items;
    }

    private function bankGroup(?string $bankName): string
    {
        $bankName = mb_strtoupper(trim((string) $bankName));

        if (str_contains($bankName, 'BCA')) {
            return 'BCA';
        }

        if (str_contains($bankName, 'BRI')) {
            return 'BRI';
        }

        return $bankName !== '' ? $bankName : 'BANK BELUM DIISI';
    }

    private function compareBanks(string $left, string $right): int
    {
        $order = ['BCA' => 0, 'BRI' => 1, 'BANK BELUM DIISI' => 3];
        $leftOrder = $order[$left] ?? 2;
        $rightOrder = $order[$right] ?? 2;

        return $leftOrder <=> $rightOrder ?: strnatcasecmp($left, $right);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Full Name',
            'No. Rekening',
            'Upah Harian',
            'Tunjangan Produksi',
            'Tunjangan Mingguan',
            'Gaji Harian Periode',
            'Potongan Terlambat',
            'Potongan Absen Tidak Lengkap',
            'Lemburan',
            'Total Allowance',
            'Potongan Lain (Kasbon)',
            'Take Home Pay',
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getColumn() === 'C' && $cell->getRow() > 4 && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        $rupiahFormat = '"Rp" #,##0;[Red]-"Rp" #,##0;"Rp" -';

        return [
            'D' => $rupiahFormat,
            'E' => $rupiahFormat,
            'F' => $rupiahFormat,
            'G' => $rupiahFormat,
            'H' => $rupiahFormat,
            'I' => $rupiahFormat,
            'J' => $rupiahFormat,
            'K' => $rupiahFormat,
            'L' => $rupiahFormat,
            'M' => $rupiahFormat,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 28,
            'C' => 22,
            'D' => 18,
            'E' => 21,
            'F' => 21,
            'G' => 22,
            'H' => 20,
            'I' => 25,
            'J' => 18,
            'K' => 21,
            'L' => 23,
            'M' => 21,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->insertNewRowBefore(1, 3);
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $startDate = $this->period->start_date->locale('id');
        $endDate = $this->period->end_date->locale('id');
        $periodRange = sprintf(
            '%s-%s %s %d',
            $startDate->format('j'),
            $endDate->format('j'),
            $startDate->translatedFormat('F'),
            $this->period->year,
        );
        $sheet->setCellValue('A1', 'PT. Followme Indonesia');
        $sheet->setCellValue('A2', 'Salary Report ('.$periodRange.')');
        $sheet->mergeCells('D3:J3');
        $sheet->setCellValue('D3', 'Allowance');

        $lastDataRow = $sheet->getHighestRow();
        $totalRow = $lastDataRow;
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A5');

        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '385D8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:M2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '385D8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A3:M4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle("A3:M{$totalRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '808080']],
            ],
        ]);
        for ($row = 5; $row < $totalRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(21);
            $firstCell = (string) $sheet->getCell("A{$row}")->getValue();
            if (str_starts_with($firstCell, 'REKENING ')) {
                $sheet->mergeCells("A{$row}:M{$row}");
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                continue;
            }

            if (($row - 5) % 2 === 1) {
                $sheet->getStyle("A{$row}:M{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F3F6FA');
            }
        }
        $sheet->getStyle("A5:M{$totalRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D5:M{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A5:C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A{$totalRow}:M{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BFBFBF']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(23);
        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(42);

        return [];
    }

    public function title(): string
    {
        return substr(sprintf('Payroll_%04d_%02d_W%d', $this->period->year, $this->period->month, $this->period->period_number), 0, 31);
    }
}
