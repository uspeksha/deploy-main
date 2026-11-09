<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">Edit Data Karyawan</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Perbarui informasi {{ $user->name }}.</p>
        </div>
    </x-slot>

    <div class="ui-page">
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200">
                Periksa kembali data yang ditandai sebelum menyimpan.
            </div>
        @endif

        <section class="ui-panel mx-auto w-full max-w-4xl">
            <form method="POST" action="{{ route('admin.employees.update', ['user' => $user] + $employeeListQuery) }}"
                  class="grid gap-x-5 gap-y-4 sm:grid-cols-2"
                  x-data="{ employmentType: @js(old('employment_type', $user->employment_type ?? 'organik')) }">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="employee_name" value="Nama lengkap" />
                    <x-text-input id="employee_name" name="name" class="mt-1 w-full ui-control" :value="old('name', $user->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_id_karyawan" value="ID karyawan" />
                    <x-text-input id="employee_id_karyawan" name="id_karyawan" class="mt-1 w-full ui-control" :value="old('id_karyawan', $user->id_karyawan)" required />
                    <x-input-error :messages="$errors->get('id_karyawan')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_gender" value="Jenis kelamin" />
                    <select id="employee_gender" name="gender" class="mt-1 w-full ui-control">
                        <option value="">Belum diisi</option>
                        <option value="male" @selected(old('gender', $user->gender) === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('gender', $user->gender) === 'female')>Perempuan</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_email" value="Email karyawan" />
                    <x-text-input id="employee_email" name="email" type="email" class="mt-1 w-full ui-control" :value="old('email', $user->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_phone" value="Nomor telepon" />
                    <x-text-input id="employee_phone" name="phone" type="tel" class="mt-1 w-full ui-control" :value="old('phone', $user->phone)" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_department" value="Departemen" />
                    <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Legacy (teks lama, tetap digunakan oleh alur yang sudah ada)</p>
                    <x-text-input id="employee_department" name="departemen" class="mt-1 w-full ui-control" :value="old('departemen', $user->departemen)" required />
                    <x-input-error :messages="$errors->get('departemen')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_department_id" value="Departemen (struktur baru)" />
                    <select id="employee_department_id" name="department_id" class="mt-1 w-full ui-control">
                        <option value="">Tidak dipilih</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_position_id" value="Jabatan (struktur baru)" />
                    <select id="employee_position_id" name="position_id" class="mt-1 w-full ui-control">
                        <option value="">Tidak dipilih</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) old('position_id', $user->position_id) === (string) $position->id)>{{ $position->name }} — {{ $position->department->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('position_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_type" value="Jenis karyawan" />
                    <select id="employee_type" name="employment_type" x-model="employmentType" required class="mt-1 w-full ui-control">
                        <option value="organik" @selected(old('employment_type', $user->employment_type) === 'organik')>Organik</option>
                        <option value="freelance" @selected(old('employment_type', $user->employment_type) === 'freelance')>Freelance</option>
                    </select>
                    <x-input-error :messages="$errors->get('employment_type')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_work_location" value="Lokasi kerja" />
                    <select id="employee_work_location" name="work_location" required class="mt-1 w-full ui-control">
                        <option value="office" @selected(old('work_location', $user->work_location) === 'office')>Kantor</option>
                        <option value="produksi" @selected(old('work_location', $user->work_location) === 'produksi')>Produksi</option>
                    </select>
                    <x-input-error :messages="$errors->get('work_location')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_nik" value="NIK (16 digit)" />
                    <x-text-input id="employee_nik" name="nik" inputmode="numeric" maxlength="16" class="mt-1 w-full ui-control" :value="old('nik', $user->nik)" required />
                    <x-input-error :messages="$errors->get('nik')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_bank" value="Nama bank" />
                    <select id="employee_bank" name="bank_name" required class="mt-1 w-full ui-control">
                        <option value="BCA" @selected(old('bank_name', $user->bank_name) === 'BCA')>BCA</option>
                        <option value="BRI" @selected(old('bank_name', $user->bank_name) === 'BRI')>BRI</option>
                    </select>
                    <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_account_number" value="Nomor rekening" />
                    <x-text-input id="employee_account_number" name="bank_account_number" class="mt-1 w-full ui-control" :value="old('bank_account_number', $user->bank_account_number)" required />
                    <x-input-error :messages="$errors->get('bank_account_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="employee_account_name" value="Nama pemilik rekening" />
                    <x-text-input id="employee_account_name" name="bank_account_name" class="mt-1 w-full ui-control" :value="old('bank_account_name', $user->bank_account_name)" required />
                    <x-input-error :messages="$errors->get('bank_account_name')" class="mt-2" />
                </div>
                <div x-show="employmentType === 'freelance'" x-cloak>
                    <x-input-label for="employee_daily_rate" value="Tarif harian freelance (Rp)" />
                    <x-text-input id="employee_daily_rate" name="daily_rate" type="number" min="1" max="100000000" x-bind:required="employmentType === 'freelance'" x-bind:disabled="employmentType !== 'freelance'" class="mt-1 w-full ui-control" :value="old('daily_rate', $dailyRate)" />
                    <x-input-error :messages="$errors->get('daily_rate')" class="mt-2" />
                </div>
                <div x-show="employmentType === 'freelance'" x-cloak>
                    <x-input-label for="employee_effective_at" value="Tarif berlaku mulai" />
                    <x-text-input id="employee_effective_at" name="effective_at" type="datetime-local" x-bind:required="employmentType === 'freelance'" x-bind:disabled="employmentType !== 'freelance'" class="mt-1 w-full ui-control" :value="old('effective_at', $nextEffectiveAt->format('Y-m-d\TH:i'))" />
                    <x-input-error :messages="$errors->get('effective_at')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="employee_address" value="Alamat" />
                    <textarea id="employee_address" name="address" rows="3" maxlength="1000" class="mt-1 w-full ui-control" required>{{ old('address', $user->address) }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:col-span-2 sm:flex-row sm:justify-end dark:border-gray-700">
                    <a href="{{ route('admin.absensi.approval.hrd', $employeeListQuery) }}" class="ui-button-secondary">Batal</a>
                    <button type="submit" class="ui-button-primary">Simpan perubahan</button>
                </div>
            </form>
        </section>
        @include('admin.employees.hr-data')
    </div>
</x-app-layout>
