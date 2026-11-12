<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">Detail Karyawan</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Biodata dan informasi pekerjaan {{ $user->name }}.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.absensi.approval.hrd', ['section' => 'employees']) }}" class="ui-button-secondary">Kembali</a>
                <a href="{{ route('admin.employees.edit', $user) }}" class="ui-button-primary">Edit data</a>
            </div>
        </div>
    </x-slot>

    @php
        $gender = $user->gender ?? $user->profile?->gender;
        $status = $user->registration_status !== 'approved'
            ? 'Menunggu email'
            : ($user->is_active ? 'Aktif' : 'Nonaktif');
        $statusClass = $user->registration_status !== 'approved'
            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
            : ($user->is_active
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200');
        $workLocation = match ($user->work_location) {
            'office' => 'Kantor',
            'produksi' => 'Produksi',
            default => 'Belum diisi',
        };
        $employmentType = match ($user->employment_type) {
            'organik' => 'Organik',
            'freelance' => 'Freelance',
            default => ucfirst($user->employment_type ?? 'Belum diisi'),
        };
        $address = $user->profile?->address ?: $user->address;
    @endphp

    <div class="ui-page space-y-6">
        <section class="ui-panel">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                @if ($user->profile_photo_path)
                    <img src="{{ route('admin.storage.file', ['path' => $user->profile_photo_path]) }}"
                         alt="Foto profil {{ $user->name }}"
                         class="h-20 w-20 rounded-full border border-gray-200 object-cover dark:border-gray-700">
                @else
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                         aria-label="Belum ada foto profil">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        ID Karyawan: {{ $user->id_karyawan ?: 'Belum diisi' }}
                    </p>
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-sm font-semibold {{ $statusClass }}">{{ $status }}</span>
            </div>
        </section>

        <section class="ui-panel space-y-5">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Biodata</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Informasi diri dan kontak karyawan.</p>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Nama lengkap' => $user->name,
                    'NIK' => $user->profile?->nik_ktp ?: $user->nik,
                    'Jenis kelamin' => match ($gender) {
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        default => 'Belum diisi',
                    },
                    'Tanggal lahir' => $user->profile?->birth_date?->format('d/m/Y') ?? 'Belum diisi',
                    'Email' => $user->email,
                    'Nomor telepon' => $user->phone,
                    'Alamat / asal domisili' => $address,
                ] as $label => $value)
                    <div class="{{ $label === 'Alamat / asal domisili' ? 'sm:col-span-2 lg:col-span-3' : '' }}">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 whitespace-pre-line break-words text-sm text-gray-900 dark:text-gray-100">{{ filled($value) ? $value : 'Belum diisi' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="ui-panel space-y-5">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Informasi pekerjaan</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Penempatan, status kerja, dan riwayat masuk yang tercatat.</p>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Tanggal masuk tercatat' => $joinedAt?->format('d/m/Y') ?? 'Belum dicatat',
                    'Jenis karyawan' => $employmentType,
                    'Status akun' => $status,
                    'Departemen' => $user->department?->name ?: $user->departemen,
                    'Cabang' => $user->branch?->name,
                    'Jabatan' => $user->position?->name,
                    'Lokasi kerja' => $workLocation,
                    'Tarif harian freelance' => $user->employment_type === 'freelance'
                        ? 'Rp '.number_format($dailyRate, 0, ',', '.')
                        : null,
                    'Status PTKP' => $user->profile?->ptkpStatus?->code,
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 break-words text-sm text-gray-900 dark:text-gray-100">{{ filled($value) ? $value : 'Belum diisi' }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <h4 class="font-semibold text-gray-900 dark:text-white">Riwayat kerja</h4>
                @forelse ($user->employmentHistories->sortByDesc('start_date') as $history)
                    <div class="mt-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-900/40">
                        <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($history->employment_type) }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $history->start_date?->format('d/m/Y') ?? '—' }}
                            &ndash;
                            {{ $history->end_date?->format('d/m/Y') ?? 'Sekarang' }}
                        </p>
                        @if ($history->note)
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $history->note }}</p>
                        @endif
                    </div>
                @empty
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat kerja yang dicatat.</p>
                @endforelse
            </div>
        </section>

        <section class="ui-panel space-y-5">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Informasi administrasi</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data keuangan dan administrasi yang hanya ditampilkan kepada HRD.</p>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'NPWP' => $user->profile?->npwp,
                    'BPJS Kesehatan' => $user->profile?->bpjs_kesehatan_no,
                    'BPJS Ketenagakerjaan' => $user->profile?->bpjs_ketenagakerjaan_no,
                    'Bank' => $user->bank_name,
                    'Nomor rekening' => $user->bank_account_number,
                    'Nama pemilik rekening' => $user->bank_account_name,
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 break-words text-sm text-gray-900 dark:text-gray-100">{{ filled($value) ? $value : 'Belum diisi' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="ui-panel space-y-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Data keluarga</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kontak keluarga dan kontak darurat yang tersimpan.</p>
            </div>
            @forelse ($user->families as $family)
                <div class="grid gap-2 border-t border-gray-200 pt-4 sm:grid-cols-2 dark:border-gray-700">
                    <p class="text-sm text-gray-900 dark:text-gray-100"><span class="font-semibold">Nama:</span> {{ $family->name }}</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100"><span class="font-semibold">Hubungan:</span> {{ $family->relationship }}</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100"><span class="font-semibold">Telepon:</span> {{ $family->phone ?: 'Belum diisi' }}</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100"><span class="font-semibold">Kontak darurat:</span> {{ $family->is_emergency_contact ? 'Ya' : 'Tidak' }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data keluarga.</p>
            @endforelse
        </section>

        <section class="ui-panel space-y-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Dokumen karyawan</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dokumen pribadi tetap diunduh melalui tautan yang memerlukan akses HRD.</p>
            </div>
            @forelse ($user->documents->sortByDesc('created_at') as $document)
                <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ \Illuminate\Support\Str::headline($document->doc_type) }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Ditambahkan {{ $document->created_at?->format('d/m/Y') ?? '—' }}
                            @if ($document->expired_at)
                                · Berlaku sampai {{ $document->expired_at->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.employees.documents.download', [$user, $document]) }}" class="ui-button-secondary">Unduh dokumen</a>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada dokumen karyawan.</p>
            @endforelse
        </section>
    </div>
</x-app-layout>
