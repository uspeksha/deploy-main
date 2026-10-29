{{-- resources/views/admin/absensi/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ $dashboardTitle ?? 'Dashboard Absensi Karyawan' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola dan pantau kehadiran karyawan secara real-time
                </p>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen">
        <div class="ui-page dash">

            {{-- Filter Section --}}
            <div class="ui-panel">
                <div class="ui-section-heading mb-6">
                    <div class="ui-section-icon">
                        <i class="fas fa-filter text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Filter Statistik</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sesuaikan periode statistik</p>
                    </div>
                </div>

                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bulan</label>
                        <select name="month" class="ui-control">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tahun</label>
                        <select name="year" class="ui-control">
                            @for ($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="ui-button-primary w-full">
                            <i class="fas fa-search"></i>
                            <span>Terapkan</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Summary Cards --}}
            @php
                $statCards = [
                    ['tone' => 'emerald', 'label' => 'Total Kehadiran', 'value' => $totalHadir,  'icon' => 'fa-check-circle'],
                    ['tone' => 'amber',   'label' => 'Total Izin',      'value' => $totalIzin,   'icon' => 'fa-sticky-note'],
                    ['tone' => 'rose',    'label' => 'Total Sakit',     'value' => $totalSakit,  'icon' => 'fa-medkit'],
                    ['tone' => 'violet',  'label' => 'Total Lembur',    'value' => $totalLembur, 'icon' => 'fa-clock'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($statCards as $i => $card)
                    <div class="stat-card" data-tone="{{ $card['tone'] }}" style="--i: {{ $i }}">
                        <div class="stat-orb"></div>
                        <div class="relative">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="stat-label">{{ $card['label'] }}</p>
                                    <p class="stat-value" data-count="{{ $card['value'] }}">{{ $card['value'] }}</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas {{ $card['icon'] }} text-white text-xl"></i>
                                </div>
                            </div>
                            <p class="stat-sub">Bulan ini</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Attendance Today Summary (Produksi vs Office) --}}
            @php
                $circ = 2 * pi() * 34;
                $percentProduksi = $statsProduksi['total'] > 0 ? ($statsProduksi['hadir'] / $statsProduksi['total']) * 100 : 0;
                $percentOffice = $statsOffice['total'] > 0 ? ($statsOffice['hadir'] / $statsOffice['total']) * 100 : 0;
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="ui-panel presence flex items-center justify-between" data-tone="orange">
                    <div class="flex items-center gap-4">
                        <div class="presence-icon">
                            <i class="fas fa-industry text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Kehadiran Produksi</h3>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-bold text-gray-800 dark:text-white" data-count="{{ $statsProduksi['hadir'] }}">{{ $statsProduksi['hadir'] }}</span>
                                <span class="text-lg text-gray-500">/ {{ $statsProduksi['total'] }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Karyawan hadir hari ini</p>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-20 h-20 transform -rotate-90">
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200 dark:text-gray-700" />
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ (1 - $percentProduksi/100) * $circ }}" style="--circ: {{ $circ }}; --off: {{ (1 - $percentProduksi/100) * $circ }}" class="ring-fg text-orange-500" stroke-linecap="round" />
                            </svg>
                            <span class="absolute text-sm font-bold text-gray-700 dark:text-gray-300">{{ round($percentProduksi) }}%</span>
                        </div>
                    </div>
                </div>

                <div class="ui-panel presence flex items-center justify-between" data-tone="blue">
                    <div class="flex items-center gap-4">
                        <div class="presence-icon">
                            <i class="fas fa-building text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Kehadiran Office</h3>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-bold text-gray-800 dark:text-white" data-count="{{ $statsOffice['hadir'] }}">{{ $statsOffice['hadir'] }}</span>
                                <span class="text-lg text-gray-500">/ {{ $statsOffice['total'] }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Karyawan hadir hari ini</p>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-20 h-20 transform -rotate-90">
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200 dark:text-gray-700" />
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ (1 - $percentOffice/100) * $circ }}" style="--circ: {{ $circ }}; --off: {{ (1 - $percentOffice/100) * $circ }}" class="ring-fg text-blue-500" stroke-linecap="round" />
                            </svg>
                            <span class="absolute text-sm font-bold text-gray-700 dark:text-gray-300">{{ round($percentOffice) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comparison Cards --}}
            @if($currentStatus === 'semua' && $comparison)
                @php
                    $cmpItems = [
                        ['key' => 'hadir',  'tone' => 'emerald', 'label' => 'Kehadiran', 'icon' => 'fa-check-circle'],
                        ['key' => 'izin',   'tone' => 'amber',   'label' => 'Izin',      'icon' => 'fa-sticky-note'],
                        ['key' => 'sakit',  'tone' => 'rose',    'label' => 'Sakit',     'icon' => 'fa-medkit'],
                        ['key' => 'lembur', 'tone' => 'violet',  'label' => 'Lembur',    'icon' => 'fa-clock'],
                    ];
                @endphp
                <div class="ui-panel">
                    <div class="ui-section-heading mb-6">
                        <div class="ui-section-icon">
                            <i class="fas fa-balance-scale text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Perbandingan Organik vs Freelance</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Statistik berdasarkan status kepegawaian</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($cmpItems as $item)
                            @php
                                $org = $comparison['organik'][$item['key']];
                                $fre = $comparison['freelance'][$item['key']];
                            @endphp
                            <div class="cmp-card" data-tone="{{ $item['tone'] }}">
                                <div class="flex items-center gap-2 mb-4">
                                    <i class="fas {{ $item['icon'] }} cmp-icon text-xl"></i>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-100">{{ $item['label'] }}</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Organik:</span>
                                        <span class="cmp-num font-bold">{{ $org }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Freelance:</span>
                                        <span class="cmp-num font-bold">{{ $fre }}</span>
                                    </div>
                                    <div class="cmp-foot pt-3">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                            @if($org > $fre)
                                                <i class="fas fa-arrow-up cmp-icon"></i> Organik lebih tinggi
                                            @elseif($org < $fre)
                                                <i class="fas fa-arrow-up cmp-icon"></i> Freelance lebih tinggi
                                            @else
                                                <i class="fas fa-equals text-gray-600"></i> Sama rata
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Statistik Bulanan --}}
                <div class="ui-panel">
                    <div class="ui-section-heading mb-6">
                        <div class="ui-section-icon">
                            <i class="fas fa-chart-bar text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Statistik Bulanan</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tahun {{ $year }}</p>
                        </div>
                    </div>
                    <div class="h-80 px-2 pt-2">
                        <canvas id="grafikBulananLine"></canvas>
                    </div>
                </div>

                {{-- Ringkasan Status --}}
                <div class="ui-panel">
                    <div class="ui-section-heading mb-6">
                        <div class="ui-section-icon">
                            <i class="fas fa-chart-pie text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Ringkasan Status</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bulan ini</p>
                        </div>
                    </div>
                    <div class="h-80 flex items-center justify-center">
                        <canvas id="grafikStatus"></canvas>
                    </div>
                </div>
            </div>

            {{-- TODAY'S STATUS: TABEL DENGAN ALPINE.JS TABS --}}
            @if(in_array($currentStatus, ['semua', 'freelance'], true))
                @php
                    $tables = [
                        ['key' => 'organik',   'title' => 'Organik',   'icon' => 'fa-users-cog',      'tone' => 'emerald', 'only_semua' => true,
                         'rows' => $dailyStatusesOrganik ?? [],   'placeholder' => 'Cari nama karyawan organik...',  'noun' => 'karyawan organik'],
                        ['key' => 'freelance', 'title' => 'Freelance', 'icon' => 'fa-users',          'tone' => 'cyan',    'only_semua' => false,
                         'rows' => $dailyStatusesFreelance ?? [], 'placeholder' => 'Cari nama freelance...',         'noun' => 'karyawan freelance'],
                        ['key' => 'borongan',  'title' => 'Borongan',  'icon' => 'fa-hammer',         'tone' => 'purple',  'only_semua' => true,
                         'rows' => $dailyStatusesBorongan ?? [],  'placeholder' => 'Cari nama karyawan borongan...', 'noun' => 'karyawan borongan'],
                        ['key' => 'magang',    'title' => 'Magang',    'icon' => 'fa-graduation-cap', 'tone' => 'sky',     'only_semua' => true,
                         'rows' => $dailyStatusesMagang ?? [],    'placeholder' => 'Cari nama karyawan magang...',   'noun' => 'karyawan magang'],
                    ];
                @endphp

                <div x-data="{ activeTab: '{{ $currentStatus === 'freelance' ? 'freelance' : 'semua' }}' }" class="space-y-6">

                    {{-- Tab Navigation Pills --}}
                    @if($currentStatus === 'semua')
                        <div class="tabbar">
                            <button type="button" @click="activeTab = 'semua'" :class="{ 'is-active': activeTab === 'semua' }" class="tab-pill">
                                <i class="fas fa-th-large"></i>
                                <span>Semua Status</span>
                            </button>
                            @foreach ($tables as $tab)
                                <button type="button" @click="activeTab = '{{ $tab['key'] }}'" :class="{ 'is-active': activeTab === '{{ $tab['key'] }}' }" class="tab-pill">
                                    <i class="fas {{ $tab['icon'] }}"></i>
                                    <span>{{ $tab['title'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($tables as $tab)
                        @if($currentStatus === 'semua')
                            <div x-show="activeTab === 'semua' || activeTab === '{{ $tab['key'] }}'" x-transition.opacity id="tabel-{{ $tab['key'] }}" class="ui-panel">
                                <div class="ui-section-heading mb-6">
                                    <div class="ui-section-icon" data-tone="{{ $tab['tone'] }}">
                                        <i class="fas {{ $tab['icon'] }} text-white"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan {{ $tab['title'] }} Hari Ini</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                                    </div>
                                </div>

                                {{-- Form Pencarian --}}
                                <form action="{{ url()->current() }}" method="GET" class="mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-grow">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                            <input type="search" name="search_{{ $tab['key'] }}" id="search_{{ $tab['key'] }}" class="ui-control pl-10" placeholder="{{ $tab['placeholder'] }}" value="{{ request('search_' . $tab['key']) }}">
                                        </div>
                                        <button type="submit" class="ui-button-primary">
                                            Cari
                                        </button>
                                        @if(request('search_' . $tab['key']))
                                            <a href="{{ route('admin.absensi.index') }}" class="reset-link text-sm text-gray-500 dark:text-gray-400">
                                                Reset
                                            </a>
                                        @endif
                                    </div>
                                </form>

                                <div class="ui-table-shell overflow-x-auto">
                                    <table class="ui-table">
                                        <thead>
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Karyawan</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Check-in</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Keterlambatan</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Check-out</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Detail</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse ($tab['rows'] as $daily)
                                                <x-admin.absensi-row :daily="$daily" :type="$tab['key']" />
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="ui-empty-state">
                                                        <div class="flex flex-col items-center">
                                                            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                            @if(request('search_' . $tab['key']))
                                                                <p class="text-gray-500">{{ ucfirst($tab['noun']) }} dengan nama "<strong class="text-emerald-600">{{ request('search_' . $tab['key']) }}</strong>" tidak ditemukan.</p>
                                                            @else
                                                                <p class="text-gray-500">Tidak ada {{ $tab['noun'] }} hari ini</p>
                                                            @endif
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

                    @if($currentStatus !== 'semua')
                        {{-- Tabel Single (Kalo Filter Dipilih) --}}
                        <div class="ui-panel">
                            <div class="ui-section-heading mb-6">
                                <div class="ui-section-icon" data-tone="cyan">
                                    <i class="fas fa-users-cog text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Absen Karyawan Hari Ini</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                                </div>
                            </div>

                            <div class="ui-table-shell overflow-x-auto">
                                <table class="ui-table">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Karyawan</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Check-in</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Keterlambatan</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Check-out</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse ($dailyStatuses as $daily)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="block group">
                                                        <div class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-emerald-600">
                                                            {{ $daily['user']->name }}
                                                        </div>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 mt-1">
                                                            {{ ucfirst($daily['user']->employment_type ?? '-') }}
                                                        </span>
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1
                                                        @if(str_contains($daily['status'], 'Belum')) bg-gray-100 text-gray-700
                                                        @elseif(str_contains($daily['status'], 'Hadir')) bg-emerald-100 text-emerald-800
                                                        @elseif(str_contains($daily['status'], 'Izin')) bg-yellow-100 text-yellow-800
                                                        @elseif(str_contains($daily['status'], 'Sakit')) bg-red-100 text-red-800
                                                        @elseif(str_contains($daily['status'], 'Pending')) bg-orange-100 text-orange-800
                                                        @endif">
                                                        {{ $daily['status'] }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($daily['check_in_time'])
                                                        <div class="flex items-center gap-2">
                                                            <i class="fas fa-sign-in-alt text-green-600 text-xs"></i>
                                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {{ \Carbon\Carbon::parse($daily['check_in_time'])->format('H:i') }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-400 text-sm">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @php
                                                        $lateMinutes = $daily['late_minutes'] ?? 0;
                                                    @endphp
                                                    @if($lateMinutes > 0)
                                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                                            <i class="fas fa-clock"></i>
                                                            @if($lateMinutes < 60)
                                                                {{ $lateMinutes }} menit
                                                            @else
                                                                @php
                                                                    $hours = floor($lateMinutes / 60);
                                                                    $mins = $lateMinutes % 60;
                                                                @endphp
                                                                {{ $hours }} jam {{ $mins > 0 ? $mins . ' menit' : '' }}
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="text-green-600 dark:text-green-400 text-sm font-semibold">✓ Tepat waktu</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($daily['check_out_time'])
                                                        <div class="flex items-center gap-2">
                                                            <i class="fas fa-sign-out-alt text-red-600 text-xs"></i>
                                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {{ \Carbon\Carbon::parse($daily['check_out_time'])->format('H:i') }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-400 text-sm">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="ui-button-secondary min-h-9 px-3 py-1.5">
                                                        <i class="fas fa-eye"></i>
                                                        <span>Detail</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="ui-empty-state">
                                                    <div class="flex flex-col items-center">
                                                        <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                        <p class="text-gray-500">Tidak ada data absensi hari ini</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>

    <style nonce="{{ config('app.csp_nonce') }}">
        /* ---------- Tata letak halaman ---------- */
        .dash { display: grid; gap: 1.5rem; }
        .dash > * { margin-top: 0 !important; }

        /* ---------- Panel & heading ---------- */
        .dash .ui-panel {
            position: relative;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(15, 42, 36, .08);
            border-radius: 1.25rem;
            box-shadow: 0 1px 2px rgba(2, 44, 34, .04), 0 18px 40px -26px rgba(2, 44, 34, .28);
            -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
        }
        .dark .dash .ui-panel {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(255, 255, 255, .08);
            box-shadow: 0 18px 40px -26px rgba(0, 0, 0, .7);
        }
        .dash .ui-section-icon {
            display: grid; place-items: center; width: 2.75rem; height: 2.75rem; flex-shrink: 0;
            border-radius: .85rem; background: linear-gradient(135deg, #10b981, #0d9488);
            box-shadow: 0 8px 18px -6px rgba(16, 185, 129, .65);
        }
        .dash .ui-section-icon[data-tone="cyan"]   { background: linear-gradient(135deg, #06b6d4, #2563eb); box-shadow: 0 8px 18px -6px rgba(37, 99, 235, .55); }
        .dash .ui-section-icon[data-tone="purple"] { background: linear-gradient(135deg, #a855f7, #db2777); box-shadow: 0 8px 18px -6px rgba(168, 85, 247, .55); }
        .dash .ui-section-icon[data-tone="sky"]    { background: linear-gradient(135deg, #0ea5e9, #4f46e5); box-shadow: 0 8px 18px -6px rgba(79, 70, 229, .55); }

        /* ---------- Kontrol form ---------- */
        .dash .ui-control {
            border-radius: .8rem; transition: border-color .2s ease, box-shadow .2s ease;
        }
        .dash .ui-control:focus {
            outline: none; border-color: #10b981; box-shadow: 0 0 0 4px rgba(16, 185, 129, .18);
        }
        .dash .ui-button-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            border-radius: .8rem; font-weight: 700; color: #fff; border: 0;
            background: linear-gradient(135deg, #10b981, #0d9488);
            box-shadow: 0 10px 22px -8px rgba(16, 185, 129, .65);
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }
        .dash .ui-button-primary:hover { transform: translateY(-2px); filter: brightness(1.06); box-shadow: 0 14px 26px -8px rgba(16, 185, 129, .75); }
        .dash .ui-button-primary:active { transform: translateY(0) scale(.98); }
        .dash .ui-button-primary:focus-visible, .dash .ui-button-secondary:focus-visible, .tab-pill:focus-visible { outline: 2px solid #34d399; outline-offset: 2px; }
        .dash .ui-button-secondary { transition: transform .18s ease, background-color .18s ease, color .18s ease; }
        .dash .ui-button-secondary:hover { transform: translateY(-1px); color: #059669; }
        .reset-link { transition: color .2s ease; }
        .reset-link:hover { color: #059669; }
        .dark .reset-link:hover { color: #6ee7b7; }

        /* ---------- Kartu ringkasan ---------- */
        .stat-card {
            --g1: #10b981; --g2: #0d9488;
            position: relative; overflow: hidden; padding: 1.5rem; border-radius: 1.25rem; color: #fff;
            background: linear-gradient(135deg, var(--g1), var(--g2));
            box-shadow: 0 18px 36px -18px var(--g2);
            transition: transform .3s cubic-bezier(.2, .8, .2, 1), box-shadow .3s ease;
            animation: statIn .6s cubic-bezier(.2, .8, .2, 1) both;
            animation-delay: calc(var(--i, 0) * 80ms);
        }
        .stat-card[data-tone="amber"]  { --g1: #f59e0b; --g2: #ea580c; }
        .stat-card[data-tone="rose"]   { --g1: #f43f5e; --g2: #db2777; }
        .stat-card[data-tone="violet"] { --g1: #8b5cf6; --g2: #9333ea; }
        .stat-card::after {
            content: ""; position: absolute; top: 0; bottom: 0; left: -70%; width: 40%;
            background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .28), transparent);
            transform: skewX(-20deg); transition: left .7s ease;
        }
        .stat-card:hover { transform: translateY(-5px) scale(1.01); box-shadow: 0 26px 44px -18px var(--g2); }
        .stat-card:hover::after { left: 140%; }
        .stat-orb { position: absolute; top: -3rem; right: -3rem; width: 7rem; height: 7rem; border-radius: 9999px; background: rgba(255, 255, 255, .12); transition: transform .5s ease; }
        .stat-card:hover .stat-orb { transform: scale(1.5); }
        .stat-label { font-size: .8rem; font-weight: 500; color: rgba(255, 255, 255, .8); }
        .stat-value { margin-top: .25rem; font-size: 2rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .stat-sub { font-size: .85rem; color: rgba(255, 255, 255, .8); }
        .stat-icon { padding: .75rem; border-radius: .9rem; background: rgba(255, 255, 255, .2); transition: transform .35s cubic-bezier(.3, 1.6, .5, 1); }
        .stat-card:hover .stat-icon { transform: rotate(-8deg) scale(1.12); }
        @keyframes statIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

        /* ---------- Kehadiran hari ini ---------- */
        .presence { transition: transform .25s ease, border-color .25s ease; }
        .presence:hover { transform: translateY(-3px); }
        .presence[data-tone="orange"]:hover { border-color: rgba(249, 115, 22, .5); }
        .presence[data-tone="blue"]:hover { border-color: rgba(59, 130, 246, .5); }
        .presence-icon { padding: 1rem; border-radius: 1.1rem; transition: transform .3s cubic-bezier(.3, 1.6, .5, 1); }
        .presence:hover .presence-icon { transform: rotate(-6deg) scale(1.08); }
        .presence[data-tone="orange"] .presence-icon { background: rgba(249, 115, 22, .14); color: #ea580c; }
        .presence[data-tone="blue"] .presence-icon { background: rgba(59, 130, 246, .14); color: #2563eb; }
        .dark .presence[data-tone="orange"] .presence-icon { color: #fb923c; }
        .dark .presence[data-tone="blue"] .presence-icon { color: #60a5fa; }
        .ring-fg { stroke-dashoffset: var(--off); animation: ringIn 1.3s cubic-bezier(.2, .8, .2, 1) both; }
        @keyframes ringIn { from { stroke-dashoffset: var(--circ); } }

        /* ---------- Perbandingan ---------- */
        .cmp-card {
            --rgb: 16, 185, 129; --ink: #047857;
            padding: 1.25rem; border-radius: 1rem;
            background: linear-gradient(145deg, rgba(var(--rgb), .10), rgba(var(--rgb), .03));
            border: 1px solid rgba(var(--rgb), .28);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .cmp-card[data-tone="amber"]  { --rgb: 245, 158, 11; --ink: #b45309; }
        .cmp-card[data-tone="rose"]   { --rgb: 244, 63, 94;  --ink: #be123c; }
        .cmp-card[data-tone="violet"] { --rgb: 139, 92, 246; --ink: #6d28d9; }
        .dark .cmp-card[data-tone="emerald"] { --ink: #6ee7b7; }
        .dark .cmp-card[data-tone="amber"]   { --ink: #fcd34d; }
        .dark .cmp-card[data-tone="rose"]    { --ink: #fda4af; }
        .dark .cmp-card[data-tone="violet"]  { --ink: #c4b5fd; }
        .cmp-card:hover { transform: translateY(-4px); box-shadow: 0 16px 30px -18px rgba(var(--rgb), .7); }
        .cmp-icon, .cmp-num { color: var(--ink); }
        .cmp-num { font-variant-numeric: tabular-nums; }
        .cmp-foot { border-top: 1px solid rgba(var(--rgb), .28); }

        /* ---------- Tab ---------- */
        .tabbar {
            display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding: .6rem;
            border-radius: 1.1rem; background: rgba(255, 255, 255, .78); border: 1px solid rgba(15, 42, 36, .08);
            -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
        }
        .dark .tabbar { background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .08); }
        .tab-pill {
            display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1rem; border-radius: .8rem;
            font-size: .85rem; font-weight: 700; color: #475569; background: rgba(100, 116, 139, .1);
            transition: background-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
        }
        .dark .tab-pill { color: #cbd5e1; background: rgba(255, 255, 255, .06); }
        .tab-pill:hover { transform: translateY(-1px); background: rgba(16, 185, 129, .16); color: #047857; }
        .dark .tab-pill:hover { color: #6ee7b7; }
        .tab-pill:active { transform: scale(.96); }
        .tab-pill.is-active, .dark .tab-pill.is-active {
            color: #fff; background: linear-gradient(135deg, #10b981, #0d9488);
            box-shadow: 0 10px 20px -8px rgba(16, 185, 129, .7);
        }

        /* ---------- Tabel ---------- */
        .dash .ui-table-shell { border-radius: 1rem; border: 1px solid rgba(15, 42, 36, .08); max-height: 34rem; overflow-y: auto; }
        .dark .dash .ui-table-shell { border-color: rgba(255, 255, 255, .08); }
        .dash .ui-table thead th {
            position: sticky; top: 0; z-index: 1; background: #ecfdf5; color: #065f46;
            white-space: nowrap;
        }
        .dark .dash .ui-table thead th { background: #0c211c; color: #a7f3d0; }
        .dash .ui-table tbody tr { transition: background-color .15s ease, box-shadow .15s ease; }
        .dash .ui-table tbody tr:hover { background: rgba(16, 185, 129, .07); box-shadow: inset 3px 0 0 #10b981; }
        .dash .ui-empty-state { padding: 3rem 1rem; text-align: center; }

        @media (prefers-reduced-motion: reduce) {
            .stat-card, .ring-fg { animation: none !important; }
            .stat-card, .stat-card::after, .stat-orb, .stat-icon, .presence, .presence-icon, .cmp-card, .tab-pill,
            .dash .ui-button-primary, .dash .ui-button-secondary { transition: none !important; }
        }
    </style>

    <script nonce="{{ config('app.csp_nonce') }}" src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script nonce="{{ config('app.csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', () => {
        // AUTO SCROLL KE TABEL YANG DI-SEARCH
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('search_organik') && urlParams.get('search_organik') !== '') {
            setTimeout(() => {
                document.getElementById('tabel-organik')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        } else if (urlParams.has('search_freelance') && urlParams.get('search_freelance') !== '') {
            setTimeout(() => {
                document.getElementById('tabel-freelance')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        } else if (urlParams.has('search_borongan') && urlParams.get('search_borongan') !== '') {
            setTimeout(() => {
                document.getElementById('tabel-borongan')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        } else if (urlParams.has('search_magang') && urlParams.get('search_magang') !== '') {
            setTimeout(() => {
                document.getElementById('tabel-magang')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }

            // ANGKA NAIK PERLAHAN (count-up), teks asli tetap jadi cadangan
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!reduceMotion) {
                document.querySelectorAll('[data-count]').forEach((el) => {
                    const raw = (el.dataset.count || '').trim();
                    if (!/^-?\d+(\.\d+)?$/.test(raw)) return;
                    const target = parseFloat(raw);
                    const decimals = (raw.split('.')[1] || '').length;
                    const duration = 1100;
                    const start = performance.now();
                    const tick = (now) => {
                        const t = Math.min((now - start) / duration, 1);
                        const eased = 1 - Math.pow(1 - t, 3);
                        el.textContent = (target * eased).toFixed(decimals);
                        if (t < 1) requestAnimationFrame(tick);
                        else el.textContent = raw;
                    };
                    requestAnimationFrame(tick);
                });
            }

            // CHART
            const root = document.documentElement;
            const palette = (dark) => ({
                text: dark ? '#cbd5e0' : '#4b5563',
                grid: dark ? 'rgba(148,163,184,0.14)' : 'rgba(100,116,139,0.09)',
                edge: dark ? '#0b1412' : '#ffffff',
            });
            let p = palette(root.classList.contains('dark'));

            Chart.defaults.color = p.text;
            Chart.defaults.borderColor = p.grid;
            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

            const areaFill = (rgb) => (context) => {
                const { chart } = context;
                const { ctx, chartArea } = chart;
                if (!chartArea) return `rgba(${rgb},0.1)`;
                const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                g.addColorStop(0, `rgba(${rgb},0.30)`);
                g.addColorStop(1, `rgba(${rgb},0)`);
                return g;
            };

            const lineChart = new Chart(document.getElementById('grafikBulananLine'), {
                type: 'line',
                data: {
                    labels: [@for ($i = 1; $i <= 12; $i++) "{{ \Carbon\Carbon::createFromFormat('!m', $i)->translatedFormat('M') }}", @endfor],
                    datasets: [
                        @if($currentStatus === 'semua')
                        {
                            label: 'Organik',
                            data: [@for ($i = 1; $i <= 12; $i++) {{ $grafikBulananOrganik[$i] ?? 0 }}, @endfor],
                            borderColor: '#3b82f6',
                            backgroundColor: areaFill('59,130,246'),
                            tension: 0.4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointRadius: 4,
                            pointHoverRadius: 8,
                            fill: true,
                        },
                        @endif
                        {
                            label: 'Freelance',
                            data: [@for ($i = 1; $i <= 12; $i++) {{ $grafikBulananFreelance[$i] ?? 0 }}, @endfor],
                            borderColor: '#fb923c',
                            backgroundColor: areaFill('251,146,60'),
                            tension: 0.4,
                            pointBackgroundColor: '#fb923c',
                            pointBorderColor: '#fff',
                            pointRadius: 4,
                            pointHoverRadius: 8,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: { duration: 1100, easing: 'easeOutQuart' },
                    plugins: {
                        legend: {
                            display: true,
                            labels: { color: p.text, usePointStyle: true, pointStyle: 'circle', font: { weight: 'bold' } }
                        },
                        tooltip: { padding: 12, cornerRadius: 10, boxPadding: 5, usePointStyle: true }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: p.text, font: { weight: 'bold' } }
                        },
                        y: {
                            grid: { color: p.grid },
                            ticks: { color: p.text }
                        }
                    },
                    elements: {
                        line: { borderWidth: 3 },
                        point: { borderWidth: 2 }
                    }
                }
            });

            const doughnutChart = new Chart(document.getElementById('grafikStatus'), {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Izin', 'Sakit', 'Lembur'],
                    datasets: [{
                        data: [{{ $totalHadir }}, {{ $totalIzin }}, {{ $totalSakit }}, {{ $totalLembur }}],
                        backgroundColor: [
                            'rgba(16,185,129,0.95)',
                            'rgba(245,158,11,0.95)',
                            'rgba(239,68,68,0.95)',
                            'rgba(168,85,247,0.95)'
                        ],
                        borderColor: p.edge,
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { animateRotate: true, duration: 1200, easing: 'easeOutQuart' },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: p.text, usePointStyle: true, pointStyle: 'circle', font: { weight: 'bold' } }
                        },
                        tooltip: { padding: 12, cornerRadius: 10 }
                    },
                    cutout: '65%'
                }
            });

            // Warna chart ikut berubah saat mode gelap/terang diganti
            new MutationObserver(() => {
                p = palette(root.classList.contains('dark'));
                Chart.defaults.color = p.text;
                Chart.defaults.borderColor = p.grid;

                lineChart.options.plugins.legend.labels.color = p.text;
                lineChart.options.scales.x.ticks.color = p.text;
                lineChart.options.scales.y.ticks.color = p.text;
                lineChart.options.scales.y.grid.color = p.grid;
                lineChart.update('none');

                doughnutChart.options.plugins.legend.labels.color = p.text;
                doughnutChart.data.datasets[0].borderColor = p.edge;
                doughnutChart.update('none');
            }).observe(root, { attributes: true, attributeFilter: ['class'] });
        });
    </script>
</x-app-layout>