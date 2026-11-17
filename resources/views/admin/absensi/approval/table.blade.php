<div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
    @forelse ($submissions as $submission)
        @php
            $user = $submission->user ?? null;
            $isOvertime = ($submission->tipe === 'lembur');
            $status = $submission->status_approval ?? 'pending';
            $submissionLabel = $submission->submission_type ?: ($submission->tipe ?: $submission->status);

            // Durasi lembur
            $durationStr = '-';
            if ($isOvertime && $submission->overtime_minutes > 0) {
                $diffInMinutes = $submission->overtime_minutes;
                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
                $durationStr = $hours . ' jam ' . $minutes . ' menit';
            } elseif ($isOvertime && $submission->lembur_start && $submission->lembur_end) {
                $start = \Carbon\Carbon::parse($submission->lembur_start);
                $end = \Carbon\Carbon::parse($submission->lembur_end);
                $diffInMinutes = $start->diffInMinutes($end);
                if ($submission->lembur_rest == 1) {
                    $diffInMinutes = max(0, $diffInMinutes - 30);
                }
                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
                $durationStr = $hours . ' jam ' . $minutes . ' menit';
            }

            // Warna Badge Status
            $statusColors = [
                'pending' => 'bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200/60 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                'default' => 'bg-gray-50 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'
            ];
            $statusClass = $statusColors[$status] ?? $statusColors['default'];

            // Border warna di sisi kiri card sesuai tipe
            $typeBorder = [
                'lembur' => 'border-l-indigo-500',
                'izin' => 'border-l-amber-500',
                'sakit' => 'border-l-rose-500',
                'telat' => 'border-l-orange-500',
                'default' => 'border-l-gray-300 dark:border-l-gray-600',
            ];
            $borderClass = $typeBorder[$submission->tipe] ?? $typeBorder['default'];

            $initial = $user->name ? strtoupper(substr($user->name, 0, 2)) : '??';
        @endphp

        <article class="ui-panel flex flex-col justify-between border-l-4 {{ $borderClass }} !rounded-lg !p-3 sm:!p-4">
            <div>
                <div class="mb-3 flex items-start justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold tracking-wide text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name ?? '-' }}</h3>
                            <span class="mt-0.5 inline-block text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $submissionLabel) }}</span>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-semibold uppercase tracking-wide {{ $statusClass }}">
                        {{ str_replace('_', ' ', $status) }}
                    </span>
                </div>

                <div class="mb-3 space-y-1.5 rounded-lg bg-slate-50 px-3 py-2 text-xs dark:bg-slate-800/60">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            @if ($submission->check_in_at)
                                {{ \Carbon\Carbon::parse($submission->check_in_at)->isoFormat('DD MMM YYYY') }}
                                @if ($submission->end_date && \Carbon\Carbon::parse($submission->end_date)->toDateString() !== \Carbon\Carbon::parse($submission->check_in_at)->toDateString())
                                    - {{ \Carbon\Carbon::parse($submission->end_date)->isoFormat('DD MMM YYYY') }}
                                @endif
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    @if ($submission->total_days > 1)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Durasi izin/cuti</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $submission->total_days }} hari</span>
                        </div>
                    @endif

                    @if($isOvertime)
                        @if($submission->lembur_start && $submission->lembur_end)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Jam lembur</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ \Carbon\Carbon::parse($submission->lembur_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($submission->lembur_end)->format('H:i') }}
                                </span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Durasi</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $durationStr }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Istirahat</span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-800 dark:text-gray-200">
                                <span class="w-1.5 h-1.5 rounded-full {{ $submission->lembur_rest == 1 ? 'bg-teal-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                {{ $submission->lembur_rest == 1 ? '30 menit' : 'Tidak' }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Keterangan</p>
                    <p class="line-clamp-2 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                        {{ $isOvertime ? ($submission->lembur_keterangan ?? '-') : ($submission->keterangan_izin_sakit ?? '-') }}
                    </p>
                </div>

                @if ($status === 'rejected' && $submission->catatan_admin)
                    <div class="mb-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 dark:border-rose-900/50 dark:bg-rose-950/20">
                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-300">Alasan penolakan</p>
                        <p class="text-xs leading-relaxed text-rose-800 dark:text-rose-200">{{ $submission->catatan_admin }}</p>
                    </div>
                @endif

                <div class="mb-3">
                    <div class="flex flex-wrap gap-1.5">
                        @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin' || $submission->tipe === 'telat')
                            @if ($submission->file_bukti)
                                <button type="button" data-image-url="{{ route('admin.storage.file', ['path' => $submission->file_bukti]) }}" class="preview-image-btn inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-800 transition-colors hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat File
                                </button>
                            @else
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">Tidak ada lampiran</span>
                            @endif
                        @elseif ($submission->tipe === 'lembur')
                            @php
                                $allFotos = array_values(array_filter([
                                    $submission->foto_pulang, $submission->foto_pulang_2, $submission->foto_pulang_3,
                                    $submission->foto_pulang_4, $submission->foto_pulang_5, $submission->foto_pulang_6,
                                ]));
                            @endphp
                            @forelse($allFotos as $i => $foto)
                                <button type="button" data-image-url="{{ route('admin.storage.file', ['path' => $foto]) }}" class="preview-image-btn inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-800 transition-colors hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    Foto {{ $i + 1 }}
                                </button>
                            @empty
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">Tidak ada foto</span>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>

            @if ($status === 'pending')
                <div class="mt-auto grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}" method="POST">
                        @csrf
                        <input type="hidden" name="catatan_admin" value="Disetujui">
                        <button type="submit" class="ui-button-primary min-h-9 w-full px-3 py-1.5 text-xs">
                            Setujui
                        </button>
                    </form>
                    
                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'reject']) }}" method="POST" class="flex min-w-0 gap-1">
                        @csrf
                        <input type="text" name="catatan_admin" placeholder="Alasan..." class="ui-control min-h-9 min-w-0 px-2 py-1.5 text-xs" required>
                        <button type="submit" class="ui-button-danger min-h-9 shrink-0 px-2.5 py-1.5 text-xs">
                            Tolak
                        </button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <div class="col-span-full ui-empty-state">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-6 9l2 2 4-4"/></svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada data pengajuan absensi saat ini.</p>
        </div>
    @endforelse
</div>