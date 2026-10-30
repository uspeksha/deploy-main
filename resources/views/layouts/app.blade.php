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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
        
        .bg-glow {
            position: fixed;
            top: 0;
            right: 0;
            width: 50%;
            height: 50%;
            background: radial-gradient(circle at 70% 30%, rgba(16, 185, 129, 0.08), transparent 70%);
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>

<body class="font-sans antialiased text-slate-900 dark:text-slate-100 min-h-screen">
    <div class="bg-glow"></div>
    
    <div class="flex min-h-screen">
        <!-- Floating Sidebar -->
        <aside class="premium-sidebar lg:translate-x-0 transition-transform duration-300"
               :class="sidebarOpen ? 'translate-x-4' : '-translate-x-full lg:translate-x-0'">
            <div class="flex flex-col h-full py-6">
                <!-- Brand -->
                <div class="px-8 mb-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 group-hover:rotate-6 transition-transform">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tight">Presensi<span class="text-emerald-500">Pro</span></span>
                    </a>
                </div>

                <!-- Navigation -->
                <nav aria-label="Menu utama" class="flex-1 space-y-1 overflow-y-auto custom-scrollbar">
                    @include('layouts.navigation')
                </nav>

                <!-- Profile Mini -->
                <div class="px-4 mt-auto">
                    <div class="lucent-panel rounded-2xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ Auth::user()->role }}</p>
                        </div>
                        <button @click="darkMode = !darkMode" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="fas" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-slate-400'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="min-w-0 flex-1 lg:ml-80 p-4 lg:p-8">
            <!-- Top Header -->
            <header class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4 lg:hidden">
                    <button @click="sidebarOpen = !sidebarOpen" class="w-10 h-10 lucent-panel rounded-xl flex items-center justify-center">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                @if (isset($header))
                    <div class="flex-1">
                        {{ $header }}
                    </div>
                @endif

                <div class="flex items-center gap-4">
                    <!-- Global Actions -->
                    <div class="hidden sm:flex items-center gap-2">
                        <span class="text-xs font-medium px-3 py-1 bg-emerald-500/10 text-emerald-600 rounded-full dark:bg-emerald-500/20 dark:text-emerald-400">
                            {{ now()->translatedFormat('d F Y') }}
                        </span>
                    </div>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-10 h-10 lucent-panel rounded-xl flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-500/30 transition-colors">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Content -->
            <div class="min-w-0 animate-fade-in-up">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });
    </script>
</body>
</html>