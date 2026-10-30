<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Dashboard <span class="text-emerald-500">Overview</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Selamat datang kembali, mari pantau aktivitas hari ini.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Premium Hero Card --}}
        <section class="lucent-panel rounded-3xl p-6 md:p-8 relative overflow-hidden group"
                 x-data="{ 
                    hour: new Date().getHours(),
                    get greeting() {
                        if (this.hour < 11) return 'Selamat Pagi';
                        if (this.hour < 15) return 'Selamat Siang';
                        if (this.hour < 19) return 'Selamat Sore';
                        return 'Selamat Malam';
                    }
                 }">
            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl -mr-32 -mt-32 group-hover:bg-emerald-500/20 transition-colors"></div>
            
            <div class="relative flex flex-col md:flex-row items-center gap-6 md:gap-8">
                <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white text-3xl md:text-4xl shadow-xl shadow-emerald-500/20 animate-float shrink-0">
                    👋
                </div>
                
                <div class="flex-1 text-center md:text-left">
                    <p class="text-emerald-600 dark:text-emerald-400 font-bold tracking-wider uppercase text-xs mb-1" x-text="greeting"></p>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mb-2">
                        Halo, {{ Auth::user()->name }}!
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xl">
                        Senang melihat Anda kembali. Semua sistem berjalan normal. Anda dapat mengelola absensi, payroll, dan laporan melalui menu di sebelah kiri.
                    </p>
                </div>

                <div class="flex gap-3 shrink-0">
                    <a href="{{ route('admin.absensi.recap') }}" class="btn-premium py-2.5 px-5 text-sm">
                        <span>Lihat Rekap</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>

        {{-- Bento Grid Quick Access --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            {{-- Rekap Card --}}
            <a href="{{ route('admin.absensi.recap') }}" class="lucent-panel rounded-2xl p-6 group hover:-translate-y-1 transition-all">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-4 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                    <i class="fas fa-calendar-check text-xl"></i>
                </div>
                <h3 class="text-lg font-bold mb-1">Rekap Bulanan</h3>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400">Lihat ringkasan absensi seluruh karyawan periode ini.</p>
            </a>

            @if (Auth::user()->isAnyAdmin())
                {{-- Holidays Card --}}
                <a href="{{ route('admin.holidays.index') }}" class="lucent-panel rounded-2xl p-6 group hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center mb-4 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <i class="fas fa-calendar-day text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-1">Hari Libur</h3>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400">Atur hari libur nasional dan kebijakan libur perusahaan.</p>
                </a>
            @endif

            @if (Auth::user()->role === 'hrd')
                {{-- Approval Card --}}
                <a href="{{ route('admin.absensi.approval.hrd') }}" class="lucent-panel rounded-2xl p-6 group hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center mb-4 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-1">HRD Approval</h3>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400">Tinjau dan proses pengajuan absensi dari karyawan.</p>
                </a>

                {{-- Payroll Card --}}
                <a href="{{ route('admin.payroll.index') }}" class="lucent-panel rounded-2xl p-6 group hover:-translate-y-1 transition-all lg:col-span-2">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-teal-500/10 text-teal-600 flex items-center justify-center group-hover:bg-teal-500 group-hover:text-white transition-colors shrink-0">
                            <i class="fas fa-file-invoice-dollar text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold mb-1">Payroll Freelance</h3>
                            <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400">Kelola perhitungan gaji dan insentif untuk karyawan freelance secara otomatis.</p>
                        </div>
                    </div>
                </a>
            @endif

            @if (Auth::user()->isSuperAdmin())
                {{-- Logs Card --}}
                <a href="{{ route('admin.activity_logs.index') }}" class="lucent-panel rounded-2xl p-6 group hover:-translate-y-1 transition-all border-rose-500/10">
                    <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center mb-4 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                        <i class="fas fa-history text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-1">Log Aktivitas</h3>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400">Pantau seluruh jejak aktivitas pengguna di dalam sistem.</p>
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
