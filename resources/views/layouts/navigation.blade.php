<div class="nav-section-label">Workspace</div>
<a href="{{ route('admin.absensi.index') }}"
   @class(['nav-item', 'active' => request()->routeIs('admin.absensi.index', 'admin.absensi.freelance')])>
    <span class="nav-icon"><i class="fas fa-house"></i></span>
    <span>Dashboard</span>
</a>

@auth
    @if (Auth::user()->role === 'hrd')
        <div class="nav-section-label">Payroll &amp; approvals</div>
        <a href="{{ route('admin.payroll.index') }}"
           @class(['nav-item', 'active' => request()->routeIs('admin.payroll.*')])>
            <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            <span>Payroll Freelance</span>
        </a>

        <a href="{{ route('admin.hr-requests.index') }}"
           @class(['nav-item', 'active' => request()->routeIs('admin.hr-requests.*')])>
            <span class="nav-icon"><i class="fas fa-file-circle-check"></i></span>
            <span>Pengajuan HR</span>
        </a>

        <a href="{{ route('admin.absensi.approval.hrd') }}"
           @class(['nav-item', 'active' => request()->routeIs('admin.absensi.approval.hrd') && request('section') !== 'employees'])>
            <span class="nav-icon"><i class="fas fa-user-check"></i></span>
            <span>HRD Approval</span>
        </a>
    @endif
@endauth

@auth
    @if (Auth::user()->isAnyAdmin())
        <div class="nav-section-label">Manajemen</div>
        <a href="{{ route('admin.holidays.index') }}"
           @class(['nav-item', 'active' => request()->routeIs('admin.holidays.*')])>
            <span class="nav-icon"><i class="fas fa-calendar-day"></i></span>
            <span>Hari Libur</span>
        </a>
        <a href="{{ route('admin.master-data.index', ['resource' => 'departments']) }}"
           @class(['nav-item', 'active' => request()->routeIs('admin.master-data.*')])>
            <span class="nav-icon"><i class="fas fa-sitemap"></i></span>
            <span>Master Data HR</span>
        </a>

        @if (Auth::user()->isSuperAdmin())
            <a href="{{ route('admin.activity_logs.index') }}"
               @class(['nav-item', 'active' => request()->routeIs('admin.activity_logs.*')])>
                <span class="nav-icon"><i class="fas fa-history"></i></span>
                <span>Log Aktivitas</span>
            </a>
        @endif
    @endif
@endauth

<div class="nav-section-label">Laporan</div>
<a href="{{ route('admin.absensi.recap') }}"
   @class(['nav-item', 'active' => request()->routeIs('admin.absensi.recap')])>
    <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
    <span>Rekap Bulanan</span>
</a>
