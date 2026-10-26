<?php

use App\Http\Controllers\Admin\AbsensiAdminController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth',
    'admin',
    \App\Http\Middleware\ActivityLogMiddleware::class,
    'super_admin',
])->prefix('admin/payroll')->name('admin.payroll.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('index');
    Route::post('/periods', [\App\Http\Controllers\Admin\PayrollController::class, 'store'])->name('store');
    Route::post('/periods/{period}/submit', [\App\Http\Controllers\Admin\PayrollController::class, 'submit'])->name('submit');
    Route::post('/periods/{period}/approve', [\App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('approve');
    Route::post('/periods/{period}/paid', [\App\Http\Controllers\Admin\PayrollController::class, 'markPaid'])->name('paid');
    Route::post('/periods/{period}/cancel', [\App\Http\Controllers\Admin\PayrollController::class, 'cancel'])->name('cancel');
    Route::delete('/periods/{period}', [\App\Http\Controllers\Admin\PayrollController::class, 'destroy'])->name('destroy');
    Route::post('/periods/{period}/corrections', [\App\Http\Controllers\Admin\PayrollController::class, 'recordCorrection'])->name('corrections.store');
    Route::post('/periods/{period}/corrections/{correction}/reconcile', [\App\Http\Controllers\Admin\PayrollController::class, 'reconcileCorrection'])->name('corrections.reconcile');
    Route::get('/periods/{period}/export', [\App\Http\Controllers\Admin\PayrollController::class, 'export'])->name('export');
    Route::get('/periods/{period}/items/{item}/slip/preview', [\App\Http\Controllers\Admin\PayrollController::class, 'previewSlip'])->name('slip.preview');
    Route::get('/periods/{period}/items/{item}/slip', [\App\Http\Controllers\Admin\PayrollController::class, 'slip'])->name('slip');
});


Route::get('/dashboard', function () {
    return redirect()->route('admin.absensi.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin', \App\Http\Middleware\ActivityLogMiddleware::class])->prefix('admin')->group(function () {

    Route::prefix('absensi')->group(function () {

        // ✅ Dashboard & List
        Route::get('/', [AbsensiAdminController::class, 'index'])->name('admin.absensi.index');
        Route::get('/freelance', [AbsensiAdminController::class, 'indexFreelance'])->name('admin.absensi.freelance');
        
        // 🛠️ EDIT JAM MASUK (Tadi salah nama jadi edit-checkin)
        Route::post('/{id}/update-checkin', [AbsensiAdminController::class, 'updateCheckIn'])
            ->name('admin.absensi.updateCheckIn');
        Route::post('/{id}/update-checkout', [AbsensiAdminController::class, 'updateCheckOut'])
            ->name('admin.absensi.updateCheckOut');

        // Detail absensi per user
        Route::get('/user/{user}', [AbsensiAdminController::class, 'show'])->name('admin.absensi.user')->middleware('block.pkl');

        // Export data
        Route::get('/user/{id}/export', [AbsensiAdminController::class, 'exportUser'])->name('admin.absensi.user.export')->middleware('block.pkl');
        Route::get('/recap', [AbsensiAdminController::class, 'recap'])->name('admin.absensi.recap');
        Route::get('/recap/export', [AbsensiAdminController::class, 'exportRecap'])->name('admin.absensi.recap.export')->middleware('block.pkl');
        
        Route::post('/bulk-export-detail', [AbsensiAdminController::class, 'bulkExportDetail'])->name('admin.absensi.bulk-export-detail')->middleware('block.pkl');
        Route::post('/bulk-export-simple', [AbsensiAdminController::class, 'bulkExportSimple'])->name('admin.absensi.bulk-export-simple');
        Route::get('/bulk-export-all-simple', [AbsensiAdminController::class, 'bulkExportAllSimple'])->name('admin.absensi.bulk-export-all-simple')->middleware('block.pkl');

        // 🔒 Khusus Super Admin
        Route::middleware('super_admin')->group(function () {
            Route::get('/user/{user}/export-slip', [AbsensiAdminController::class, 'exportSlipGaji'])->name('admin.absensi.user.export-slip')->middleware('block.pkl');
            Route::get('/user/{id}/export-slip-pdf', [AbsensiAdminController::class, 'exportSlipGajiPdf'])->name('admin.absensi.user.export-slip-pdf')->middleware('block.pkl');
            Route::post('/bulk-export-pdf', [AbsensiAdminController::class, 'bulkExportPdf'])->name('admin.absensi.bulk-export-pdf')->middleware('block.pkl');
            Route::get('/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.activity_logs.index');
        });
    });

    // HARI LIBUR
    Route::prefix('holidays')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HolidayController::class, 'index'])->name('admin.holidays.index');
        Route::get('/create', [\App\Http\Controllers\Admin\HolidayController::class, 'create'])->name('admin.holidays.create');
        Route::post('/', [\App\Http\Controllers\Admin\HolidayController::class, 'store'])->name('admin.holidays.store');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\HolidayController::class, 'destroy'])->name('admin.holidays.destroy');
        Route::post('/sync', [\App\Http\Controllers\Admin\HolidayController::class, 'sync'])->name('admin.holidays.sync');
    });

    // IZIN KELUAR
    Route::prefix('izin-keluar')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\IzinKeluarAdminController::class, 'index'])->name('admin.izin-keluar.index');
    });

    // APPROVAL ABSENSI
    Route::prefix('approval')->group(function () {
        Route::get('/hrd', [ApprovalController::class, 'hrd'])->name('admin.absensi.approval.hrd');
        Route::redirect('/supervisor', '/admin/approval/hrd');
        Route::redirect('/manager', '/admin/approval/hrd');
        Route::redirect('/hrga', '/admin/approval/hrd');

        Route::post('/{absensi}/{action}', [ApprovalController::class, 'handleAction'])
            ->name('admin.absensi.approval.action')
            ->where('action', 'approve|reject');

        Route::post('/bulk-approve', [ApprovalController::class, 'handleBulkAction'])->name('admin.absensi.approval.bulk-action');

        Route::post('/scheduled-lembur/{id}/{action}', [ApprovalController::class, 'handleScheduledLemburAction'])
            ->name('admin.absensi.approval.scheduled-lembur.action')
            ->where('action', 'approve|reject');
        Route::post('/izin-keluar/{id}/{action}', [ApprovalController::class, 'handleIzinKeluarAction'])
            ->name('admin.absensi.approval.izin-keluar.action')
            ->where('action', 'approve|reject');
        Route::post('/employees', [ApprovalController::class, 'createEmployeeAccount'])
            ->name('admin.absensi.approval.employees.store');
        Route::post('/employees/{user}/resend-access', [ApprovalController::class, 'resendEmployeeCredentials'])
            ->name('admin.absensi.approval.employees.resend');
    });

    // --- ABSENSI MANAGEMENT (Adjustment Gaji) ---
    Route::post('/absensi/{absensi}/adjustment', [AbsensiAdminController::class, 'updateAdjustment'])
        ->name('admin.absensi.update-adjustment');
        
});

require __DIR__ . '/auth.php';