<section class="ui-panel space-y-6">
    <div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Profil HR</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Data ini terpisah dari kolom legacy di akun karyawan.</p>
    </div>

    <form method="POST" action="{{ route('admin.employees.hr-profile.update', $user) }}"
          class="grid gap-4 sm:grid-cols-2" x-data="{ visible: false }">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="hr_birth_date" value="Tanggal lahir" />
            <x-text-input id="hr_birth_date" name="birth_date" type="date" class="mt-1 w-full ui-control"
                          :value="old('birth_date', $profile?->birth_date?->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="hr_ptkp_status_id" value="Status PTKP (berlaku hari ini)" />
            <select id="hr_ptkp_status_id" name="ptkp_status_id" class="mt-1 w-full ui-control">
                <option value="">Tidak dipilih</option>
                @foreach ($ptkpStatuses as $ptkpStatus)
                    <option value="{{ $ptkpStatus->id }}" @selected((string) old('ptkp_status_id', $profile?->ptkp_status_id) === (string) $ptkpStatus->id)>
                        {{ $ptkpStatus->code }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('ptkp_status_id')" class="mt-2" />
        </div>
        @foreach ([
            'npwp' => 'NPWP',
            'bpjs_kesehatan_no' => 'Nomor BPJS Kesehatan',
            'bpjs_ketenagakerjaan_no' => 'Nomor BPJS Ketenagakerjaan',
        ] as $field => $label)
            <div>
                <x-input-label :for="'hr_'.$field" :value="$label" />
                <div class="mt-1 flex gap-2">
                    <input
                        type="password"
                        x-bind:type="visible ? 'text' : 'password'"
                        id="hr_{{ $field }}"
                        name="{{ $field }}"
                        value="{{ $profile?->{$field} }}"
                        autocomplete="new-password"
                        class="w-full ui-control"
                    >
                    <button type="button" class="ui-button-secondary shrink-0" @click="visible = !visible"
                            x-text="visible ? 'Tutup' : 'Lihat'"></button>
                </div>
                <x-input-error :messages="$errors->get($field)" class="mt-2" />
            </div>
        @endforeach
        <div class="sm:col-span-2">
            <button type="submit" class="ui-button-primary">Simpan profil HR</button>
        </div>
    </form>
</section>

<section class="ui-panel space-y-5">
    <div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Keluarga</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Catatan keluarga tidak mengubah profil akun legacy.</p>
    </div>

    @forelse ($families as $family)
        <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <form method="POST" action="{{ route('admin.employees.families.update', [$user, $family]) }}"
                  class="grid gap-3 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label :for="'family_name_'.$family->id" value="Nama" />
                    <x-text-input :id="'family_name_'.$family->id" name="name" class="mt-1 w-full ui-control" :value="$family->name" required />
                </div>
                <div>
                    <x-input-label :for="'family_relationship_'.$family->id" value="Hubungan" />
                    <x-text-input :id="'family_relationship_'.$family->id" name="relationship" class="mt-1 w-full ui-control" :value="$family->relationship" required />
                </div>
                <div>
                    <x-input-label :for="'family_phone_'.$family->id" value="Nomor telepon" />
                    <x-text-input :id="'family_phone_'.$family->id" name="phone" class="mt-1 w-full ui-control" :value="$family->phone" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="is_emergency_contact" value="1" @checked($family->is_emergency_contact)>
                    Kontak darurat
                </label>
                <div class="sm:col-span-2">
                    <button type="submit" class="ui-button-secondary">Simpan perubahan keluarga</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.employees.families.destroy', [$user, $family]) }}"
                  class="mt-2" onsubmit="return confirm('Hapus catatan keluarga ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-button-danger">Hapus</button>
            </form>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data keluarga.</p>
    @endforelse

    <form method="POST" action="{{ route('admin.employees.families.store', $user) }}"
          class="grid gap-3 border-t border-gray-200 pt-4 sm:grid-cols-2 dark:border-gray-700">
        @csrf
        <div>
            <x-input-label for="new_family_name" value="Nama" />
            <x-text-input id="new_family_name" name="name" class="mt-1 w-full ui-control" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="new_family_relationship" value="Hubungan" />
            <x-text-input id="new_family_relationship" name="relationship" class="mt-1 w-full ui-control" required />
            <x-input-error :messages="$errors->get('relationship')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="new_family_phone" value="Nomor telepon" />
            <x-text-input id="new_family_phone" name="phone" class="mt-1 w-full ui-control" />
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="is_emergency_contact" value="1">
            Kontak darurat
        </label>
        <div class="sm:col-span-2">
            <button type="submit" class="ui-button-primary">Tambah keluarga</button>
        </div>
    </form>
</section>

<section class="ui-panel space-y-5">
    <div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Riwayat kerja</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Catatan ini tidak mengubah users.employment_type dan bukan backfill otomatis.</p>
    </div>

    @forelse ($employmentHistories as $history)
        <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
            <form method="POST" action="{{ route('admin.employees.employment-histories.update', [$user, $history]) }}"
                  class="grid gap-3 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label :for="'history_type_'.$history->id" value="Jenis pekerjaan" />
                    <x-text-input :id="'history_type_'.$history->id" name="employment_type" class="mt-1 w-full ui-control" :value="$history->employment_type" required />
                </div>
                <div>
                    <x-input-label :for="'history_start_'.$history->id" value="Mulai" />
                    <x-text-input :id="'history_start_'.$history->id" name="start_date" type="date" class="mt-1 w-full ui-control" :value="$history->start_date?->format('Y-m-d')" required />
                </div>
                <div>
                    <x-input-label :for="'history_end_'.$history->id" value="Selesai" />
                    <x-text-input :id="'history_end_'.$history->id" name="end_date" type="date" class="mt-1 w-full ui-control" :value="$history->end_date?->format('Y-m-d')" />
                </div>
                <div>
                    <x-input-label :for="'history_note_'.$history->id" value="Catatan" />
                    <x-text-input :id="'history_note_'.$history->id" name="note" class="mt-1 w-full ui-control" :value="$history->note" />
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="ui-button-secondary">Simpan riwayat</button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.employees.employment-histories.destroy', [$user, $history]) }}"
                  class="mt-2" onsubmit="return confirm('Hapus catatan riwayat kerja ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="ui-button-danger">Hapus</button>
            </form>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat kerja.</p>
    @endforelse

    <form method="POST" action="{{ route('admin.employees.employment-histories.store', $user) }}"
          class="grid gap-3 border-t border-gray-200 pt-4 sm:grid-cols-2 dark:border-gray-700">
        @csrf
        <div>
            <x-input-label for="new_history_type" value="Jenis pekerjaan" />
            <x-text-input id="new_history_type" name="employment_type" class="mt-1 w-full ui-control" required />
            <x-input-error :messages="$errors->get('employment_type')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="new_history_start" value="Mulai" />
            <x-text-input id="new_history_start" name="start_date" type="date" class="mt-1 w-full ui-control" required />
            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="new_history_end" value="Selesai" />
            <x-text-input id="new_history_end" name="end_date" type="date" class="mt-1 w-full ui-control" />
            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="new_history_note" value="Catatan" />
            <x-text-input id="new_history_note" name="note" class="mt-1 w-full ui-control" />
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="ui-button-primary">Tambah riwayat kerja</button>
        </div>
    </form>
</section>

<section class="ui-panel space-y-5">
    <div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Dokumen karyawan</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">PDF, JPEG, atau PNG; maksimal 5 MB. File disimpan privat dan hanya dapat diunduh oleh HRD.</p>
    </div>

    @forelse ($documents as $document)
        <div class="flex flex-col justify-between gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center dark:border-gray-700">
            <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $document->doc_type }}</p>
                @if ($document->expired_at)
                    @if ($document->expired_at->lt(today()))
                        <p class="mt-1 text-sm font-medium text-rose-700 dark:text-rose-300">Sudah kedaluwarsa — {{ $document->expired_at->format('d/m/Y') }}</p>
                    @else
                        <p class="mt-1 text-sm font-medium text-amber-700 dark:text-amber-300">Akan kedaluwarsa — {{ $document->expired_at->format('d/m/Y') }}</p>
                    @endif
                @else
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanggal kedaluwarsa belum dicatat.</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.employees.documents.download', [$user, $document]) }}" class="ui-button-secondary">Unduh</a>
                <form method="POST" action="{{ route('admin.employees.documents.destroy', [$user, $document]) }}"
                      onsubmit="return confirm('Hapus dokumen dan file fisiknya?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ui-button-danger">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada dokumen karyawan.</p>
    @endforelse

    <form method="POST" action="{{ route('admin.employees.documents.store', $user) }}" enctype="multipart/form-data"
          class="grid gap-3 border-t border-gray-200 pt-4 sm:grid-cols-2 dark:border-gray-700">
        @csrf
        <div>
            <x-input-label for="document_type" value="Jenis dokumen" />
            <x-text-input id="document_type" name="doc_type" class="mt-1 w-full ui-control" required />
            <x-input-error :messages="$errors->get('doc_type')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="document_file" value="File" />
            <input id="document_file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required class="mt-1 block w-full ui-control">
            <x-input-error :messages="$errors->get('file')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="document_expired_at" value="Tanggal kedaluwarsa (opsional)" />
            <x-text-input id="document_expired_at" name="expired_at" type="date" class="mt-1 w-full ui-control" />
            <x-input-error :messages="$errors->get('expired_at')" class="mt-2" />
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="ui-button-primary">Unggah dokumen</button>
        </div>
    </form>
</section>
