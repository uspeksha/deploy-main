<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Slip Gaji</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->slip_number }}</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('admin.payroll.slip', [$period, $item]) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Unduh PDF</a>
                <button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cetak Slip</button>
                <a href="{{ route('admin.payroll.index', ['period_id' => $period->id]) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Kembali</a>
            </div>
        </div>
    </x-slot>

    @php
        $money = fn ($amount) => 'Rp' . number_format((int) $amount, 0, ',', '.');
        $statusLabels = [
            'draft' => 'DRAFT - BELUM FINAL',
            'pending_approval' => 'MENUNGGU PERSETUJUAN',
            'approved' => 'DISETUJUI - BELUM DIBAYARKAN',
            'paid' => 'DIBAYARKAN (KONFIRMASI MANUAL)',
            'cancelled' => 'DIBATALKAN',
        ];
    @endphp
    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <article id="slip-preview" class="mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 print:max-w-none print:border-0 print:p-0 print:shadow-none">
            <header class="flex items-center gap-5 border-b-4 border-emerald-600 pb-5">
                <img src="{{ asset('images/logo-followme.png') }}" alt="Logo Followme" class="h-14 w-auto">
                <div>
                    <p class="text-2xl font-black tracking-widest text-emerald-700 dark:text-emerald-400">FOLLOWME</p>
                    <h1 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">SLIP GAJI KARYAWAN FREELANCE</h1>
                </div>
            </header>

            <dl class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500 dark:text-gray-400">Nomor slip</dt><dd class="font-semibold">{{ $item->slip_number }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Periode</dt><dd class="font-semibold">{{ $period->label }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Tanggal terbit</dt><dd class="font-semibold">{{ now()->format('d/m/Y') }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Rentang</dt><dd class="font-semibold">{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}</dd></div>
            </dl>

            <h2 class="mt-6 border-b border-gray-200 pb-2 font-bold dark:border-gray-700">Identitas Karyawan</h2>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500 dark:text-gray-400">ID Karyawan</dt><dd class="font-semibold">{{ $item->employee_id ?? '-' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Nama lengkap</dt><dd class="font-semibold">{{ $item->employee_name }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Jenis karyawan</dt><dd class="font-semibold">{{ ucfirst($item->employment_type) }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Departemen</dt><dd class="font-semibold">{{ $item->department ?? '-' }}</dd></div>
            </dl>

            <h2 class="mt-6 border-b border-gray-200 pb-2 font-bold dark:border-gray-700">Pendapatan & Potongan</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr><td class="py-2">{{ $item->work_days }} hari kerja × {{ $money($item->daily_rate) }}</td><td class="py-2 text-right">{{ $money($item->gross_pay) }}</td></tr>
                        <tr><td colspan="2" class="py-2 font-semibold">Rincian keterlambatan</td></tr>
                        @forelse ($item->latenessDetails as $detail)
                            <tr>
                                <td class="py-2 pl-4 text-gray-600 dark:text-gray-300">
                                    {{ $detail->work_date->format('d/m/Y') }} — {{ $detail->late_minutes }} menit
                                    ({{ $detail->penalty_blocks }} blok 30 menit)
                                </td>
                                <td class="py-2 text-right">{{ $money($detail->deduction_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2 pl-4 text-gray-500" colspan="2">Tidak ada keterlambatan tercatat.</td></tr>
                        @endforelse
                        <tr><td class="py-2">Potongan keterlambatan</td><td class="py-2 text-right">{{ $money($item->late_deduction) }}</td></tr>
                        <tr><td class="py-2">Potongan lain</td><td class="py-2 text-right">{{ $money($item->other_deductions) }}</td></tr>
                        <tr class="text-base font-bold text-emerald-800 dark:text-emerald-300"><td class="py-3">Gaji bersih diterima</td><td class="py-3 text-right">{{ $money($item->net_pay) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 rounded-lg border border-gray-200 px-4 py-3 text-sm dark:border-gray-700">
                <strong>Status: {{ $statusLabels[$period->status] ?? $period->status }}</strong>
                @if ($period->status === 'paid')
                    <p class="mt-1">Pembayaran manual dikonfirmasi {{ $period->paid_at?->format('d/m/Y H:i') }} oleh {{ $period->payer?->name ?? '-' }}. Referensi: {{ $period->payment_reference }}</p>
                @elseif ($period->status === 'approved')
                    <p class="mt-1">Disetujui {{ $period->approved_at?->format('d/m/Y H:i') }} oleh {{ $period->approver?->name ?? '-' }}.</p>
                @endif
            </div>
            <p class="mt-5 text-xs text-gray-500 dark:text-gray-400">Keterlambatan memakai nilai yang tersimpan pada absensi (baseline pukul 08.00). Potongan Rp7.500 dikenakan untuk setiap blok penuh 30 menit per tanggal. Slip ini menggunakan snapshot payroll dan tidak mengubah data absensi.</p>
            <div class="mt-10 text-right text-sm">
                <p>Petugas Payroll Followme</p>
                <p class="mt-12 font-semibold">{{ $period->approver?->name ?? 'Belum disetujui' }}</p>
            </div>
        </article>
    </div>

    @push('styles')
        <style>
            @media print {
                body * { visibility: hidden; }
                #slip-preview, #slip-preview * { visibility: visible; }
                #slip-preview { position: absolute; inset: 0; width: 100%; }
            }
        </style>
    @endpush
</x-app-layout>
