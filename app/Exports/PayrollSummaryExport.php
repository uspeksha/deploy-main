<?php

namespace App\Exports;

use App\Models\PayrollPeriod;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PayrollSummaryExport implements WithMultipleSheets
{
    public function __construct(private readonly PayrollPeriod $period) {}

    public function sheets(): array
    {
        return [new PayrollSummarySheet($this->period)];
    }
}
