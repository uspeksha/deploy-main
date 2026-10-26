{{-- resources/views/admin/holidays/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('admin.holidays.index') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-300 dark:hover:text-emerald-200">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                    </a>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Tambah Hari Libur Manual
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Tambahkan Cuti Bersama atau kebijakan libur khusus perusahaan.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="ui-page">
        <div class="mx-auto w-full max-w-3xl">
            <div class="ui-panel">
                
                <form action="{{ route('admin.holidays.store') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="holiday_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Libur <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-day text-gray-400"></i>
                            </div>
                            <input type="date" name="holiday_date" id="holiday_date" value="{{ old('holiday_date') }}" required
                                class="ui-control pl-10 @error('holiday_date') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror">
                        </div>
                        @error('holiday_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan / Nama Libur <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-align-left text-gray-400"></i>
                            </div>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Contoh: Cuti Bersama Lebaran / Pengganti Hari Selasa"
                                class="ui-control pl-10 @error('name') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror">
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Libur yang ditambahkan manual akan berstatus "Kebijakan Perusahaan".</p>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <a href="{{ route('admin.holidays.index') }}" class="ui-button-secondary">
                            Batal
                        </a>
                        <button type="submit" class="ui-button-primary">
                            Simpan Hari Libur
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
