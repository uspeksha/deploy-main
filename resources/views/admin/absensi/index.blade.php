{{-- resources/views/admin/absensi/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    Attendance <span class="text-emerald-500">Monitor</span>
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Pantau kehadiran karyawan secara real-time melalui dashboard interaktif.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        {{-- Filter Section (Compact & Aesthetic) --}}
        <section class="lucent-panel rounded-2xl p-4 md:p-5">
            <form method="GET" class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm">Filter Statistik</h3>
                        <p class="text-[11px] text-slate-500">Pilih bulan & tahun periode</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <select name="month" class="lucent-input py-2 text-xs">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <select name="year" class="lucent-input py-2 text-xs">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <button type="submit" class="btn-premium py-2 px-5 text-xs shrink-0">
                        <i class="fas fa-sync-alt text-[10px]"></i>
                        <span>Perbarui</span>
                    </button>
                </div>
            </form>
        </section>

        {{-- Summary Cards (Bento Style) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
            @php
                $statCards = [
                    ['tone' => 'emerald', 'label' => 'Total Kehadiran', 'value' => $totalHadir,  'icon' => 'fa-check-circle', 'desc' => 'Karyawan hadir'],
                    ['tone' => 'red',     'label' => 'Total Telat',     'value' => $totalTelat,  'icon' => 'fa-user-clock',   'desc' => 'Karyawan terlambat'],
                    ['tone' => 'amber',   'label' => 'Total Izin',      'value' => $totalIzin,   'icon' => 'fa-sticky-note',  'desc' => 'Izin disetujui'],
                    ['tone' => 'rose',    'label' => 'Total Sakit',     'value' => $totalSakit,  'icon' => 'fa-medkit',       'desc' => 'Keterangan sakit'],
                    ['tone' => 'indigo',  'label' => 'Total Lembur',    'value' => $totalLembur, 'icon' => 'fa-clock',        'desc' => 'Jam lembur tercatat'],
                ];
            @endphp
            @foreach ($statCards as $card)
                <div class="lucent-panel rounded-3xl p-6 group relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-{{ $card['tone'] }}-500/5 rounded-full blur-2xl group-hover:bg-{{ $card['tone'] }}-500/10 transition-colors"></div>
                    
                    <div class="relative flex flex-col h-full">
                        <div class="w-12 h-12 rounded-2xl bg-{{ $card['tone'] }}-500/10 text-{{ $card['tone'] }}-600 flex items-center justify-center mb-4 transition-transform group-hover:scale-110 group-hover:rotate-3">
                            <i class="fas {{ $card['icon'] }} text-xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ $card['label'] }}</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $card['value'] }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2 italic">
                            {{ $card['desc'] }} · {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

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
                                                        <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 font-bold text-xs uppercase group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                                            {{ substr($daily['user']->name, 0, 1) }}
                                                        </div>
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
            $statusColors = ['#10b981', '#f59e0b', '#ef4444', '#64748b'];
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
            <div class="lucent-panel min-w-0 rounded-3xl p-6 md:p-8">
                <div class="mb-5 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">Tren Kehadiran</h3>
                        <p class="text-xs text-slate-500">Kehadiran freelance per bulan, {{ $year }}</p>
                    </div>
                </div>
                <div class="relative h-64 min-w-0 rounded-2xl bg-gradient-to-br from-emerald-50/80 to-white/40 p-3 dark:from-emerald-950/20 dark:to-slate-900/30 sm:h-72 sm:p-4">
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
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.24" />
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0.01" />
                            </linearGradient>
                        </defs>
                        <path d="{{ $areaPath }}" fill="url(#attendance-area)" />
                        <path d="{{ $linePath }}" fill="none" stroke="#10b981" stroke-width="4"
                              stroke-linecap="round" stroke-linejoin="round" />
                        @foreach ($chartPoints as $index => $point)
                            <circle cx="{{ round($point['x'], 1) }}" cy="{{ round($point['y'], 1) }}" r="5"
                                    fill="#10b981" stroke="white" stroke-width="2">
                                <title>{{ $chartMonths[$index] }}: {{ $point['value'] }} kehadiran</title>
                            </circle>
                            <text x="{{ round($point['x'], 1) }}" y="242" text-anchor="middle"
                                  class="fill-slate-500" font-size="11">{{ $chartMonths[$index] }}</text>
                        @endforeach
                    </svg>
                </div>
            </div>

            <div class="lucent-panel min-w-0 rounded-3xl p-6 md:p-8">
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
                <div class="relative flex h-64 min-w-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50/70 to-white/40 p-4 dark:from-indigo-950/20 dark:to-slate-900/30 sm:h-72">
                    <div class="flex w-full flex-col items-center justify-center gap-6 sm:flex-row sm:gap-10">
                        <div class="relative h-44 w-44 shrink-0">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 180 180" role="img"
                                 aria-label="Grafik distribusi status absensi freelance">
                                <circle cx="90" cy="90" r="72" fill="none" stroke="#e2e8f0" stroke-width="28" />
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