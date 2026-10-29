<style nonce="{{ config('app.csp_nonce') }}">
    .fm-nav {
        position: sticky; top: 0; z-index: 40;
        background: linear-gradient(100deg, var(--sb-top, #0a3a31), var(--sb-bottom, #05201b));
        border-bottom: 1px solid rgba(255, 255, 255, .06);
        transition: box-shadow .25s ease;
    }
    .fm-nav::before {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: radial-gradient(420px 120px at 0% 0%, rgba(52, 211, 153, .2), transparent 70%);
    }
    .fm-nav::after {
        content: ""; position: absolute; left: 0; right: 0; bottom: -1px; height: 2px;
        background: linear-gradient(90deg, transparent, #10b981, #14b8a6, #22d3ee, transparent);
        opacity: .75;
    }
    .fm-nav.is-scrolled { box-shadow: 0 12px 30px -16px rgba(2, 44, 34, .7); }
    [x-cloak] { display: none !important; }

    /* Brand */
    .fm-brand-tile { transition: transform .4s cubic-bezier(.3, 1.6, .5, 1); }
    .fm-brand:hover .fm-brand-tile { transform: rotate(-8deg) scale(1.08); }

    /* Link desktop (menimpa style bawaan x-nav-link) */
    .fm-nav .fm-link {
        position: relative; color: rgba(209, 250, 229, .75) !important;
        border: 0 !important; font-weight: 500; transition: color .2s ease;
    }
    .fm-nav .fm-link::after {
        content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px;
        border-radius: 3px 3px 0 0; background: #34d399;
        box-shadow: 0 0 14px 2px rgba(52, 211, 153, .65);
        transform: scaleX(0); transform-origin: center; transition: transform .3s cubic-bezier(.2, .8, .2, 1);
    }
    .fm-nav .fm-link:hover { color: #fff !important; }
    .fm-nav .fm-link:hover::after { transform: scaleX(.55); }
    .fm-nav .fm-link.is-active { color: #fff !important; }
    .fm-nav .fm-link.is-active::after { transform: scaleX(1); }

    /* Tombol ikon */
    .fm-btn {
        display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: .75rem;
        color: rgba(209, 250, 229, .8); transition: background-color .2s ease, color .2s ease, transform .2s ease;
    }
    .fm-btn:hover { background: rgba(255, 255, 255, .1); color: #fff; }
    .fm-btn:active { transform: scale(.92); }
    .fm-nav button:focus-visible, .fm-nav a:focus-visible { outline: 2px solid #34d399 !important; outline-offset: 2px; }

    /* Toggle tema: ikon dibuat ulang oleh JS, animasi ikut berjalan lagi */
    #theme-toggle { color: #6ee7b7; padding: 0; margin-right: .75rem; }
    #theme-toggle:hover { background: rgba(255, 255, 255, .1); }
    #theme-toggle svg { animation: fmPop .45s cubic-bezier(.3, 1.6, .5, 1); }
    #theme-toggle-light-icon { color: #fbbf24; }
    @keyframes fmPop {
        from { transform: rotate(-120deg) scale(.4); opacity: 0; }
        to   { transform: rotate(0) scale(1); opacity: 1; }
    }

    /* Chip user */
    .fm-user {
        display: flex; align-items: center; gap: .6rem; padding: .3rem .7rem .3rem .3rem;
        border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .1);
        background: rgba(255, 255, 255, .06); color: #ecfdf5;
        transition: background-color .2s ease, border-color .2s ease;
    }
    .fm-user:hover { background: rgba(255, 255, 255, .12); border-color: rgba(52, 211, 153, .45); }
    .fm-avatar {
        position: relative; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 9999px;
        background: linear-gradient(135deg, #34d399, #0d9488); color: #fff; font-size: .8rem; font-weight: 700;
    }
    .fm-avatar::after {
        content: ""; position: absolute; right: -1px; bottom: -1px; width: 9px; height: 9px;
        border-radius: 9999px; background: #34d399; border: 2px solid #0a3a31;
    }
    .fm-chevron { transition: transform .25s ease; }
    [aria-expanded="true"] .fm-chevron { transform: rotate(180deg); }

    /* Isi dropdown */
    .fm-nav .fm-dd-link { transition: background-color .15s ease, color .15s ease, padding-left .2s ease; }
    .fm-nav .fm-dd-link:hover {
        background: rgba(16, 185, 129, .12) !important; color: #047857 !important; padding-left: 1.25rem;
    }
    .dark .fm-nav .fm-dd-link:hover { color: #6ee7b7 !important; background: rgba(16, 185, 129, .18) !important; }

    /* Menu mobile */
    .fm-mobile { background: var(--sb-bottom, #05201b); border-top: 1px solid rgba(255, 255, 255, .08); }
    .fm-nav .fm-rlink {
        color: rgba(209, 250, 229, .8) !important; border-color: transparent !important;
        border-left-width: 4px; transition: background-color .2s ease, color .2s ease, padding-left .2s ease;
    }
    .fm-nav .fm-rlink:hover { background: rgba(255, 255, 255, .07) !important; color: #fff !important; padding-left: 1.5rem; }
    .fm-nav .fm-rlink.is-active {
        background: linear-gradient(90deg, rgba(52, 211, 153, .25), transparent) !important;
        color: #fff !important; border-color: #34d399 !important;
    }

    @media (prefers-reduced-motion: reduce) {
        #theme-toggle svg { animation: none; }
        .fm-brand-tile, .fm-link::after, .fm-btn, .fm-chevron, .fm-dd-link, .fm-rlink { transition: none !important; }
    }
</style>

<nav x-data="{ open: false, scrolled: false }"
     @scroll.window.passive="scrolled = window.scrollY > 4"
     @keydown.escape.window="open = false"
     :class="{ 'is-scrolled': scrolled }"
     class="fm-nav">
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="fm-brand flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="fm-brand-tile grid place-items-center w-10 h-10 rounded-xl bg-white shadow-lg shadow-emerald-950/40">
                        <img src="{{ asset('images/logo-followme.png') }}" class="block h-7 w-auto" alt="Logo PT Followme">
                    </a>
                    <span class="font-bold text-lg text-white">PT. Followme</span>
                </div>
            </div>

            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                            class="fm-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    {{ __('Dashboard') }}
                </x-nav-link>
                {{-- Tambahkan link navigasi lain di sini jika ada --}}
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <button id="theme-toggle" type="button" aria-label="Ganti tema"
                        class="fm-btn focus:outline-none rounded-lg text-sm">
                    {{-- Icons will be set by JS --}}
                </button>

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="fm-user text-sm font-medium focus:outline-none">
                                <span class="fm-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                                <div>{{ Auth::user()->name }}</div>
                                <div>
                                    <svg class="fm-chevron fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')" class="fm-dd-link text-gray-700 dark:text-gray-300">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        data-action="submit-logout-form"
                                        class="fm-dd-link text-gray-700 dark:text-gray-300">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="fm-btn focus:outline-none"
                        :aria-expanded="open.toString()" aria-label="Buka atau tutup menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fm-mobile relative sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                                   class="fm-rlink {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            {{-- Tambahkan responsive link lain di sini --}}
        </div>

        @auth
            <div class="pt-4 pb-2 border-t border-white/10">
                <div class="px-4 flex items-center gap-3">
                    <span class="fm-avatar w-10 h-10">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    <div class="min-w-0">
                        <div class="font-medium text-base text-white truncate">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-emerald-200/60 truncate">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')" class="fm-rlink">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                data-action="submit-logout-form"
                                class="fm-rlink">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>

{{-- Pastikan script untuk dark mode toggle ada di app.blade.php atau guest.blade.php --}}
{{-- Atau tambahkan di bawah sini jika navbar ini berdiri sendiri tanpa layout parent --}}
<script nonce="{{ config('app.csp_nonce') }}">
    document.addEventListener('click', function(e) {
        var target = e.target.closest('[data-action="submit-logout-form"]');
        if (target) {
            e.preventDefault();
            var form = target.closest('form');
            if (form) form.submit();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const themeToggleBtn = document.getElementById('theme-toggle');
        if (themeToggleBtn) {
            const htmlElement = document.documentElement;

            // Set initial icon based on current theme
            if (htmlElement.classList.contains('dark')) {
                themeToggleBtn.innerHTML = `
                    <svg id="theme-toggle-light-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 11a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 0114 13zM4 13a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm3.293-1.707a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-4-1a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 016 14zM16 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM3 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM13.293 4.293a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM7 4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1z"></path></svg>
                `; // Sun icon
            } else {
                themeToggleBtn.innerHTML = `
                    <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.292 8.614a5.955 5.955 0 01-8.23-5.228 5.955 5.955 0 00-7.394 6.786 5.956 5.956 0 006.786 7.394 5.955 5.955 0 005.228-8.23z"></path></svg>
                `; // Moon icon
            }

            themeToggleBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                if (htmlElement.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                    themeToggleBtn.innerHTML = `
                        <svg id="theme-toggle-light-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 11a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 0114 13zM4 13a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm3.293-1.707a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-4-1a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 016 14zM16 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM3 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM13.293 4.293a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM7 4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1z"></path></svg>
                    `; // Sun icon
                } else {
                    localStorage.setItem('theme', 'light');
                    themeToggleBtn.innerHTML = `
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.292 8.614a5.955 5.955 0 01-8.23-5.228 5.955 5.955 0 00-7.394 6.786 5.956 5.956 0 006.786 7.394 5.955 5.955 0 005.228-8.23z"></path></svg>
                    `; // Moon icon
                }
            });
        }
    });
</script>