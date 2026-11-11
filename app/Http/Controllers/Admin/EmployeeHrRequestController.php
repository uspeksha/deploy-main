<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Notification;
use App\Models\Reimbursement;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeHrRequestController extends Controller
{
    private function ensureHrd(): void
    {
        abort_unless(auth()->user()?->role === 'hrd', 403);
    }

    public function index(): View
    {
        $this->ensureHrd();

        $reimbursements = Reimbursement::query()
            ->with(['user:id,name,id_karyawan', 'type:id,name', 'approvedBy:id,name', 'paidBy:id,name'])
            ->latest()
            ->paginate(15, ['*'], 'reimbursement_page')
            ->withQueryString();
        $loans = Loan::query()
            ->with(['user:id,name,id_karyawan', 'approvedBy:id,name', 'installments'])
            ->latest()
            ->paginate(15, ['*'], 'loan_page')
            ->withQueryString();

        return view('admin.hr-requests.index', compact('reimbursements', 'loans'));
    }

    public function reimbursementAction(Request $request, Reimbursement $reimbursement, string $action): RedirectResponse
    {
        $this->ensureHrd();
        abort_unless(in_array($action, ['approve', 'reject', 'paid'], true), 404);
        $validated = $request->validate(match ($action) {
            'reject' => ['reason' => ['required', 'string', 'min:5', 'max:1000']],
            'paid' => ['payment_reference' => ['required', 'string', 'min:3', 'max:100']],
            default => [],
        });

        DB::transaction(function () use ($action, $reimbursement, $validated): void {
            $locked = Reimbursement::query()->lockForUpdate()->findOrFail($reimbursement->id);
            abort_unless($locked->status === match ($action) {
                'approve', 'reject' => Reimbursement::STATUS_PENDING,
                'paid' => Reimbursement::STATUS_APPROVED,
            }, 409, 'Status reimbursement sudah berubah.');

            if ($action === 'approve') {
                $locked->update([
                    'status' => Reimbursement::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'rejection_reason' => null,
                ]);
            } elseif ($action === 'reject') {
                $locked->update([
                    'status' => Reimbursement::STATUS_REJECTED,
                    'approved_by' => auth()->id(),
                    'rejection_reason' => trim($validated['reason']),
                ]);
            } else {
                $locked->update([
                    'status' => Reimbursement::STATUS_PAID,
                    'paid_by' => auth()->id(),
                    'paid_at' => now(),
                    'payment_reference' => trim($validated['payment_reference']),
                ]);
            }

            ActivityLog::log("reimbursement.{$action}", "Reimbursement ID: {$locked->id}", [
                'user_id' => $locked->user_id,
                'amount' => $locked->amount,
            ]);
            Notification::query()->create([
                'user_id' => $locked->user_id,
                'title' => match ($action) {
                    'approve' => 'Reimbursement disetujui',
                    'reject' => 'Reimbursement ditolak',
                    default => 'Reimbursement dibayarkan',
                },
                'message' => match ($action) {
                    'approve' => 'Pengajuan reimbursement telah disetujui HRD.',
                    'reject' => 'Pengajuan reimbursement ditolak. Catatan: '.$locked->rejection_reason,
                    default => 'Reimbursement telah dibayarkan. Referensi: '.$locked->payment_reference,
                },
                'type' => "reimbursement_{$action}",
                'target_page' => '/hr_requests',
                'target_id' => $locked->id,
                'is_read' => false,
            ]);
        });

        return redirect()->route('admin.hr-requests.index')
            ->with('success', 'Status reimbursement berhasil diperbarui.');
    }

    public function loanAction(Request $request, Loan $loan, string $action): RedirectResponse
    {
        $this->ensureHrd();
        abort_unless(in_array($action, ['approve', 'reject'], true), 404);
        $validated = $request->validate($action === 'reject'
            ? ['reason' => ['required', 'string', 'min:5', 'max:1000']]
            : []);

        DB::transaction(function () use ($loan, $action, $validated): void {
            $locked = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            abort_unless($locked->status === Loan::STATUS_PENDING, 409, 'Status pinjaman sudah berubah.');

            if ($action === 'approve') {
                abort_if(
                    $locked->principal > config('hr.loan.max_principal')
                        || $locked->tenor_months > config('hr.loan.max_tenor_months'),
                    409,
                    'Nominal atau tenor melampaui kebijakan pinjaman.',
                );
                $locked->update([
                    'status' => Loan::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
                $baseAmount = intdiv($locked->principal, $locked->tenor_months);
                $firstDueMonth = Carbon::now()->startOfMonth()->addMonth();

                for ($number = 1; $number <= $locked->tenor_months; $number++) {
                    LoanInstallment::query()->create([
                        'loan_id' => $locked->id,
                        'installment_no' => $number,
                        'amount' => $number === $locked->tenor_months
                            ? $locked->principal - ($baseAmount * ($number - 1))
                            : $baseAmount,
                        'due_date' => $firstDueMonth->copy()->addMonths($number - 1)->endOfMonth()->toDateString(),
                        'status' => LoanInstallment::STATUS_APPROVED,
                    ]);
                }
            } else {
                $locked->update([
                    'status' => Loan::STATUS_REJECTED,
                    'approved_by' => auth()->id(),
                    'rejection_reason' => trim($validated['reason']),
                ]);
            }

            ActivityLog::log("loan.{$action}", "Loan ID: {$locked->id}", [
                'user_id' => $locked->user_id,
                'principal' => $locked->principal,
                'tenor_months' => $locked->tenor_months,
            ]);
            Notification::query()->create([
                'user_id' => $locked->user_id,
                'title' => $action === 'approve' ? 'Pinjaman disetujui' : 'Pinjaman ditolak',
                'message' => $action === 'approve'
                    ? 'Pengajuan pinjaman disetujui. Cicilan tanpa bunga dimulai bulan berikutnya.'
                    : 'Pengajuan pinjaman ditolak. Catatan: '.$locked->rejection_reason,
                'type' => "loan_{$action}",
                'target_page' => '/hr_requests',
                'target_id' => $locked->id,
                'is_read' => false,
            ]);
        });

        return redirect()->route('admin.hr-requests.index')
            ->with('success', 'Status pinjaman berhasil diperbarui.');
    }

    public function installmentPaid(Request $request, LoanInstallment $installment): RedirectResponse
    {
        $this->ensureHrd();
        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        DB::transaction(function () use ($installment, $validated): void {
            $locked = LoanInstallment::query()
                ->with('loan')
                ->lockForUpdate()
                ->findOrFail($installment->id);
            abort_unless(
                $locked->status === LoanInstallment::STATUS_APPROVED
                    && $locked->loan?->status === Loan::STATUS_APPROVED,
                409,
                'Cicilan belum dapat ditandai lunas atau statusnya sudah berubah.',
            );

            $locked->update([
                'status' => LoanInstallment::STATUS_PAID,
                'paid_at' => now(),
                'payment_reference' => trim($validated['payment_reference']),
            ]);

            ActivityLog::log('loan.installment_paid', "Loan installment ID: {$locked->id}", [
                'loan_id' => $locked->loan_id,
                'installment_no' => $locked->installment_no,
                'amount' => $locked->amount,
                'payment_reference' => $locked->payment_reference,
            ]);
            Notification::query()->create([
                'user_id' => $locked->loan->user_id,
                'title' => 'Cicilan pinjaman tercatat lunas',
                'message' => "Cicilan {$locked->installment_no} sebesar Rp ".number_format($locked->amount, 0, ',', '.')
                    .' telah dikonfirmasi HRD. Referensi: '.$locked->payment_reference,
                'type' => 'loan_installment_paid',
                'target_page' => '/hr_requests',
                'target_id' => $locked->loan_id,
                'is_read' => false,
            ]);
        });

        return redirect()->route('admin.hr-requests.index')
            ->with('success', 'Pembayaran cicilan berhasil dicatat.');
    }

    public function downloadReceipt(Reimbursement $reimbursement): StreamedResponse
    {
        $this->ensureHrd();
        abort_unless(Storage::disk('local')->exists($reimbursement->receipt_path), 404);

        ActivityLog::log('reimbursement.receipt_downloaded', "Reimbursement ID: {$reimbursement->id}");

        return Storage::disk('local')->download(
            $reimbursement->receipt_path,
            'bukti-reimbursement-'.$reimbursement->id.'.'.pathinfo($reimbursement->receipt_path, PATHINFO_EXTENSION),
        );
    }
}
