<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight" data-aos="fade-right">
            Selamat Datang di Dashboard
        </h2>
    </x-slot>

    <div class="ui-page">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 py-8 space-y-6">

            {{-- ===== Hero sambutan ===== --}}
            <section class="hero" data-aos="fade-up" data-aos-delay="150"
                     x-data="{
                         hour: new Date().getHours(),
                         waving: true,
                         get greet() {
                             return this.hour < 11 ? 'Selamat pagi' : this.hour < 15 ? 'Selamat siang' : this.hour < 19 ? 'Selamat sore' : 'Selamat malam';
                         },
                         wave() { this.waving = false; this.$nextTick(() => this.waving = true); }
                     }"
                     @pointermove="
                         const r = $el.getBoundingClientRect();
                         $el.style.setProperty('--hx', ($event.clientX - r.left) + 'px');
                         $el.style.setProperty('--hy', ($event.clientY - r.top) + 'px');">
                <div class="hero-orb hero-orb-a" aria-hidden="true"></div>
                <div class="hero-orb hero-orb-b" aria-hidden="true"></div>

                <div class="relative mx-auto max-w-3xl space-y-4 py-6 text-center">
                    <button type="button" @click="wave()" class="wave-btn" aria-label="Lambaikan tangan">
                        <span class="text-6xl inline-block" :class="waving ? 'is-waving' : ''">👋</span>
                    </button>

                    <p class="text-sm font-medium text-emerald-200/80" x-text="greet + ','"></p>

                    <h3 class="text-2xl sm:text-3xl font-bold text-white">
                        Hai {{ Auth::user()->name }}! Kamu sudah berhasil login ke sistem 🎉
                    </h3>
                    <p class="text-emerald-100/75">
                        Silakan pilih menu di sidebar untuk mulai menggunakan aplikasi.
                    </p>
                </div>
            </section>

            {{-- ===== Akses cepat (mengikuti menu sidebar & hak akses yang sama) ===== --}}
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('admin.absensi.recap') }}" class="quick" data-aos="fade-up" data-aos-delay="200"
                   x-data @pointermove="
                       const r = $el.getBoundingClientRect();
                       $el.style.setProperty('--qx', ($event.clientX - r.left) + 'px');
                       $el.style.setProperty('--qy', ($event.clientY - r.top) + 'px');">
                    <span class="quick-icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="quick-body">
                        <span class="quick-title">Rekap Bulanan</span>
                        <span class="quick-desc">Lihat ringkasan absensi per bulan.</span>
                    </span>
                    <i class="fas fa-arrow-right quick-go"></i>
                </a>

                @if (Auth::user()->isAnyAdmin())
                    <a href="{{ route('admin.holidays.index') }}" class="quick" data-aos="fade-up" data-aos-delay="260"
                       x-data @pointermove="
                           const r = $el.getBoundingClientRect();
                           $el.style.setProperty('--qx', ($event.clientX - r.left) + 'px');
                           $el.style.setProperty('--qy', ($event.clientY - r.top) + 'px');">
                        <span class="quick-icon"><i class="fas fa-calendar-day"></i></span>
                        <span class="quick-body">
                            <span class="quick-title">Hari Libur</span>
                            <span class="quick-desc">Atur kalender libur perusahaan.</span>
                        </span>
                        <i class="fas fa-arrow-right quick-go"></i>
                    </a>
                @endif

                @if (Auth::user()->role === 'hrd')
                    <a href="{{ route('admin.absensi.approval.hrd') }}" class="quick" data-aos="fade-up" data-aos-delay="320"
                       x-data @pointermove="
                           const r = $el.getBoundingClientRect();
                           $el.style.setProperty('--qx', ($event.clientX - r.left) + 'px');
                           $el.style.setProperty('--qy', ($event.clientY - r.top) + 'px');">
                        <span class="quick-icon"><i class="fas fa-user-check"></i></span>
                        <span class="quick-body">
                            <span class="quick-title">Approval HRD</span>
                            <span class="quick-desc">Tinjau dan setujui pengajuan.</span>
                        </span>
                        <i class="fas fa-arrow-right quick-go"></i>
                    </a>

                    <a href="{{ route('admin.payroll.index') }}" class="quick" data-aos="fade-up" data-aos-delay="380"
                       x-data @pointermove="
                           const r = $el.getBoundingClientRect();
                           $el.style.setProperty('--qx', ($event.clientX - r.left) + 'px');
                           $el.style.setProperty('--qy', ($event.clientY - r.top) + 'px');">
                        <span class="quick-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="quick-body">
                            <span class="quick-title">Payroll Freelance</span>
                            <span class="quick-desc">Kelola penggajian freelance.</span>
                        </span>
                        <i class="fas fa-arrow-right quick-go"></i>
                    </a>
                @endif

                @if (Auth::user()->isSuperAdmin())
                    <a href="{{ route('admin.activity_logs.index') }}" class="quick quick-danger" data-aos="fade-up" data-aos-delay="440"
                       x-data @pointermove="
                           const r = $el.getBoundingClientRect();
                           $el.style.setProperty('--qx', ($event.clientX - r.left) + 'px');
                           $el.style.setProperty('--qy', ($event.clientY - r.top) + 'px');">
                        <span class="quick-icon"><i class="fas fa-history"></i></span>
                        <span class="quick-body">
                            <span class="quick-title">Log Aktivitas</span>
                            <span class="quick-desc">Pantau aktivitas pengguna.</span>
                        </span>
                        <i class="fas fa-arrow-right quick-go"></i>
                    </a>
                @endif
            </section>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
        <style nonce="{{ config('app.csp_nonce') }}">
            /* ---------- Hero ---------- */
            .hero {
                position: relative; overflow: hidden; border-radius: 1.5rem; padding: 2rem 1.5rem;
                background: linear-gradient(135deg, var(--sb-top, #0a3a31), var(--sb-bottom, #05201b));
                border: 1px solid rgba(255, 255, 255, .08);
                box-shadow: 0 30px 60px -30px rgba(2, 44, 34, .8);
            }
            .hero::before {
                content: ""; position: absolute; inset: 0; pointer-events: none;
                background: radial-gradient(320px circle at var(--hx, 50%) var(--hy, 0%), rgba(52, 211, 153, .22), transparent 70%);
            }
            .hero-orb { position: absolute; border-radius: 9999px; filter: blur(50px); pointer-events: none; }
            .hero-orb-a { width: 16rem; height: 16rem; top: -6rem; right: -4rem; background: rgba(34, 211, 238, .22); animation: orb 14s ease-in-out infinite alternate; }
            .hero-orb-b { width: 14rem; height: 14rem; bottom: -6rem; left: -3rem; background: rgba(52, 211, 153, .28); animation: orb 18s ease-in-out -5s infinite alternate-reverse; }
            @keyframes orb { to { transform: translate3d(30px, 20px, 0) scale(1.15); } }

            .wave-btn { display: inline-block; border-radius: 1rem; cursor: pointer; line-height: 1; transition: transform .2s ease; }
            .wave-btn:hover { transform: scale(1.1); }
            .wave-btn:active { transform: scale(.92); }
            .wave-btn:focus-visible { outline: 2px solid #34d399; outline-offset: 4px; }
            .is-waving { transform-origin: 70% 70%; animation: wave 1.6s ease-in-out 1; }
            @keyframes wave {
                0%, 60%, 100% { transform: rotate(0); }
                10% { transform: rotate(16deg); } 20% { transform: rotate(-10deg); }
                30% { transform: rotate(16deg); } 40% { transform: rotate(-6deg); }
                50% { transform: rotate(10deg); }
            }

            /* ---------- Kartu akses cepat ---------- */
            .quick {
                position: relative; display: flex; align-items: center; gap: .9rem; overflow: hidden;
                padding: 1rem 1.1rem; border-radius: 1rem; text-decoration: none;
                background: rgba(255, 255, 255, .75); border: 1px solid rgba(15, 23, 42, .08);
                -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
                transition: transform .25s cubic-bezier(.2, .8, .2, 1), border-color .2s ease, box-shadow .25s ease;
            }
            .dark .quick { background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .08); }
            .quick::before {
                content: ""; position: absolute; inset: 0; opacity: 0; pointer-events: none; transition: opacity .25s ease;
                background: radial-gradient(180px circle at var(--qx, 50%) var(--qy, 50%), rgba(16, 185, 129, .18), transparent 70%);
            }
            .quick:hover { transform: translateY(-4px); border-color: rgba(16, 185, 129, .5); box-shadow: 0 18px 34px -18px rgba(5, 150, 105, .6); }
            .quick:hover::before { opacity: 1; }
            .quick:active { transform: translateY(-1px) scale(.99); }
            .quick:focus-visible { outline: 2px solid #34d399; outline-offset: 2px; }

            .quick-icon {
                position: relative; display: grid; place-items: center; width: 2.75rem; height: 2.75rem; flex-shrink: 0;
                border-radius: .85rem; color: #fff; font-size: 1rem;
                background: linear-gradient(135deg, #10b981, #0d9488);
                box-shadow: 0 8px 18px -6px rgba(16, 185, 129, .7);
                transition: transform .3s cubic-bezier(.3, 1.6, .5, 1);
            }
            .quick:hover .quick-icon { transform: scale(1.1) rotate(-6deg); }
            .quick-body { position: relative; display: flex; flex-direction: column; min-width: 0; flex: 1; }
            .quick-title { font-weight: 600; font-size: .95rem; color: #0f172a; }
            .dark .quick-title { color: #fff; }
            .quick-desc { font-size: .8rem; color: #64748b; }
            .dark .quick-desc { color: #94a3b8; }
            .quick-go { position: relative; font-size: .8rem; color: #10b981; opacity: 0; transform: translateX(-8px); transition: opacity .2s ease, transform .25s ease; }
            .quick:hover .quick-go { opacity: 1; transform: translateX(0); }

            .quick-danger .quick-icon { background: linear-gradient(135deg, #f43f5e, #e11d48); box-shadow: 0 8px 18px -6px rgba(244, 63, 94, .7); }
            .quick-danger:hover { border-color: rgba(244, 63, 94, .5); box-shadow: 0 18px 34px -18px rgba(225, 29, 72, .55); }
            .quick-danger::before { background: radial-gradient(180px circle at var(--qx, 50%) var(--qy, 50%), rgba(244, 63, 94, .16), transparent 70%); }
            .quick-danger .quick-go { color: #f43f5e; }

            @media (prefers-reduced-motion: reduce) {
                .hero-orb, .is-waving { animation: none !important; }
                .quick, .quick-icon, .quick-go, .wave-btn { transition: none !important; }
            }
        </style>
    @endpush

    @push('scripts')
        <script nonce="{{ config('app.csp_nonce') }}" src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
        <script nonce="{{ config('app.csp_nonce') }}">
            AOS.init();
        </script>
    @endpush
</x-app-layout>