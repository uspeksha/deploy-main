<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Approval HRD</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Semua pengajuan absensi menunggu satu persetujuan HRD.</p>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @foreach (['success', 'warning', 'error'] as $flashType)
                @if (session($flashType))
                    <div class="mb-5 rounded-xl border px-4 py-3 text-sm {{ [
                        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
                        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
                        'error' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200',
                    ][$flashType] }}">
                        {{ session($flashType) }}
                    </div>
                @endif
            @endforeach

            @if (auth()->user()->role === 'hrd')
                <section class="mb-6 rounded-2xl border border-indigo-200 bg-white p-6 shadow-lg dark:border-indigo-900 dark:bg-gray-800">
                    <div class="mb-5">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Buat akun karyawan baru</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            HRD mengisi data karyawan. Akun diaktifkan setelah password sementara berhasil dikirim ke email karyawan.
                            NIK dan rekening disimpan terenkripsi.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.absensi.approval.employees.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @csrf
                        <div>
                            <x-input-label for="employee_name" value="Nama lengkap" />
                            <x-text-input id="employee_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_id_karyawan" value="ID karyawan" />
                            <x-text-input id="employee_id_karyawan" name="id_karyawan" type="text" class="mt-1 block w-full" :value="old('id_karyawan')" required />
                            <x-input-error :messages="$errors->get('id_karyawan')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_email" value="Email karyawan" />
                            <x-text-input id="employee_email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_department" value="Departemen" />
                            <x-text-input id="employee_department" name="departemen" type="text" class="mt-1 block w-full" :value="old('departemen')" required />
                            <x-input-error :messages="$errors->get('departemen')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_type" value="Jenis karyawan" />
                            <select id="employee_type" name="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">Pilih jenis karyawan</option>
                                <option value="organik" @selected(old('employment_type') === 'organik')>Organik</option>
                                <option value="freelance" @selected(old('employment_type') === 'freelance')>Freelance</option>
                            </select>
                            <x-input-error :messages="$errors->get('employment_type')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_work_location" value="Lokasi kerja" />
                            <select id="employee_work_location" name="work_location" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">Pilih lokasi kerja</option>
                                <option value="office" @selected(old('work_location') === 'office')>Kantor</option>
                                <option value="produksi" @selected(old('work_location') === 'produksi')>Produksi</option>
                            </select>
                            <x-input-error :messages="$errors->get('work_location')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_nik" value="NIK (16 digit)" />
                            <x-text-input id="employee_nik" name="nik" type="text" inputmode="numeric" maxlength="16" class="mt-1 block w-full" :value="old('nik')" required />
                            <x-input-error :messages="$errors->get('nik')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_bank" value="Nama bank" />
                            <x-text-input id="employee_bank" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name')" required />
                            <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="employee_account_number" value="Nomor rekening" />
                            <x-text-input id="employee_account_number" name="bank_account_number" type="text" class="mt-1 block w-full" :value="old('bank_account_number')" required />
                            <x-input-error :messages="$errors->get('bank_account_number')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <x-input-label for="employee_account_name" value="Nama pemilik rekening" />
                            <x-text-input id="employee_account_name" name="bank_account_name" type="text" class="mt-1 block w-full" :value="old('bank_account_name')" required />
                            <x-input-error :messages="$errors->get('bank_account_name')" class="mt-2" />
                        </div>
                        <div class="flex items-end sm:col-span-2 lg:col-span-3">
                            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                Buat akun & kirim akses lewat email
                            </button>
                        </div>
                    </form>

                    @if ($pendingAccounts->isNotEmpty())
                        <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Akun menunggu email terkirim</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Akun ini belum aktif karena pengiriman email sebelumnya gagal.</p>
                            @foreach ($pendingAccounts as $account)
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-amber-50 p-3 dark:bg-amber-950/20">
                                    <div class="text-sm">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $account->name }}</span>
                                        <span class="text-gray-600 dark:text-gray-400"> · {{ $account->email }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('admin.absensi.approval.employees.resend', $account) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200">
                                            Buat password baru & kirim ulang
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

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
