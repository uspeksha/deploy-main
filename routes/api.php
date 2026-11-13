<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AttendanceGeofenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeHrRequestController;
use App\Http\Controllers\Api\EmployeePayrollController;
use App\Http\Controllers\Api\FcmDeviceController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\KpiIntegrationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserPasswordController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Seluruh endpoint API di sini telah diproteksi dengan Rate Limiting
| untuk mencegah serangan Brute Force dan DDoS.
*/

Route::middleware('throttle:api')->prefix('v1')->group(function () {

    // ==========================================
    // KPI INTEGRATION ROUTE (Protected by API Key)
    // ==========================================
    Route::middleware(['api.kpi'])->group(function () {
        Route::get('/kpi-attendance-summary', [KpiIntegrationController::class, 'getAttendanceSummary']);
        Route::get('/kpi-active-employees', [KpiIntegrationController::class, 'getActiveEmployees']);
    });

    // ==========================================
    // PUBLIC ROUTES (Tanpa Auth)
    // ==========================================

    // ✅ LOGIN - Rate limit: 5 attempts per menit (anti brute force)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // ✅ FORGOT PASSWORD - Rate limit ketat untuk prevent spam
    Route::prefix('forgot-password')->group(function () {
        Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp'])
            ->middleware('throttle:3,1');  // Max 3x/menit

        Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
            ->middleware('throttle:5,1');  // Max 5x/menit

        Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
            ->middleware('throttle:3,1');  // Max 3x/menit
    });

    // ==========================================
    // PROTECTED ROUTES (Harus Login)
    // ==========================================

    Route::middleware(['auth:sanctum', 'active.account'])->group(function () {

        // ========== AUTH & PROFILE ==========
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/user/fcm-token', [FcmDeviceController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::delete('/user/fcm-token', [FcmDeviceController::class, 'destroy'])
            ->middleware('throttle:10,1');
        Route::get('/attendance/geofence', [AttendanceGeofenceController::class, 'show']);
        Route::get('/payroll/slips', [EmployeePayrollController::class, 'index']);
        Route::get('/payroll/slips/{item}/download', [EmployeePayrollController::class, 'download'])
            ->whereNumber('item')
            ->middleware('throttle:10,1');
        Route::get('/hr-requests', [EmployeeHrRequestController::class, 'index']);
        Route::post('/hr-requests/reimbursements', [EmployeeHrRequestController::class, 'storeReimbursement'])
            ->middleware('throttle:5,1');
        Route::post('/hr-requests/loans', [EmployeeHrRequestController::class, 'storeLoan'])
            ->middleware('throttle:5,1');
        Route::put('/user/password', [UserPasswordController::class, 'update'])
            ->middleware('throttle:10,1');

        // Profile routes
        Route::get('/user/profile', [UserProfileController::class, 'show']);
        Route::match(['post', 'put'], '/user/profile', [UserProfileController::class, 'update'])
            ->middleware('throttle:10,1');  // ✅ Max 10x/menit untuk update profile

        Route::match(['post', 'put'], '/user/update-employment-status', [AuthController::class, 'updateEmploymentStatus'])
            ->middleware('throttle:5,1');  // ✅ Max 5x/menit untuk update status

        // ========== ABSENSI - POST ENDPOINTS (dengan rate limit) ==========
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/absensi/masuk', [AbsensiController::class, 'absenMasuk']);
            Route::post('/absensi/pulang', [AbsensiController::class, 'absenPulang']);
            Route::post('/absensi/lembur', [AbsensiController::class, 'absenLembur']);
            Route::post('/absensi/submit-lembur', [AbsensiController::class, 'submitLembur']);
            Route::post('/absensi/sakit', [AbsensiController::class, 'absenSakit']);
            Route::post('/absensi/izin', [AbsensiController::class, 'absenIzin']);
            Route::post('/absensi/telat', [AbsensiController::class, 'pengajuanTelat']);
        });

        // ========== ABSENSI - RESUBMIT ENDPOINTS (rate limit lebih ketat) ==========
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/absensi/sakit/{id}/resubmit', [AbsensiController::class, 'resubmitSakit']);
            Route::post('/absensi/izin/{id}/resubmit', [AbsensiController::class, 'resubmitIzin']);
            Route::post('/absensi/lembur/{id}/resubmit', [AbsensiController::class, 'resubmitLembur']);
            Route::post('/absensi/resubmit/{id}', [AbsensiController::class, 'resubmit']);
        });

        // ========== ABSENSI - GET ENDPOINTS ==========
        Route::get('/absensi/me', [AbsensiController::class, 'meAbsensi']);
        Route::get('/absensi/detail/{id}', [AbsensiController::class, 'getDetailAbsensi']);

        // ========== NOTIFICATIONS ==========
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('throttle:30,1');

        // ✅ Notification store - KHUSUS ADMIN!
        Route::post('/notifications', [NotificationController::class, 'store'])
            ->middleware('throttle:20,1');
    });

});
