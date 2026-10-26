<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight" data-aos="fade-right">
            Selamat Datang di Dashboard
        </h2>
    </x-slot>

    <div class="ui-page min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="ui-panel text-center" data-aos="fade-up" data-aos-delay="150">
            <div class="mx-auto max-w-3xl space-y-4 py-4">
                    <div class="text-5xl">👋</div>
                    <h3 class="text-xl font-semibold">
                        Hai {{ Auth::user()->name }}! Kamu sudah berhasil login ke sistem 🎉
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Silakan pilih menu di sidebar untuk mulai menggunakan aplikasi.
                    </p>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    @endpush

    @push('scripts')
        <script nonce="{{ config('app.csp_nonce') }}" src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
        <script nonce="{{ config('app.csp_nonce') }}">
            AOS.init();
        </script>
    @endpush
</x-app-layout>
