<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                @if (request('section') === 'employees')
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">Data Karyawan</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola akun, detail, dan status karyawan.</p>
                @else
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">Approval HRD</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Semua pengajuan absensi menunggu satu persetujuan HRD.</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="ui-page" x-data="{ section: @js(request('section') === 'employees' || $errors->any() ? 'employees' : 'approvals') }">
        @foreach (['success', 'warning', 'error'] as $flashType)
            @if (session($flashType))
                <div class="rounded-xl border px-4 py-3 text-sm {{ [
                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
                    'error' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200',
                ][$flashType] }}">
                    {{ session($flashType) }}
                </div>
            @endif
        @endforeach

        <div class="ui-panel flex flex-wrap items-center gap-2 p-2" role="tablist" aria-label="Bagian HRD">
            <button type="button" role="tab" id="hrd-tab-approvals" aria-controls="hrd-panel-approvals"
                    :aria-selected="section === 'approvals'" @click="section = 'approvals'"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="fas fa-clipboard-check"></i> Approval
            </button>
            <button type="button" role="tab" id="hrd-tab-employees" aria-controls="hrd-panel-employees"
                    :aria-selected="section === 'employees'" @click="section = 'employees'"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="fas fa-users"></i> Data Karyawan
            </button>
        </div>

        <section id="hrd-panel-employees" x-show="section === 'employees'" x-cloak role="tabpanel"
                 aria-labelledby="hrd-tab-employees">
            @include('admin.absensi.approval.partials.employee-management')
        </section>

        <section id="hrd-panel-approvals" x-show="section === 'approvals'" x-cloak role="tabpanel"
                 aria-labelledby="hrd-tab-approvals" class="space-y-6">
            <div class="ui-panel flex w-fit flex-wrap items-center gap-2">
                @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $filter => $label)
                    <a href="{{ request()->fullUrlWithQuery(['section' => 'approvals', 'status' => $filter]) }}"
                       class="rounded-xl px-5 py-2.5 text-sm font-bold {{ $status === $filter ? 'bg-emerald-600 text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <section class="ui-panel">
                <h3 class="mb-5 text-lg font-bold text-gray-900 dark:text-white">Pengajuan absensi ({{ $submissions->count() }})</h3>
                @include('admin.absensi.approval.table', ['submissions' => $submissions])
            </section>

        </section>
    </div>

    @include('admin.absensi.approval.partials.reject-modal')
    @include('admin.absensi.approval.partials.image-preview-modal')
</x-app-layout>
