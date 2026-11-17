<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">
                Ruang kerja
            </p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-[28px]">
                Dashboard
            </h1>
        </div>
    </x-slot>

    <div class="space-y-7">
        <section class="relative overflow-hidden rounded-xl border border-[#294453] bg-[#1c3443] p-5 text-white md:p-7"
                 x-data="{
                    hour: new Date().getHours(),
                    get greeting() {
                        if (this.hour < 11) return 'Selamat Pagi';
                        if (this.hour < 15) return 'Selamat Siang';
                        if (this.hour < 19) return 'Selamat Sore';
                        return 'Selamat Malam';
                    }
                 }">
            <div class="absolute inset-y-0 right-0 hidden w-2/5 border-l border-white/5 bg-white/[0.025] md:block"></div>
            <div class="relative flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div class="min-w-0">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-[#9cc4d0]" x-text="greeting"></p>
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Halo, {{ Auth::user()->name }}
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                        Kelola absensi, pengajuan, payroll, dan laporan dari satu tempat.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <a href="{{ route('admin.absensi.recap') }}" class="btn-premium">
                        <span>Buka rekap</span>
                        <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Akses cepat</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Menu yang sering digunakan dalam pengelolaan harian.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <a href="{{ route('admin.absensi.recap') }}" class="admin-quick-link">
                    <span class="admin-quick-link-icon">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Rekap Bulanan</span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">Ringkasan absensi seluruh karyawan.</span>
                    </span>
                </a>

            @if (Auth::user()->isAnyAdmin())
                <a href="{{ route('admin.holidays.index') }}" class="admin-quick-link">
                    <span class="admin-quick-link-icon">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Hari Libur</span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">Kelola kalender hari libur.</span>
                    </span>
                </a>
            @endif

            @if (Auth::user()->role === 'hrd')
                <a href="{{ route('admin.absensi.approval.hrd') }}" class="admin-quick-link">
                    <span class="admin-quick-link-icon">
                        <i class="fas fa-user-check"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">HRD Approval</span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">Tinjau pengajuan absensi karyawan.</span>
                    </span>
                </a>

                <a href="{{ route('admin.payroll.index') }}" class="admin-quick-link">
                    <span class="admin-quick-link-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Payroll Freelance</span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">Kelola perhitungan gaji dan insentif.</span>
                    </span>
                </a>
            @endif

            @if (Auth::user()->isSuperAdmin())
                <a href="{{ route('admin.activity_logs.index') }}" class="admin-quick-link">
                    <span class="admin-quick-link-icon">
                        <i class="fas fa-history"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Log Aktivitas</span>
                        <span class="mt-1 block text-sm leading-5 text-slate-500 dark:text-slate-400">Pantau aktivitas pengguna di sistem.</span>
                    </span>
                </a>
            @endif
            </div>
        </section>
    </div>
</x-app-layout>
