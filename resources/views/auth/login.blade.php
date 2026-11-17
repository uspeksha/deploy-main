<x-guest-layout>
    <main class="login-page">
        {{-- Decorative Glow --}}
        <div class="login-glow login-glow-blue absolute top-0 left-0 w-[500px] h-[500px] rounded-full blur-[120px] -translate-x-1/2 -translate-y-1/2"></div>
        <div class="login-glow login-glow-purple absolute bottom-0 right-0 w-[400px] h-[400px] rounded-full blur-[100px] translate-x-1/4 translate-y-1/4"></div>

        <div class="login-layout">
            <section class="login-card">
                <div class="mb-12">
                    <a href="/" class="inline-flex w-fit group">
                        <img src="{{ asset('images/followme-horizontal.png') }}" alt="Logo Followme Indonesia" class="login-brand w-52 max-w-full h-auto rounded-xl object-contain grayscale shadow-xl transition-transform group-hover:scale-[1.03]">
                    </a>
                </div>

                <div class="mb-10">
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Selamat datang kembali!</h1>
                    <p class="text-slate-500 dark:text-slate-400">Silakan masuk untuk mengelola aktivitas dan kehadiran Anda hari ini.</p>
                </div>

                @if(session('status'))
                    <div class="login-status mb-6 p-4 rounded-2xl text-sm font-bold">
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
                            <p class="login-error text-xs font-bold mt-1 ml-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center px-1">
                            <label for="password" class="text-xs font-bold text-slate-500 uppercase tracking-widest">Kata Sandi</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="login-link text-[10px] font-bold uppercase tracking-tighter">Lupa sandi?</a>
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
                            <p class="login-error text-xs font-bold mt-1 ml-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center px-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input id="remember_me" type="checkbox" name="remember" class="login-checkbox w-4 h-4 rounded border-slate-300">
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
                                    <stop offset="0" stop-color="#fff8f1" />
                                    <stop offset="1" stop-color="#fcebdc" />
                                </linearGradient>
                                <linearGradient id="screen-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#854d28" />
                                    <stop offset="1" stop-color="#593622" />
                                </linearGradient>
                            </defs>

                            <rect x="22" y="18" width="376" height="304" rx="34" fill="url(#scene-background)" />
                            <circle cx="338" cy="73" r="31" fill="#f5d2b5" opacity=".8" />
                            <rect x="71" y="49" width="278" height="149" rx="17" fill="#fff" opacity=".78" />
                            <rect x="83" y="61" width="254" height="125" rx="10" fill="#fff8f1" />
                            <path d="M210 61v125M83 123h254" stroke="#fff" stroke-width="7" />
                            <path d="M99 164c22-25 43-25 62 0M226 165c22-34 46-34 69 0" fill="none" stroke="#eab88f" stroke-width="7" stroke-linecap="round" />

                            <g class="login-plant">
                                <path d="M105 250v-41m0 22c-20-4-25-17-24-29 17 2 26 11 24 29Zm1-9c18-6 22-18 20-30-16 4-23 13-20 30Z" fill="#c47a3c" />
                                <path d="M91 249h29l-4 24H95z" fill="#e5e5e5" />
                            </g>

                            <ellipse cx="211" cy="279" rx="126" ry="15" fill="#e5e5e5" />
                            <path d="M164 166c9-13 25-20 47-20s38 7 47 20l15 75H149z" fill="#a8642f" />
                            <path d="M178 169c8 13 20 19 33 19s25-6 33-19" fill="none" stroke="#f5d2b5" stroke-width="4" stroke-linecap="round" />
                            <rect x="198" y="143" width="25" height="28" rx="10" fill="#d88f68" />
                            <ellipse cx="210" cy="119" rx="34" ry="39" fill="#e9ad83" />
                            <path d="M177 118c-4-29 11-47 34-47 25 0 39 17 34 47-8-4-13-12-15-22-12 10-29 15-51 14z" fill="#333333" />
                            <path d="M192 125h2m32 0h2" stroke="#525252" stroke-width="4" stroke-linecap="round" />
                            <path d="M202 140c5 4 11 4 16 0" fill="none" stroke="#b96756" stroke-width="3" stroke-linecap="round" />

                            <g class="login-arm login-arm-left">
                                <path d="M168 178c-12 9-22 23-26 39l26 10 20-35z" fill="#a8642f" />
                                <path d="m151 216 27 9-6 12c-3 6-10 8-16 5l-10-5c-5-3-7-10-4-15z" fill="#e9ad83" />
                            </g>
                            <g class="login-arm login-arm-right">
                                <path d="M253 178c12 9 22 23 26 39l-26 10-20-35z" fill="#a8642f" />
                                <path d="m270 216-27 9 6 12c3 6 10 8 16 5l10-5c5-3 7-10 4-15z" fill="#e9ad83" />
                            </g>

                            <path d="M70 250h280v13H70z" fill="#b77955" />
                            <path d="M85 263h10v25H85zm240 0h10v25h-10z" fill="#8d5f48" />
                            <g class="login-laptop">
                                <rect x="164" y="197" width="94" height="62" rx="7" fill="#593622" />
                                <rect x="170" y="203" width="82" height="49" rx="4" fill="url(#screen-gradient)" />
                                <circle cx="211" cy="227" r="12" fill="#da9967" opacity=".35" />
                                <path d="m206 227 4 4 8-9" fill="none" stroke="#f5d2b5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M153 259h116l-8 7h-99z" fill="#e5e5e5" />
                            </g>
                            <path d="M294 228h25l-3 23h-19z" fill="#f5c27b" />
                            <path d="M300 226c1-8 12-8 13 0" fill="none" stroke="#f5c27b" stroke-width="4" stroke-linecap="round" />
                            <path d="M301 218c-5-7 5-9 1-15m10 15c-5-7 5-9 1-15" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".8" />

                            <g class="login-work-badge">
                                <rect x="278" y="33" width="91" height="34" rx="17" fill="#fff" />
                                <circle cx="296" cy="50" r="5" fill="#c47a3c" />
                                <path d="m294 50 2 2 4-5" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M309 47h45m-45 7h29" stroke="#da9967" stroke-width="3" stroke-linecap="round" />
                            </g>
                        </svg>
                    </div>
                    <div class="mt-7">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-3">Kerja Nyaman, Tim Produktif</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto">Kelola aktivitas dan kehadiran tim dengan lebih mudah bersama Followme.</p>
                    </div>
                </div>
                
                {{-- Decorative circles --}}
                <div class="login-orbit login-orbit-outer absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[120%] h-[120%] border rounded-full pointer-events-none"></div>
                <div class="login-orbit login-orbit-inner absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] h-[90%] border rounded-full pointer-events-none"></div>
            </section>
        </div>
        
        <footer class="absolute bottom-8 left-1/2 -translate-x-1/2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] z-20">
            © {{ date('Y') }} PT. Followme · Digital Attendance System
        </footer>
    </main>
    @push('styles')
        <style nonce="{{ config('app.csp_nonce') }}">
            .login-page {
                background: radial-gradient(ellipse at 18% 12%, rgba(218, 153, 103, .12), transparent 42%),
                    radial-gradient(ellipse at 85% 88%, rgba(196, 122, 60, .08), transparent 44%),
                    #f5f5f5;
                --login-accent: #a8642f;
                --login-accent-strong: #c47a3c;
            }
            .dark .login-page {
                background: radial-gradient(ellipse at 18% 12%, rgba(196, 122, 60, .14), transparent 42%),
                    radial-gradient(ellipse at 85% 88%, rgba(168, 100, 47, .12), transparent 44%),
                    #0a0a0a;
            }
            .login-page .login-layout {
                border-color: rgba(196, 122, 60, .16);
                box-shadow: 0 30px 90px -38px rgba(64, 64, 64, .18);
            }
            .dark .login-page .login-layout {
                border-color: rgba(218, 153, 103, .2);
                box-shadow: 0 30px 90px -38px rgba(0, 0, 0, .7);
            }
            .login-page .login-visual {
                background: linear-gradient(145deg, #fff8f1 0%, #fcebdc 52%, #f5f5f5 100%);
            }
            .dark .login-page .login-visual {
                background: linear-gradient(145deg, #321d12 0%, #593622 52%, #6b4026 100%);
            }
            .login-page .login-visual::before {
                background-image: radial-gradient(circle at 50% 50%, #c47a3c 1px, transparent 1px);
                opacity: .14;
            }
            .login-page .login-work-scene {
                filter: drop-shadow(0 22px 28px rgba(64, 64, 64, .12));
            }
            .login-page .login-brand { box-shadow: 0 12px 25px -5px rgba(64, 64, 64, .12); }
            .login-page .lucent-input:focus {
                border-color: #c47a3c;
                box-shadow: 0 0 0 3px rgba(196, 122, 60, .14);
            }
            .login-page .btn-premium {
                background: linear-gradient(120deg, #a8642f, #b87336 55%, #c47a3c);
                box-shadow: 0 10px 24px -8px rgba(168, 100, 47, .34);
            }
            .login-page .btn-premium:hover {
                box-shadow: 0 14px 28px -8px rgba(168, 100, 47, .42);
            }
            .login-page .login-glow-blue { background: rgba(218, 153, 103, .12); }
            .login-page .login-glow-purple { background: rgba(196, 122, 60, .1); }
            .login-page .login-status {
                border: 1px solid rgba(196, 122, 60, .2);
                background: rgba(196, 122, 60, .08);
                color: #854d28;
            }
            .dark .login-page .login-status { color: #f5d2b5; }
            .login-page .login-link { color: #a8642f; }
            .login-page .login-link:hover { color: #854d28; }
            .dark .login-page .login-link { color: #eab88f; }
            .login-page .login-error { color: #b91c1c; }
            .dark .login-page .login-error { color: #fca5a5; }
            .login-page .login-checkbox { accent-color: #a8642f; }
            .login-page .login-orbit-outer { border-color: rgba(196, 122, 60, .06); }
            .login-page .login-orbit-inner { border-color: rgba(196, 122, 60, .1); }
        </style>
    @endpush
</x-guest-layout>
