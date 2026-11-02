<div class="space-y-6">
        <details class="ui-panel group" {{ $errors->any() ? 'open' : '' }}>
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

        <section class="ui-panel overflow-hidden !p-0">
            <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Daftar karyawan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nonaktifkan akun tanpa menghapus riwayat absensi atau payroll.</p>
                </div>
                <form method="GET" action="{{ route('admin.employees.index') }}" class="flex gap-2">
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
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employee->id_karyawan }}</div>
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
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.employees.edit', $employee) }}" class="ui-button-secondary !px-3 !py-2">Edit</a>
                                        @if ($employee->registration_status === 'pending')
                                            <form method="POST" action="{{ route('admin.absensi.approval.employees.resend', $employee) }}">
                                                @csrf
                                                <button type="submit" class="ui-button-primary !px-3 !py-2">Kirim akses</button>
                                            </form>
                                        @elseif ($employee->is_active)
                                            <form method="POST" action="{{ route('admin.employees.deactivate', $employee) }}" onsubmit="return confirm('Nonaktifkan akun ini? Data dan riwayat tetap disimpan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ui-button-danger !px-3 !py-2">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.employees.activate', $employee) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="ui-button-primary !px-3 !py-2">Aktifkan</button>
                                            </form>
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
