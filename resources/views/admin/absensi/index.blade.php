{{-- resources/views/admin/absensi/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    Ringkasan Absensi
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Pantau kehadiran karyawan dan tren bulanan.
                </p>
            </div>
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

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Statistik Karyawan</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Gender seluruh karyawan aktif; ranking dari absensi disetujui pada {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ([
                    ['label' => 'Laki-laki', 'value' => $employeeGenderStats['male'], 'icon' => 'fa-mars', 'icon_classes' => 'bg-blue-500/10 text-blue-600'],
                    ['label' => 'Perempuan', 'value' => $employeeGenderStats['female'], 'icon' => 'fa-venus', 'icon_classes' => 'bg-pink-500/10 text-pink-600'],
                    ['label' => 'Belum diisi', 'value' => $employeeGenderStats['unspecified'], 'icon' => 'fa-user', 'icon_classes' => 'bg-slate-500/10 text-slate-600'],
                ] as $genderCard)
                    <div class="lucent-panel flex items-center gap-4 rounded-xl p-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $genderCard['icon_classes'] }}">
                            <i class="fas {{ $genderCard['icon'] }} text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $genderCard['label'] }}</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $genderCard['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ([
                    ['key' => 'attendance', 'title' => 'Paling Rajin Hadir', 'column' => 'attendance_count', 'icon' => 'fa-trophy', 'icon_classes' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300', 'text_class' => 'text-emerald-700 dark:text-emerald-300'],
                    ['key' => 'sick', 'title' => 'Paling Sering Sakit', 'column' => 'sick_count', 'icon' => 'fa-medkit', 'icon_classes' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300', 'text_class' => 'text-rose-700 dark:text-rose-300'],
                    ['key' => 'late', 'title' => 'Paling Sering Terlambat', 'column' => 'late_count', 'icon' => 'fa-user-clock', 'icon_classes' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300', 'text_class' => 'text-amber-700 dark:text-amber-300'],
                    ['key' => 'leave', 'title' => 'Paling Banyak Izin', 'column' => 'leave_count', 'icon' => 'fa-calendar-minus', 'icon_classes' => 'bg-teal-500/10 text-teal-700 dark:text-teal-300', 'text_class' => 'text-teal-700 dark:text-teal-300'],
                ] as $ranking)
                    <section class="lucent-panel overflow-hidden rounded-xl">
                        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $ranking['icon_classes'] }}">
                                <i class="fas {{ $ranking['icon'] }}"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $ranking['title'] }}</h3>
                        </div>
                        <ol class="divide-y divide-slate-100 px-4 dark:divide-slate-800">
                            @foreach ([1, 2, 3] as $rank)
                                @php
                                    $employee = $employeeRankings[$ranking['key']]->get($rank - 1);
                                @endphp
                                <li data-ranking-slot="{{ $ranking['key'] }}-{{ $rank }}" class="flex min-h-14 items-center gap-3 py-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $rank === 1 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $rank }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium {{ $employee ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500' }}">
                                            {{ $employee?->name ?? 'Belum ada data' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold {{ $employee ? $ranking['text_class'] : 'text-slate-400 dark:text-slate-500' }}">
                                        {{ $employee ? $employee->{$ranking['column']} . ' ' . ($ranking['key'] === 'attendance' ? 'hari' : 'kali') : '—' }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
        </section>

        {{-- Tables Section --}}
        <div class="lucent-table-wrapper">
                            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h3 class="text-xl font-bold">Karyawan Freelance</h3>
                                </div>

                                <form action="{{ url()->current() }}" method="GET" class="relative group">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="search" name="search_freelance" placeholder="Cari nama..." class="lucent-input pl-10 w-full md:w-64 group-hover:border-emerald-500/50" value="{{ request('search_freelance') }}">
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                </form>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="lucent-table">
                                    <thead>
                                        <tr>
                                            <th>Karyawan</th>
                                            <th>Status Presensi</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status Waktu</th>
                                            <th class="text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dailyStatusesFreelance as $daily)
                                            <tr class="group">
                                                <td>
                                                    <div class="flex items-center gap-3">
                                                        @if ($daily['user']->profile_photo_path)
                                                            <div x-data="{ imageFailed: false }" class="relative h-9 w-9 shrink-0">
                                                                <img
                                                                    src="{{ route('admin.storage.file', ['path' => $daily['user']->profile_photo_path]) }}"
                                                                    alt="Foto profil {{ $daily['user']->name }}"
                                                                    class="absolute inset-0 h-full w-full rounded-full object-cover"
                                                                    x-show="!imageFailed"
                                                                    x-on:error="imageFailed = true"
                                                                >
                                                                <div
                                                                    x-show="imageFailed"
                                                                    x-cloak
                                                                    class="flex h-full w-full items-center justify-center rounded-full bg-slate-100 text-xs font-bold uppercase text-slate-500 transition-colors group-hover:bg-emerald-500 group-hover:text-white dark:bg-slate-800"
                                                                >
                                                                    {{ substr($daily['user']->name, 0, 1) }}
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold uppercase text-slate-500 transition-colors group-hover:bg-emerald-500 group-hover:text-white dark:bg-slate-800">
                                                                {{ substr($daily['user']->name, 0, 1) }}
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <p class="font-bold text-slate-900 dark:text-white">{{ $daily['user']->name }}</p>
                                                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-tighter">{{ $daily['user']->id_karyawan ?? 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5
                                                        @if(str_contains($daily['status'], 'Belum')) bg-slate-100 text-slate-500
                                                        @elseif(str_contains($daily['status'], 'Hadir')) bg-emerald-500/10 text-emerald-600
                                                        @elseif(str_contains($daily['status'], 'Izin')) bg-amber-500/10 text-amber-600
                                                        @elseif(str_contains($daily['status'], 'Sakit')) bg-rose-500/10 text-rose-600
                                                        @else bg-indigo-500/10 text-indigo-600
                                                        @endif">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                                                        {{ $daily['status'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($daily['check_in_time'])
                                                        <div class="flex flex-col">
                                                            <span class="text-sm font-bold">{{ \Carbon\Carbon::parse($daily['check_in_time'])->format('H:i') }}</span>
                                                            <span class="text-[10px] text-slate-400 uppercase font-bold">WIB</span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-300">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($daily['check_out_time'])
                                                        <div class="flex flex-col">
                                                            <span class="text-sm font-bold">{{ \Carbon\Carbon::parse($daily['check_out_time'])->format('H:i') }}</span>
                                                            <span class="text-[10px] text-slate-400 uppercase font-bold">WIB</span>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-300">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php $late = $daily['late_minutes'] ?? 0; @endphp
                                                    @if($daily['check_in_time'])
                                                        @if($late > 0)
                                                            <span class="text-red-600 dark:text-red-400 text-xs font-bold flex items-center gap-1.5">
                                                                <i class="fas fa-clock text-[10px]"></i>
                                                                Terlambat {{ $late }}m
                                                            </span>
                                                        @else
                                                            <span class="text-emerald-500 text-xs font-bold flex items-center gap-1.5">
                                                                <i class="fas fa-check-circle text-[10px]"></i>
                                                                On Time
                                                            </span>
                                                        @endif
                                                    @elseif(str_starts_with($daily['status'], 'Belum'))
                                                        <span class="text-slate-400 text-xs font-semibold">Belum Absen</span>
                                                    @else
                                                        <span class="text-slate-400 text-xs">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-emerald-500 hover:text-white text-slate-400 transition-all">
                                                        <i class="fas fa-external-link-alt text-xs"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-6 py-12 text-center">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-300 mb-4">
                                                            <i class="fas fa-inbox text-2xl"></i>
                                                        </div>
                                                        <p class="text-slate-500 font-medium">Tidak ada data ditemukan</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
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

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 items-stretch gap-6 xl:grid-cols-2">
            <div class="lucent-panel min-w-0 rounded-xl p-4 sm:p-5">
                <div class="mb-5 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
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

            <div class="lucent-panel min-w-0 rounded-xl p-4 sm:p-5">
                <div class="mb-5 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">Distribusi Status</h3>
                        <p class="text-xs text-slate-500">
                            Status absensi freelance, {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                        </p>
                    </div>
                </div>
                <div class="relative flex h-64 min-w-0 items-center justify-center rounded-lg bg-teal-50/70 p-4 dark:bg-teal-950/20 sm:h-72">
                    <div class="flex w-full flex-col items-center justify-center gap-6 sm:flex-row sm:gap-10">
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
                        <ul class="grid w-full max-w-xs grid-cols-2 gap-3 text-sm sm:grid-cols-1">
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
    </div>

</x-app-layout>