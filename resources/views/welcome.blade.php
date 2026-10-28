<x-guest-layout>
    <main class="welcome-page">
        <div class="welcome-shell">
            <header class="welcome-header">
                <a class="welcome-brand" href="/" aria-label="Follow Me - Beranda">
                    <span class="welcome-brand-mark" aria-hidden="true">F</span>
                    <span class="welcome-brand-name">follow<span>me</span></span>
                </a>
                <span class="welcome-header-note">PORTAL ABSENSI KARYAWAN</span>
            </header>

            <section class="welcome-hero" aria-labelledby="welcome-title">
                <div class="welcome-art">
                    <span class="welcome-art-glow" aria-hidden="true"></span>
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
                    <p class="welcome-eyebrow"><span></span> SISTEM ABSENSI PT FOLLOW ME</p>
                    <h1 id="welcome-title">Kerja lebih <span>teratur.</span><br>Mulai dari hadir.</h1>
                    <p class="welcome-description">
                        Selamat datang di portal absensi PT Follow Me. Catat kehadiran dan mulai aktivitas kerja dengan lebih mudah.
                    </p>
                    <a class="welcome-login" href="{{ route('login') }}">
                        Masuk Akun
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
                --welcome-ink: #20243a;
                --welcome-muted: #777d90;
                --welcome-blue: #4263eb;
                min-height: 100vh;
                min-height: 100svh;
                overflow: hidden;
                background:
                    radial-gradient(ellipse at 3% 5%, rgba(250, 223, 202, .36), transparent 26rem),
                    radial-gradient(ellipse at 98% 93%, rgba(217, 224, 255, .42), transparent 28rem),
                    #f8f9fc;
                color: var(--welcome-ink);
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

            .welcome-header {
                display: flex;
                min-height: 5.5rem;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid rgba(44, 51, 83, .08);
            }

            .welcome-brand {
                display: inline-flex;
                align-items: center;
                gap: .65rem;
                color: var(--welcome-ink);
                text-decoration: none;
            }

            .welcome-brand-mark {
                display: grid;
                width: 2.2rem;
                height: 2.2rem;
                place-items: center;
                border-radius: .72rem;
                background: var(--welcome-blue);
                box-shadow: 0 5px 12px rgba(66, 99, 235, .22);
                color: #fff;
                font-size: 1.15rem;
                font-weight: 800;
            }

            .welcome-brand-name {
                font-size: 1.1rem;
                font-weight: 800;
                letter-spacing: -.055em;
            }

            .welcome-brand-name span {
                color: var(--welcome-blue);
            }

            .welcome-header-note {
                color: #9297a7;
                font-size: .65rem;
                font-weight: 700;
                letter-spacing: .12em;
            }

            .welcome-hero {
                display: grid;
                flex: 1;
                grid-template-columns: 1.03fr .97fr;
                align-items: center;
                gap: clamp(2.5rem, 7vw, 6rem);
                padding: 2.75rem 0;
            }

            .welcome-art {
                position: relative;
                display: grid;
                min-height: 32rem;
                place-items: center;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, .85);
                border-radius: 2rem;
                background: linear-gradient(145deg, #fff4ec, #ffede2 58%, #f8e9f0);
                box-shadow: 0 22px 60px rgba(65, 54, 83, .08);
                isolation: isolate;
                animation: welcome-enter-left 700ms cubic-bezier(.2, .7, .25, 1) both;
            }

            .welcome-art-glow {
                position: absolute;
                z-index: -1;
                top: 8%;
                right: -12%;
                width: 15rem;
                aspect-ratio: 1;
                border-radius: 50%;
                background: rgba(255, 255, 255, .48);
                filter: blur(2px);
            }

            .welcome-team {
                display: block;
                width: min(84%, 350px);
                height: auto;
                max-height: 90%;
                border-radius: 1rem;
                mix-blend-mode: multiply;
                object-fit: contain;
                animation: welcome-image-float 7s ease-in-out 700ms infinite;
            }

            .welcome-art-caption {
                position: absolute;
                right: 1.15rem;
                bottom: 1.15rem;
                left: 1.15rem;
                display: flex;
                align-items: center;
                gap: .7rem;
                padding: .8rem .9rem;
                border: 1px solid rgba(255, 255, 255, .8);
                border-radius: 1rem;
                background: rgba(255, 255, 255, .82);
                box-shadow: 0 8px 25px rgba(65, 54, 83, .07);
                backdrop-filter: blur(12px);
            }

            .welcome-caption-icon {
                display: grid;
                width: 2.15rem;
                height: 2.15rem;
                flex: none;
                place-items: center;
                border-radius: .7rem;
                background: #e9f7ef;
                color: #27a368;
            }

            .welcome-caption-icon svg {
                width: 1.1rem;
                stroke: currentColor;
                stroke-linecap: round;
                stroke-linejoin: round;
                stroke-width: 2;
            }

            .welcome-art-caption strong,
            .welcome-art-caption small {
                display: block;
            }

            .welcome-art-caption strong {
                color: var(--welcome-ink);
                font-size: .74rem;
                font-weight: 750;
            }

            .welcome-art-caption small {
                margin-top: .15rem;
                color: var(--welcome-muted);
                font-size: .63rem;
            }

            .welcome-copy {
                max-width: 35rem;
                padding: 1rem 0;
                animation: welcome-enter-right 650ms cubic-bezier(.2, .7, .25, 1) 100ms both;
            }

            .welcome-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                margin: 0 0 1.25rem;
                padding: .48rem .75rem;
                border: 1px solid #e6eafe;
                border-radius: 999px;
                background: #f0f3ff;
                color: #5164bd;
                font-size: .64rem;
                font-weight: 750;
                letter-spacing: .075em;
            }

            .welcome-eyebrow span {
                width: .42rem;
                height: .42rem;
                border-radius: 50%;
                background: #5b78f0;
                box-shadow: 0 0 0 3px rgba(91, 120, 240, .12);
            }

            .welcome-copy h1 {
                margin: 0;
                color: var(--welcome-ink);
                font-size: clamp(3rem, 5.4vw, 5.1rem);
                font-weight: 760;
                letter-spacing: -.075em;
                line-height: 1.04;
            }

            .welcome-copy h1 span {
                color: var(--welcome-blue);
            }

            .welcome-description {
                max-width: 29rem;
                margin: 1.25rem 0 1.75rem;
                color: var(--welcome-muted);
                font-size: .98rem;
                line-height: 1.75;
            }

            .welcome-login {
                display: inline-flex;
                min-height: 3.25rem;
                align-items: center;
                justify-content: center;
                gap: .85rem;
                padding: 0 1.35rem;
                border: 1px solid var(--welcome-blue);
                border-radius: .85rem;
                background: var(--welcome-blue);
                box-shadow: 0 9px 20px rgba(66, 99, 235, .22);
                color: #fff;
                font-size: .87rem;
                font-weight: 750;
                text-decoration: none;
                transition: background .18s ease, box-shadow .18s ease, transform .18s ease;
            }

            .welcome-login:hover {
                transform: translateY(-2px);
                background: #3453d1;
                box-shadow: 0 12px 24px rgba(66, 99, 235, .28);
            }

            .welcome-login:focus-visible {
                outline: 3px solid #c9d3ff;
                outline-offset: 3px;
            }

            .welcome-login svg {
                width: 1.05rem;
                fill: none;
                stroke: currentColor;
                stroke-linecap: round;
                stroke-linejoin: round;
                stroke-width: 1.8;
                transition: transform .18s ease;
            }

            .welcome-login:hover svg {
                transform: translateX(3px);
            }

            .welcome-assurance {
                display: flex;
                align-items: center;
                gap: .45rem;
                margin: 1rem 0 0;
                color: #8a90a1;
                font-size: .68rem;
            }

            .welcome-assurance svg {
                width: 1rem;
                flex: none;
                stroke: #39a877;
                stroke-linecap: round;
                stroke-linejoin: round;
                stroke-width: 1.6;
            }

            .welcome-footer {
                display: flex;
                min-height: 3.5rem;
                align-items: center;
                justify-content: space-between;
                border-top: 1px solid rgba(44, 51, 83, .08);
                color: #979bab;
                font-size: .65rem;
            }

            @keyframes welcome-enter-left {
                from {
                    opacity: 0;
                    transform: translateX(-14px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes welcome-enter-right {
                from {
                    opacity: 0;
                    transform: translateX(14px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes welcome-image-float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-5px); }
            }

            @media (max-width: 760px) {
                .welcome-shell {
                    padding: 0 1.25rem;
                }

                .welcome-header {
                    min-height: 4.5rem;
                }

                .welcome-hero {
                    grid-template-columns: 1fr;
                    gap: 1.75rem;
                    padding: 1.5rem 0 2rem;
                }

                .welcome-art {
                    min-height: clamp(16rem, 58vw, 23rem);
                    border-radius: 1.5rem;
                }

                .welcome-team {
                    width: auto;
                    height: min(48vw, 19rem);
                    max-width: 76%;
                }

                .welcome-art-caption {
                    right: .75rem;
                    bottom: .75rem;
                    left: .75rem;
                }

                .welcome-copy {
                    justify-self: center;
                    padding: 0;
                }

                .welcome-copy h1 {
                    font-size: clamp(2.8rem, 11vw, 4.25rem);
                }

                .welcome-description {
                    margin: .9rem 0 1.25rem;
                    font-size: .91rem;
                }
            }

            @media (max-width: 420px) {
                .welcome-header-note {
                    max-width: 7.5rem;
                    font-size: .55rem;
                    text-align: right;
                }

                .welcome-art {
                    min-height: 15rem;
                }

                .welcome-team {
                    height: 12.5rem;
                }

                .welcome-art-caption {
                    gap: .5rem;
                    padding: .65rem .7rem;
                }

                .welcome-art-caption strong {
                    font-size: .67rem;
                }

                .welcome-art-caption small {
                    font-size: .57rem;
                }

                .welcome-footer {
                    min-height: 3rem;
                    font-size: .58rem;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .welcome-art,
                .welcome-team,
                .welcome-copy {
                    animation: none;
                }

                .welcome-login,
                .welcome-login svg {
                    transition: none;
                }
            }
        </style>
    @endpush
</x-guest-layout>
