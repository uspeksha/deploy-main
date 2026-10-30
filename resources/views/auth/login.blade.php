<x-guest-layout>
    <main class="login-page">
        {{-- Decorative Glow --}}
        <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-emerald-500/10 rounded-full blur-[120px] -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[100px] translate-x-1/4 translate-y-1/4"></div>

        <div class="login-layout">
            <section class="login-card">
                <div class="mb-12">
                    <a href="/" class="flex items-center gap-3 group w-fit">
                        <div class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-emerald-500/20 group-hover:rotate-6 transition-transform">
                            <i class="fas fa-bolt text-xl"></i>
                        </div>
                        <span class="text-2xl font-black tracking-tighter">Presensi<span class="text-emerald-500">Pro</span></span>
                    </a>
                </div>

                <div class="mb-10">
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Selamat Datang!</h1>
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
                <div class="relative z-10 text-center space-y-8 animate-float">
                    <div class="w-32 h-32 bg-white dark:bg-slate-800 rounded-[2.5rem] flex items-center justify-center text-6xl shadow-2xl mx-auto">
                        🏢
                    </div>
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-4">Efisiensi Dalam Genggaman</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto">Sistem absensi modern yang dirancang untuk produktivitas tim Anda.</p>
                    </div>
                    
                    <div class="flex gap-2 justify-center">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-300"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-100"></span>
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
