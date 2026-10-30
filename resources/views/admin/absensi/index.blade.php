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

    <div class="space-y-6">
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
            @foreach ($statCards as $i => $card)
                <div class="lucent-panel rounded-3xl p-6 group relative overflow-hidden" data-aos="fade-up" data-aos-delay="{{ $i * 100 }}">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-{{ $card['tone'] }}-500/5 rounded-full blur-2xl group-hover:bg-{{ $card['tone'] }}-500/10 transition-colors"></div>
                    
                    <div class="relative flex flex-col h-full">
                        <div class="w-12 h-12 rounded-2xl bg-{{ $card['tone'] }}-500/10 text-{{ $card['tone'] }}-600 flex items-center justify-center mb-4 transition-transform group-hover:scale-110 group-hover:rotate-3">
                            <i class="fas {{ $card['icon'] }} text-xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ $card['label'] }}</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $card['value'] }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2 italic">{{ $card['desc'] }} bulan ini</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Progress Summary --}}
        @php
            $circ = 2 * pi() * 34;
            $percentProduksi = $statsProduksi['total'] > 0 ? ($statsProduksi['hadir'] / $statsProduksi['total']) * 100 : 0;
            $percentOffice = $statsOffice['total'] > 0 ? ($statsOffice['hadir'] / $statsOffice['total']) * 100 : 0;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="lucent-panel rounded-3xl p-6 flex items-center justify-between group">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-orange-500/10 text-orange-600 flex items-center justify-center group-hover:rotate-6 transition-transform shrink-0">
                        <i class="fas fa-industry text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-500 uppercase tracking-widest text-xs mb-1">Produksi</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black">{{ $statsProduksi['hadir'] }}</span>
                            <span class="text-slate-400 font-medium text-sm">/ {{ $statsProduksi['total'] }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5 italic">Personel hadir hari ini</p>
                    </div>
                </div>
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-20 h-20 transform -rotate-90">
                        <circle cx="40" cy="40" r="28" stroke="currentColor" stroke-width="6" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                        <circle cx="40" cy="40" r="28" stroke="currentColor" stroke-width="6" fill="transparent" stroke-dasharray="{{ 2 * pi() * 28 }}" stroke-dashoffset="{{ (1 - $percentProduksi/100) * (2 * pi() * 28) }}" class="text-orange-500 transition-all duration-1000" stroke-linecap="round" />
                    </svg>
                    <span class="absolute text-xs font-black">{{ round($percentProduksi) }}%</span>
                </div>
            </div>

            <div class="lucent-panel rounded-3xl p-6 flex items-center justify-between group">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center group-hover:rotate-6 transition-transform shrink-0">
                        <i class="fas fa-building text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-500 uppercase tracking-widest text-xs mb-1">Office</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black">{{ $statsOffice['hadir'] }}</span>
                            <span class="text-slate-400 font-medium text-sm">/ {{ $statsOffice['total'] }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5 italic">Personel hadir hari ini</p>
                    </div>
                </div>
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-20 h-20 transform -rotate-90">
                        <circle cx="40" cy="40" r="28" stroke="currentColor" stroke-width="6" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                        <circle cx="40" cy="40" r="28" stroke="currentColor" stroke-width="6" fill="transparent" stroke-dasharray="{{ 2 * pi() * 28 }}" stroke-dashoffset="{{ (1 - $percentOffice/100) * (2 * pi() * 28) }}" class="text-blue-500 transition-all duration-1000" stroke-linecap="round" />
                    </svg>
                    <span class="absolute text-xs font-black">{{ round($percentOffice) }}%</span>
                </div>
            </div>
        </div>

        {{-- Comparison Section --}}
        @if($currentStatus === 'semua' && $comparison)
            <section class="lucent-panel rounded-3xl p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base">Perbandingan Status Pegawai</h3>
                        <p class="text-xs text-slate-500">Statistik antara karyawan Organik dan Freelance</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    @php
                        $cmpItems = [
                            ['key' => 'hadir',  'tone' => 'emerald', 'label' => 'Kehadiran', 'icon' => 'fa-check-circle'],
                            ['key' => 'izin',   'tone' => 'amber',   'label' => 'Izin',      'icon' => 'fa-sticky-note'],
                            ['key' => 'sakit',  'tone' => 'rose',    'label' => 'Sakit',     'icon' => 'fa-medkit'],
                            ['key' => 'lembur', 'tone' => 'indigo',  'label' => 'Lembur',    'icon' => 'fa-clock'],
                        ];
                    @endphp
                    @foreach ($cmpItems as $item)
                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 group hover:border-{{ $item['tone'] }}-500/30 transition-all">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-{{ $item['tone'] }}-500/10 text-{{ $item['tone'] }}-600 flex items-center justify-center">
                                    <i class="fas {{ $item['icon'] }} text-xs"></i>
                                </div>
                                <h4 class="font-bold text-sm text-slate-700 dark:text-slate-300">{{ $item['label'] }}</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-medium tracking-tight">Organik</span>
                                    <span class="font-black text-slate-900 dark:text-white">{{ $comparison['organik'][$item['key']] }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-medium tracking-tight">Freelance</span>
                                    <span class="font-black text-slate-900 dark:text-white">{{ $comparison['freelance'][$item['key']] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Tables Section --}}
        @if(in_array($currentStatus, ['semua', 'freelance'], true))
            <div x-data="{ activeTab: '{{ $currentStatus === 'freelance' ? 'freelance' : 'semua' }}' }" class="space-y-8">
                {{-- Modern Tab Bar --}}
                @if($currentStatus === 'semua')
                    <div class="flex items-center p-1.5 bg-slate-100 dark:bg-slate-900 rounded-2xl w-fit mx-auto lg:mx-0 shadow-inner">
                        <button @click="activeTab = 'semua'" :class="activeTab === 'semua' ? 'bg-white dark:bg-slate-800 text-emerald-500 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-6 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-th-large text-xs"></i>
                            <span>Semua</span>
                        </button>
                        <button @click="activeTab = 'organik'" :class="activeTab === 'organik' ? 'bg-white dark:bg-slate-800 text-emerald-500 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-6 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-users-cog text-xs"></i>
                            <span>Organik</span>
                        </button>
                        <button @click="activeTab = 'freelance'" :class="activeTab === 'freelance' ? 'bg-white dark:bg-slate-800 text-emerald-500 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-6 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-users text-xs"></i>
                            <span>Freelance</span>
                        </button>
                    </div>
                @endif

                @php
                    $tables = [
                        ['key' => 'organik',   'title' => 'Organik',   'icon' => 'fa-users-cog', 'rows' => $dailyStatusesOrganik ?? []],
                        ['key' => 'freelance', 'title' => 'Freelance', 'icon' => 'fa-users',     'rows' => $dailyStatusesFreelance ?? []],
                        ['key' => 'borongan',  'title' => 'Borongan',  'icon' => 'fa-hammer',    'rows' => $dailyStatusesBorongan ?? []],
                        ['key' => 'magang',    'title' => 'Magang',    'icon' => 'fa-graduation-cap', 'rows' => $dailyStatusesMagang ?? []],
                    ];
                @endphp

                @foreach ($tables as $tab)
                    @if($currentStatus === 'semua' || $currentStatus === $tab['key'])
                        <div x-show="activeTab === 'semua' || activeTab === '{{ $tab['key'] }}'" x-transition.opacity class="lucent-table-wrapper">
                            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                                        <i class="fas {{ $tab['icon'] }}"></i>
                                    </div>
                                    <h3 class="text-xl font-bold">Karyawan {{ $tab['title'] }}</h3>
                                </div>

                                <form action="{{ url()->current() }}" method="GET" class="relative group">
                                    <input type="search" name="search_{{ $tab['key'] }}" placeholder="Cari nama..." class="lucent-input pl-10 w-full md:w-64 group-hover:border-emerald-500/50" value="{{ request('search_' . $tab['key']) }}">
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
                                        @forelse ($tab['rows'] as $daily)
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
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="lucent-panel rounded-3xl p-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">Tren Kehadiran</h3>
                        <p class="text-xs text-slate-500">Statistik bulanan tahun {{ $year }}</p>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="grafikBulananLine"></canvas>
                </div>
            </div>

            <div class="lucent-panel rounded-3xl p-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">Distribusi Status</h3>
                        <p class="text-xs text-slate-500">Persentase status bulan ini</p>
                    </div>
                </div>
                <div class="h-80 flex items-center justify-center">
                    <canvas id="grafikStatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Logika grafik dipindahkan ke sini atau tetap di controller, 
            // pastikan variabel data grafiknya sesuai dengan yang dikirim controller.
            document.addEventListener('DOMContentLoaded', function() {
                const ctxLine = document.getElementById('grafikBulananLine').getContext('2d');
                new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($monthsLabels ?? []) !!},
                        datasets: [{
                            label: 'Hadir',
                            data: {!! json_encode($hadirData ?? []) !!},
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 4,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: '#10b981'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                           y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });

                const ctxPie = document.getElementById('grafikStatus').getContext('2d');
                new Chart(ctxPie, {
                    type: 'doughnut',
                    data: {
                        labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                        datasets: [{
                            data: [{{ $totalHadir }}, {{ $totalIzin }}, {{ $totalSakit }}, {{ $totalAlpha ?? 0 }}],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#64748b'],
                            borderWidth: 0,
                            cutout: '70%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>