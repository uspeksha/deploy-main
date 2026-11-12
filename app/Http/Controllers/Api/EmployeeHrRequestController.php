<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Loan;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeHrRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'user', 403);
        $monthStart = Carbon::now()->startOfMonth();
        $monthlyReimbursementUsage = Reimbursement::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', Reimbursement::STATUS_REJECTED)
            ->whereBetween('created_at', [$monthStart, $monthStart->copy()->endOfMonth()])
            ->selectRaw('type_id, SUM(amount) as used_amount')
            ->groupBy('type_id')
            ->pluck('used_amount', 'type_id');

        return response()->json([
            'success' => true,
            'data' => [
                'reimbursement_types' => ReimbursementType::query()
                    ->whereNotNull('monthly_limit')
                    ->orderBy('name')
                    ->get(['id', 'name', 'monthly_limit'])
                    ->map(fn (ReimbursementType $type): array => [
                        'id' => $type->id,
                        'name' => $type->name,
                        'monthly_limit' => $type->monthly_limit,
                        'used_this_month' => (int) ($monthlyReimbursementUsage[$type->id] ?? 0),
                    ]),
                'loan_policy' => [
                    'max_principal' => (int) config('hr.loan.max_principal', 5_000_000),
                    'max_tenor_months' => (int) config('hr.loan.max_tenor_months', 12),
                ],
                'reimbursements' => $user->reimbursements()
                    ->with('type:id,name,monthly_limit')
                    ->latest()
                    ->limit(100)
                    ->get()
                    ->map(fn (Reimbursement $reimbursement): array => [
                        'id' => $reimbursement->id,
                        'type_id' => $reimbursement->type_id,
                        'type' => $reimbursement->type?->name,
                        'amount' => $reimbursement->amount,
                        'description' => $reimbursement->description,
                        'status' => $reimbursement->status,
                        'rejection_reason' => $reimbursement->rejection_reason,
                        'payment_reference' => $reimbursement->payment_reference,
                        'created_at' => $reimbursement->created_at?->toIso8601String(),
                        'paid_at' => $reimbursement->paid_at?->toIso8601String(),
                    ]),
                'loans' => $user->loans()
                    ->with('installments:id,loan_id,installment_no,amount,due_date,status,paid_at,payment_reference')
                    ->latest()
                    ->limit(100)
                    ->get()
                    ->map(fn (Loan $loan): array => [
                        'id' => $loan->id,
                        'principal' => $loan->principal,
                        'tenor_months' => $loan->tenor_months,
                        'monthly_installment' => $loan->tenor_months > 0
                            ? (int) ceil($loan->principal / $loan->tenor_months)
                            : 0,
                        'purpose' => $loan->purpose,
                        'status' => $loan->status,
                        'rejection_reason' => $loan->rejection_reason,
                        'approved_at' => $loan->approved_at?->toIso8601String(),
                        'installments' => $loan->installments->map(fn ($installment): array => [
                            'number' => $installment->installment_no,
                            'amount' => $installment->amount,
                            'due_date' => $installment->due_date->toDateString(),
                            'status' => $installment->status,
                            'paid_at' => $installment->paid_at?->toIso8601String(),
                            'payment_reference' => $installment->payment_reference,
                        ])->values()->all(),
                        'created_at' => $loan->created_at?->toIso8601String(),
                    ]),
            ],
        ]);
    }

    public function storeReimbursement(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'user', 403);

        $validated = $request->validate([
            'type_id' => ['required', 'integer', 'exists:reimbursement_types,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        return DB::transaction(function () use ($request, $user, $validated): JsonResponse {
            $type = ReimbursementType::query()->lockForUpdate()->findOrFail($validated['type_id']);
            if ($type->monthly_limit === null) {
                throw ValidationException::withMessages([
                    'type_id' => 'Kategori reimbursement ini belum memiliki batas bulanan dari HRD.',
                ]);
            }

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $usedAmount = Reimbursement::query()
                ->where('user_id', $user->id)
                ->where('type_id', $type->id)
                ->where('status', '!=', Reimbursement::STATUS_REJECTED)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            if ($usedAmount + $validated['amount'] > $type->monthly_limit) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Pengajuan melebihi sisa limit kategori %s bulan ini.',
                        $type->name,
                    ),
                ]);
            }

            $receiptPath = $request->file('receipt')->store(
                "reimbursements/{$user->id}",
                'local',
            );
            if ($receiptPath === false) {
                throw new \RuntimeException('Bukti reimbursement gagal disimpan.');
            }

            $reimbursement = Reimbursement::query()->create([
                'user_id' => $user->id,
                'type_id' => $type->id,
                'amount' => $validated['amount'],
                'description' => trim($validated['description']),
                'receipt_path' => $receiptPath,
                'status' => Reimbursement::STATUS_PENDING,
            ]);

            ActivityLog::log('employee.reimbursement_submitted', "Reimbursement ID: {$reimbursement->id}", [
                'type_id' => $type->id,
                'amount' => $reimbursement->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan reimbursement berhasil dikirim ke HRD.',
                'data' => ['id' => $reimbursement->id, 'status' => $reimbursement->status],
            ], 201);
        });
    }

    public function storeLoan(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'user', 403);

        $validated = $request->validate([
            'principal' => [
                'required',
                'integer',
                'min:1',
                'max:'.config('hr.loan.max_principal', 5_000_000),
            ],
            'tenor_months' => [
                'required',
                'integer',
                'between:1,'.config('hr.loan.max_tenor_months', 12),
            ],
            'purpose' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return DB::transaction(function () use ($user, $validated): JsonResponse {
            $user->newQuery()->lockForUpdate()->findOrFail($user->id);
            if ($user->loans()->where('status', Loan::STATUS_PENDING)->exists()) {
                throw ValidationException::withMessages([
                    'principal' => 'Masih ada pengajuan pinjaman yang menunggu keputusan HRD.',
                ]);
            }

            $loan = $user->loans()->create([
                'principal' => $validated['principal'],
                'tenor_months' => $validated['tenor_months'],
                'purpose' => trim($validated['purpose']),
                'status' => Loan::STATUS_PENDING,
            ]);

            ActivityLog::log('employee.loan_submitted', "Loan ID: {$loan->id}", [
                'principal' => $loan->principal,
                'tenor_months' => $loan->tenor_months,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan kasbon berhasil dikirim ke HRD.',
                'data' => ['id' => $loan->id, 'status' => $loan->status],
            ], 201);
        });
    }
}
