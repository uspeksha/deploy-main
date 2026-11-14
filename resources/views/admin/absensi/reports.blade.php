<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Analitik &amp; Rekap Absensi</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Statistik karyawan dan rekap kehadiran freelance hari ini.
                </p>
            </div>
            <a href="{{ route('admin.absensi.index') }}" class="btn-premium self-start">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Kembali ke dashboard</span>
            </a>
        </div>
    </x-slot>

    <div class="ui-page">
        <section class="lucent-panel rounded-xl p-4 md:p-5">
            <form method="GET" action="{{ route('admin.absensi.reports') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_8rem_1fr_auto] lg:items-end">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Bulan
                    <select name="month" class="lucent-input mt-1 w-full">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Tahun
                    <select name="year" class="lucent-input mt-1 w-full">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Cari karyawan freelance
                    <input type="search" name="search_freelance" value="{{ request('search_freelance') }}"
                           placeholder="Ketik nama karyawan..." class="lucent-input mt-1 w-full">
                </label>
                <button type="submit" class="btn-premium justify-center">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    <span>Terapkan</span>
                </button>
            </form>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Statistik Karyawan</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Statistik gender karyawan aktif dan ranking absensi disetujui pada
                    {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}.
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
                                @php $employee = $employeeRankings[$ranking['key']]->get($rank - 1); @endphp
                                <li data-ranking-slot="{{ $ranking['key'] }}-{{ $rank }}" class="flex min-h-14 items-center gap-3 py-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $rank === 1 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $rank }}
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium {{ $employee ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500' }}">
                                        {{ $employee?->name ?? 'Belum ada data' }}
                                    </span>
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

        <section class="lucent-table-wrapper">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Karyawan Freelance</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Status absensi hari ini</p>
                </div>
                <span class="text-sm font-semibold text-slate-500">{{ count($dailyStatusesFreelance) }} karyawan</span>
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
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($daily['user']->profile_photo_path)
                                            <div x-data="{ imageFailed: false }" class="relative h-9 w-9 shrink-0">
                                                <img src="{{ route('admin.storage.file', ['path' => $daily['user']->profile_photo_path]) }}"
                                                     alt="Foto profil {{ $daily['user']->name }}"
                                                     class="absolute inset-0 h-full w-full rounded-full object-cover"
                                                     x-show="!imageFailed"
                                                     x-on:error="imageFailed = true">
                                                <span x-show="imageFailed" x-cloak
                                                      class="flex h-full w-full items-center justify-center rounded-full bg-slate-100 text-xs font-bold uppercase text-slate-500 dark:bg-slate-800">
                                                    {{ substr($daily['user']->name, 0, 1) }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold uppercase text-slate-500 dark:bg-slate-800">
                                                {{ substr($daily['user']->name, 0, 1) }}
                                            </span>
                                        @endif
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $daily['user']->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $daily['user']->id_karyawan ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest
                                        @if (str_contains($daily['status'], 'Belum')) bg-slate-100 text-slate-500
                                        @elseif (str_contains($daily['status'], 'Hadir')) bg-emerald-500/10 text-emerald-600
                                        @elseif (str_contains($daily['status'], 'Izin')) bg-amber-500/10 text-amber-600
                                        @elseif (str_contains($daily['status'], 'Sakit')) bg-rose-500/10 text-rose-600
                                        @else bg-indigo-500/10 text-indigo-600
                                        @endif">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ $daily['status'] }}
                                    </span>
                                </td>
                                <td>
                                    @if ($daily['check_in_time'])
                                        <span class="block font-bold">{{ \Carbon\Carbon::parse($daily['check_in_time'])->format('H:i') }}</span>
                                        <span class="text-[10px] font-bold uppercase text-slate-400">WIB</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($daily['check_out_time'])
                                        <span class="block font-bold">{{ \Carbon\Carbon::parse($daily['check_out_time'])->format('H:i') }}</span>
                                        <span class="text-[10px] font-bold uppercase text-slate-400">WIB</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($daily['check_in_time'])
                                        @if (($daily['late_minutes'] ?? 0) > 0)
                                            <span class="flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400">
                                                <i class="fas fa-clock text-[10px]"></i>
                                                Terlambat {{ $daily['late_minutes'] }}m
                                            </span>
                                        @else
                                            <span class="flex items-center gap-1.5 text-xs font-bold text-emerald-500">
                                                <i class="fas fa-check-circle text-[10px]"></i>
                                                On Time
                                            </span>
                                        @endif
                                    @elseif (str_starts_with($daily['status'], 'Belum'))
                                        <span class="text-xs font-semibold text-slate-400">Belum Absen</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-emerald-500 hover:text-white" aria-label="Lihat absensi {{ $daily['user']->name }}">
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Tidak ada data ditemukan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
