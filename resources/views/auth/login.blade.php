<x-guest-layout>
    <main class="login-page">
        {{-- Decorative Glow --}}
        <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-emerald-500/10 rounded-full blur-[120px] -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[100px] translate-x-1/4 translate-y-1/4"></div>

        <div class="login-layout">
            <section class="login-card">
                <div class="mb-12">
                    <a href="/" class="inline-flex w-fit group">
                        <img src="{{ asset('images/followme-horizontal.png') }}" alt="Logo Followme Indonesia" class="w-52 max-w-full h-auto rounded-xl object-contain shadow-xl shadow-emerald-500/20 transition-transform group-hover:scale-[1.03]">
                    </a>
                </div>

                <div class="mb-10">
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Selamat datang kembali!</h1>
                    <p class="text-slate-500 dark:text-slate-400">Silakan masuk untuk mengelola aktivitas dan kehadiran Anda hari ini.</p>
                </div>

                @if(session('status'))
                    <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-600 text-sm font-bold">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Email Karyawan</label>
                        <div class="relative">
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                class="lucent-input pl-11"
                                placeholder="nama@perusahaan.com"
                            >
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                        @error('email')
                            <p class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center px-1">
                            <label for="password" class="text-xs font-bold text-slate-500 uppercase tracking-widest">Kata Sandi</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-500 uppercase tracking-tighter">Lupa sandi?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                class="lucent-input pl-11"
                                placeholder="••••••••"
                            >
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                        @error('password')
                            <p class="text-rose-500 text-xs font-bold mt-1 ml-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center px-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500/20">
                            <span class="text-xs font-bold text-slate-500 group-hover:text-slate-700 transition-colors uppercase tracking-widest">Ingat Saya</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-premium w-full py-4 text-lg">
                        <span>Masuk Sekarang</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>
            </section>

            <section class="login-visual">
                <div class="login-illustration-wrap relative z-10 w-full max-w-md text-center">
                    <div class="login-work-scene mx-auto">
                        <svg class="login-work-art" viewBox="0 0 420 340" role="img" aria-labelledby="login-work-title login-work-description">
                            <title id="login-work-title">Ilustrasi karyawan sedang bekerja</title>
                            <desc id="login-work-description">Animasi seseorang bekerja menggunakan laptop di ruang kerja yang nyaman.</desc>
                            <defs>
                                <linearGradient id="scene-background" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#ecfdf5" />
                                    <stop offset="1" stop-color="#dff7f1" />
                                </linearGradient>
                                <linearGradient id="screen-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#163d36" />
                                    <stop offset="1" stop-color="#0b2925" />
                                </linearGradient>
                            </defs>

                            <rect x="22" y="18" width="376" height="304" rx="34" fill="url(#scene-background)" />
                            <circle cx="338" cy="73" r="31" fill="#bef2d9" opacity=".8" />
                            <rect x="71" y="49" width="278" height="149" rx="17" fill="#fff" opacity=".78" />
                            <rect x="83" y="61" width="254" height="125" rx="10" fill="#d9f4ed" />
                            <path d="M210 61v125M83 123h254" stroke="#fff" stroke-width="7" />
                            <path d="M99 164c22-25 43-25 62 0M226 165c22-34 46-34 69 0" fill="none" stroke="#a1d9c7" stroke-width="7" stroke-linecap="round" />

                            <g class="login-plant">
                                <path d="M105 250v-41m0 22c-20-4-25-17-24-29 17 2 26 11 24 29Zm1-9c18-6 22-18 20-30-16 4-23 13-20 30Z" fill="#20a778" />
                                <path d="M91 249h29l-4 24H95z" fill="#f3a66b" />
                            </g>

                            <ellipse cx="211" cy="279" rx="126" ry="15" fill="#b7e9d8" />
                            <path d="M164 166c9-13 25-20 47-20s38 7 47 20l15 75H149z" fill="#10a77a" />
                            <path d="M178 169c8 13 20 19 33 19s25-6 33-19" fill="none" stroke="#7ee0b6" stroke-width="4" stroke-linecap="round" />
                            <rect x="198" y="143" width="25" height="28" rx="10" fill="#d88f68" />
                            <ellipse cx="210" cy="119" rx="34" ry="39" fill="#e9ad83" />
                            <path d="M177 118c-4-29 11-47 34-47 25 0 39 17 34 47-8-4-13-12-15-22-12 10-29 15-51 14z" fill="#263e3a" />
                            <path d="M192 125h2m32 0h2" stroke="#314d46" stroke-width="4" stroke-linecap="round" />
                            <path d="M202 140c5 4 11 4 16 0" fill="none" stroke="#b96756" stroke-width="3" stroke-linecap="round" />

                            <g class="login-arm login-arm-left">
                                <path d="M168 178c-12 9-22 23-26 39l26 10 20-35z" fill="#10a77a" />
                                <path d="m151 216 27 9-6 12c-3 6-10 8-16 5l-10-5c-5-3-7-10-4-15z" fill="#e9ad83" />
                            </g>
                            <g class="login-arm login-arm-right">
                                <path d="M253 178c12 9 22 23 26 39l-26 10-20-35z" fill="#10a77a" />
                                <path d="m270 216-27 9 6 12c3 6 10 8 16 5l10-5c5-3 7-10 4-15z" fill="#e9ad83" />
                            </g>

                            <path d="M70 250h280v13H70z" fill="#b77955" />
                            <path d="M85 263h10v25H85zm240 0h10v25h-10z" fill="#8d5f48" />
                            <g class="login-laptop">
                                <rect x="164" y="197" width="94" height="62" rx="7" fill="#254b45" />
                                <rect x="170" y="203" width="82" height="49" rx="4" fill="url(#screen-gradient)" />
                                <circle cx="211" cy="227" r="12" fill="#20c997" opacity=".22" />
                                <path d="m206 227 4 4 8-9" fill="none" stroke="#72e4b7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M153 259h116l-8 7h-99z" fill="#dcebe6" />
                            </g>
                            <path d="M294 228h25l-3 23h-19z" fill="#f5c27b" />
                            <path d="M300 226c1-8 12-8 13 0" fill="none" stroke="#f5c27b" stroke-width="4" stroke-linecap="round" />
                            <path d="M301 218c-5-7 5-9 1-15m10 15c-5-7 5-9 1-15" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".8" />

                            <g class="login-work-badge">
                                <rect x="278" y="33" width="91" height="34" rx="17" fill="#fff" />
                                <circle cx="296" cy="50" r="5" fill="#10b981" />
                                <path d="m294 50 2 2 4-5" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M309 47h45m-45 7h29" stroke="#8ca9a0" stroke-width="3" stroke-linecap="round" />
                            </g>
                        </svg>
                    </div>
                    <div class="mt-7">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-3">Kerja Nyaman, Tim Produktif</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto">Kelola aktivitas dan kehadiran tim dengan lebih mudah bersama Followme.</p>
                    </div>
                </div>
                
                {{-- Decorative circles --}}
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] border border-emerald-500/5 rounded-full pointer-events-none"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] h-[90%] border border-emerald-500/10 rounded-full pointer-events-none"></div>
            </section>
        </div>
        
        <footer class="absolute bottom-8 left-1/2 -translate-x-1/2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] z-20">
            © {{ date('Y') }} PT. Followme · Digital Attendance System
        </footer>
    </main>
</x-guest-layout>
