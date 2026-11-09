<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark', sidebarOpen: false }"
      x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="font-sans antialiased text-slate-900 dark:text-slate-100 min-h-screen">
    <div class="flex min-h-screen">
        <button x-cloak x-show="sidebarOpen" @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
                aria-label="Tutup menu"></button>

        <aside class="premium-sidebar"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-full flex-col py-5">
                <div class="mb-6 px-5">
                    <a href="{{ route('dashboard') }}" class="inline-flex rounded-lg focus-visible:outline-white">
                        <img src="{{ asset('images/followme-horizontal.png') }}" alt="Followme Indonesia" class="h-auto w-44 max-w-full rounded-md object-contain">
                    </a>
                </div>

                <nav aria-label="Menu utama" class="flex-1 overflow-y-auto custom-scrollbar">
                    @include('layouts.navigation')
                </nav>

                <div class="mt-auto px-3 pt-4">
                    <div class="flex items-center gap-3 rounded-xl border border-[#dce4e9] bg-[#f3f6f8] p-3 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 font-bold text-white">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ Auth::user()->name }}</p>
                            <p class="truncate text-xs capitalize text-slate-500 dark:text-slate-300">{{ Auth::user()->role }}</p>
                        </div>
                        <button @click="darkMode = !darkMode" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-white hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white" aria-label="Ganti tema">
                            <i class="fas" :class="darkMode ? 'fa-sun text-amber-300' : 'fa-moon'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1 lg:ml-72 px-4 py-5 md:px-7 lg:px-9">
            <header class="admin-page-header mb-6 flex min-h-12 flex-wrap items-center justify-between gap-4 border-b border-[#dce4e9] pb-5 dark:border-slate-700">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#dce4e9] bg-white text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 lg:hidden" aria-label="Buka menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    @if (isset($header))
                        <div class="min-w-0 flex-1">
                            {{ $header }}
                        </div>
                    @endif
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2">
                        <span class="rounded-lg border border-[#dce4e9] bg-white px-3 py-2 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ now()->translatedFormat('d F Y') }}
                        </span>
                    </div>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex h-10 items-center gap-2 rounded-lg border border-[#dce4e9] bg-white px-3 text-sm font-medium text-slate-600 transition-colors hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-rose-900 dark:hover:bg-rose-950/30 dark:hover:text-rose-300">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                            <span class="hidden md:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <div class="admin-page-content mx-auto min-w-0 max-w-[1600px]">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>