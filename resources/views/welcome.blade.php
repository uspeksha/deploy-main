<x-guest-layout>
    <main class="welcome-page"
          x-data="{
              px: 0, py: 0,
              now: new Date(),
              init() { setInterval(() => this.now = new Date(), 1000); },
              track(e) {
                  if (e.pointerType !== 'mouse') return;
                  const r = e.currentTarget.getBoundingClientRect();
                  this.px = ((e.clientX - r.left) / r.width - .5) * 2;
                  this.py = ((e.clientY - r.top) / r.height - .5) * 2;
              },
              reset() { this.px = 0; this.py = 0; }
          }">
        <div class="welcome-shell">
            <header class="welcome-header">
                <a class="welcome-brand" href="/" aria-label="Follow Me - Beranda">
                    <span class="welcome-brand-mark" aria-hidden="true">F</span>
                    <span class="welcome-brand-name">follow<span>me</span></span>
                </a>
                <div class="welcome-header-right">
                    <span class="welcome-header-note">Portal absensi karyawan</span>
                    <button type="button" class="welcome-theme" @click="darkMode = !darkMode"
                            :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'">
                        <i class="fas fa-moon" x-show="!darkMode" x-transition.opacity.duration.200ms></i>
                        <i class="fas fa-sun" x-show="darkMode" x-transition.opacity.duration.200ms style="display:none"></i>
                    </button>
                </div>
            </header>

            <section class="welcome-hero" aria-labelledby="welcome-title">
                <div class="welcome-art"
                     @pointermove="track($event)" @pointerleave="reset()"
                     :style="{ '--px': px, '--py': py, '--sx': ((px + 1) * 50) + '%', '--sy': ((py + 1) * 50) + '%' }">
                    <span class="welcome-art-spot" aria-hidden="true"></span>
                    <span class="welcome-art-glow" aria-hidden="true"></span>

                    <div class="welcome-chip" aria-hidden="true">
                        <span class="welcome-pulse"></span>
                        <span>
                            <strong x-text="now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })"></strong>
                            <small x-text="now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' })"></small>
                        </span>
                    </div>

                    <img
                        class="welcome-team"
                        src="{{ asset('images/welcome-team-reference.png') }}"
                        alt="Rekan kerja berkolaborasi di depan komputer"
                    >
                    <div class="welcome-art-caption">
                        <span class="welcome-caption-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                        </span>
                        <span><strong>Kerja bareng, lebih terarah</strong><small>Semua kehadiran dalam satu tempat</small></span>
                    </div>
                </div>

                <div class="welcome-copy">
                    <p class="welcome-eyebrow"><span></span> Sistem absensi PT Follow Me</p>
                    <h1 id="welcome-title">Kerja lebih <span>teratur.</span><br>Mulai dari hadir.</h1>
                    <p class="welcome-description">
                        Selamat datang di portal absensi PT Follow Me. Catat kehadiran dan mulai aktivitas kerja dengan lebih mudah.
                    </p>
                    <a class="welcome-login" href="{{ route('login') }}"
                       x-data="{ mx: 0, my: 0 }"
                       @pointermove="
                           if ($event.pointerType === 'mouse') {
                               const r = $el.getBoundingClientRect();
                               mx = ($event.clientX - (r.left + r.width / 2)) * .16;
                               my = ($event.clientY - (r.top + r.height / 2)) * .28;
                           }"
                       @pointerleave="mx = 0; my = 0"
                       :style="`translate: ${mx}px ${my}px`">
                        <span class="welcome-login-label">Masuk Akun</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6" />
                        </svg>
                    </a>
                    <p class="welcome-assurance">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M10 2.5 4 5v4.2c0 3.8 2.5 6.8 6 8.3 3.5-1.5 6-4.5 6-8.3V5l-6-2.5Z" />
                            <path d="m7.5 9.8 1.7 1.7 3.5-3.7" />
                        </svg>
                        Akses khusus karyawan PT Follow Me
                    </p>
                </div>
            </section>

            <footer class="welcome-footer">
                <span>© {{ date('Y') }} PT Follow Me</span>
                <span>Presensi jadi lebih sederhana.</span>
            </footer>
        </div>
    </main>

    @push('styles')
        <style nonce="{{ config('app.csp_nonce') }}">
            .welcome-page {
                --w-ink: #0f2a24;
                --w-muted: #5f7a72;
                --w-faint: #8aa39b;
                --w-line: rgba(15, 42, 36, .09);
                --w-green: #059669;
                --w-green-2: #0d9488;
                --w-chip-bg: #e7f8f0;
                --w-chip-line: #c9eedd;
                --w-chip-ink: #047857;
                min-height: 100vh;
                min-height: 100svh;
                overflow: hidden;
                color: var(--w-ink);
            }
            .dark .welcome-page {
                --w-ink: #ecfdf5;
                --w-muted: #9ec2b6;
                --w-faint: #6f9487;
                --w-line: rgba(255, 255, 255, .09);
                --w-chip-bg: rgba(52, 211, 153, .12);
                --w-chip-line: rgba(52, 211, 153, .25);
                --w-chip-ink: #6ee7b7;
            }

            .welcome-shell {
                display: flex;
                width: min(100%, 1320px);
                min-height: 100vh;
                min-height: 100svh;
                flex-direction: column;
                margin: 0 auto;
                padding: 0 clamp(1.5rem, 5.5vw, 5rem);
            }

            /* ---------- Header ---------- */
            .welcome-header {
                display: flex;
                min-height: 5.5rem;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid var(--w-line);
            }
            .welcome-header-right { display: flex; align-items: center; gap: .9rem; }

            .welcome-brand {
                display: inline-flex; align-items: center; gap: .65rem;
                color: var(--w-ink); text-decoration: none;
            }
            .welcome-brand-mark {
                display: grid; width: 2.2rem; height: 2.2rem; place-items: center;
                border-radius: .72rem; color: #fff; font-size: 1.15rem; font-weight: 800;
                background: linear-gradient(135deg, #10b981, #0d9488);
                box-shadow: 0 6px 16px rgba(16, 185, 129, .35);
                transition: transform .4s cubic-bezier(.3, 1.6, .5, 1);
            }
            .welcome-brand:hover .welcome-brand-mark { transform: rotate(-8deg) scale(1.08); }
            .welcome-brand-name { font-size: 1.1rem; font-weight: 800; letter-spacing: -.055em; }
            .welcome-brand-name span { color: var(--w-green); }
            .dark .welcome-brand-name span { color: #34d399; }

            .welcome-header-note { color: var(--w-faint); font-size: .75rem; font-weight: 600; }

            .welcome-theme {
                display: grid; width: 2.4rem; height: 2.4rem; place-items: center;
                border: 1px solid var(--w-line); border-radius: .75rem;
                background: rgba(255, 255, 255, .5); color: var(--w-muted); cursor: pointer;
                transition: background-color .2s ease, color .2s ease, transform .2s ease;
            }
            .dark .welcome-theme { background: rgba(255, 255, 255, .05); }
            .welcome-theme:hover { background: rgba(16, 185, 129, .14); color: var(--w-green); }
            .dark .welcome-theme:hover { color: #6ee7b7; }
            .welcome-theme:active { transform: scale(.92); }
            .welcome-theme:focus-visible { outline: 2px solid #34d399; outline-offset: 2px; }
            .welcome-theme .fa-sun { color: #fbbf24; }

            /* ---------- Hero ---------- */
            .welcome-hero {
                display: grid; flex: 1; grid-template-columns: 1.03fr .97fr; align-items: center;
                gap: clamp(2.5rem, 7vw, 6rem); padding: 2.75rem 0;
            }

            .welcome-art {
                position: relative; display: grid; min-height: 32rem; place-items: center; overflow: hidden;
                border: 1px solid rgba(255, 255, 255, .85); border-radius: 2rem;
                background: linear-gradient(145deg, #eafbf3, #d9f6ea 58%, #dcf3f6);
                box-shadow: 0 28px 70px -28px rgba(5, 120, 85, .45);
                isolation: isolate;
                animation: welcome-enter-left 700ms cubic-bezier(.2, .7, .25, 1) both;
            }
            .dark .welcome-art { border-color: rgba(255, 255, 255, .12); box-shadow: 0 28px 70px -28px rgba(0, 0, 0, .7); }

            .welcome-art-spot {
                position: absolute; inset: 0; z-index: -1; pointer-events: none;
                background: radial-gradient(360px circle at var(--sx, 50%) var(--sy, 40%), rgba(255, 255, 255, .7), transparent 70%);
            }
            .welcome-art-glow {
                position: absolute; z-index: -1; top: 8%; right: -12%; width: 15rem; aspect-ratio: 1;
                border-radius: 50%; background: rgba(52, 211, 153, .28); filter: blur(24px);
                translate: calc(var(--px, 0) * -18px) calc(var(--py, 0) * -14px);
                transition: translate .35s cubic-bezier(.2, .8, .2, 1);
            }

            .welcome-team {
                display: block; width: min(84%, 350px); height: auto; max-height: 90%;
                border-radius: 1rem; mix-blend-mode: multiply; object-fit: contain;
                translate: calc(var(--px, 0) * 14px) calc(var(--py, 0) * 10px);
                transition: translate .35s cubic-bezier(.2, .8, .2, 1);
                animation: welcome-image-float 7s ease-in-out 700ms infinite;
            }

            .welcome-chip {
                position: absolute; top: 1.15rem; left: 1.15rem; display: flex; align-items: center; gap: .6rem;
                padding: .55rem .85rem .55rem .7rem; border: 1px solid rgba(255, 255, 255, .85); border-radius: 9999px;
                background: rgba(255, 255, 255, .82); box-shadow: 0 8px 25px rgba(5, 80, 60, .1);
                -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
                translate: calc(var(--px, 0) * 22px) calc(var(--py, 0) * 16px);
                transition: translate .35s cubic-bezier(.2, .8, .2, 1);
            }
            .welcome-chip strong { display: block; color: #0f2a24; font-size: .82rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.2; }
            .welcome-chip small { display: block; color: #5f7a72; font-size: .65rem; }
            .welcome-pulse { position: relative; width: 8px; height: 8px; border-radius: 9999px; background: #10b981; }
            .welcome-pulse::after {
                content: ""; position: absolute; inset: 0; border-radius: inherit; background: #10b981;
                animation: welcome-ping 1.8s cubic-bezier(0, 0, .2, 1) infinite;
            }

            .welcome-art-caption {
                position: absolute; right: 1.15rem; bottom: 1.15rem; left: 1.15rem; display: flex; align-items: center; gap: .7rem;
                padding: .8rem .9rem; border: 1px solid rgba(255, 255, 255, .8); border-radius: 1rem;
                background: rgba(255, 255, 255, .82); box-shadow: 0 8px 25px rgba(5, 80, 60, .08);
                -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
            }
            .welcome-caption-icon {
                display: grid; width: 2.15rem; height: 2.15rem; flex: none; place-items: center;
                border-radius: .7rem; background: linear-gradient(135deg, #10b981, #0d9488); color: #fff;
                box-shadow: 0 6px 14px -4px rgba(16, 185, 129, .7);
            }
            .welcome-caption-icon svg { width: 1.1rem; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2; }
            .welcome-art-caption strong, .welcome-art-caption small { display: block; }
            .welcome-art-caption strong { color: #0f2a24; font-size: .78rem; font-weight: 750; }
            .welcome-art-caption small { margin-top: .15rem; color: #5f7a72; font-size: .66rem; }

            /* ---------- Teks ---------- */
            .welcome-copy {
                max-width: 35rem; padding: 1rem 0;
                animation: welcome-enter-right 650ms cubic-bezier(.2, .7, .25, 1) 100ms both;
            }
            .welcome-eyebrow {
                display: inline-flex; align-items: center; gap: .5rem; margin: 0 0 1.25rem; padding: .48rem .8rem;
                border: 1px solid var(--w-chip-line); border-radius: 999px; background: var(--w-chip-bg);
                color: var(--w-chip-ink); font-size: .75rem; font-weight: 650;
            }
            .welcome-eyebrow span {
                width: .42rem; height: .42rem; border-radius: 50%; background: #10b981;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, .18);
            }
            .welcome-copy h1 {
                margin: 0; color: var(--w-ink); font-size: clamp(3rem, 5.4vw, 5.1rem);
                font-weight: 760; letter-spacing: -.075em; line-height: 1.04;
            }
            .welcome-copy h1 span {
                background: linear-gradient(100deg, #059669, #0d9488 45%, #06b6d4 90%);
                -webkit-background-clip: text; background-clip: text; color: transparent;
            }
            .dark .welcome-copy h1 span { background-image: linear-gradient(100deg, #34d399, #2dd4bf 45%, #22d3ee 90%); }
            .welcome-description { max-width: 29rem; margin: 1.25rem 0 1.75rem; color: var(--w-muted); font-size: .98rem; line-height: 1.75; }

            /* ---------- Tombol masuk ---------- */
            .welcome-login {
                position: relative; display: inline-flex; min-height: 3.25rem; align-items: center; justify-content: center;
                gap: .85rem; padding: 0 1.5rem; overflow: hidden; border-radius: .9rem; color: #fff;
                font-size: .9rem; font-weight: 750; text-decoration: none;
                background: linear-gradient(135deg, #10b981, #0d9488);
                box-shadow: 0 12px 26px -8px rgba(16, 185, 129, .65), inset 0 1px 0 rgba(255, 255, 255, .25);
                transition: translate .25s cubic-bezier(.2, .8, .2, 1), transform .18s ease, box-shadow .18s ease;
            }
            .welcome-login::before {
                content: ""; position: absolute; top: 0; bottom: 0; left: -60%; width: 40%;
                background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .45), transparent);
                transform: skewX(-20deg); transition: left .6s ease;
            }
            .welcome-login:hover { transform: translateY(-2px); box-shadow: 0 16px 32px -8px rgba(16, 185, 129, .75), inset 0 1px 0 rgba(255, 255, 255, .25); }
            .welcome-login:hover::before { left: 130%; }
            .welcome-login:active { transform: translateY(0) scale(.98); }
            .welcome-login:focus-visible { outline: 3px solid #6ee7b7; outline-offset: 3px; }
            .welcome-login-label, .welcome-login svg { position: relative; }
            .welcome-login svg {
                width: 1.05rem; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round;
                stroke-width: 1.8; transition: transform .18s ease;
            }
            .welcome-login:hover svg { transform: translateX(4px); }

            .welcome-assurance { display: flex; align-items: center; gap: .45rem; margin: 1rem 0 0; color: var(--w-faint); font-size: .75rem; }
            .welcome-assurance svg { width: 1rem; flex: none; stroke: #10b981; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.6; }

            .welcome-footer {
                display: flex; min-height: 3.5rem; align-items: center; justify-content: space-between;
                border-top: 1px solid var(--w-line); color: var(--w-faint); font-size: .72rem;
            }

            @keyframes welcome-enter-left { from { opacity: 0; transform: translateX(-14px); } to { opacity: 1; transform: translateX(0); } }
            @keyframes welcome-enter-right { from { opacity: 0; transform: translateX(14px); } to { opacity: 1; transform: translateX(0); } }
            @keyframes welcome-image-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
            @keyframes welcome-ping { 75%, 100% { transform: scale(2.6); opacity: 0; } }

            @media (max-width: 760px) {
                .welcome-shell { padding: 0 1.25rem; }
                .welcome-header { min-height: 4.5rem; }
                .welcome-hero { grid-template-columns: 1fr; gap: 1.75rem; padding: 1.5rem 0 2rem; }
                .welcome-art { min-height: clamp(16rem, 58vw, 23rem); border-radius: 1.5rem; }
                .welcome-team { width: auto; height: min(48vw, 19rem); max-width: 76%; }
                .welcome-art-caption { right: .75rem; bottom: .75rem; left: .75rem; }
                .welcome-chip { top: .75rem; left: .75rem; }
                .welcome-copy { justify-self: center; padding: 0; }
                .welcome-copy h1 { font-size: clamp(2.8rem, 11vw, 4.25rem); }
                .welcome-description { margin: .9rem 0 1.25rem; font-size: .91rem; }
            }

            @media (max-width: 420px) {
                .welcome-header-note { display: none; }
                .welcome-art { min-height: 15rem; }
                .welcome-team { height: 12.5rem; }
                .welcome-art-caption { gap: .5rem; padding: .65rem .7rem; }
                .welcome-art-caption strong { font-size: .7rem; }
                .welcome-art-caption small { font-size: .6rem; }
                .welcome-footer { min-height: 3rem; font-size: .66rem; }
            }

            @media (prefers-reduced-motion: reduce) {
                .welcome-art, .welcome-team, .welcome-copy, .welcome-pulse::after { animation: none; }
                .welcome-team, .welcome-chip, .welcome-art-glow, .welcome-login, .welcome-login::before,
                .welcome-login svg, .welcome-brand-mark, .welcome-theme { transition: none; }
                .welcome-team, .welcome-chip, .welcome-art-glow { translate: none; }
            }
        </style>
    @endpush
</x-guest-layout>