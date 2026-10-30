{{-- Dashboard --}}
<a href="{{ route('admin.absensi.index') }}"
   class="nav-item {{ request()->routeIs('admin.absensi.index', 'admin.absensi.freelance') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-grid-2"></i></div>
    <span>Dashboard</span>
</a>

@auth
@if (Auth::user()->role === 'hrd')
<div class="px-6 pt-4 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
    Payroll & Finance
</div>
<a href="{{ route('admin.payroll.index') }}"
   class="nav-item {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></div>
    <span>Payroll Freelance</span>
</a>

<div class="px-6 pt-4 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
    Approvals
</div>
<a href="{{ route('admin.absensi.approval.hrd') }}"
   class="nav-item {{ request()->routeIs('admin.absensi.approval.hrd') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-user-check"></i></div>
    <span>HRD Approval</span>
</a>
@endif
@endauth

@auth
@if (Auth::user()->isAnyAdmin())
<div class="px-6 pt-4 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
    Management
</div>
<a href="{{ route('admin.holidays.index') }}"
   class="nav-item {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-calendar-day"></i></div>
    <span>Hari Libur</span>
</a>

@if(Auth::user()->isSuperAdmin())
<a href="{{ route('admin.activity_logs.index') }}"
   class="nav-item {{ request()->routeIs('admin.activity_logs.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-history"></i></div>
    <span>Log Aktivitas</span>
</a>
@endif
@endif
@endauth

<div class="px-6 pt-4 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
    Reports
</div>
<a href="{{ route('admin.absensi.recap') }}"
   class="nav-item {{ request()->routeIs('admin.absensi.recap') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
    <span>Rekap Bulanan</span>
</a>
