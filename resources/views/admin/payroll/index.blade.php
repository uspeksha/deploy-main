<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Payroll Freelance</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rekap berbasis absensi yang telah disetujui.</p>
            </div>
        </div>
    </x-slot>

    @php
        $statusLabels = [
            'draft' => 'Draft',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'paid' => 'Dibayarkan (manual)',
            'cancelled' => 'Dibatalkan',
        ];
        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
            'pending_approval' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        ];
        $flashClasses = [
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
            'error' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200',
        ];
        $money = fn ($amount) => 'Rp' . number_format((int) $amount, 0, ',', '.');
        $defaultYear = $canRecreatePeriod ? $selectedPeriod->year : now()->year;
        $defaultMonth = $canRecreatePeriod ? $selectedPeriod->month : now()->month;
        $defaultPeriodNumber = $canRecreatePeriod ? $selectedPeriod->period_number : 1;
    @endphp

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @foreach (['success' => 'emerald', 'warning' => 'amber', 'error' => 'rose'] as $flash => $color)
                @if (session($flash))
                    <div class="rounded-xl border px-4 py-3 text-sm {{ $flashClasses[$flash] }}" role="status">
                        {{ session($flash) }}
                    </div>
                @endif
            @endforeach
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $canRecreatePeriod ? 'Buat Ulang Periode Payroll' : 'Buat Draft Payroll' }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $canRecreatePeriod ? 'Periode yang dibatalkan dipilih otomatis. Klik tombol untuk membuat draft baru dari absensi yang sudah disetujui.' : 'Nilai dihitung sekali dari data absensi yang disetujui dan disimpan sebagai snapshot.' }}
                    </p>
                </div>
                @if ($canRecreatePeriod)
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                        <p class="font-semibold">Periode yang akan dibuat ulang: {{ $selectedPeriod->label }}</p>
                        <p class="mt-1">Payroll lama tetap berstatus Dibatalkan untuk riwayat. Draft baru akan dihitung dari absensi yang sudah disetujui.</p>
                        <p class="mt-1">Alasan pembatalan lama: {{ $selectedPeriod->cancellation_reason }}</p>
                    </div>
                @elseif ($selectedPeriod?->status === 'cancelled')
                    <div class="mb-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Periode ini sudah dibuat ulang. Pilih baris periode yang lebih baru di Riwayat Periode untuk melihat atau melanjutkan prosesnya.
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.payroll.store') }}" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @csrf
                    <div>
                        <label for="payroll-year" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                        <select id="payroll-year" name="year" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @for ($year = now()->year; $year >= 2020; $year--)
                                <option value="{{ $year }}" @selected((int) old('year', $defaultYear) === $year)>{{ $year }}</option>
                            @endfor
                        </select>
                        @error('year')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="payroll-month" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                        <select id="payroll-month" name="month" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @for ($month = 1; $month <= 12; $month++)
                                <option value="{{ $month }}" @selected((int) old('month', $defaultMonth) === $month)>
                                    {{ \Carbon\Carbon::createFromDate(2026, $month, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                        @error('month')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="payroll-half" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Periode</label>
                        <select id="payroll-half" name="period_number" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="1" @selected((int) old('period_number', $defaultPeriodNumber) === 1)>Minggu 1 (1–15)</option>
                            <option value="2" @selected((int) old('period_number', $defaultPeriodNumber) === 2)>Minggu 2 (16–akhir bulan)</option>
                        </select>
                        @error('period_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <i class="fas fa-calculator mt-1"></i>
                        {{ $canRecreatePeriod ? 'Buat Payroll Baru' : 'Hitung Draft' }}
                    </button>
                </form>
                <p class="mt-4 text-xs leading-5 text-gray-500 dark:text-gray-400">
                    Potongan memakai nilai keterlambatan tersimpan di absensi (aturan aplikasi saat ini berbasis pukul 08.00): Rp7.500 per 30 menit penuh pada setiap tanggal. Tidak ada potongan lain otomatis.
                </p>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Riwayat Periode</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih periode untuk melihat rincian dan proses status.</p>
                    </div>
                    <form method="GET" action="{{ route('admin.payroll.index') }}" class="flex flex-wrap gap-2">
                        <select name="status" aria-label="Filter status payroll" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Semua status</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Filter</button>
                    </form>
                </div>

                @if ($periods->isEmpty())
                    <p class="rounded-xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">Belum ada payroll. Buat draft dari absensi aktual untuk memulai.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Karyawan</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($periods as $periodRow)
                                    <tr class="{{ $selectedPeriod?->id === $periodRow->id ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '' }}">
                                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $periodRow->label }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $periodRow->items_count }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$periodRow->status] ?? $statusClasses['draft'] }}">
                                                {{ $statusLabels[$periodRow->status] ?? $periodRow->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.payroll.index', ['period_id' => $periodRow->id]) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                                Lihat rincian
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $periods->links() }}</div>
                @endif
            </section>

            @if ($selectedPeriod && $items)
                <section class="space-y-5">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Periode terpilih</p>
                                <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $selectedPeriod->label }}</h3>
                                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$selectedPeriod->status] ?? $statusClasses['draft'] }}">
                                    {{ $statusLabels[$selectedPeriod->status] ?? $selectedPeriod->status }}
                                </span>
                                @if ($selectedPeriod->revision > 1)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        Payroll ini dibuat ulang dari catatan lama #{{ $selectedPeriod->revision_of_id }}.
                                    </p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.payroll.export', $selectedPeriod) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="fas fa-file-excel text-emerald-600"></i> Ekspor Excel
                                </a>
                                @if ($selectedPeriod->status === 'draft')
                                    <form method="POST" action="{{ route('admin.payroll.submit', $selectedPeriod) }}" onsubmit="return confirm('Kirim snapshot payroll ini untuk persetujuan?')">
                                        @csrf
                                        <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Ajukan Persetujuan</button>
                                    </form>
                                @elseif ($selectedPeriod->status === 'pending_approval')
                                    <form method="POST" action="{{ route('admin.payroll.approve', $selectedPeriod) }}" onsubmit="return confirm('Setujui nominal payroll yang tersimpan?')">
                                        @csrf
                                        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Setujui Payroll</button>
                                    </form>
                                @elseif ($selectedPeriod->status === 'approved')
                                    <form method="POST" action="{{ route('admin.payroll.paid', $selectedPeriod) }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Konfirmasi pembayaran manual untuk seluruh periode ini?')">
                                        @csrf
                                        <input name="payment_reference" required minlength="3" maxlength="100" placeholder="No. bukti/referensi pembayaran" class="min-w-56 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Konfirmasi Dibayar</button>
                                    </form>
                                @endif
                                @if (in_array($selectedPeriod->status, ['draft', 'pending_approval', 'approved'], true))
                                    <form method="POST" action="{{ route('admin.payroll.cancel', $selectedPeriod) }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Batalkan payroll? Alasannya wajib dicatat.')">
                                        @csrf
                                        <input name="reason" required minlength="5" maxlength="1000" placeholder="Alasan pembatalan" class="min-w-48 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <button class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/30">Batalkan</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if ($selectedPeriod->status === 'paid')
                            <p class="mt-4 text-sm text-emerald-700 dark:text-emerald-300">
                                Pembayaran manual dikonfirmasi {{ $selectedPeriod->paid_at?->format('d/m/Y H:i') }}
                                oleh {{ $selectedPeriod->payer?->name ?? 'pengguna yang sudah dihapus' }}.
                                Referensi: {{ $selectedPeriod->payment_reference }}
                            </p>
                            <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Rekonsiliasi manual koreksi pascapembayaran</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Payroll yang dibayar tidak diubah dan tidak diproses ulang otomatis. Catat selisih dan penyelesaian manual di sini untuk audit.</p>
                                <form method="POST" action="{{ route('admin.payroll.corrections.store', $selectedPeriod) }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3" onsubmit="return confirm('Catat koreksi untuk rekonsiliasi manual? Nominal payroll tidak berubah.')">
                                    @csrf
                                    <input type="number" name="adjustment_amount" required min="-1000000000" max="1000000000" placeholder="Selisih Rp (+/-)" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <input type="text" name="reason" required minlength="5" maxlength="1000" placeholder="Alasan koreksi" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:col-span-2">
                                    <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 sm:col-span-3 sm:justify-self-start">Catat Koreksi</button>
                                </form>
                                @if ($selectedPeriod->corrections->isNotEmpty())
                                    <div class="mt-4 overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead><tr class="border-b text-left text-xs uppercase text-gray-500"><th class="py-2 pr-4">Selisih</th><th class="py-2 pr-4">Alasan / Pencatat</th><th class="py-2 pr-4">Status</th><th class="py-2">Penyelesaian</th></tr></thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach ($selectedPeriod->corrections as $correction)
                                                    <tr>
                                                        <td class="py-3 pr-4 font-semibold">{{ $money($correction->adjustment_amount) }}</td>
                                                        <td class="py-3 pr-4">{{ $correction->reason }}<div class="text-xs text-gray-500">{{ $correction->recorder?->name ?? '-' }} · {{ $correction->created_at->format('d/m/Y H:i') }}</div></td>
                                                        <td class="py-3 pr-4">{{ $correction->status === 'open' ? 'Perlu rekonsiliasi' : 'Direkonsiliasi' }}</td>
                                                        <td class="py-3">
                                                            @if ($correction->status === 'open')
                                                                <form method="POST" action="{{ route('admin.payroll.corrections.reconcile', [$selectedPeriod, $correction]) }}" class="flex flex-wrap gap-2">
                                                                    @csrf
                                                                    <input name="settlement_reference" required minlength="3" maxlength="100" placeholder="Referensi penyelesaian" class="rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                                    <input name="settlement_note" maxlength="1000" placeholder="Catatan (opsional)" class="rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                                    <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white">Tandai selesai</button>
                                                                </form>
                                                            @else
                                                                {{ $correction->settled_at?->format('d/m/Y H:i') }} · {{ $correction->settler?->name ?? '-' }}
                                                                <div class="text-xs text-gray-500">{{ $correction->settlement_reference }} {{ $correction->settlement_note }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @elseif ($selectedPeriod->status === 'cancelled')
                            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200">
                                <p class="font-semibold">Riwayat payroll lama — tetap Dibatalkan</p>
                                <p class="mt-1">Dibatalkan {{ $selectedPeriod->cancelled_at?->format('d/m/Y H:i') }}. Alasan pembatalan: {{ $selectedPeriod->cancellation_reason }}</p>
                                <p class="mt-1">Status ini tidak berubah. Draft baru akan muncul sebagai baris periode terpisah di Riwayat Periode.</p>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['label' => 'Hari Kerja Freelance', 'value' => number_format((int) $selectedPeriod->items()->sum('work_days'))],
                            ['label' => 'Total Gaji Kotor', 'value' => $money($totals['gross_pay'])],
                            ['label' => 'Total Potongan', 'value' => $money((int) $totals['late_deduction'] + (int) $totals['other_deductions'])],
                            ['label' => 'Total Gaji Bersih', 'value' => $money($totals['net_pay'])],
                        ] as $card)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rincian Payroll</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nominal berasal dari snapshot backend; perubahan absensi setelah draft dibuat tidak mengubah hasil ini.</p>
                            </div>
                            <form method="GET" action="{{ route('admin.payroll.index') }}" class="flex gap-2">
                                <input type="hidden" name="period_id" value="{{ $selectedPeriod->id }}">
                                <input name="search" value="{{ request('search') }}" placeholder="Cari ID atau nama" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <select name="sort" aria-label="Urutkan payroll" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @foreach (['employee_name' => 'Nama', 'employee_id' => 'ID karyawan', 'work_days' => 'Hari kerja', 'gross_pay' => 'Gaji kotor', 'late_occurrences' => 'Kejadian terlambat', 'late_deduction' => 'Potongan', 'net_pay' => 'Gaji bersih'] as $column => $label)
                                        <option value="{{ $column }}" @selected(request('sort', 'employee_name') === $column)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="direction" aria-label="Arah pengurutan" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="asc" @selected(request('direction', 'asc') === 'asc')>Naik</option>
                                    <option value="desc" @selected(request('direction') === 'desc')>Turun</option>
                                </select>
                                <button class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">Cari</button>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-[1500px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                    <tr>
                                        <th class="px-3 py-3">No.</th>
                                        <th class="px-3 py-3">ID / Nama</th>
                                        <th class="px-3 py-3">Jenis / Departemen</th>
                                        <th class="px-3 py-3">Hari</th>
                                        <th class="px-3 py-3">Tarif</th>
                                        <th class="px-3 py-3">Kotor</th>
                                        <th class="px-3 py-3">Terlambat</th>
                                        <th class="px-3 py-3">Menit</th>
                                        <th class="px-3 py-3">Potongan Telat</th>
                                        <th class="px-3 py-3">Potongan Lain</th>
                                        <th class="px-3 py-3">Bersih</th>
                                        <th class="px-3 py-3">Status</th>
                                        <th class="px-3 py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($items as $index => $item)
                                        <tr>
                                            <td class="px-3 py-3 text-gray-500">{{ $items->firstItem() + $index }}</td>
                                            <td class="px-3 py-3">
                                                <div class="font-semibold text-gray-900 dark:text-white">{{ $item->employee_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $item->employee_id ?? '-' }}</div>
                                            </td>
                                            <td class="px-3 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($item->employment_type) }}<br>{{ $item->department ?? '-' }}</td>
                                            <td class="px-3 py-3">{{ $item->work_days }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $money($item->daily_rate) }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $money($item->gross_pay) }}</td>
                                            <td class="px-3 py-3">{{ $item->late_occurrences }}x</td>
                                            <td class="px-3 py-3">{{ $item->total_late_minutes }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $money($item->late_deduction) }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $money($item->other_deductions) }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap font-bold">{{ $money($item->net_pay) }}</td>
                                            <td class="px-3 py-3">
                                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$selectedPeriod->status] ?? $statusClasses['draft'] }}">
                                                    {{ $statusLabels[$selectedPeriod->status] ?? $selectedPeriod->status }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex flex-col gap-2">
                                                    <a href="{{ route('admin.payroll.slip.preview', [$selectedPeriod, $item]) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Lihat slip / cetak</a>
                                                    <a href="{{ route('admin.payroll.slip', [$selectedPeriod, $item]) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Unduh slip PDF</a>
                                                    <details class="max-w-72">
                                                        <summary class="cursor-pointer font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-300">Rincian hitung</summary>
                                                        <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                                                            {{ $item->work_days }} hari × {{ $money($item->daily_rate) }} = {{ $money($item->gross_pay) }}.
                                                            {{ $item->total_late_minutes }} menit total keterlambatan; potongan tiap tanggal dibulatkan ke bawah per 30 menit penuh.
                                                        </p>
                                                        @if ($item->latenessDetails->isNotEmpty())
                                                            <ul class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                                                @foreach ($item->latenessDetails as $detail)
                                                                    <li>
                                                                        {{ $detail->work_date->format('d/m/Y') }}:
                                                                        {{ $detail->late_minutes }} menit,
                                                                        {{ $detail->penalty_blocks }} blok,
                                                                        {{ $money($detail->deduction_amount) }}
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="mt-2 text-xs text-gray-500">Tidak ada keterlambatan tercatat.</p>
                                                        @endif
                                                    </details>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="13" class="px-4 py-10 text-center text-gray-500">Tidak ada karyawan yang cocok pada filter ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $items->links() }}</div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
