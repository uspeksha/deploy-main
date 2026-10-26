<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Rekap Absensi Bulanan') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pantau dan ekspor absensi karyawan freelance.</p>
            </div>
            <a href="{{ route('admin.absensi.index') }}" class="ui-button-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </x-slot>

    @php
        $year = $selectedYear;
        $month = $selectedMonth;
        $firstDay = \Carbon\Carbon::create($year, $month, 1);
        $firstMonday = $firstDay->copy()->startOfMonth();
        if (! $firstMonday->isMonday()) {
            $firstMonday = $firstMonday->next(\Carbon\Carbon::MONDAY);
        }
        if ($firstMonday->month !== (int) $month) {
            $firstMonday = $firstDay->copy()->startOfMonth();
        }
        $weeks = 0;
        $tempDate = $firstMonday->copy();
        while ($tempDate->month === (int) $month) {
            $weeks++;
            $tempDate->addWeek();
        }
        $freelanceData = array_values(array_filter(
            $recapData,
            fn ($data) => ($data['kategori'] ?? null) === 'freelance',
        ));
        usort($freelanceData, fn ($a, $b) => $a['user']->name <=> $b['user']->name);
        $totalGajiFreelance = array_sum(array_column($freelanceData, 'total_gaji'));
        $isPkl = auth()->user()->role === 'pkl';
        $columnCount = $isPkl ? 10 : 14;
    @endphp

    <div class="ui-page">
        <section class="ui-panel overflow-hidden">
            <div class="ui-section-heading mb-5">
                <span class="ui-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Filter Periode</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih rentang waktu rekap absensi freelance.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.absensi.recap') }}" id="filterForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div>
                    <label for="monthSelect" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                    <select name="month" id="monthSelect" class="ui-control">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((int) $selectedMonth === $m)>
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="yearSelect" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                    <select name="year" id="yearSelect" class="ui-control">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected((int) $selectedYear === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="rangeSelect" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Rentang</label>
                    <select name="range" id="rangeSelect" class="ui-control">
                        <option value="monthly" @selected(request('range', 'monthly') === 'monthly')>Bulanan</option>
                        <option value="weekly" @selected(request('range') === 'weekly')>Mingguan</option>
                        <option value="custom" @selected(request('range') === 'custom')>Custom</option>
                    </select>
                </div>

                <div id="weekField" class="hidden">
                    <label for="weekSelect" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Minggu</label>
                    <select name="week" id="weekSelect" class="ui-control">
                        <option value="">Semua minggu</option>
                        @for ($i = 1; $i <= $weeks; $i++)
                            <option value="{{ $i }}" @selected((string) request('week') === (string) $i)>Minggu {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div id="customDateStart" class="hidden">
                    <label for="startDate" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari</label>
                    <input id="startDate" type="date" name="start_date" value="{{ request('start_date') }}" class="ui-control">
                </div>

                <div id="customDateEnd" class="hidden">
                    <label for="endDate" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai</label>
                    <input id="endDate" type="date" name="end_date" value="{{ request('end_date') }}" class="ui-control">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="ui-button-primary w-full">
                        <i class="fas fa-magnifying-glass"></i> Tampilkan
                    </button>
                </div>
            </form>
        </section>

        <section class="ui-panel">
            <form method="POST">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="ui-section-heading">
                        <span class="ui-section-icon"><i class="fas fa-user-clock"></i></span>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Karyawan Freelance</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $startDate->translatedFormat('d M Y') }} – {{ $endDate->translatedFormat('d M Y') }}
                                <span class="mx-1">·</span>{{ count($freelanceData) }} karyawan
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" formaction="{{ route('admin.absensi.bulk-export-simple') }}" class="ui-button-secondary">
                            <i class="fas fa-table"></i> Simple
                        </button>
                        <button type="submit" formaction="{{ route('admin.absensi.bulk-export-detail') }}" class="ui-button-primary">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button type="submit" formaction="{{ route('admin.absensi.bulk-export-pdf') }}" class="ui-button-danger">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>

                <div class="ui-table-shell">
                    <table class="ui-table min-w-[1050px]">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" class="toggle-table-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" data-category="freelance" aria-label="Pilih semua karyawan freelance">
                                </th>
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-4 py-3 text-center">Hadir</th>
                                <th class="px-4 py-3 text-center">Izin</th>
                                <th class="px-4 py-3 text-center">Sakit</th>
                                <th class="px-4 py-3 text-center">Alfa</th>
                                <th class="px-4 py-3 text-center">Lembur</th>
                                <th class="px-4 py-3 text-center">Telat</th>
                                @unless ($isPkl)
                                    <th class="px-4 py-3 text-right">Potongan</th>
                                    <th class="px-4 py-3 text-center">Menit OT</th>
                                    <th class="px-4 py-3 text-right">Gaji OT</th>
                                    <th class="px-4 py-3 text-right">Total Gaji</th>
                                @endunless
                                <th class="px-4 py-3 text-center">Izin Keluar</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($freelanceData as $data)
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-freelance rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" aria-label="Pilih {{ $data['user']->name }}">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $data['user']->name }}
                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $data['user']->id_karyawan }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $data['total_hadir'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $data['total_izin'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $data['total_sakit'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $data['total_alfa'] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-center">{{ $data['total_lembur'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $data['total_telat'] ?? 0 }}x</td>
                                    @unless ($isPkl)
                                        <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-center">{{ $data['total_menit_lembur'] ?? 0 }}'</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}</td>
                                    @endunless
                                    <td class="px-4 py-3 text-center">
                                        {{ $data['total_izin_keluar'] ?? 0 }}x
                                        @if (($data['total_izin_keluar_ditolak'] ?? 0) > 0)
                                            <div class="text-xs text-rose-600 dark:text-rose-400">{{ $data['total_izin_keluar_ditolak'] }} ditolak</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="ui-button-secondary min-h-9 px-3 py-1.5" aria-label="Lihat detail {{ $data['user']->name }}">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $columnCount }}" class="px-4 py-10 text-center">
                                        <div class="ui-empty-state">
                                            <i class="fas fa-user-clock text-2xl text-gray-400"></i>
                                            <p class="mt-2 font-medium text-gray-700 dark:text-gray-300">Belum ada data karyawan freelance</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Coba pilih rentang tanggal yang berbeda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            @if (count($freelanceData) > 0 && ! $isPkl)
                                <tr class="bg-emerald-50/70 font-semibold dark:bg-emerald-950/20">
                                    <td colspan="11" class="px-4 py-4 text-right text-gray-900 dark:text-white">Total Gaji Freelance</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-emerald-700 dark:text-emerald-300">
                                        Rp {{ number_format($totalGajiFreelance, 0, ',', '.') }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    </div>

    @push('scripts')
        <script nonce="{{ config('app.csp_nonce') }}">
            document.addEventListener('DOMContentLoaded', function () {
                const rangeSelect = document.getElementById('rangeSelect');
                const weekField = document.getElementById('weekField');
                const customDateStart = document.getElementById('customDateStart');
                const customDateEnd = document.getElementById('customDateEnd');
                const monthSelect = document.getElementById('monthSelect');
                const yearSelect = document.getElementById('yearSelect');

                function toggleFields() {
                    const selectedRange = rangeSelect.value;
                    weekField.classList.toggle('hidden', selectedRange !== 'weekly');
                    customDateStart.classList.toggle('hidden', selectedRange !== 'custom');
                    customDateEnd.classList.toggle('hidden', selectedRange !== 'custom');
                    monthSelect.disabled = selectedRange === 'custom';
                    yearSelect.disabled = selectedRange === 'custom';
                }

                rangeSelect.addEventListener('change', toggleFields);
                toggleFields();

                const selectAll = document.querySelector('.toggle-table-checkbox');
                selectAll?.addEventListener('change', function () {
                    document.querySelectorAll('.user-checkbox-freelance').forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
