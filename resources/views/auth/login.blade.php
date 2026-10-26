<x-guest-layout>
    <main class="login-page">
        <div class="login-layout">
            <section class="login-card" aria-labelledby="login-heading">
                <a href="/" class="login-brand" aria-label="Followme - Beranda">
                    <span class="login-logo" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5a8.5 8.5 0 1 0 8.5 8.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                            <path d="M12 7v5l3.2 2" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="m16.5 4.5 2 2 3-3" stroke="#F2B943" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>
                        <strong>FOLLOWME</strong>
                        <small>SISTEM ABSENSI KERJA</small>
                    </span>
                </a>

                <div class="login-heading">
                    <p class="login-eyebrow">PORTAL KARYAWAN</p>
                    <h1 id="login-heading">Selamat datang<br>kembali</h1>
                    <p>Masuk untuk mulai bekerja dan mencatat kehadiranmu.</p>
                </div>

                @if(session('status'))
                    <div class="login-status" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf

                    <div class="login-field">
                        <label for="email">Alamat email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="username"
                            placeholder="nama@perusahaan.com"
                        >
                        @error('email')
                            <span class="login-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="login-field">
                        <div class="login-label-row">
                            <label for="password">Kata sandi</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
                            @endif
                        </div>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                        >
                        @error('password')
                            <span class="login-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="login-remember" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>

                    <button type="submit" class="login-submit">
                        Masuk ke akun
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="login-security">
                    <span aria-hidden="true">●</span>
                    Akses aman untuk seluruh aktivitas kerja
                </p>
            </section>

            <section class="login-visual" aria-label="Ilustrasi karyawan melakukan absensi kerja">
                <div class="visual-copy">
                    <span class="visual-tag"><span></span> SIAP UNTUK HARI INI</span>
                    <h2>Mulai hari kerja<br>dengan lebih mudah.</h2>
                    <p>Absensi, aktivitas, dan informasi kerja dalam satu tempat.</p>
                </div>

                <svg class="login-illustration" viewBox="0 0 620 430" fill="none" role="img" aria-label="Karyawan mencatat kehadiran menggunakan perangkat absensi">
                    <ellipse cx="335" cy="372" rx="247" ry="31" fill="#C2A8F4" opacity=".55"/>
                    <circle cx="475" cy="116" r="63" fill="#FFE3AE"/>
                    <circle cx="475" cy="116" r="44" fill="#FFF9EF"/>
                    <path d="M475 87v31l22 13" stroke="#F4A44E" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="475" cy="116" r="5" fill="#F4A44E"/>
                    <g class="illustration-float">
                        <rect x="100" y="125" width="106" height="76" rx="18" fill="#fff" transform="rotate(-10 100 125)"/>
                        <path d="m129 160 13 13 30-34" stroke="#48B98A" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <path d="M94 330h434l-39 36H127l-33-36Z" fill="#9271D6"/>
                    <path d="M128 366h361v18H128z" fill="#7959C0"/>
                    <rect x="405" y="197" width="111" height="143" rx="16" fill="#4B3B77"/>
                    <rect x="415" y="209" width="91" height="111" rx="10" fill="#F5F1FF"/>
                    <rect x="427" y="222" width="66" height="9" rx="4.5" fill="#D8CCF3"/>
                    <circle cx="460" cy="263" r="22" fill="#D9F4E8"/>
                    <path d="m450 263 7 7 14-16" stroke="#28A878" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="447" y="326" width="28" height="5" rx="2.5" fill="#C8BAE9"/>
                    <g class="illustration-worker">
                        <path d="M238 323c2-51 15-87 47-101 19-8 49-8 69 2 27 14 39 51 39 99H238Z" fill="#3D93A2"/>
                        <path d="M269 236c8 18 22 27 39 27 18 0 31-9 39-27l-18-21h-42l-18 21Z" fill="#F2A77D"/>
                        <path d="M279 202c0-25 13-42 31-42s31 17 31 42v11c0 21-14 38-31 38s-31-17-31-38v-11Z" fill="#F6B88F"/>
                        <path d="M274 197c0-30 15-49 38-49 24 0 39 19 39 49v8c-10-3-19-11-25-20-14 13-31 19-52 18v-6Z" fill="#34435E"/>
                        <path d="M274 192c5-23 19-36 41-36 23 0 35 14 39 36h-80Z" fill="#F2B943"/>
                        <path d="M301 222c6 5 14 5 20 0" stroke="#A9584F" stroke-width="3" stroke-linecap="round"/>
                        <path d="M280 280c-18 6-33 20-42 41l-18-2c-8 0-13 8-10 15 3 6 11 8 18 5l32 4 30-40-10-23Z" fill="#F6B88F"/>
                        <path d="M369 272c13 5 24 16 34 31l38-22c9-6 20 2 18 12l-3 6-41 34c-10 8-24 7-33-1l-33-27 20-33Z" fill="#F6B88F"/>
                        <path d="M238 323h155l-6 16H237l1-16Z" fill="#F2B943"/>
                    </g>
                    <g class="illustration-sparkle">
                        <path d="M190 252v20m-10-10h20" stroke="#F1A543" stroke-width="4" stroke-linecap="round"/>
                        <path d="M555 272v16m-8-8h16" stroke="#E98F9A" stroke-width="4" stroke-linecap="round"/>
                    </g>
                </svg>
                <div class="visual-caption">
                    <span class="caption-icon" aria-hidden="true">✓</span>
                    <span><strong>Hadir dan siap bekerja</strong><small>Catat kehadiranmu dengan praktis</small></span>
                </div>
            </section>
        </div>
        <footer class="login-footer">© {{ date('Y') }} PT. Followme <span>·</span> Sistem Absensi Digital</footer>
    </main>
</x-guest-layout>