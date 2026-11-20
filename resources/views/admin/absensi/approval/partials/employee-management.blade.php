@php
    $employeeListQuery = array_filter([
        'section' => 'employees',
        'employee_status' => in_array(request('employee_status', 'all'), ['active', 'inactive', 'all'], true)
            ? request('employee_status', 'all')
            : 'all',
        'employee_search' => request('employee_search'),
        'employee_page' => filter_var(request('employee_page'), FILTER_VALIDATE_INT) > 0
            ? (int) request('employee_page')
            : null,
    ], fn ($value) => $value !== null && $value !== '');
@endphp
<div class="space-y-6">
        <details class="ui-panel group" {{ $errors->any() && ! $errors->has('employee_file') && ! $errors->has('employee_import') && ! $errors->has('confirm_organization_department') ? 'open' : '' }}>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Buat akun karyawan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Isi data sekali. Akun dan detail karyawan langsung tersimpan di daftar ini.</p>
                </div>
                <span class="ui-button-primary shrink-0"><i class="fas fa-plus mr-2"></i>Buat akun</span>
            </summary>

            <form method="POST" action="{{ route('admin.absensi.approval.employees.store') }}"
                  class="mt-6 grid gap-x-5 gap-y-4 border-t border-gray-200 pt-6 sm:grid-cols-2 lg:grid-cols-3"
                  x-data="{ employmentType: @js(old('employment_type', '')) }">
                @csrf
                <div>
                    <x-input-label for="employee_name" value="Nama lengkap" />
                    <x-text-input id="employee_name" name="name" class="mt-1 w-full ui-control" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_id_karyawan" value="ID karyawan" />
                    <x-text-input id="employee_id_karyawan" name="id_karyawan" class="mt-1 w-full ui-control" :value="old('id_karyawan')" required />
                    <x-input-error :messages="$errors->get('id_karyawan')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_gender" value="Jenis kelamin" />
                    <select id="employee_gender" name="gender" class="mt-1 w-full ui-control">
                        <option value="">Belum diisi</option>
                        <option value="male" @selected(old('gender') === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('gender') === 'female')>Perempuan</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_email" value="Email karyawan" />
                    <x-text-input id="employee_email" name="email" type="email" class="mt-1 w-full ui-control" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_phone" value="Nomor telepon" />
                    <x-text-input id="employee_phone" name="phone" type="tel" class="mt-1 w-full ui-control" :value="old('phone')" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_department" value="Departemen" />
                    <x-text-input id="employee_department" name="departemen" class="mt-1 w-full ui-control" :value="old('departemen')" required />
                    <x-input-error :messages="$errors->get('departemen')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_type" value="Jenis karyawan" />
                    <select id="employee_type" name="employment_type" x-model="employmentType" required class="mt-1 w-full ui-control">
                        <option value="">Pilih jenis karyawan</option>
                        <option value="organik" @selected(old('employment_type') === 'organik')>Organik</option>
                        <option value="freelance" @selected(old('employment_type') === 'freelance')>Freelance</option>
                    </select>
                    <x-input-error :messages="$errors->get('employment_type')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_work_location" value="Lokasi kerja" />
                    <select id="employee_work_location" name="work_location" required class="mt-1 w-full ui-control">
                        <option value="">Pilih lokasi kerja</option>
                        <option value="office" @selected(old('work_location') === 'office')>Kantor</option>
                        <option value="produksi" @selected(old('work_location') === 'produksi')>Produksi</option>
                    </select>
                    <x-input-error :messages="$errors->get('work_location')" class="mt-2" />
                </div>
                <div x-show="employmentType === 'freelance'" x-cloak>
                    <x-input-label for="employee_daily_rate" value="Tarif harian freelance (Rp)" />
                    <x-text-input id="employee_daily_rate" name="daily_rate" type="number" min="1" max="100000000"
                                  x-bind:required="employmentType === 'freelance'" x-bind:disabled="employmentType !== 'freelance'"
                                  class="mt-1 w-full ui-control" :value="old('daily_rate', \App\Models\EmployeeSalaryRate::LEGACY_DAILY_RATE)" />
                    <x-input-error :messages="$errors->get('daily_rate')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_nik" value="NIK (16 digit)" />
                    <x-text-input id="employee_nik" name="nik" inputmode="numeric" maxlength="16" class="mt-1 w-full ui-control" :value="old('nik')" required />
                    <x-input-error :messages="$errors->get('nik')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_bank" value="Nama bank" />
                    <select id="employee_bank" name="bank_name" required class="mt-1 w-full ui-control">
                        <option value="">Pilih bank</option>
                        <option value="BCA" @selected(old('bank_name') === 'BCA')>BCA</option>
                        <option value="BRI" @selected(old('bank_name') === 'BRI')>BRI</option>
                    </select>
                    <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_account_number" value="Nomor rekening" />
                    <x-text-input id="employee_account_number" name="bank_account_number" class="mt-1 w-full ui-control" :value="old('bank_account_number')" required />
                    <x-input-error :messages="$errors->get('bank_account_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_account_name" value="Nama pemilik rekening" />
                    <x-text-input id="employee_account_name" name="bank_account_name" class="mt-1 w-full ui-control" :value="old('bank_account_name')" required />
                    <x-input-error :messages="$errors->get('bank_account_name')" class="mt-2" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-input-label for="employee_address" value="Alamat" />
                    <textarea id="employee_address" name="address" rows="3" maxlength="1000" class="mt-1 w-full ui-control" required>{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <button type="submit" class="ui-button-primary"><i class="fas fa-paper-plane mr-2"></i>Buat akun & kirim akses</button>
                </div>
            </form>
        </details>

        <section class="ui-panel space-y-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Impor karyawan dari Excel</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Unggah file sekali untuk memeriksa banyak karyawan. Akun belum dibuat sampai pratinjau dikonfirmasi.
                </p>
            </div>
            <a href="{{ route('admin.absensi.approval.employees.import.template') }}" class="ui-button-secondary inline-flex">
                <i class="fas fa-download mr-2"></i>Unduh template CSV
            </a>
            <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-900/50 dark:text-gray-300">
                <p class="font-semibold">Sebelum mengunggah:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>Kolom wajib: Employee ID, Full Name, Email, Mobile Phone, Residential Address, Department, Employment Type, Join Date, Work Location, NIK, Bank Name, Bank Account, dan Bank Account Holder.</li>
                    <li>Employment Type: <span class="font-medium">organik</span> atau <span class="font-medium">freelance</span>; Work Location: <span class="font-medium">office</span> atau <span class="font-medium">produksi</span>.</li>
                    <li>Bank yang didukung saat ini hanya BCA dan BRI. Isi Daily Rate untuk setiap karyawan freelance.</li>
                    <li>Pastikan NIK benar-benar NIK 16 digit, bukan NPWP. Simpan Employee ID, nomor telepon, NIK, dan nomor rekening sebagai teks agar angka nol atau digit tidak hilang.</li>
                    <li>File sumber dengan kolom Organization boleh dipakai, tetapi saat pratinjau HRD harus memastikan Organization memang berisi nama departemen.</li>
                </ul>
            </div>
            <form method="POST" action="{{ route('admin.absensi.approval.employees.import.preview') }}"
                  enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-input-label for="employee_import_file" value="File Excel (.xlsx) atau CSV (maksimal 10 MB, 500 baris)" />
                    <input id="employee_import_file" name="employee_file" type="file" accept=".xlsx,.csv"
                           class="mt-1 block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" required />
                    <x-input-error :messages="$errors->get('employee_file')" class="mt-2" />
                </div>
                <button type="submit" class="ui-button-primary shrink-0">
                    <i class="fas fa-eye mr-2"></i>Periksa & tampilkan pratinjau
                </button>
            </form>

            @if ($employeeImportPreview)
                <div class="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">Pratinjau impor</h4>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ count($employeeImportPreview['rows']) }} baris siap dibuat;
                            {{ count($employeeImportPreview['errors']) }} baris perlu diperbaiki.
                            Tidak ada akun yang dibuat sebelum konfirmasi.
                        </p>
                    </div>

                    @if ($employeeImportPreview['uses_organization'])
                        <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                            Kolom <strong>Organization</strong> akan dipakai sebagai departemen. Pastikan nilainya memang nama departemen sebelum melanjutkan.
                        </div>
                    @endif

                    @if ($employeeImportPreview['errors'] !== [])
                        <div class="max-h-64 overflow-auto rounded-lg border border-rose-200 bg-rose-50 p-3 dark:border-rose-900 dark:bg-rose-950/20">
                            <h5 class="font-semibold text-rose-800 dark:text-rose-200">Perbaiki baris berikut di Excel, lalu unggah kembali:</h5>
                            <ul class="mt-2 space-y-2 text-sm text-rose-800 dark:text-rose-200">
                                @foreach ($employeeImportPreview['errors'] as $rowError)
                                    <li><strong>Baris {{ $rowError['row'] }}:</strong> {{ implode(' ', $rowError['messages']) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="max-h-64 overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Employee ID</th>
                                        <th class="px-3 py-2 text-left">Nama</th>
                                        <th class="px-3 py-2 text-left">Email</th>
                                        <th class="px-3 py-2 text-left">Jenis karyawan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($employeeImportPreview['rows'] as $row)
                                        <tr>
                                            <td class="px-3 py-2">{{ $row['id_karyawan'] }}</td>
                                            <td class="px-3 py-2">{{ $row['name'] }}</td>
                                            <td class="px-3 py-2">{{ $row['email'] }}</td>
                                            <td class="px-3 py-2">{{ ucfirst($row['employment_type']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($errors->has('employee_import'))
                        <div class="text-sm text-rose-700 dark:text-rose-300">{{ $errors->first('employee_import') }}</div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        @if ($employeeImportPreview['errors'] === [] && $employeeImportPreview['rows'] !== [])
                            <form method="POST" action="{{ route('admin.absensi.approval.employees.import.confirm') }}"
                                  onsubmit="return confirm('Buat {{ count($employeeImportPreview['rows']) }} akun karyawan dari file ini?')">
                                @csrf
                                @if ($employeeImportPreview['uses_organization'])
                                    <label class="mb-3 flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                                        <input type="checkbox" name="confirm_organization_department" value="1" required
                                               class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>Saya memastikan Organization adalah nama departemen.</span>
                                    </label>
                                @endif
                                <button type="submit" class="ui-button-primary">
                                    <i class="fas fa-check mr-2"></i>Konfirmasi & buat akun
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.absensi.approval.employees.import.cancel') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ui-button-secondary">Batalkan pratinjau</button>
                        </form>
                    </div>
                </div>
            @endif
        </section>

        <section class="ui-panel overflow-hidden !p-0">
            <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Daftar karyawan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Semua karyawan, termasuk akun nonaktif, ditampilkan. Nonaktifkan untuk memblokir login tanpa menghapus riwayat; aktifkan kembali kapan saja. Hapus permanen menghapus akun beserta riwayat absensi dan payroll.</p>
                </div>
                <form method="GET" action="{{ route('admin.employees.index') }}" class="flex gap-2">
                    <select name="employee_status" class="ui-control min-w-36">
                        <option value="all" @selected($employeeStatus === 'all')>Semua status</option>
                        <option value="active" @selected($employeeStatus === 'active')>Aktif & menunggu</option>
                        <option value="inactive" @selected($employeeStatus === 'inactive')>Nonaktif</option>
                    </select>
                    <input type="search" name="employee_search" value="{{ request('employee_search') }}" placeholder="Cari nama, email, ID"
                           class="ui-control min-w-0 sm:w-64">
                    <button type="submit" class="ui-button-secondary">Cari</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Karyawan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kontak</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Pekerjaan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Identitas & rekening</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-800">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.employees.show', ['user' => $employee] + $employeeListQuery) }}"
                                           class="shrink-0 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           aria-label="Lihat detail {{ $employee->name }}">
                                            @if ($employee->profile_photo_path)
                                                <img src="{{ route('admin.storage.file', ['path' => $employee->profile_photo_path]) }}"
                                                     alt="Foto profil {{ $employee->name }}"
                                                     class="h-11 w-11 rounded-full border border-gray-200 object-cover dark:border-gray-700">
                                            @else
                                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                                                     aria-label="Belum ada foto profil">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            @endif
                                        </a>
                                        <div>
                                            <a href="{{ route('admin.employees.show', ['user' => $employee] + $employeeListQuery) }}"
                                               class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                                                {{ $employee->name }}
                                            </a>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employee->id_karyawan }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-800 dark:text-gray-200">{{ $employee->email }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employee->phone ?: '—' }}</div>
                                    <div class="mt-1 max-w-xs whitespace-normal text-xs text-gray-500 dark:text-gray-400">{{ $employee->address ?: '—' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    <div>{{ $employee->departemen ?: '—' }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($employee->employment_type ?? 'organik') }} · {{ $employee->work_location === 'office' ? 'Kantor' : ($employee->work_location === 'produksi' ? 'Produksi' : '—') }}</div>
                                    @if ($employee->employment_type === 'freelance')
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tarif: Rp {{ number_format($employee->dailyRateAt(), 0, ',', '.') }}/hari</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    <div>NIK: {{ $employee->nik ?: '—' }}</div>
                                    <div class="mt-1">{{ $employee->bank_name ?: '—' }} · {{ $employee->bank_account_number ?: '—' }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employee->bank_account_name ?: '—' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($employee->registration_status !== 'approved')
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Menunggu email</span>
                                    @elseif ($employee->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('admin.employees.show', ['user' => $employee] + $employeeListQuery) }}"
                                           class="ui-button-primary !px-3 !py-2">Detail</a>
                                        <a href="{{ route('admin.employees.edit', ['user' => $employee] + $employeeListQuery) }}" class="ui-button-secondary !px-3 !py-2">Edit</a>
                                        @if ($employee->registration_status === 'pending')
                                            <form method="POST" action="{{ route('admin.absensi.approval.employees.resend', ['user' => $employee] + $employeeListQuery) }}">
                                                @csrf
                                                <button type="submit" class="ui-button-primary !px-3 !py-2">Kirim akses</button>
                                            </form>
                                        @elseif ($employee->is_active)
                                            <form method="POST" action="{{ route('admin.employees.deactivate', ['user' => $employee] + $employeeListQuery) }}" onsubmit="return confirm('Nonaktifkan akun ini? Data dan riwayat tetap disimpan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ui-button-danger !px-3 !py-2">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.employees.activate', ['user' => $employee] + $employeeListQuery) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="ui-button-primary !px-3 !py-2">Aktifkan</button>
                                            </form>
                                        @endif
                                        @if (! $employee->is_active)
                                            <form method="POST" action="{{ route('admin.employees.destroy', ['user' => $employee] + $employeeListQuery) }}"
                                                  onsubmit="return confirm('HAPUS PERMANEN akun ini, seluruh riwayat absensi, dan data payroll-nya? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ui-button-danger !px-3 !py-2">Hapus permanen</button>
                                            </form>
                                        @else
                                            <div class="text-right">
                                                <button type="button" disabled title="Nonaktifkan akun terlebih dahulu"
                                                        class="ui-button-danger !px-3 !py-2 cursor-not-allowed opacity-50">
                                                    Hapus permanen
                                                </button>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nonaktifkan dulu untuk menghapus</p>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada data karyawan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-700">{{ $employees->links() }}</div>
        </section>
</div>
