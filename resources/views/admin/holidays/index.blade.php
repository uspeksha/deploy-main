{{-- resources/views/admin/holidays/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="ui-section-heading">
                <span class="ui-section-icon"><i class="fas fa-calendar-days"></i></span>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Manajemen Hari Libur
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Kelola kalender hari libur nasional dan cuti bersama perusahaan.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <form action="{{ route('admin.holidays.sync') }}" method="POST">
                    @csrf
                    <input type="hidden" name="year" value="{{ date('Y') }}">
                    <button type="submit" class="ui-button-secondary">
                        <i class="fas fa-sync"></i>
                        Sync Kalender {{ date('Y') }}
                    </button>
                </form>
                <a href="{{ route('admin.holidays.create') }}" class="ui-button-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Libur Manual
                </a>
            </div>
        </div>
    </x-slot>

    <div class="ui-page">

            @if(session('success'))
                <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg dark:bg-emerald-900/30">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="ui-panel">
                <div class="ui-table-shell">
                    <table class="ui-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tipe Libur</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($holiday->holiday_date)->translatedFormat('l, d F Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ $holiday->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($holiday->is_national)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">
                                                <i class="fas fa-flag"></i> Nasional
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                                                <i class="fas fa-building"></i> Kebijakan Perusahaan
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="{{ route('admin.holidays.destroy', $holiday->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-button-danger min-h-9 px-3 py-1.5">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-5">
                                        <div class="ui-empty-state">
                                            <i class="fas fa-calendar-times mb-3 text-3xl text-emerald-600 dark:text-emerald-400"></i>
                                            <p class="text-base font-semibold text-gray-800 dark:text-gray-100">Belum ada data hari libur.</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Silakan klik tombol "Sync Kalender" untuk menarik data libur nasional.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($holidays->hasPages())
                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        {{ $holidays->links() }}
                    </div>
                @endif
            </div>

    </div>
</x-app-layout>
