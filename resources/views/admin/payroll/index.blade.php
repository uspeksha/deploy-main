<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </span>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Payroll Freelance</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rekap berbasis absensi yang telah disetujui.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $statusLabels = [
            'draft' => 'Draft',
            'exported' => 'Ditarik',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'paid' => 'Dibayarkan (manual)',
            'cancelled' => 'Dibatalkan',
        ];
        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
            'exported' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
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
        $panelClasses = 'ui-panel';
        $fieldClasses = 'ui-control';
        $primaryButtonClasses = 'ui-button-primary';
        $secondaryButtonClasses = 'ui-button-secondary';
        $money = fn ($amount) => 'Rp' . number_format((int) $amount, 0, ',', '.');
        $defaultYear = $canRecreatePeriod ? $selectedPeriod->year : now()->year;
        $defaultMonth = $canRecreatePeriod ? $selectedPeriod->month : now()->month;
        $defaultPeriodNumber = $canRecreatePeriod ? $selectedPeriod->period_number : 1;
    @endphp

    <div class="ui-page">
        <div class="space-y-6">
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

            <section class="{{ $panelClasses }}">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        <i class="fas fa-calculator"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $canRecreatePeriod ? 'Buat Ulang Periode Payroll' : 'Buat Draft Payroll' }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $canRecreatePeriod ? 'Periode yang dibatalkan dipilih otomatis. Klik tombol untuk membuat draft baru dari absensi yang sudah disetujui.' : 'Nilai dihitung sekali dari data absensi yang disetujui dan disimpan sebagai snapshot.' }}
                        </p>
                    </div>
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
                <form method="POST" action="{{ route('admin.payroll.store') }}" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <div>
                        <label for="payroll-year" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                        <select id="payroll-year" name="year" class="{{ $fieldClasses }}">
                            @for ($year = now()->year; $year >= 2020; $year--)
                                <option value="{{ $year }}" @selected((int) old('year', $defaultYear) === $year)>{{ $year }}</option>
                            @endfor
                        </select>
                        @error('year')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="payroll-month" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                        <select id="payroll-month" name="month" class="{{ $fieldClasses }}">
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
                        <select id="payroll-half" name="period_number" class="{{ $fieldClasses }}">
                            <option value="1" @selected((int) old('period_number', $defaultPeriodNumber) === 1)>Minggu 1 (1–15)</option>
                            <option value="2" @selected((int) old('period_number', $defaultPeriodNumber) === 2)>Minggu 2 (16–akhir bulan)</option>
                        </select>
                        @error('period_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="{{ $primaryButtonClasses }}">
                        <i class="fas fa-calculator"></i>
                        {{ $canRecreatePeriod ? 'Buat Payroll Baru' : 'Hitung Draft' }}
                    </button>
                </form>
                <p class="mt-4 text-xs leading-5 text-gray-500 dark:text-gray-400">
                    Potongan memakai nilai keterlambatan tersimpan di absensi (aturan aplikasi saat ini berbasis pukul 08.00): Rp7.500 per 30 menit penuh pada setiap tanggal. Tidak ada potongan lain otomatis.
                </p>
            </section>

            <section class="{{ $panelClasses }}">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <i class="fas fa-clock-rotate-left"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Riwayat Periode</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih periode untuk melihat rincian dan proses status.</p>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('admin.payroll.index') }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <select name="status" aria-label="Filter status payroll" class="{{ $fieldClasses }} sm:w-56">
                            <option value="">Semua status</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="{{ $secondaryButtonClasses }}"><i class="fas fa-filter"></i> Filter</button>
                    </form>
                </div>

                @if ($periods->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center dark:border-gray-600 dark:bg-gray-900/50">
                        <i class="fas fa-folder-open text-2xl text-gray-400 dark:text-gray-500"></i>
                        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">Belum ada riwayat payroll</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat draft dari absensi aktual untuk memulai.</p>
                    </div>
                @else
                    <div class="ui-table-shell">
                        <table class="ui-table">
                            <thead class="bg-gray-50/80 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/70 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Karyawan</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($periods as $periodRow)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ $selectedPeriod?->id === $periodRow->id ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : '' }}">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $periodRow->label }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $periodRow->items_count }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$periodRow->status] ?? $statusClasses['draft'] }}">
                                                {{ $statusLabels[$periodRow->status] ?? $periodRow->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.payroll.index', ['period_id' => $periodRow->id]) }}#rincian-payroll" class="{{ $primaryButtonClasses }} min-h-9 px-3 py-1.5">
                                                <i class="fas fa-eye"></i> Lihat
                                                </a>
                                                <form method="POST" action="{{ route('admin.payroll.destroy', $periodRow) }}" onsubmit="return confirm('Hapus permanen payroll {{ $periodRow->label }} (status: {{ $statusLabels[$periodRow->status] ?? $periodRow->status }}) beserta snapshot, slip, dan catatan koreksinya? Tindakan ini tidak dapat dibatalkan.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-900/50">
                                                        <i class="fas fa-trash-can"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
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
                <section id="rincian-payroll" class="space-y-5">
                    <div class="{{ $panelClasses }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Periode terpilih</p>
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
                            <div class="flex flex-col gap-3 sm:items-end">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.payroll.export', $selectedPeriod) }}" class="{{ $secondaryButtonClasses }}">
                                        <i class="fas fa-file-excel text-emerald-600"></i> Ekspor Excel
                                    </a>
                                    @if ($selectedPeriod->status === 'pending_approval')
                                        <form method="POST" action="{{ route('admin.payroll.approve', $selectedPeriod) }}" onsubmit="return confirm('Setujui nominal payroll yang tersimpan?')">
                                            @csrf
                                            <button class="{{ $primaryButtonClasses }}">
                                                <i class="fas fa-check"></i> Setujui Payroll
                                            </button>
                                        </form>
                                    @elseif ($selectedPeriod->status === 'approved')
                                        <form method="POST" action="{{ route('admin.payroll.paid', $selectedPeriod) }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Konfirmasi pembayaran manual untuk seluruh periode ini?')">
                                            @csrf
                                            <input name="payment_reference" required minlength="3" maxlength="100" placeholder="No. bukti/referensi pembayaran" class="{{ $fieldClasses }} min-w-56">
                                            <button class="{{ $primaryButtonClasses }}">
                                                <i class="fas fa-money-check-alt"></i> Konfirmasi Dibayar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @if (in_array($selectedPeriod->status, ['draft', 'exported', 'pending_approval', 'approved'], true))
                                    <details class="w-full sm:w-auto">
                                        <summary class="cursor-pointer text-sm font-semibold text-gray-500 hover:text-rose-700 dark:text-gray-400 dark:hover:text-rose-300 sm:text-right">
                                            <i class="fas fa-circle-minus mr-1"></i> Opsi lainnya: batalkan payroll
                                        </summary>
                                        <form method="POST" action="{{ route('admin.payroll.cancel', $selectedPeriod) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end" onsubmit="return confirm('Batalkan payroll? Alasannya wajib dicatat.')">
                                            @csrf
                                            <input name="reason" required minlength="5" maxlength="1000" placeholder="Alasan pembatalan (wajib)" aria-label="Alasan pembatalan payroll" class="{{ $fieldClasses }} min-w-56">
                                            <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-900/50"><i class="fas fa-ban"></i> Batalkan</button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        </div>
                        @if ($selectedPeriod->status === 'paid')
                            <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                                Pembayaran manual dikonfirmasi {{ $selectedPeriod->paid_at?->format('d/m/Y H:i') }}
                                oleh {{ $selectedPeriod->payer?->name ?? 'pengguna yang sudah dihapus' }}.
                                Referensi: {{ $selectedPeriod->payment_reference }}
                            </p>
                            <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-700 dark:bg-gray-900/40">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Rekonsiliasi manual koreksi pascapembayaran</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Payroll yang dibayar tidak diubah dan tidak diproses ulang otomatis. Catat selisih dan penyelesaian manual di sini untuk audit.</p>
                                <form method="POST" action="{{ route('admin.payroll.corrections.store', $selectedPeriod) }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3" onsubmit="return confirm('Catat koreksi untuk rekonsiliasi manual? Nominal payroll tidak berubah.')">
                                    @csrf
                                    <input type="number" name="adjustment_amount" required min="-1000000000" max="1000000000" placeholder="Selisih Rp (+/-)" class="{{ $fieldClasses }}">
                                    <input type="text" name="reason" required minlength="5" maxlength="1000" placeholder="Alasan koreksi" class="{{ $fieldClasses }} sm:col-span-2">
                                    <button class="{{ $primaryButtonClasses }} sm:col-span-3 sm:justify-self-start"><i class="fas fa-plus"></i> Catat Koreksi</button>
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
                            <div class="{{ $panelClasses }} relative overflow-hidden p-5">
                                <span class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-emerald-500/5"></span>
                                <p class="relative text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                                <p class="relative mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="{{ $panelClasses }}">
                        <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <i class="fas fa-list-check"></i>
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rincian Payroll</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nominal berasal dari snapshot backend; perubahan absensi setelah draft dibuat tidak mengubah hasil ini.</p>
                                </div>
                            </div>
                            <form method="GET" action="{{ route('admin.payroll.index') }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:flex xl:flex-wrap">
                                <input type="hidden" name="period_id" value="{{ $selectedPeriod->id }}">
                                <input name="search" value="{{ request('search') }}" placeholder="Cari ID atau nama" class="{{ $fieldClasses }} sm:col-span-2 xl:w-56">
                                <select name="sort" aria-label="Urutkan payroll" class="{{ $fieldClasses }} xl:w-48">
                                    @foreach (['employee_name' => 'Nama', 'employee_id' => 'ID karyawan', 'work_days' => 'Hari kerja', 'gross_pay' => 'Gaji kotor', 'late_occurrences' => 'Kejadian terlambat', 'late_deduction' => 'Potongan', 'net_pay' => 'Gaji bersih'] as $column => $label)
                                        <option value="{{ $column }}" @selected(request('sort', 'employee_name') === $column)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="direction" aria-label="Arah pengurutan" class="{{ $fieldClasses }} xl:w-32">
                                    <option value="asc" @selected(request('direction', 'asc') === 'asc')>Naik</option>
                                    <option value="desc" @selected(request('direction') === 'desc')>Turun</option>
                                </select>
                                <button class="{{ $primaryButtonClasses }}"><i class="fas fa-magnifying-glass"></i> Cari</button>
                            </form>
                        </div>
                        <div class="ui-table-shell">
                            <table class="ui-table min-w-[1500px]">
                                <thead class="bg-gray-50/80 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/70 dark:text-gray-400">
                                    <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30">
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
                                                    <a href="{{ route('admin.payroll.slip.preview', [$selectedPeriod, $item]) }}" class="{{ $secondaryButtonClasses }} min-h-9 px-3 py-1.5 text-xs">Lihat slip / cetak</a>
                                                    <a href="{{ route('admin.payroll.slip', [$selectedPeriod, $item]) }}" class="{{ $primaryButtonClasses }} min-h-9 px-3 py-1.5 text-xs">Unduh slip PDF</a>
                                                    <details class="max-w-72 rounded-lg">
                                                        <summary class="cursor-pointer rounded-lg px-2 py-1.5 text-center text-xs font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">Rincian hitung</summary>
                                                        <p class="mt-2 rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-900/60 dark:text-gray-300">
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
                                        <tr><td colspan="13" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Tidak ada karyawan yang cocok pada filter ini.</td></tr>
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
