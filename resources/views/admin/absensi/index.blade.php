{{-- resources/views/admin/absensi/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    Ringkasan Absensi
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Pantau kehadiran karyawan dan tren bulanan.
                </p>
            </div>
            <a href="{{ route('admin.absensi.reports') }}" class="btn-premium">
                <i class="fas fa-chart-column" aria-hidden="true"></i>
                <span>Analitik lengkap</span>
            </a>
        </div>
    </x-slot>

    <div class="ui-page">
        <section class="lucent-panel rounded-xl p-4 md:p-5" aria-labelledby="work-queue-title">
            <div class="mb-4">
                <h2 id="work-queue-title" class="text-lg font-bold text-slate-900 dark:text-white">Perlu ditindaklanjuti</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Antrean yang masih menunggu proses admin.</p>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 {{ auth()->user()?->role === 'hrd' ? 'xl:grid-cols-4' : '' }}">
                <a href="{{ auth()->user()?->role === 'hrd' ? route('admin.absensi.approval.hrd', ['status' => 'pending']) : route('admin.absensi.index') }}"
                   class="rounded-xl border border-amber-200 bg-amber-50 p-4 transition hover:border-amber-400 dark:border-amber-900 dark:bg-amber-950/30">
                    <span class="text-sm font-semibold text-amber-900 dark:text-amber-200">Pengajuan absensi menunggu</span>
                    <span class="mt-1 block text-2xl font-black text-amber-950 dark:text-amber-100">{{ $pendingAttendanceCount }}</span>
                </a>
                @if (auth()->user()?->role === 'hrd')
                    <a href="{{ route('admin.absensi.approval.hrd', ['section' => 'employees']) }}"
                       class="rounded-xl border border-sky-200 bg-sky-50 p-4 transition hover:border-sky-400 dark:border-sky-900 dark:bg-sky-950/30">
                        <span class="text-sm font-semibold text-sky-900 dark:text-sky-200">Akun karyawan menunggu</span>
                        <span class="mt-1 block text-2xl font-black text-sky-950 dark:text-sky-100">{{ $pendingEmployeeCount }}</span>
                    </a>
                    <a href="{{ route('admin.payroll.index', ['status' => 'pending_approval']) }}"
                       class="rounded-xl border border-violet-200 bg-violet-50 p-4 transition hover:border-violet-400 dark:border-violet-900 dark:bg-violet-950/30">
                        <span class="text-sm font-semibold text-violet-900 dark:text-violet-200">Payroll menunggu persetujuan</span>
                        <span class="mt-1 block text-2xl font-black text-violet-950 dark:text-violet-100">{{ $pendingPayrollCount }}</span>
                    </a>
                    <a href="{{ route('admin.hr-requests.index') }}"
                       class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-400 dark:border-emerald-900 dark:bg-emerald-950/30">
                        <span class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Pengajuan HR menunggu</span>
                        <span class="mt-1 block text-2xl font-black text-emerald-950 dark:text-emerald-100">{{ $pendingHrRequestCount }}</span>
                    </a>
                @endif
            </div>
        </section>

        <section class="lucent-panel rounded-xl p-4 md:p-5">
            <form method="GET" class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm">Filter Statistik</h3>
                        <p class="text-xs text-slate-500">Pilih bulan dan tahun periode</p>
                    </div>
                </div>

                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-[1fr_7rem_auto] lg:w-auto">
                    <select name="month" class="lucent-input">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <select name="year" class="lucent-input">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <button type="submit" class="btn-premium whitespace-nowrap">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        <span>Terapkan</span>
                    </button>
                </div>
            </form>
        </section>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
            @php
                $statCards = [
                    ['icon_classes' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300', 'label' => 'Total Kehadiran', 'value' => $totalHadir,  'icon' => 'fa-check-circle', 'desc' => 'Karyawan hadir'],
                    ['icon_classes' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300', 'label' => 'Total Telat', 'value' => $totalTelat, 'icon' => 'fa-user-clock', 'desc' => 'Karyawan terlambat'],
                    ['icon_classes' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300', 'label' => 'Total Izin', 'value' => $totalIzin, 'icon' => 'fa-sticky-note', 'desc' => 'Izin disetujui'],
                    ['icon_classes' => 'bg-orange-500/10 text-orange-700 dark:text-orange-300', 'label' => 'Total Sakit', 'value' => $totalSakit, 'icon' => 'fa-medkit', 'desc' => 'Keterangan sakit'],
                    ['icon_classes' => 'bg-teal-500/10 text-teal-700 dark:text-teal-300', 'label' => 'Total Lembur', 'value' => $totalLembur, 'icon' => 'fa-clock', 'desc' => 'Jam lembur tercatat'],
                ];
            @endphp
            @foreach ($statCards as $card)
                <div class="lucent-panel rounded-xl p-4 sm:p-5">
                    <div class="flex h-full flex-col">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg {{ $card['icon_classes'] }}">
                            <i class="fas {{ $card['icon'] }} text-xl"></i>
                        </div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $card['value'] }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ $card['desc'] }} · {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        @php
            $chartMonths = collect(range(1, 12))
                ->map(fn ($chartMonth) => \Carbon\Carbon::createFromDate($year, $chartMonth, 1)->translatedFormat('M'));
            $chartAttendance = array_values($grafikBulanan);
            $chartMax = max(3, (int) (ceil(max($chartAttendance) / 3) * 3));
            $chartPoints = collect($chartAttendance)->map(fn ($value, $index) => [
                'x' => 50 + ($index * (650 / 11)),
                'y' => 216 - (($value / $chartMax) * 190),
                'value' => $value,
            ]);
            $linePath = $chartPoints
                ->map(fn ($point, $index) => ($index === 0 ? 'M ' : 'L ').round($point['x'], 1).' '.round($point['y'], 1))
                ->implode(' ');
            $areaPath = $linePath.' L 700 216 L 50 216 Z';
            $statusColors = ['#265970', '#4b7484', '#d29a43', '#8795a0'];
            $statusTotal = array_sum($grafikStatus['values']);
            $statusCircumference = 2 * pi() * 72;
            $statusOffset = 0;
            $statusSlices = collect($grafikStatus['values'])->map(function ($value, $index) use ($statusCircumference, &$statusOffset, $statusTotal, $statusColors, $grafikStatus) {
                $length = $statusTotal > 0 ? ($value / $statusTotal) * $statusCircumference : 0;
                $slice = [
                    'label' => $grafikStatus['labels'][$index],
                    'value' => $value,
                    'color' => $statusColors[$index],
                    'length' => $length,
                    'offset' => $statusOffset,
                ];
                $statusOffset += $length;

                return $slice;
            });
        @endphp

        <section class="space-y-4" aria-labelledby="attendance-charts-title">
            <div>
                <h2 id="attendance-charts-title" class="text-xl font-black text-slate-900 dark:text-white">Grafik Absensi</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Tren dan distribusi kehadiran ditampilkan setelah ringkasan periode.
                </p>
            </div>

            <div class="grid grid-cols-1 items-stretch gap-4 xl:grid-cols-2">
                <div class="lucent-panel flex min-w-0 flex-col rounded-xl p-4 sm:p-5">
                    <div class="mb-5 flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="font-bold">Tren Kehadiran</h3>
                            <p class="text-xs text-slate-500">Kehadiran freelance per bulan, {{ $year }}</p>
                        </div>
                    </div>
                    <div class="relative h-64 min-w-0 rounded-lg bg-emerald-50/60 p-3 dark:bg-emerald-950/20 sm:h-72 sm:p-4">
                        <svg class="h-full w-full overflow-visible" viewBox="0 0 720 260" role="img"
                             aria-label="Grafik tren kehadiran freelance per bulan">
                            @foreach ([0, 1, 2, 3] as $gridStep)
                                @php $gridY = 216 - (($gridStep / 3) * 190); @endphp
                                <line x1="50" y1="{{ $gridY }}" x2="700" y2="{{ $gridY }}"
                                      stroke="currentColor" stroke-opacity="0.12" />
                                <text x="40" y="{{ $gridY + 4 }}" text-anchor="end"
                                      class="fill-slate-400" font-size="11">{{ (int) round($chartMax * $gridStep / 3) }}</text>
                            @endforeach
                            <defs>
                                <linearGradient id="attendance-area" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#265970" stop-opacity="0.24" />
                                    <stop offset="100%" stop-color="#265970" stop-opacity="0.01" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $areaPath }}" fill="url(#attendance-area)" />
                            <path d="{{ $linePath }}" fill="none" stroke="#265970" stroke-width="3"
                                  stroke-linecap="round" stroke-linejoin="round" />
                            @foreach ($chartPoints as $index => $point)
                                <circle cx="{{ round($point['x'], 1) }}" cy="{{ round($point['y'], 1) }}" r="5"
                                        fill="#265970" stroke="white" stroke-width="2">
                                    <title>{{ $chartMonths[$index] }}: {{ $point['value'] }} kehadiran</title>
                                </circle>
                                <text x="{{ round($point['x'], 1) }}" y="242" text-anchor="middle"
                                      class="fill-slate-500" font-size="11">{{ $chartMonths[$index] }}</text>
                            @endforeach
                        </svg>
                    </div>
                </div>

                <div class="lucent-panel flex min-w-0 flex-col rounded-xl p-4 sm:p-5">
                    <div class="mb-5 flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div>
                            <h3 class="font-bold">Distribusi Status</h3>
                            <p class="text-xs text-slate-500">
                                Status absensi freelance, {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="relative flex min-h-64 min-w-0 items-center justify-center rounded-lg bg-teal-50/70 p-4 dark:bg-teal-950/20 sm:min-h-72">
                        <div class="flex w-full flex-col items-center justify-center gap-5 2xl:flex-row 2xl:gap-8">
                            <div class="relative h-44 w-44 shrink-0">
                                <svg class="h-full w-full -rotate-90" viewBox="0 0 180 180" role="img"
                                     aria-label="Grafik distribusi status absensi freelance">
                                    <circle cx="90" cy="90" r="72" fill="none" stroke="#e5e5e5" stroke-width="28" />
                                    @foreach ($statusSlices as $slice)
                                        @if ($slice['length'] > 0)
                                            <circle cx="90" cy="90" r="72" fill="none" stroke="{{ $slice['color'] }}"
                                                    stroke-width="28" stroke-dasharray="{{ $slice['length'] }} {{ $statusCircumference - $slice['length'] }}"
                                                    stroke-dashoffset="-{{ $slice['offset'] }}">
                                                <title>{{ $slice['label'] }}: {{ $slice['value'] }}</title>
                                            </circle>
                                        @endif
                                    @endforeach
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $statusTotal }}</span>
                                    <span class="text-xs text-slate-500">Absensi</span>
                                </div>
                            </div>
                            <ul class="grid w-full max-w-xs grid-cols-2 gap-3 text-sm 2xl:grid-cols-1">
                                @foreach ($statusSlices as $slice)
                                    <li class="flex items-center justify-between gap-3">
                                        <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $slice['color'] }}"></span>
                                            {{ $slice['label'] }}
                                        </span>
                                        <strong class="text-slate-900 dark:text-white">{{ $slice['value'] }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>

</x-app-layout>