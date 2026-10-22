<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Approval HRD</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Semua pengajuan absensi menunggu satu persetujuan HRD.</p>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-2 mb-8 bg-white dark:bg-gray-800 p-1.5 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 w-fit">
                @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $filter => $label)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $filter]) }}"
                       class="px-5 py-2.5 rounded-xl text-sm font-bold {{ $status === $filter ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Pengajuan absensi ({{ $submissions->count() }})</h3>
                @include('admin.absensi.approval.table', ['submissions' => $submissions])
            </section>

            @if($status === 'pending')
                @include('admin.absensi.approval.partials.scheduled-lembur-table')
                @include('admin.absensi.approval.partials.izin-keluar-table')
            @endif
        </div>
    </div>

    @include('admin.absensi.approval.partials.reject-modal')
</x-app-layout>
