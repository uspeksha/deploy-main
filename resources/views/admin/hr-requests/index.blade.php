<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Antrean Pengajuan HR</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tinjau reimbursement dan pinjaman karyawan.</p>
        </div>
    </x-slot>

    <div class="ui-page space-y-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Reimbursement</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Setujui atau tolak pengajuan; tandai pembayaran setelah transfer dilakukan.</p>
            </div>
            <div class="ui-table-shell">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">Karyawan</th>
                            <th class="px-4 py-3 text-left">Kategori / keterangan</th>
                            <th class="px-4 py-3 text-left">Nominal</th>
                            <th class="px-4 py-3 text-left">Bukti</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reimbursements as $reimbursement)
                            <tr>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-semibold">{{ $reimbursement->user?->name ?? 'Karyawan dihapus' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $reimbursement->user?->id_karyawan ?? '-' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $reimbursement->created_at?->format('d M Y H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-medium">{{ $reimbursement->type?->name ?? '-' }}</span>
                                    <span class="block max-w-sm whitespace-pre-wrap text-xs text-slate-500">{{ $reimbursement->description ?: '-' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold">Rp {{ number_format($reimbursement->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <a class="ui-button-secondary min-h-9 px-3 py-1.5" href="{{ route('admin.hr-requests.reimbursements.receipt', $reimbursement) }}">
                                        <i class="fas fa-download"></i> Unduh
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ ucfirst($reimbursement->status) }}</span>
                                    @if ($reimbursement->rejection_reason)
                                        <span class="mt-1 block max-w-xs text-xs text-rose-700">{{ $reimbursement->rejection_reason }}</span>
                                    @endif
                                    @if ($reimbursement->payment_reference)
                                        <span class="mt-1 block text-xs text-slate-500">Ref: {{ $reimbursement->payment_reference }}</span>
                                    @endif
                                </td>
                                <td class="min-w-64 px-4 py-3">
                                    @if ($reimbursement->status === \App\Models\Reimbursement::STATUS_PENDING)
                                        <div class="flex flex-col gap-2">
                                            <form method="POST" action="{{ route('admin.hr-requests.reimbursements.action', [$reimbursement, 'approve']) }}">
                                                @csrf
                                                <button class="ui-button-primary min-h-9 px-3 py-1.5" type="submit">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.hr-requests.reimbursements.action', [$reimbursement, 'reject']) }}" class="flex flex-col gap-2">
                                                @csrf
                                                <input class="ui-control w-full" name="reason" required minlength="5" maxlength="1000" placeholder="Alasan penolakan">
                                                <button class="ui-button-danger min-h-9 px-3 py-1.5" type="submit">Tolak</button>
                                            </form>
                                        </div>
                                    @elseif ($reimbursement->status === \App\Models\Reimbursement::STATUS_APPROVED)
                                        <form method="POST" action="{{ route('admin.hr-requests.reimbursements.action', [$reimbursement, 'paid']) }}" class="flex flex-col gap-2">
                                            @csrf
                                            <input class="ui-control w-full" name="payment_reference" required minlength="3" maxlength="100" placeholder="Referensi transfer">
                                            <button class="ui-button-primary min-h-9 px-3 py-1.5" type="submit">Tandai dibayar</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-500">Tidak ada tindakan lanjutan.</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada pengajuan reimbursement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $reimbursements->links() }}
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Kasbon / pinjaman</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pinjaman tanpa bunga dijadwalkan mulai bulan berikutnya. Setelah potongan atau pembayaran benar-benar dilakukan di luar aplikasi, HRD mencatat referensinya di sini.</p>
            </div>
            <div class="ui-table-shell">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">Karyawan</th>
                            <th class="px-4 py-3 text-left">Nominal / tenor</th>
                            <th class="px-4 py-3 text-left">Tujuan</th>
                            <th class="px-4 py-3 text-left">Status / cicilan</th>
                            <th class="px-4 py-3 text-left">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loans as $loan)
                            <tr>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-semibold">{{ $loan->user?->name ?? 'Karyawan dihapus' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $loan->user?->id_karyawan ?? '-' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $loan->created_at?->format('d M Y H:i') }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    <span class="font-semibold">Rp {{ number_format($loan->principal, 0, ',', '.') }}</span>
                                    <span class="block text-xs text-slate-500">{{ $loan->tenor_months }} bulan · Rp {{ number_format((int) ceil($loan->principal / max(1, $loan->tenor_months)), 0, ',', '.') }}/bulan</span>
                                </td>
                                <td class="max-w-sm whitespace-pre-wrap px-4 py-3 text-sm">{{ $loan->purpose ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ ucfirst($loan->status) }}</span>
                                    @if ($loan->rejection_reason)
                                        <span class="mt-1 block max-w-xs text-xs text-rose-700">{{ $loan->rejection_reason }}</span>
                                    @endif
                                    @foreach ($loan->installments as $installment)
                                        <div class="mt-2 border-t border-slate-200 pt-2 text-xs text-slate-500">
                                            <span class="block">Cicilan {{ $installment->installment_no }} · {{ $installment->due_date?->format('d M Y') }} · Rp {{ number_format($installment->amount, 0, ',', '.') }} · {{ ucfirst($installment->status) }}</span>
                                            @if ($installment->status === \App\Models\LoanInstallment::STATUS_APPROVED)
                                                <form method="POST" action="{{ route('admin.hr-requests.installments.paid', $installment) }}" class="mt-2 flex flex-col gap-2">
                                                    @csrf
                                                    <input class="ui-control w-full" name="payment_reference" required minlength="3" maxlength="100" placeholder="Referensi pembayaran">
                                                    <button class="ui-button-primary min-h-9 px-3 py-1.5" type="submit">Catat cicilan lunas</button>
                                                </form>
                                            @elseif ($installment->payment_reference)
                                                <span class="block">Ref: {{ $installment->payment_reference }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="min-w-64 px-4 py-3">
                                    @if ($loan->status === \App\Models\Loan::STATUS_PENDING)
                                        <div class="flex flex-col gap-2">
                                            <form method="POST" action="{{ route('admin.hr-requests.loans.action', [$loan, 'approve']) }}">
                                                @csrf
                                                <button class="ui-button-primary min-h-9 px-3 py-1.5" type="submit">Setujui &amp; buat cicilan</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.hr-requests.loans.action', [$loan, 'reject']) }}" class="flex flex-col gap-2">
                                                @csrf
                                                <input class="ui-control w-full" name="reason" required minlength="5" maxlength="1000" placeholder="Alasan penolakan">
                                                <button class="ui-button-danger min-h-9 px-3 py-1.5" type="submit">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500">Tidak ada tindakan lanjutan.</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada pengajuan pinjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $loans->links() }}
        </section>
    </div>
</x-app-layout>
