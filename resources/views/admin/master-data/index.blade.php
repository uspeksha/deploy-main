<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="ui-section-heading">
                <span class="ui-section-icon"><i class="fas fa-sitemap"></i></span>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Master Data HR - {{ $definition['label'] }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $definition['description'] }}</p>
                </div>
            </div>
            <a href="{{ route('admin.master-data.create', ['resource' => $resource]) }}" class="ui-button-primary">
                <i class="fas fa-plus"></i>
                Tambah {{ $definition['label'] }}
            </a>
        </div>
    </x-slot>

    <div class="ui-page">
        <nav class="ui-tabs" aria-label="Kategori master data HR">
            @foreach([
                'companies' => 'Perusahaan',
                'branches' => 'Cabang',
                'departments' => 'Departemen',
                'job-levels' => 'Level Jabatan',
                'payroll-components' => 'Komponen Payroll',
                'positions' => 'Jabatan',
                'shifts' => 'Shift',
                'leave-types' => 'Jenis Cuti',
                'reimbursement-types' => 'Kategori Reimbursement',
            ] as $key => $label)
                <a href="{{ route('admin.master-data.index', ['resource' => $key]) }}"
                   @class(['ui-tab', 'active' => $resource === $key])>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @if (session('success'))
            <div class="mb-5 rounded-r-lg border-l-4 border-emerald-500 bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-5 rounded-r-lg border-l-4 border-rose-500 bg-rose-50 p-4 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if (isset($definition['warning']))
            <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200" role="note">
                <i class="fas fa-triangle-exclamation mr-2"></i>{{ $definition['warning'] }}
            </div>
        @endif

        <section class="space-y-4">
            <div class="ui-table-shell">
                <table class="ui-table">
                    <thead>
                        <tr>
                            @foreach ($definition['columns'] as $label)
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ $label }}</th>
                            @endforeach
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                @foreach ($definition['columns'] as $column => $label)
                                    @php($value = data_get($record, $column))
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        @if (is_bool($value))
                                            {{ $value ? 'Ya' : 'Tidak' }}
                                        @elseif ($value === null || $value === '')
                                            -
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <a href="{{ route('admin.master-data.edit', ['resource' => $resource, 'record' => $record->getKey()]) }}" class="ui-button-secondary min-h-9 px-3 py-1.5">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.master-data.destroy', ['resource' => $resource, 'record' => $record->getKey()]) }}" method="POST" class="ml-1 inline-block" onsubmit="return confirm('Hapus data ini? Data yang masih digunakan akan ditolak.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-button-danger min-h-9 px-3 py-1.5">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($definition['columns']) + 1 }}" class="px-6 py-5">
                                    <div class="ui-empty-state">
                                        <i class="fas fa-inbox mb-3 text-3xl text-emerald-600 dark:text-emerald-400"></i>
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100">Belum ada data {{ strtolower($definition['label']) }}.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <div class="rounded-xl border border-[#e6e1d7] bg-[#fffefa] px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
                    {{ $records->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
