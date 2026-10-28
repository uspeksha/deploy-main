<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PayrollSummaryExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PayrollCorrection;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Services\PayrollGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PayrollController extends Controller
{
    private function ensureHrd(): void
    {
        abort_unless(auth()->user()?->role === 'hrd', 403);
    }

    public function index(Request $request): View
    {
        $this->ensureHrd();

        $periodQuery = PayrollPeriod::query()
            ->withCount('items')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('period_number')
            ->orderByDesc('revision');
        if ($request->filled('status') && in_array($request->string('status')->toString(), [
            PayrollPeriod::STATUS_DRAFT,
            PayrollPeriod::STATUS_EXPORTED,
            PayrollPeriod::STATUS_PENDING_APPROVAL,
            PayrollPeriod::STATUS_APPROVED,
            PayrollPeriod::STATUS_PAID,
            PayrollPeriod::STATUS_CANCELLED,
        ], true)) {
            $periodQuery->where('status', $request->string('status')->toString());
        }
        $periods = $periodQuery->paginate(12)->withQueryString();

        $selectedPeriod = $request->filled('period_id')
            ? PayrollPeriod::query()
                ->with(['items.latenessDetails', 'approver', 'payer', 'canceller', 'previousRevision', 'corrections.recorder', 'corrections.settler'])
                ->findOrFail($request->integer('period_id'))
            : PayrollPeriod::query()
                ->with(['items.latenessDetails', 'approver', 'payer', 'canceller', 'previousRevision', 'corrections.recorder', 'corrections.settler'])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->orderByDesc('period_number')
                ->first();
        $canRecreatePeriod = $selectedPeriod
            && $selectedPeriod->status === PayrollPeriod::STATUS_CANCELLED
            && ! PayrollPeriod::query()
                ->where('year', $selectedPeriod->year)
                ->where('month', $selectedPeriod->month)
                ->where('period_number', $selectedPeriod->period_number)
                ->where('revision', '>', $selectedPeriod->revision)
                ->exists();

        $items = null;
        $sortColumn = $request->string('sort', 'employee_name')->toString();
        $sortColumn = in_array($sortColumn, [
            'employee_id',
            'employee_name',
            'work_days',
            'gross_pay',
            'late_occurrences',
            'late_deduction',
            'net_pay',
        ], true) ? $sortColumn : 'employee_name';
        $sortDirection = $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $totals = [
            'gross_pay' => 0,
            'late_deduction' => 0,
            'other_deductions' => 0,
            'net_pay' => 0,
        ];

        if ($selectedPeriod) {
            $itemQuery = $selectedPeriod->items()->with('latenessDetails');
            if ($request->filled('search')) {
                $search = trim((string) $request->string('search'));
                $itemQuery->where(function ($query) use ($search) {
                    $query->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }
            $items = $itemQuery
                ->orderBy($sortColumn, $sortDirection)
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString();

            $totals = $selectedPeriod->items()
                ->selectRaw('COALESCE(SUM(gross_pay), 0) as gross_pay')
                ->selectRaw('COALESCE(SUM(late_deduction), 0) as late_deduction')
                ->selectRaw('COALESCE(SUM(other_deductions), 0) as other_deductions')
                ->selectRaw('COALESCE(SUM(net_pay), 0) as net_pay')
                ->first()
                ->only(array_keys($totals));
        }

        return view('admin.payroll.index', compact('periods', 'selectedPeriod', 'items', 'totals', 'canRecreatePeriod'));
    }

    public function store(Request $request, PayrollGenerationService $generationService): RedirectResponse
    {
        $this->ensureHrd();

        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'period_number' => ['required', 'integer', Rule::in([1, 2])],
        ]);

        $previousPeriod = PayrollPeriod::query()
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('period_number', $validated['period_number'])
            ->orderByDesc('revision')
            ->first();

        if ($previousPeriod && $previousPeriod->status !== PayrollPeriod::STATUS_CANCELLED) {
            return redirect()
                ->route('admin.payroll.index', ['period_id' => $previousPeriod->id])
                ->with('warning', 'Payroll periode ini sudah pernah dibuat dan tidak dihitung ulang.');
        }

        $correctionReason = $previousPeriod
            ? "Dibuat ulang setelah payroll #{$previousPeriod->id} dibatalkan"
            : null;
        $revision = ($previousPeriod?->revision ?? 0) + 1;
        try {
            $period = DB::transaction(function () use ($validated, $generationService, $previousPeriod, $revision, $correctionReason) {
                $period = $generationService->createPeriod(
                    (int) $validated['year'],
                    (int) $validated['month'],
                    (int) $validated['period_number'],
                    (int) auth()->id(),
                    $revision,
                    $previousPeriod?->id,
                    $previousPeriod ? $correctionReason : null,
                );

                ActivityLog::log(
                    $previousPeriod ? 'payroll.revision_created' : 'payroll.draft_created',
                    "Payroll period ID: {$period->id}",
                    [
                        'year' => $period->year,
                        'month' => $period->month,
                        'period_number' => $period->period_number,
                        'revision' => $period->revision,
                        'revision_of_id' => $period->revision_of_id,
                        'correction_reason' => $period->correction_reason,
                        'employee_count' => $period->items->count(),
                        'start_date' => $period->start_date->toDateString(),
                        'end_date' => $period->end_date->toDateString(),
                    ],
                );

                return $period;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $periodQuery = PayrollPeriod::query()
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->where('period_number', $validated['period_number'])
                ->where('revision', $revision);
            if ($previousPeriod) {
                $periodQuery->where('revision_of_id', $previousPeriod->id);
            } else {
                $periodQuery->whereNull('revision_of_id');
            }
            $period = $periodQuery->first();

            if (! $period) {
                throw $exception;
            }

            return redirect()
                ->route('admin.payroll.index', ['period_id' => $period->id])
                ->with('warning', 'Payroll periode ini baru saja dibuat oleh pengguna lain; data lama tidak dihitung ulang.');
        }

        return redirect()
            ->route('admin.payroll.index', ['period_id' => $period->id])
            ->with('success', 'Draft payroll berhasil dihitung dari absensi yang sudah disetujui.');
    }

    public function submit(PayrollPeriod $period): RedirectResponse
    {
        return $this->transition($period, [
            PayrollPeriod::STATUS_DRAFT,
            PayrollPeriod::STATUS_EXPORTED,
        ], PayrollPeriod::STATUS_PENDING_APPROVAL, function (PayrollPeriod $lockedPeriod): array {
            $lockedPeriod->submitted_at = now();

            return ['submitted_at' => $lockedPeriod->submitted_at];
        }, 'payroll.submitted');
    }

    public function approve(PayrollPeriod $period): RedirectResponse
    {
        return $this->transition($period, PayrollPeriod::STATUS_PENDING_APPROVAL, PayrollPeriod::STATUS_APPROVED, function (PayrollPeriod $lockedPeriod): array {
            $lockedPeriod->approved_by = auth()->id();
            $lockedPeriod->approved_at = now();

            return [
                'approved_by' => $lockedPeriod->approved_by,
                'approved_at' => $lockedPeriod->approved_at,
            ];
        }, 'payroll.approved');
    }

    public function markPaid(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $this->ensureHrd();
        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        return $this->transition($period, PayrollPeriod::STATUS_APPROVED, PayrollPeriod::STATUS_PAID, function (PayrollPeriod $lockedPeriod) use ($validated): array {
            $lockedPeriod->paid_by = auth()->id();
            $lockedPeriod->paid_at = now();
            $lockedPeriod->payment_reference = trim($validated['payment_reference']);

            return [
                'paid_by' => $lockedPeriod->paid_by,
                'paid_at' => $lockedPeriod->paid_at,
                'payment_reference' => $lockedPeriod->payment_reference,
            ];
        }, 'payroll.manually_marked_paid');
    }

    public function cancel(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $this->ensureHrd();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return DB::transaction(function () use ($period, $validated) {
            $lockedPeriod = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);
            abort_unless(
                in_array($lockedPeriod->status, [
                    PayrollPeriod::STATUS_DRAFT,
                    PayrollPeriod::STATUS_EXPORTED,
                    PayrollPeriod::STATUS_PENDING_APPROVAL,
                    PayrollPeriod::STATUS_APPROVED,
                ], true),
                409,
                'Payroll yang sudah dibayarkan atau dibatalkan tidak dapat dibatalkan lagi.',
            );

            $previousStatus = $lockedPeriod->status;
            $lockedPeriod->status = PayrollPeriod::STATUS_CANCELLED;
            $lockedPeriod->cancelled_by = auth()->id();
            $lockedPeriod->cancelled_at = now();
            $lockedPeriod->cancellation_reason = trim($validated['reason']);
            $lockedPeriod->save();

            ActivityLog::log('payroll.cancelled', "Payroll period ID: {$lockedPeriod->id}", [
                'reason' => $lockedPeriod->cancellation_reason,
                'previous_status' => $previousStatus,
            ]);

            return redirect()
                ->route('admin.payroll.index', ['period_id' => $lockedPeriod->id])
                ->with('success', 'Payroll dibatalkan dan alasannya tercatat.');
        });
    }

    public function destroy(PayrollPeriod $period): RedirectResponse
    {
        $this->ensureHrd();

        return DB::transaction(function () use ($period) {
            $lockedPeriod = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);

            ActivityLog::log('payroll.deleted', "Payroll period ID: {$lockedPeriod->id}", [
                'year' => $lockedPeriod->year,
                'month' => $lockedPeriod->month,
                'period_number' => $lockedPeriod->period_number,
                'revision' => $lockedPeriod->revision,
                'status' => $lockedPeriod->status,
                'items_count' => $lockedPeriod->items()->count(),
                'corrections_count' => $lockedPeriod->corrections()->count(),
            ]);

            $lockedPeriod->corrections()->delete();
            $lockedPeriod->delete();

            return redirect()
                ->route('admin.payroll.index')
                ->with('success', 'Payroll beserta snapshot dan catatan koreksinya berhasil dihapus permanen.');
        });
    }

    public function recordCorrection(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $this->ensureHrd();
        $validated = $request->validate([
            'adjustment_amount' => ['required', 'integer', 'between:-1000000000,1000000000', 'not_in:0'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return DB::transaction(function () use ($period, $validated) {
            $lockedPeriod = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);
            abort_unless($lockedPeriod->status === PayrollPeriod::STATUS_PAID, 409, 'Rekonsiliasi hanya tersedia untuk payroll yang sudah dibayar.');

            $correction = PayrollCorrection::query()->create([
                'payroll_period_id' => $lockedPeriod->id,
                'recorded_by' => auth()->id(),
                'adjustment_amount' => (int) $validated['adjustment_amount'],
                'reason' => trim($validated['reason']),
                'status' => 'open',
            ]);

            ActivityLog::log('payroll.post_payment_correction_recorded', "Payroll correction ID: {$correction->id}", [
                'payroll_period_id' => $lockedPeriod->id,
                'adjustment_amount' => $correction->adjustment_amount,
                'reason' => $correction->reason,
            ]);

            return redirect()
                ->route('admin.payroll.index', ['period_id' => $lockedPeriod->id])
                ->with('success', 'Koreksi dicatat untuk rekonsiliasi manual; nominal payroll dan pembayaran tidak diubah.');
        });
    }

    public function reconcileCorrection(Request $request, PayrollPeriod $period, PayrollCorrection $correction): RedirectResponse
    {
        $this->ensureHrd();
        $validated = $request->validate([
            'settlement_reference' => ['required', 'string', 'min:3', 'max:100'],
            'settlement_note' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($period, $correction, $validated) {
            $lockedPeriod = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);
            abort_unless($lockedPeriod->status === PayrollPeriod::STATUS_PAID, 409, 'Payroll asal tidak berada dalam status Dibayarkan.');

            $lockedCorrection = PayrollCorrection::query()
                ->where('payroll_period_id', $lockedPeriod->id)
                ->lockForUpdate()
                ->findOrFail($correction->id);
            abort_unless($lockedCorrection->status === 'open', 409, 'Koreksi ini sudah direkonsiliasi.');

            $lockedCorrection->status = 'reconciled';
            $lockedCorrection->settled_by = auth()->id();
            $lockedCorrection->settled_at = now();
            $lockedCorrection->settlement_reference = trim($validated['settlement_reference']);
            $lockedCorrection->settlement_note = isset($validated['settlement_note'])
                ? trim($validated['settlement_note'])
                : null;
            $lockedCorrection->save();

            ActivityLog::log('payroll.post_payment_correction_reconciled', "Payroll correction ID: {$lockedCorrection->id}", [
                'payroll_period_id' => $lockedPeriod->id,
                'adjustment_amount' => $lockedCorrection->adjustment_amount,
                'settlement_reference' => $lockedCorrection->settlement_reference,
                'settlement_note' => $lockedCorrection->settlement_note,
            ]);

            return redirect()
                ->route('admin.payroll.index', ['period_id' => $lockedPeriod->id])
                ->with('success', 'Rekonsiliasi manual dicatat. Snapshot payroll awal tetap tidak berubah.');
        });
    }

    public function export(PayrollPeriod $period): BinaryFileResponse
    {
        $this->ensureHrd();
        abort_unless(
            in_array($period->status, [PayrollPeriod::STATUS_APPROVED, PayrollPeriod::STATUS_PAID], true),
            409,
            'Payroll hanya dapat diekspor setelah disetujui HRD.',
        );

        $response = Excel::download(
            new PayrollSummaryExport($period),
            sprintf('payroll-%04d-%02d-minggu-%d.xlsx', $period->year, $period->month, $period->period_number),
        );

        ActivityLog::log('payroll.summary_exported', "Payroll period ID: {$period->id}", [
            'year' => $period->year,
            'month' => $period->month,
            'period_number' => $period->period_number,
            'status' => $period->status,
        ]);

        return $response;
    }

    public function previewSlip(PayrollPeriod $period, PayrollItem $item): View
    {
        $this->ensureHrd();
        abort_unless($item->payroll_period_id === $period->id, 404);

        $item->load('latenessDetails');
        ActivityLog::log('payroll.slip_viewed', "Payroll item ID: {$item->id}", [
            'payroll_period_id' => $period->id,
            'slip_number' => $item->slip_number,
        ]);

        return view('admin.payroll.preview', compact('period', 'item'));
    }

    public function slip(PayrollPeriod $period, PayrollItem $item): Response
    {
        $this->ensureHrd();
        abort_unless($item->payroll_period_id === $period->id, 404);

        $item->load('latenessDetails');
        ActivityLog::log('payroll.slip_downloaded', "Payroll item ID: {$item->id}", [
            'payroll_period_id' => $period->id,
            'slip_number' => $item->slip_number,
        ]);

        return Pdf::loadView('admin.payroll.slip', compact('period', 'item'))
            ->setPaper('a4', 'portrait')
            ->download($item->slip_number.'.pdf');
    }

    private function transition(
        PayrollPeriod $period,
        string|array $expectedStatus,
        string $newStatus,
        callable $applyTransition,
        string $action,
    ): RedirectResponse {
        $this->ensureHrd();

        return DB::transaction(function () use ($period, $expectedStatus, $newStatus, $applyTransition, $action) {
            $lockedPeriod = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);
            $expectedStatuses = (array) $expectedStatus;
            abort_unless(
                in_array($lockedPeriod->status, $expectedStatuses, true),
                409,
                'Status payroll sudah berubah atau tindakan ini tidak diizinkan.',
            );

            $previousStatus = $lockedPeriod->status;
            $changes = $applyTransition($lockedPeriod);
            $lockedPeriod->status = $newStatus;
            $lockedPeriod->save();

            ActivityLog::log($action, "Payroll period ID: {$lockedPeriod->id}", [
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                ...$changes,
            ]);

            return redirect()
                ->route('admin.payroll.index', ['period_id' => $lockedPeriod->id])
                ->with('success', 'Status payroll berhasil diperbarui.');
        });
    }
}
