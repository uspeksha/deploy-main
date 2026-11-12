<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeePayrollController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = PayrollItem::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('period', fn ($query) => $query->where('status', PayrollPeriod::STATUS_PAID))
            ->with('period:id,year,month,period_number,start_date,end_date,status,paid_at,payment_reference')
            ->orderByDesc('payroll_period_id')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $items->getCollection()->map(fn (PayrollItem $item): array => [
                'id' => $item->id,
                'slip_number' => $item->slip_number,
                'year' => $item->period->year,
                'month' => $item->period->month,
                'period_number' => $item->period->period_number,
                'start_date' => $item->period->start_date->toDateString(),
                'end_date' => $item->period->end_date->toDateString(),
                'work_days' => $item->work_days,
                'gross_pay' => $item->gross_pay,
                'late_deduction' => $item->late_deduction,
                'other_deductions' => $item->other_deductions,
                'net_pay' => $item->net_pay,
                'paid_at' => $item->period->paid_at?->toIso8601String(),
                'payment_reference' => $item->period->payment_reference,
            ])->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function download(Request $request, int $itemId): Response
    {
        $item = PayrollItem::query()
            ->whereKey($itemId)
            ->where('user_id', $request->user()->id)
            ->whereHas('period', fn ($query) => $query->where('status', PayrollPeriod::STATUS_PAID))
            ->with(['period.approver', 'period.payer', 'latenessDetails'])
            ->firstOrFail();
        $period = $item->period;

        ActivityLog::log('employee.payroll_slip_downloaded', "Payroll item ID: {$item->id}", [
            'payroll_period_id' => $period->id,
            'slip_number' => $item->slip_number,
        ]);

        return Pdf::loadView('admin.payroll.slip', compact('period', 'item'))
            ->setPaper('a4', 'portrait')
            ->download($item->slip_number.'.pdf');
    }
}
