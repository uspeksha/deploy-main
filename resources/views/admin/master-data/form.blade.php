<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.master-data.index', ['resource' => $resource]) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-300">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke daftar {{ strtolower($definition['label']) }}
            </a>
            <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ $item ? 'Edit' : 'Tambah' }} {{ $definition['label'] }}
            </h2>
        </div>
    </x-slot>

    <div class="ui-page">
        @if (isset($definition['warning']))
            <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200" role="note">
                <i class="fas fa-triangle-exclamation mr-2"></i>{{ $definition['warning'] }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                Periksa kembali data yang ditandai sebelum menyimpan.
            </div>
        @endif

        <section class="ui-panel mx-auto w-full max-w-4xl">
            <form method="POST"
                  action="{{ $item
                    ? route('admin.master-data.update', ['resource' => $resource, 'record' => $item->getKey()])
                    : route('admin.master-data.store', ['resource' => $resource]) }}"
                  class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                @csrf
                @if ($item)
                    @method('PUT')
                @endif

                @foreach ($definition['fields'] as $name => $field)
                    @php
                        $currentValue = old($name, $item?->getAttribute($name));
                        if ($field['type'] === 'time' && is_string($currentValue)) {
                            $currentValue = substr($currentValue, 0, 5);
                        }
                    @endphp
                    <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
                        <label for="field_{{ $name }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $field['label'] }}
                        </label>

                        @if ($field['type'] === 'textarea')
                            <textarea id="field_{{ $name }}" name="{{ $name }}" rows="3" class="ui-control w-full @error($name) border-red-500 @enderror">{{ $currentValue }}</textarea>
                        @elseif ($field['type'] === 'select')
                            <select id="field_{{ $name }}" name="{{ $name }}" class="ui-control w-full @error($name) border-red-500 @enderror">
                                @if (in_array('nullable', $field['rules'], true))
                                    <option value="">Tidak ada</option>
                                @else
                                    <option value="">Pilih {{ strtolower($field['label']) }}</option>
                                @endif
                                @foreach (($field['static_options'] ?? (isset($field['source']) ? ($options[$field['source']] ?? []) : [])) as $optionKey => $option)
                                    @php
                                        $optionValue = is_object($option) ? $option->getKey() : $optionKey;
                                        $optionLabel = is_object($option) ? $option->name : $option;
                                    @endphp
                                    <option value="{{ $optionValue }}" @selected((string) $currentValue === (string) $optionValue)>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif ($field['type'] === 'checkbox')
                            <label class="flex min-h-11 items-center gap-3">
                                <input id="field_{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) old($name, $item?->getAttribute($name) ?? false)) class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $field['label'] }}</span>
                            </label>
                        @else
                            <input id="field_{{ $name }}" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $currentValue }}" @isset($field['step']) step="{{ $field['step'] }}" @endisset class="ui-control w-full @error($name) border-red-500 @enderror">
                        @endif

                        @error($name)
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700 sm:col-span-2">
                    <a href="{{ route('admin.master-data.index', ['resource' => $resource]) }}" class="ui-button-secondary">Batal</a>
                    <button type="submit" class="ui-button-primary">
                        {{ $item ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
