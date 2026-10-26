<x-app-layout>
    <div class="ui-page">
        <div class="ui-panel">
            <div class="ui-section-heading mb-2">
                <span class="ui-section-icon"><i class="fas fa-history"></i></span>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Log Aktivitas Admin</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Rekaman jejak digital persetujuan absensi, lembur, dll oleh semua role admin.
                    </p>
                </div>
            </div>

                <div class="ui-table-shell mt-6">
                    <table class="ui-table">
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3">Waktu</th>
                                <th scope="col" class="px-6 py-3">Admin (Pelaku)</th>
                                <th scope="col" class="px-6 py-3">Role</th>
                                <th scope="col" class="px-6 py-3">Aksi</th>
                                <th scope="col" class="px-6 py-3">Target</th>
                                <th scope="col" class="px-6 py-3">Detail/Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $log->created_at->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $log->user->name ?? 'System/Unknown' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if(in_array(($log->user->role ?? ''), ['super_admin', 'manager', 'supervisor', 'hrga'])) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif
                                        ">
                                            {{ strtoupper($log->user->role ?? 'UNKNOWN') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-lg font-bold
                                            @if(str_contains(strtolower($log->action ?? ''), 'approve')) bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/50 dark:border-green-800
                                            @elseif(str_contains(strtolower($log->action ?? ''), 'reject')) bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/50 dark:border-red-800
                                            @else bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 @endif
                                        ">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $log->subject ?? '-' }}
                                    </td>
                                    <td class="max-w-xs truncate px-6 py-4" title="{{ $log->details }}">
                                        {{ $log->details ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-5">
                                        <div class="ui-empty-state text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-inbox mb-3 text-3xl text-emerald-600 dark:text-emerald-400"></i>
                                            <p class="font-medium">Belum ada aktivitas yang direkam.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
        </div>
    </div>
</x-app-layout>
