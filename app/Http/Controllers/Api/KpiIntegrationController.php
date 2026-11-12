<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use App\Services\AttendanceAlfaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KpiIntegrationController extends Controller
{
    public function getAttendanceSummary(Request $request)
    {
        // Validasi parameter bulan dan tahun (wajib), employee_id (opsional)
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'employee_id' => 'nullable|exists:users,id',
        ]);

        $monthStart = Carbon::createFromDate((int) $request->tahun, (int) $request->bulan, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $alfaThrough = today()->lt($monthEnd) ? today() : $monthEnd;
        $query = Absensi::query()->whereBetween('check_in_at', [$monthStart, $monthEnd]);

        // Filter jika spesifik untuk 1 karyawan
        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        $summary = $query->select('user_id')
            ->selectRaw(
                'SUM(CASE WHEN LOWER(status) = ? THEN 1 ELSE 0 END) as total_hadir',
                ['hadir'],
            )
            ->selectRaw(
                'SUM(CASE WHEN LOWER(status) = ? AND late_minutes > 0 THEN 1 ELSE 0 END) as total_telat',
                ['hadir'],
            )
            ->selectRaw(
                'SUM(CASE WHEN LOWER(status) = ? THEN 1 ELSE 0 END) as total_izin',
                ['izin'],
            )
            ->selectRaw(
                'SUM(CASE WHEN LOWER(status) = ? THEN 1 ELSE 0 END) as total_sakit',
                ['sakit'],
            )
            ->selectRaw(
                'SUM(CASE WHEN LOWER(status) = ? THEN 1 ELSE 0 END) as total_alfa',
                ['alfa'],
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $userIds = $summary->keys();

        if ($request->filled('employee_id')) {
            $userIds = collect([(int) $request->employee_id]);
        } else {
            $userIds = $userIds->merge(
                User::query()
                    ->where('registration_status', 'approved')
                    ->where('is_active', true)
                    ->pluck('id')
            )->unique()->values();
        }

        $alfaService = app(AttendanceAlfaService::class);
        $workDates = $alfaService->workDatesBetween($monthStart, $alfaThrough);
        $attendanceDates = $alfaService->confirmedAttendanceDatesByUser($userIds, $monthStart, $alfaThrough);

        $summary = $userIds->map(function (int $userId) use ($summary, $workDates, $attendanceDates, $alfaService): array {
            $attendanceSummary = $summary->get($userId);
            $attendedWorkDates = $attendanceDates->get($userId, []);

            return [
                'user_id' => $userId,
                'total_hadir' => (int) ($attendanceSummary->total_hadir ?? 0),
                'total_telat' => (int) ($attendanceSummary->total_telat ?? 0),
                'total_izin' => (int) ($attendanceSummary->total_izin ?? 0),
                'total_sakit' => (int) ($attendanceSummary->total_sakit ?? 0),
                'total_alfa' => count($alfaService->missingWorkDates($workDates, $attendedWorkDates)),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Menyediakan daftar karyawan aktif beserta kode profilnya untuk kebutuhan sinkronisasi KPI
     */
    public function getActiveEmployees(Request $request)
    {
        // Mengambil semua user dengan informasi profil mereka langsung dari tabel users
        $employees = User::where('registration_status', 'approved')
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'id_karyawan as code')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $employees,
        ]);
    }
}
