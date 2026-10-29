<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }"
      x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ config('app.csp_nonce') }}">
        /* ---------- Token warna ---------- */
        :root {
            --sb-top: #0a3a31;
            --sb-bottom: #05201b;
            --accent: #34d399;
            --accent-2: #14b8a6;
            --surface: #f4f7f6;
            --surface-glow: rgba(52, 211, 153, .16);
            --header-bg: rgba(255, 255, 255, .72);
            --header-line: rgba(15, 23, 42, .08);
        }
        .dark {
            --sb-top: #082a24;
            --sb-bottom: #031612;
            --surface: #0b1412;
            --surface-glow: rgba(20, 184, 166, .12);
            --header-bg: rgba(11, 20, 18, .72);
            --header-line: rgba(255, 255, 255, .07);
        }

        /* ---------- Scrollbar ---------- */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.14); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.28); }

        @media (min-width: 1024px) {
            .lg\:ml-64 { margin-left: 16rem !important; }
            .lg\:ml-0 { margin-left: 0 !important; }
        }

        /* ---------- Latar halaman ---------- */
        .app-surface {
            background-color: var(--surface);
            background-image:
                radial-gradient(900px 380px at 85% -80px, var(--surface-glow), transparent 70%),
                radial-gradient(rgba(100, 116, 139, .14) 1px, transparent 1px);
            background-size: auto, 22px 22px;
            background-attachment: fixed, fixed;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            background: linear-gradient(185deg, var(--sb-top), var(--sb-bottom));
            color: #d1fae5;
        }
        .sidebar::after {
            content: ""; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(260px 220px at 0% 0%, rgba(52,211,153,.18), transparent 70%);
        }
        .sb-divider { border-top: 1px solid rgba(255,255,255,.07); }

        .sb-group-label {
            display: flex; align-items: center; gap: .5rem;
            padding: .4rem .75rem; font-size: .75rem; font-weight: 600;
            color: rgba(167, 243, 208, .55);
        }

        .nav-link {
            position: relative; display: flex; align-items: center; gap: .75rem;
            padding: .6rem .75rem; border-radius: .75rem;
            font-size: .875rem; font-weight: 500;
            color: rgba(209, 250, 229, .78);
            transition: background-color .2s ease, color .2s ease, transform .2s ease;
            animation: navIn .55s cubic-bezier(.2,.8,.2,1) both;
            animation-delay: calc(var(--i, 0) * 55ms + 120ms);
        }
        .nav-link .nav-icon {
            display: grid; place-items: center; width: 2rem; height: 2rem; flex-shrink: 0;
            border-radius: .6rem; background: rgba(255,255,255,.06);
            transition: transform .25s cubic-bezier(.3,1.6,.5,1), background-color .2s ease, color .2s ease;
        }
        .nav-link:hover { background: rgba(255,255,255,.07); color: #fff; transform: translateX(3px); }
        .nav-link:hover .nav-icon { transform: scale(1.12) rotate(-6deg); background: rgba(52,211,153,.22); color: var(--accent); }
        .nav-link:active { transform: translateX(3px) scale(.98); }

        .nav-link.is-active {
            color: #fff;
            background: linear-gradient(90deg, rgba(52,211,153,.28), rgba(20,184,166,.06));
            box-shadow: inset 0 0 0 1px rgba(52,211,153,.25);
        }
        .nav-link.is-active .nav-icon {
            background: linear-gradient(135deg, #10b981, #0d9488); color: #fff;
            box-shadow: 0 6px 16px -4px rgba(16,185,129,.7);
        }
        .nav-link.is-active::before {
            content: ""; position: absolute; left: -.75rem; top: 20%; bottom: 20%; width: 4px;
            border-radius: 0 6px 6px 0; background: var(--accent);
            box-shadow: 0 0 14px 2px rgba(52,211,153,.7);
        }
        .nav-link.is-danger.is-active {
            background: linear-gradient(90deg, rgba(251,113,133,.25), rgba(251,113,133,.04));
            box-shadow: inset 0 0 0 1px rgba(251,113,133,.25);
        }
        .nav-link.is-danger.is-active .nav-icon { background: linear-gradient(135deg,#f43f5e,#e11d48); box-shadow: 0 6px 16px -4px rgba(244,63,94,.7); }
        .nav-link.is-danger.is-active::before { background: #fb7185; box-shadow: 0 0 14px 2px rgba(251,113,133,.7); }

        .nav-sub { margin-left: .75rem; padding: .45rem .65rem; font-size: .8125rem; }
        .nav-sub .nav-icon { width: 1.5rem; height: 1.5rem; font-size: .6875rem; border-radius: .5rem; }

        .nav-link:focus-visible, .ui-btn:focus-visible {
            outline: 2px solid var(--accent); outline-offset: 2px;
        }

        @keyframes navIn {
            from { opacity: 0; transform: translateX(-14px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ---------- Brand & user ---------- */
        .brand-logo { transition: transform .4s cubic-bezier(.3,1.6,.5,1); }
        .brand:hover .brand-logo { transform: rotate(-8deg) scale(1.08); }

        .user-card {
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
            border-radius: 1rem; transition: background-color .2s ease, border-color .2s ease;
        }
        .user-card:hover { background: rgba(255,255,255,.1); border-color: rgba(52,211,153,.35); }
        .avatar { position: relative; }
        .avatar::after {
            content: ""; position: absolute; right: -1px; bottom: -1px; width: 10px; height: 10px;
            border-radius: 9999px; background: #34d399; border: 2px solid #0a3a31;
        }

        /* ---------- Header ---------- */
        .app-header {
            background: var(--header-bg);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid var(--header-line);
            transition: box-shadow .25s ease;
        }
        .app-header.is-scrolled { box-shadow: 0 10px 30px -18px rgba(2, 44, 34, .45); }
        .scroll-progress {
            position: absolute; left: 0; bottom: -1px; height: 2px; width: 100%;
            background: linear-gradient(90deg, #10b981, #14b8a6, #22d3ee);
            transform-origin: 0 50%; transition: transform .1s linear;
        }

        .ui-btn {
            display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: .75rem;
            color: #475569; transition: background-color .2s ease, color .2s ease, transform .2s ease;
        }
        .dark .ui-btn { color: #cbd5e1; }
        .ui-btn:hover { background: rgba(16,185,129,.12); color: #059669; }
        .dark .ui-btn:hover { color: #6ee7b7; }
        .ui-btn:active { transform: scale(.92); }

        .clock-chip {
            display: flex; align-items: center; gap: .6rem; padding: .35rem .85rem .35rem .6rem;
            border-radius: 9999px; border: 1px solid var(--header-line);
            background: rgba(16,185,129,.07);
        }
        .pulse-dot { position: relative; width: 8px; height: 8px; border-radius: 9999px; background: #10b981; }
        .pulse-dot::after {
            content: ""; position: absolute; inset: 0; border-radius: inherit; background: #10b981;
            animation: ping 1.8s cubic-bezier(0,0,.2,1) infinite;
        }
        @keyframes ping { 75%, 100% { transform: scale(2.6); opacity: 0; } }

        .theme-toggle { position: relative; overflow: hidden; }
        .theme-toggle i { position: absolute; }

        @media (prefers-reduced-motion: reduce) {
            .nav-link, .pulse-dot::after { animation: none !important; }
            .nav-link, .nav-link .nav-icon, .brand-logo, .ui-btn, .scroll-progress { transition: none !important; }
        }

        /* Neutral application shell shared by every authenticated page. */
        .app-surface {
            background: #f4f6f8 !important;
            background-image: none !important;
        }
        .dark .app-surface { background: #111827 !important; }
        .sidebar {
            border-right: 1px solid #e5e7eb;
            background: #fff !important;
            color: #374151;
            box-shadow: 0 1px 3px rgb(15 23 42 / 5%);
        }
        .dark .sidebar {
            border-right-color: #374151;
            background: #1f2937 !important;
            color: #e5e7eb;
        }
        .sidebar::after { display: none; }
        .sb-divider { border-top-color: #e5e7eb; }
        .dark .sb-divider { border-top-color: #374151; }
        .sb-group-label { color: #6b7280; }
        .dark .sb-group-label { color: #9ca3af; }
        .nav-link {
            color: #4b5563;
            animation: none;
        }
        .dark .nav-link { color: #d1d5db; }
        .nav-link:hover,
        .nav-link:active {
            transform: none;
            background: #f3f4f6;
            color: #111827;
        }
        .dark .nav-link:hover,
        .dark .nav-link:active {
            background: #374151;
            color: #f9fafb;
        }
        .nav-link .nav-icon {
            background: #f3f4f6;
            color: #6b7280;
        }
        .dark .nav-link .nav-icon {
            background: #374151;
            color: #d1d5db;
        }
        .nav-link:hover .nav-icon {
            transform: none;
            background: #e5e7eb;
            color: #047857;
        }
        .dark .nav-link:hover .nav-icon {
            background: #4b5563;
            color: #6ee7b7;
        }
        .nav-link.is-active,
        .nav-link.is-danger.is-active {
            background: #ecfdf5;
            color: #047857;
            box-shadow: inset 0 0 0 1px #d1fae5;
        }
        .dark .nav-link.is-active,
        .dark .nav-link.is-danger.is-active {
            background: rgb(16 185 129 / 12%);
            color: #6ee7b7;
            box-shadow: inset 0 0 0 1px rgb(16 185 129 / 22%);
        }
        .nav-link.is-active .nav-icon,
        .nav-link.is-danger.is-active .nav-icon {
            background: #d1fae5;
            color: #047857;
            box-shadow: none;
        }
        .dark .nav-link.is-active .nav-icon,
        .dark .nav-link.is-danger.is-active .nav-icon {
            background: rgb(16 185 129 / 18%);
            color: #6ee7b7;
            box-shadow: none;
        }
        .nav-link.is-active::before,
        .nav-link.is-danger.is-active::before {
            background: #059669;
            box-shadow: none;
        }
        .sidebar .brand {
            border-bottom: 1px solid #e5e7eb;
            background: #fff !important;
        }
        .dark .sidebar .brand {
            border-bottom-color: #374151;
            background: #1f2937 !important;
        }
        .sidebar .brand h1 { color: #111827 !important; }
        .sidebar .brand p { color: #6b7280 !important; }
        .dark .sidebar .brand h1 { color: #f9fafb !important; }
        .dark .sidebar .brand p { color: #9ca3af !important; }
        .brand-logo { box-shadow: none !important; }
        .brand:hover .brand-logo { transform: none; }
        .user-card {
            border-color: #e5e7eb;
            background: #f9fafb;
        }
        .user-card:hover {
            border-color: #d1d5db;
            background: #f3f4f6;
        }
        .dark .user-card {
            border-color: #374151;
            background: #111827;
        }
        .dark .user-card:hover {
            border-color: #4b5563;
            background: #273449;
        }
        .user-card .text-white { color: #111827 !important; }
        .user-card .text-emerald-200\/60 { color: #6b7280 !important; }
        .dark .user-card .text-white { color: #f9fafb !important; }
        .dark .user-card .text-emerald-200\/60 { color: #9ca3af !important; }
        .user-card .avatar {
            background: #047857 !important;
            box-shadow: none !important;
        }
        .sidebar > div:last-child {
            background: linear-gradient(0deg, #fff 65%, transparent) !important;
        }
        .dark .sidebar > div:last-child {
            background: linear-gradient(0deg, #1f2937 65%, transparent) !important;
        }
        .avatar::after { border-color: #fff; }
        .dark .avatar::after { border-color: #1f2937; }
        .app-header {
            border-bottom-color: #e5e7eb;
            background: #fff;
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }
        .dark .app-header {
            border-bottom-color: #374151;
            background: #1f2937;
        }
        .app-header.is-scrolled { box-shadow: 0 2px 8px rgb(15 23 42 / 5%); }
        .scroll-progress { display: none; }
        .clock-chip { border-radius: 0.625rem; background: #f9fafb; }
        .dark .clock-chip { background: #111827; }
        .pulse-dot,
        .pulse-dot::after { animation: none; }
        .lg\:ml-64,
        .lg\:ml-0 { transition: none !important; }
        .ui-btn:active { transform: none; }
        .fixed.inset-0.bg-emerald-950\/60 {
            background: rgb(17 24 39 / 38%);
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }
    </style>
    @stack('styles')
</head>

<body class="font-sans antialiased text-gray-900 dark:text-gray-100">
<div class="min-h-screen w-full app-surface" x-data="{ sidebarOpen: window.innerWidth >= 1024 }"
     @keydown.escape.window="if (window.innerWidth < 1024) sidebarOpen = false">

    <!-- ========================================================= -->
    <!-- SIDEBAR -->
    <!-- ========================================================= -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="sidebar fixed top-0 left-0 bottom-0 z-50 w-64 border-r border-white/5 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none flex flex-col overflow-y-auto custom-scrollbar">

        <!-- Logo -->
        <div class="brand relative sticky top-0 shrink-0 flex items-center gap-3 px-5 py-4 z-20 backdrop-blur-md"
             style="background: linear-gradient(180deg, var(--sb-top) 60%, transparent);">
            <div class="grid place-items-center w-10 h-10 rounded-xl bg-white shadow-lg shadow-emerald-900/40 shrink-0">
                <img src="{{ asset('images/logo-followme.png') }}" class="brand-logo h-6 w-auto" alt="Logo Followme">
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-bold text-white truncate">PT. Followme</h1>
                <p class="text-[11px] text-emerald-200/60 truncate">Sistem Absensi</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="relative z-10 flex-1 w-full">
            <nav class="px-3 py-2 space-y-1">

            <!-- Dashboard -->
            <a href="{{ route('admin.absensi.index') }}"
               @if (request()->routeIs('admin.absensi.index', 'admin.absensi.freelance')) aria-current="page" @endif
               style="--i:0"
               class="nav-link {{ request()->routeIs('admin.absensi.index', 'admin.absensi.freelance') ? 'is-active' : '' }}">
                <span class="nav-icon"><i class="fas fa-home text-sm"></i></span>
                <span>Dashboard</span>
            </a>

            @auth
            @if (Auth::user()->role === 'hrd')
            <div class="sb-divider pt-3 mt-3 space-y-1">
                <div class="sb-group-label">
                    <i class="fas fa-money-bill-wave w-4 text-emerald-400"></i>
                    <span>Payroll</span>
                </div>
                <a href="{{ route('admin.payroll.index') }}"
                   style="--i:1"
                   class="nav-link {{ request()->routeIs('admin.payroll.*') ? 'is-active' : '' }}"
                   @if (request()->routeIs('admin.payroll.*')) aria-current="page" @endif>
                    <span class="nav-icon"><i class="fas fa-file-invoice-dollar text-sm"></i></span>
                    <span>Payroll Freelance</span>
                </a>
            </div>
            @endif
            @endauth

            <!-- APPROVAL SECTION -->
            @auth
            @if (Auth::user()->role === 'hrd')
            <div class="sb-divider pt-3 mt-3 space-y-1">
                <div class="sb-group-label">
                    <i class="fas fa-user-shield w-4"></i>
                    <span>Approval</span>
                </div>

                <a href="{{ route('admin.absensi.approval.hrd') }}"
                   style="--i:2"
                   class="nav-link nav-sub {{ request()->routeIs('admin.absensi.approval.hrd') ? 'is-active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-user-check"></i></span>
                    <span class="truncate">HRD</span>
                </a>
            </div>
            @endif
            @endauth

            <!-- MANAGEMENT -->
            @auth
            @if (Auth::user()->isAnyAdmin())
            <div class="sb-divider pt-3 mt-3 space-y-1">
                <div class="sb-group-label">
                    <i class="fas fa-toolbox w-4"></i>
                    <span>Manage</span>
                </div>
                <a href="{{ route('admin.holidays.index') }}"
                   style="--i:3"
                   class="nav-link nav-sub {{ request()->routeIs('admin.holidays.*') ? 'is-active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-calendar-day"></i></span>
                    <span>Hari Libur</span>
                </a>

                @if(Auth::user()->isSuperAdmin())
                <a href="{{ route('admin.activity_logs.index') }}"
                   style="--i:4"
                   class="nav-link nav-sub is-danger {{ request()->routeIs('admin.activity_logs.*') ? 'is-active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-history"></i></span>
                    <span>Log Aktivitas</span>
                </a>
                @endif
            </div>
            @endif
            @endauth

            <!-- Export & Recap -->
            <div class="sb-divider pt-3 mt-3 space-y-1">
                <a href="{{ route('admin.absensi.recap') }}"
                   style="--i:5"
                   class="nav-link {{ request()->routeIs('admin.absensi.recap') ? 'is-active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-calendar-check text-sm"></i></span>
                    <span>Rekap Bulanan</span>
                </a>
            </div>
            </nav>
        </div>

        <!-- User Info -->
        @auth
        <div class="relative z-20 sticky bottom-0 shrink-0 mt-auto p-3"
             style="background: linear-gradient(0deg, var(--sb-bottom) 65%, transparent);">
            <div class="user-card flex items-center gap-2.5 p-2.5">
                <div class="avatar w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white text-sm font-bold shrink-0 shadow-lg shadow-emerald-900/50">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-emerald-200/60 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit"
                            class="grid place-items-center w-8 h-8 text-emerald-200/70 hover:text-white hover:bg-rose-500/80 rounded-lg transition-all hover:rotate-6 active:scale-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-400"
                            title="Logout" aria-label="Logout">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </aside>

    <!-- Overlay (mobile) -->
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-emerald-950/60 backdrop-blur-sm lg:hidden z-40"></div>

    <!-- ========================================================= -->
    <!-- MAIN CONTENT -->
    <!-- ========================================================= -->
    <div :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-0'"
         class="flex flex-col min-h-screen transition-all duration-300 ease-in-out">

        <!-- Top Nav -->
        <header class="app-header sticky top-0 z-30 shrink-0"
                x-data="{
                    scrolled: false,
                    progress: 0,
                    now: new Date(),
                    update() {
                        const el = document.documentElement;
                        const max = el.scrollHeight - el.clientHeight;
                        this.scrolled = window.scrollY > 4;
                        this.progress = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
                    },
                    init() { this.update(); setInterval(() => this.now = new Date(), 1000); }
                }"
                @scroll.window.passive="update()"
                :class="{ 'is-scrolled': scrolled }">
            <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3">
                <button @click="sidebarOpen = !sidebarOpen" class="ui-btn shrink-0"
                        :aria-expanded="sidebarOpen.toString()" aria-label="Buka atau tutup menu">
                    <i class="fas fa-bars text-lg transition-transform duration-300"
                       :class="sidebarOpen ? 'rotate-0' : 'rotate-90'"></i>
                </button>

                <div class="flex-1 min-w-0">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                </div>

                <!-- Jam langsung -->
                <div class="clock-chip hidden md:flex" aria-hidden="true">
                    <span class="pulse-dot"></span>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold tabular-nums text-gray-800 dark:text-gray-100"
                           x-text="now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })"></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400"
                           x-text="now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' })"></p>
                    </div>
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode" class="ui-btn theme-toggle shrink-0"
                        :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'">
                    <i x-show="!darkMode"
                       x-transition:enter="transition duration-300 ease-out"
                       x-transition:enter-start="opacity-0 -rotate-90 scale-50"
                       x-transition:enter-end="opacity-100 rotate-0 scale-100"
                       x-transition:leave="transition duration-150 ease-in"
                       x-transition:leave-start="opacity-100 rotate-0 scale-100"
                       x-transition:leave-end="opacity-0 rotate-90 scale-50"
                       class="fas fa-moon text-lg"></i>
                    <i x-show="darkMode"
                       x-transition:enter="transition duration-300 ease-out"
                       x-transition:enter-start="opacity-0 rotate-90 scale-50"
                       x-transition:enter-end="opacity-100 rotate-0 scale-100"
                       x-transition:leave="transition duration-150 ease-in"
                       x-transition:leave-start="opacity-100 rotate-0 scale-100"
                       x-transition:leave-end="opacity-0 -rotate-90 scale-50"
                       class="fas fa-sun text-lg text-amber-400"></i>
                </button>
            </div>

            <!-- Progress scroll -->
            <div class="scroll-progress" :style="`transform: scaleX(${progress})`"></div>
        </header>

        <!-- Page Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>