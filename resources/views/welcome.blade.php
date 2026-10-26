<x-guest-layout>
    <section class="ui-page flex min-h-screen max-w-none items-center justify-center px-6 py-20">
        <div class="grid w-full max-w-7xl items-center gap-12 lg:grid-cols-2">

            <!-- Left Content -->
            <div data-aos="fade-right" data-aos-duration="1500" class="space-y-6">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo-followme.png') }}" alt="Logo PT Followme" class="h-auto max-h-20 w-full max-w-48 object-contain object-left animate__animated animate__fadeInLeft">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-700 dark:text-gray-200">PT. Followme</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 tracking-wide">Sistem Absensi Digital Terintegrasi</p>
                    </div>
                </div>

                <h2 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white animate__animated animate__fadeInDown">
                    Presensi Online <br>
                    <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Cepat & Efisien</span>
                </h2>

                <p class="text-lg text-gray-600 dark:text-gray-300 animate__animated animate__fadeIn animate__delay-1s">
                    Kelola kehadiran karyawan dengan akurat, berbasis foto & lokasi realtime. Solusi modern untuk perusahaan profesional.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}"
                       class="ui-button-primary group animate__animated animate__fadeInUp animate__delay-2s">
                        <span>Masuk Sekarang</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>

                    
                </div>

                <!-- Features List -->
                <div class="pt-8 space-y-3 animate__animated animate__fadeIn animate__delay-4s">
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <i class="fas fa-check text-sm text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                        <span class="font-medium">Absensi berbasis GPS & Foto</span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <i class="fas fa-check text-sm text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                        <span class="font-medium">Laporan Real-time & Analitik</span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                            <i class="fas fa-check text-sm text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                        <span class="font-medium">Mudah digunakan & Terintegrasi</span>
                    </div>
                </div>
            </div>

            <!-- Right Illustration (Optional) -->
            <div data-aos="fade-left" data-aos-duration="1500" class="hidden justify-center lg:flex">
                <div class="relative">
                    <!-- Decorative Elements -->
                    <div class="absolute -top-4 -left-4 w-72 h-72 bg-emerald-300 dark:bg-emerald-900 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob"></div>
                    <div class="absolute -bottom-8 right-4 w-72 h-72 bg-teal-300 dark:bg-teal-900 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
                    <div class="absolute -bottom-4 left-20 w-72 h-72 bg-cyan-300 dark:bg-cyan-900 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

                    <!-- Icon Grid -->
                    <div class="relative grid grid-cols-2 gap-6 p-8">
                        <div class="ui-panel transform p-8 transition-all hover:scale-110">
                            <i class="fas fa-users text-5xl text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                        <div class="ui-panel transform p-8 transition-all hover:scale-110">
                            <i class="fas fa-clock text-5xl text-teal-600 dark:text-teal-400"></i>
                        </div>
                        <div class="ui-panel transform p-8 transition-all hover:scale-110">
                            <i class="fas fa-chart-line text-5xl text-cyan-600 dark:text-cyan-400"></i>
                        </div>
                        <div class="ui-panel transform p-8 transition-all hover:scale-110">
                            <i class="fas fa-mobile-alt text-5xl text-emerald-600 dark:text-emerald-400"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center text-sm py-6 text-gray-600 dark:text-gray-400 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm border-t border-gray-200 dark:border-gray-800">
        &copy; {{ date('Y') }} PT. Followme. All rights reserved.
    </footer>

    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"
              integrity="sha512-1cK78a1o+ht2JcaW6g8OXYwqpev9+6GqOkz9xmBN9iUUhIndKtxwILGWYOSibOKjLsEdjyjZvYDq/cZwNeak0w=="
              crossorigin="anonymous"
              referrerpolicy="no-referrer"
              nonce="{{ config('app.csp_nonce') }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
              integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw=="
              crossorigin="anonymous"
              referrerpolicy="no-referrer"
              nonce="{{ config('app.csp_nonce') }}"/>
        <style nonce="{{ config('app.csp_nonce') }}">
            @keyframes blob {
                0%, 100% {
                    transform: translate(0, 0) scale(1);
                }
                33% {
                    transform: translate(30px, -50px) scale(1.1);
                }
                66% {
                    transform: translate(-20px, 20px) scale(0.9);
                }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"
                integrity="sha512-A7AYk1fGKX6S2SsHywmPkrnzTZHrgiVT7GcQkLGDe2ev0aWb8zejytzS8wjo7PGEXKqJOrjQ4oORtnimIRZBtw=="
                crossorigin="anonymous"
                referrerpolicy="no-referrer"
                nonce="{{ config('app.csp_nonce') }}"></script>
        <script nonce="{{ config('app.csp_nonce') }}">
            AOS.init({
                once: true,
                duration: 1000
            });
        </script>
    @endpush
</x-guest-layout>
