<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiResource;
use App\Models\Absensi;
use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\Notification;
use App\Services\AttendanceGeofence;
use App\Services\FreelancePayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AbsensiController extends Controller
{
    private $workflowTemplates = [
        'produksi' => ['hrd' => 'pending'],
        'office' => ['hrd' => 'pending'],
        'organik' => ['hrd' => 'pending'],
    ];

    private function determineResubmitLevel($rejectedBy, $workflowStatus)
    {
        return 1;
    }

    private function resetWorkflowFromLevel($workflow, $startLevel, $employment)
    {
        return ['hrd' => 'pending'];
    }

    private function calculateLateMinutes($checkInTime): int
    {
        return FreelancePayrollCalculator::lateMinutesFromCheckIn(Carbon::parse($checkInTime));
    }

    public function absenMasuk(Request $request, AttendanceGeofence $geofence)
    {
        try {
            $request->validate([
                'foto' => 'required|image|max:2048',
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'accuracy_meters' => 'sometimes|nullable|numeric|gt:0|max:100',
                'status' => 'required|in:hadir,sakit,izin',
                'is_mocked' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $kategori = $user->kategori_absensi;

            if ($request->boolean('is_mocked')) {
                return response()->json([
                    'message' => 'Absensi ditolak karena perangkat melaporkan lokasi palsu.',
                ], 403);
            }

            if ($request->status === 'hadir') {
                $distance = $geofence->distanceFromWorkplace(
                    $user,
                    (float) $request->input('lat'),
                    (float) $request->input('lng'),
                );

                if (! $geofence->isWithinAllowedRadius(
                    $distance,
                    (float) ($request->input('accuracy_meters') ?? 0),
                )) {
                    return response()->json([
                        'message' => 'Absensi ditolak. Anda berada di luar area lokasi kerja yang diizinkan.',
                        'distance_meters' => (int) round($distance),
                        'allowed_radius_meters' => (int) config('attendance.geofence.radius_meters'),
                    ], 403);
                }
            }

            $today = Carbon::today();

            if ($request->status === 'hadir' && Holiday::isHoliday($today)) {
                $holiday = Holiday::where('holiday_date', $today->toDateString())->first();

                return response()->json([
                    'message' => "Hari ini adalah hari libur ({$holiday->name}). Anda tidak dapat melakukan absen reguler. Silakan gunakan menu Absen Lembur jika Anda ditugaskan masuk.",
                ], 403);
            }

            $existingAbsensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->first();

            if ($existingAbsensi) {

                if (in_array($existingAbsensi->status_approval, ['approved', 'pending'])
                    && in_array($existingAbsensi->tipe, ['sakit', 'izin'])) {

                    $statusText = $existingAbsensi->status_approval == 'approved' ? 'Disetujui' : 'Sedang Diproses';

                    return response()->json([
                        'message' => "Anda sudah mengajukan {$existingAbsensi->tipe} ({$statusText}). Tidak perlu absen masuk.",
                        'tipe' => $existingAbsensi->tipe,
                        'status_approval' => $existingAbsensi->status_approval,
                    ], 409);
                }

                if ($existingAbsensi->status_approval == 'rejected'
                    && in_array($existingAbsensi->tipe, ['sakit', 'izin'])) {

                    $fotoPath = $request->file('foto')->store('absensi_foto', 'public');

                    $lokasiMasuk = $request->lat.','.$request->lng;
                    $checkInTime = now();
                    $isWeekend = Absensi::isWeekend($checkInTime);
                    $lateMinutes = 0;

                    if ($request->status === 'hadir') {
                        $lateMinutes = $this->calculateLateMinutes($checkInTime);
                    }

                    $salaryData = Absensi::calculateSalary(
                        $lateMinutes,
                        'hadir',
                        null,
                        $isWeekend,
                        $checkInTime,
                        null,
                        $kategori,
                        false,
                        $user->dailyRateAt($checkInTime),
                    );

                    $existingAbsensi->update([
                        'check_in_at' => $checkInTime,
                        'foto_masuk' => $fotoPath,
                        'lokasi_masuk' => $lokasiMasuk,
                        'status' => 'hadir',
                        'tipe' => null,
                        'status_approval' => 'approved',
                        'keterangan_izin_sakit' => null,
                        'file_bukti' => null,
                        'late_minutes' => $lateMinutes,
                        'rounded_late_minutes' => $salaryData['rounded_late_minutes'],
                        'base_salary' => $salaryData['base_salary'],
                        'late_penalty' => $salaryData['late_penalty'],
                        'final_salary' => $salaryData['final_salary'],
                        'is_weekend_overtime' => false,
                        'is_mocked' => $request->boolean('is_mocked'),
                    ]);

                    $existingAbsensi->load('user');

                    $existingAbsensi->foto_masuk_url = Storage::url($existingAbsensi->foto_masuk);

                    ActivityLog::log('Absen Masuk (Update)', "User: {$user->name}", 'Izin ditolak diubah jadi hadir.');

                    return response()->json([

                        'message' => 'Absensi berhasil! Izin yang ditolak otomatis diubah jadi hadir.'
                                    .($isWeekend ? ' (Weekend - Gaji 2x Lipat)' : ''),
                        'data' => $existingAbsensi,
                    ], 200);
                }

                if ($existingAbsensi->status == 'hadir' && $existingAbsensi->check_in_at) {
                    return response()->json([
                        'message' => 'Anda sudah absen masuk hari ini.',
                    ], 409);
                }
            }

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $lokasiMasuk = $request->lat.','.$request->lng;

            $checkInTime = now();
            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $isWeekend = Absensi::isWeekend($checkInTime);
            $lateMinutes = 0;

            if ($request->status === 'hadir') {
                if ($kategori !== 'organik' && $kategori !== 'magang') {
                    $lateMinutes = $this->calculateLateMinutes($checkInTime);
                }
            }

            $salaryData = Absensi::calculateSalary(
                $lateMinutes,
                $request->status,
                null,
                $isWeekend,
                $checkInTime,
                null,
                $kategori,
                false,
                $user->dailyRateAt($checkInTime),
            );

            $absensi = Absensi::create([
                'user_id' => $user->id,
                'check_in_at' => $checkInTime,
                'foto_masuk' => $fotoPath,
                'lokasi_masuk' => $lokasiMasuk,
                'status' => $request->status,
                'tipe' => ($request->status == 'hadir') ? null : $request->status,
                'status_approval' => ($request->status == 'hadir') ? 'approved' : 'pending',
                'current_approval_level' => 1,
                'workflow_status' => $workflow,
                'late_minutes' => $lateMinutes,
                'rounded_late_minutes' => $salaryData['rounded_late_minutes'],
                'base_salary' => $salaryData['base_salary'],
                'late_penalty' => $salaryData['late_penalty'],
                'final_salary' => $salaryData['final_salary'],
                'is_weekend_overtime' => false,
            ]);

            $absensi->load('user');

            $absensi->foto_masuk_url = Storage::url($absensi->foto_masuk);

            ActivityLog::log('Absen Masuk', "User: {$user->name}", "Melakukan absen masuk status: {$request->status}");

            return response()->json([

                'message' => 'Absensi masuk berhasil'.($isWeekend ? ' (Weekend - Gaji 2x Lipat)' : ''),
                'data' => $absensi,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        }
    }

    public function absenPulang(Request $request, AttendanceGeofence $geofence)
    {
        try {
            $request->validate([
                'foto' => 'required|image|max:2048',
                'foto_2' => 'nullable|image|max:2048',
                'foto_3' => 'nullable|image|max:2048',
                'foto_4' => 'nullable|image|max:2048',
                'foto_5' => 'nullable|image|max:2048',
                'foto_6' => 'nullable|image|max:2048',
                'keterangan_goals' => 'nullable|string|max:2000',
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'accuracy_meters' => 'sometimes|nullable|numeric|gt:0|max:100',
                'tipe' => 'nullable|in:lembur,cuti,sakit,izin',
                'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'nullable|string|max:500',
                'is_mocked' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            if ($request->boolean('is_mocked')) {
                return response()->json([
                    'message' => 'Absensi pulang ditolak karena perangkat melaporkan lokasi palsu.',
                ], 403);
            }

            $distance = $geofence->distanceFromWorkplace(
                $user,
                (float) $request->input('lat'),
                (float) $request->input('lng'),
            );

            if (! $geofence->isWithinAllowedRadius(
                $distance,
                (float) ($request->input('accuracy_meters') ?? 0),
            )) {
                return response()->json([
                    'message' => 'Absen pulang ditolak. Anda berada di luar area lokasi kerja yang diizinkan.',
                    'distance_meters' => (int) round($distance),
                    'allowed_radius_meters' => (int) config('attendance.geofence.radius_meters'),
                ], 403);
            }

            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if (! $absensi) {
                return response()->json(['message' => 'Anda belum absen masuk hari ini.'], 400);
            }

            if ($absensi->tipe === 'sakit' || $absensi->tipe === 'izin') {
                return response()->json([
                    'message' => 'Anda tidak perlu absen pulang. Anda telah mengajukan '.ucfirst($absensi->tipe).' hari ini.',
                    'tipe' => $absensi->tipe,
                ], 400);
            }

            if ($absensi->check_out_at) {
                return response()->json(['message' => 'Anda sudah absen pulang hari ini.'], 409);
            }

            $checkInTimeParsed = Carbon::parse($absensi->check_in_at);

            $isKurang8Jam = false;

            // ATURAN WAKTU KERJA
            if ($request->tipe !== 'sakit' && $request->tipe !== 'izin') {
                $kategori = $user->kategori_absensi;

                $isFreelance = ($kategori === 'freelance');
                $isBorongan = ($kategori === 'borongan');
                $isMagang = ($kategori === 'magang');
                $isOrganik = ($kategori === 'organik');

                $currentTime = Carbon::now('Asia/Jakarta');
                $minutesWorked = $checkInTimeParsed->diffInMinutes($currentTime);
                $isAfterFivePM = $currentTime->hour >= 17;

                if ($minutesWorked < 540) {
                    $isKurang8Jam = true;
                }

                if ($isFreelance || $isBorongan) {
                    if (! $isAfterFivePM && $minutesWorked < 540) {
                        return response()->json([
                            'message' => 'Freelance/Borongan baru boleh pulang kalau sudah jam 17:00 (5 sore) atau sudah kerja 8 jam. Semangat Para Pejuang Rupiah!',
                        ], 400);
                    }
                }

            }

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $fotoPath2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('absensi_foto', 'public') : null;
            $fotoPath3 = $request->hasFile('foto_3') ? $request->file('foto_3')->store('absensi_foto', 'public') : null;
            $fotoPath4 = $request->hasFile('foto_4') ? $request->file('foto_4')->store('absensi_foto', 'public') : null;
            $fotoPath5 = $request->hasFile('foto_5') ? $request->file('foto_5')->store('absensi_foto', 'public') : null;
            $fotoPath6 = $request->hasFile('foto_6') ? $request->file('foto_6')->store('absensi_foto', 'public') : null;
            $fileBuktiPath = $request->hasFile('file_bukti') ? $request->file('file_bukti')->store('bukti_sakit_izin', 'public') : $absensi->file_bukti;
            $lokasiPulang = $request->lat.','.$request->lng;
            $checkOutTime = Carbon::now('Asia/Jakarta');
            $isWeekend = Absensi::isWeekend($absensi->check_in_at);
            $lateMinutes = $absensi->late_minutes ?? 0;

            $kategori = $user->kategori_absensi;

            $updatedSalary = Absensi::calculateSalary(
                $lateMinutes,
                $absensi->status,
                $request->tipe,
                $isWeekend,
                $absensi->check_in_at,
                $checkOutTime,
                $kategori,
                $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN',
                $absensi->user->dailyRateAt($absensi->check_in_at),
            );

            if ($request->tipe === 'lembur') {
                $statusApproval = 'pending';
                $employment = strtolower($user->work_location ?? 'office');
                $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
                $currentLevel = 1;
            } else {
                $statusApproval = 'approved';
                $workflow = $absensi->workflow_status;
                $currentLevel = $absensi->current_approval_level;
            }

            $absensi->update([
                'check_out_at' => $checkOutTime,
                'is_kurang_8_jam' => $isKurang8Jam,
                'foto_pulang' => $fotoPath,
                'foto_pulang_2' => $fotoPath2,
                'foto_pulang_3' => $fotoPath3,
                'foto_pulang_4' => $fotoPath4,
                'foto_pulang_5' => $fotoPath5,
                'foto_pulang_6' => $fotoPath6,
                'keterangan_goals' => $request->keterangan_goals,
                'lokasi_pulang' => $lokasiPulang,
                'tipe' => $request->tipe,
                'file_bukti' => $fileBuktiPath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit ?? $absensi->keterangan_izin_sakit,
                'status_approval' => $statusApproval,
                'workflow_status' => $workflow,
                'current_approval_level' => $currentLevel,
                'base_salary' => $updatedSalary['base_salary'],
                'late_penalty' => $updatedSalary['late_penalty'],
                'final_salary' => $updatedSalary['final_salary'],
                'is_mocked' => $request->boolean('is_mocked'),
            ]);

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);
            if ($absensi->foto_pulang_2) {
                $absensi->foto_pulang_2_url = Storage::url($absensi->foto_pulang_2);
            }
            if ($absensi->foto_pulang_3) {
                $absensi->foto_pulang_3_url = Storage::url($absensi->foto_pulang_3);
            }
            if ($absensi->foto_pulang_4) {
                $absensi->foto_pulang_4_url = Storage::url($absensi->foto_pulang_4);
            }
            if ($absensi->foto_pulang_5) {
                $absensi->foto_pulang_5_url = Storage::url($absensi->foto_pulang_5);
            }
            if ($absensi->foto_pulang_6) {
                $absensi->foto_pulang_6_url = Storage::url($absensi->foto_pulang_6);
            }

            ActivityLog::log('Absen Pulang', "User: {$user->name}", "Melakukan absen pulang tipe: {$request->tipe}");

            return response()->json([
                'message' => 'Terima kasih telah bekerja keras bersama PT Follow Me!',
                'checkout_confirmation' => [
                    'message' => 'Terima kasih telah bekerja keras bersama PT Follow Me!',
                    'image_url' => asset('images/checkout-cat.png'),
                ],
                'data' => $absensi,
            ]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server. '.(config('app.debug') ? $e->getMessage() : '')], 500);
        }
    }

    public function meAbsensi(Request $request)
    {
        try {
            $userId = Auth::id();

            \Log::info('📡 [ME ABSENSI] URL Called: '.$request->fullUrl());
            \Log::info('📡 [ME ABSENSI] All Inputs:', $request->all());

            $query = Absensi::with(['user', 'children'])->where('user_id', $userId)->whereNull('parent_id');

            if ($request->filled('search_date')) {
                $searchDate = Carbon::parse($request->input('search_date'));
                $query->whereBetween('check_in_at', [$searchDate->copy()->startOfDay(), $searchDate->copy()->endOfDay()]);
            } elseif ($request->filled('month')) {
                $month = $request->input('month');
                $year = $request->input('year', Carbon::now('Asia/Jakarta')->year);
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $endOfMonth = (clone $startOfMonth)->endOfMonth();
                $query->whereBetween('check_in_at', [$startOfMonth, $endOfMonth]);
            } else {
                $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now('Asia/Jakarta')->subYears(2)->startOfDay();
                $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now('Asia/Jakarta')->addMonth()->endOfDay();
                $query->whereBetween('check_in_at', [$startDate, $endDate]);
            }

            $absensi = $query->orderBy('check_in_at', 'desc')->orderBy('id', 'desc')->paginate(30);

            $absensi->getCollection()->transform(function ($item) {
                $item->foto_masuk_url = $item->foto_masuk ? Storage::url($item->foto_masuk) : null;
                $item->foto_pulang_url = $item->foto_pulang ? Storage::url($item->foto_pulang) : null;
                $item->foto_pulang_2_url = $item->foto_pulang_2 ? Storage::url($item->foto_pulang_2) : null;
                $item->foto_pulang_3_url = $item->foto_pulang_3 ? Storage::url($item->foto_pulang_3) : null;
                $item->foto_pulang_4_url = $item->foto_pulang_4 ? Storage::url($item->foto_pulang_4) : null;
                $item->foto_pulang_5_url = $item->foto_pulang_5 ? Storage::url($item->foto_pulang_5) : null;
                $item->foto_pulang_6_url = $item->foto_pulang_6 ? Storage::url($item->foto_pulang_6) : null;
                $item->file_bukti_url = $item->file_bukti ? Storage::url($item->file_bukti) : null;

                if ($item->children->isNotEmpty()) {

                    $pendingChild = $item->children->where('status_approval', 'pending')->first();
                    if ($pendingChild) {
                        $item->tipe = $pendingChild->tipe;
                        $item->status_approval = 'pending';

                        if ($pendingChild->tipe === 'lembur') {
                            $item->lembur_start = $pendingChild->lembur_start;
                            $item->lembur_end = $pendingChild->lembur_end;
                            $item->overtime_minutes = $pendingChild->overtime_minutes;
                        }
                        if ($pendingChild->tipe === 'telat') {
                            $item->file_bukti_url = $pendingChild->file_bukti ? Storage::url($pendingChild->file_bukti) : null;
                            $item->keterangan_izin_sakit = $pendingChild->keterangan_izin_sakit;
                        }
                    } else {
                        $rejectedChild = $item->children
                            ->whereIn('status_approval', ['rejected', 'ditolak'])
                            ->sortByDesc('updated_at')
                            ->first();

                        if ($rejectedChild) {
                            $item->tipe = $rejectedChild->tipe;
                            $item->status_approval = $rejectedChild->status_approval;
                            $item->workflow_status = $rejectedChild->workflow_status;
                            $item->catatan_admin = $rejectedChild->catatan_admin;
                            $item->rejected_by = $rejectedChild->rejected_by;
                            $item->rejected_at = $rejectedChild->rejected_at;

                            if ($rejectedChild->tipe === 'lembur') {
                                $item->lembur_start = $rejectedChild->lembur_start;
                                $item->lembur_end = $rejectedChild->lembur_end;
                                $item->overtime_minutes = $rejectedChild->overtime_minutes;
                            }
                            if ($rejectedChild->tipe === 'telat') {
                                $item->file_bukti_url = $rejectedChild->file_bukti ? Storage::url($rejectedChild->file_bukti) : null;
                                $item->keterangan_izin_sakit = $rejectedChild->keterangan_izin_sakit;
                            }
                        } else {
                            $approvedChild = $item->children->where('status_approval', 'approved')->first();
                            if ($approvedChild) {
                                $item->tipe = $approvedChild->tipe;
                                $item->status_approval = 'approved';

                                $item->workflow_status = $approvedChild->workflow_status;
                            }
                        }
                    }
                }

                return $item;
            });

            \Log::info('✅ [ME ABSENSI] Found: '.$absensi->total().' records for User: '.$userId);

            return response()->json($absensi);

        } catch (\Exception $e) {
            \Log::error('❌ [ME ABSENSI] Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server. '.(config('app.debug') ? $e->getMessage() : ''),
            ], 500);
        }
    }

    public function absenSakit(Request $request)
    {
        return $this->handlePengajuanIzinSakit($request, 'sakit');
    }

    public function absenIzin(Request $request)
    {
        $user = Auth::user();
        $submissionType = $request->catatan_admin ?? null;

        if ($user->employment_type === 'organik') {

            $validCutiTypes = [
                'cuti_tahunan', 'cuti_melahirkan', 'cuti_keguguran',
                'cuti_haji', 'cuti_umroh', 'cuti_menikah', 'cuti_khitanan',
                'cuti_baptis', 'cuti_meninggal', 'change_off', 'unpaid_leave',
                'izin_pulang_cepat',
            ];
            if (! $submissionType || ! in_array($submissionType, $validCutiTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan organik tidak bisa mengajukan izin biasa. Gunakan fitur Cuti untuk pengajuan izin.',
                ], 422);
            }
        }

        return $this->handlePengajuanIzinSakit($request, 'izin');
    }

    private function leaveNoticePeriodError(Carbon $startDate): ?JsonResponse
    {
        $minimumStartDate = Carbon::today()->addDays(7)->startOfDay();
        if ($startDate->copy()->startOfDay()->greaterThanOrEqualTo($minimumStartDate)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Pengajuan izin/cuti harus diajukan minimal H-7. Tanggal mulai paling cepat yang dapat dipilih adalah '.$minimumStartDate->format('d/m/Y').'.',
        ], 422);
    }

    private function handlePengajuanIzinSakit(Request $request, string $defaultStatus)
    {
        DB::beginTransaction();
        try {
            $isHalfDayRequest = $request->input('catatan_admin') === 'izin_pulang_cepat';
            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'required|string|max:500',
                'status' => 'nullable|in:sakit,izin',
                'start_date' => 'nullable|date|after_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'catatan_admin' => 'nullable|string|max:255',
                'jam_pulang_rencana' => [
                    $isHalfDayRequest ? 'required' : 'nullable',
                    'date_format:H:i',
                ],
            ]);

            $user = Auth::user();
            $status = $request->status ?? $defaultStatus;
            $submissionType = $request->catatan_admin ?? null;
            $jamPulangRencana = $request->jam_pulang_rencana;

            if ($isHalfDayRequest && $status !== 'izin') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan setengah hari harus menggunakan status izin.',
                ], 422);
            }

            if ($status === 'izin' && $submissionType !== 'izin_pulang_cepat') {
                $leaveStartDate = $request->start_date
                    ? Carbon::parse($request->start_date)
                    : Carbon::today();
                $noticePeriodError = $this->leaveNoticePeriodError($leaveStartDate);
                if ($noticePeriodError) {
                    DB::rollBack();

                    return $noticePeriodError;
                }
            }

            if ($status !== 'lembur' && $submissionType !== 'izin_pulang_cepat') {
                $today = Carbon::today();
                $hasCheckInToday = Absensi::where('user_id', $user->id)
                    ->whereDate('check_in_at', $today)
                    ->where('status', 'hadir')
                    ->exists();

                if ($hasCheckInToday) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak bisa mengajukan izin atau cuti karena Anda sudah melakukan absen masuk hari ini.',
                    ], 422);
                }
            }

            $cutiConfig = [
                'cuti_tahunan' => ['maxDays' => 12,  'potongJatah' => true,  'gratisHari' => 0],
                'cuti_melahirkan' => ['maxDays' => 90,  'potongJatah' => false, 'gratisHari' => 0],
                'cuti_keguguran' => ['maxDays' => 45,  'potongJatah' => false, 'gratisHari' => 0],
                'cuti_haji' => ['maxDays' => 60,  'potongJatah' => true,  'gratisHari' => 14],
                'cuti_umroh' => ['maxDays' => 60,  'potongJatah' => true,  'gratisHari' => 12],
                'cuti_menikah' => ['maxDays' => 3,   'potongJatah' => false, 'gratisHari' => 0],
                'cuti_khitanan' => ['maxDays' => 2,   'potongJatah' => false, 'gratisHari' => 0],
                'cuti_baptis' => ['maxDays' => 2,   'potongJatah' => false, 'gratisHari' => 0],
                'cuti_meninggal' => ['maxDays' => 2,   'potongJatah' => false, 'gratisHari' => 0],
                'change_off' => ['maxDays' => 1,   'potongJatah' => false, 'gratisHari' => 0],
                'unpaid_leave' => ['maxDays' => 30,  'potongJatah' => false, 'gratisHari' => 0],
            ];

            $startDateInput = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
            $endDateInput = $request->end_date ? Carbon::parse($request->end_date)->startOfDay() : $startDateInput->copy()->startOfDay();

            if ($isHalfDayRequest && (
                ! $startDateInput->isToday()
                || ! $endDateInput->equalTo($startDateInput)
            )) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan setengah hari hanya dapat diajukan untuk hari ini.',
                ], 422);
            }

            $halfDayAttendance = null;
            if ($isHalfDayRequest) {
                $halfDayAttendance = Absensi::where('user_id', $user->id)
                    ->whereDate('check_in_at', Carbon::today())
                    ->where('status', 'hadir')
                    ->where('status_approval', 'approved')
                    ->whereNull('parent_id')
                    ->whereNull('submission_type')
                    ->first();

                if (! $halfDayAttendance) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Pengajuan setengah hari hanya dapat diajukan setelah Anda absen masuk hari ini.',
                    ], 422);
                }

                $checkInTime = Carbon::parse($halfDayAttendance->check_in_at)->format('H:i');
                if ($jamPulangRencana <= $checkInTime) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Jam pulang rencana harus setelah jam absen masuk.',
                    ], 422);
                }
            }

            $calendarCutiTypes = ['cuti_melahirkan', 'cuti_keguguran', 'unpaid_leave'];
            $isCalendarLeave = in_array($submissionType, $calendarCutiTypes);

            $validDates = [];
            $tempDate = $startDateInput->copy();
            while ($tempDate->lte($endDateInput)) {
                $isWeekend = Absensi::isWeekend($tempDate);
                $isHoliday = Holiday::isHoliday($tempDate);

                if ($isCalendarLeave || $isHalfDayRequest || (! $isWeekend && ! $isHoliday)) {
                    $validDates[] = $tempDate->copy();
                }
                $tempDate->addDay();
            }

            if (empty($validDates)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Tidak ada hari kerja efektif pada rentang tanggal yang dipilih.'], 422);
            }

            $startDate = $validDates[0];
            $endDate = end($validDates);
            $totalDays = count($validDates);

            $maxDays = 30;
            $gratisHari = 0;
            $potongJatah = false;
            if ($submissionType && isset($cutiConfig[$submissionType])) {
                $maxDays = $cutiConfig[$submissionType]['maxDays'];
                $gratisHari = $cutiConfig[$submissionType]['gratisHari'];
                $potongJatah = $cutiConfig[$submissionType]['potongJatah'];
            }

            if ($totalDays > $maxDays) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => "Pengajuan {$submissionType} maksimal {$maxDays} hari."], 422);
            }

            $hariPotongCuti = 0;
            $hariUnpaid = 0;
            if ($submissionType && $gratisHari > 0 && $totalDays > $gratisHari) {
                $hariMelebihiGratis = $totalDays - $gratisHari;
                $sisaCuti = $user->sisa_cuti ?? 0;
                if ($sisaCuti >= $hariMelebihiGratis) {
                    $hariPotongCuti = $hariMelebihiGratis;
                } else {
                    $hariPotongCuti = $sisaCuti;
                    $hariUnpaid = $hariMelebihiGratis - $sisaCuti;
                }
            } elseif ($potongJatah && $gratisHari === 0) {
                $hariPotongCuti = $totalDays;
                $sisaCuti = $user->sisa_cuti ?? 0;
                if ($sisaCuti < $hariPotongCuti) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => "Sisa cuti tidak cukup. Sisa: {$sisaCuti} hari, Dibutuhkan: {$hariPotongCuti} hari."], 422);
                }
            }

            $existingAbsensiQuery = Absensi::where('user_id', $user->id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('check_in_at', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('check_in_at', '<=', $startDate)->where('end_date', '>=', $endDate);
                        });
                })
                ->whereIn('status_approval', ['pending', 'approved', 'rejected']);

            if ($halfDayAttendance) {
                $existingAbsensiQuery->where('id', '!=', $halfDayAttendance->id);
            }

            $existingAbsensi = $existingAbsensiQuery->first();

            if ($existingAbsensi) {
                DB::rollBack();
                $conflictDate = Carbon::parse($existingAbsensi->check_in_at)->format('d/m/Y');
                if ($existingAbsensi->status_approval == 'rejected') {
                    return response()->json([
                        'success' => false,
                        'message' => "Anda sudah memiliki pengajuan {$existingAbsensi->tipe} yang DITOLAK pada tanggal {$conflictDate}. Silakan gunakan tombol 'Ajukan Ulang'.",
                        'rejected_id' => $existingAbsensi->id,
                        'action' => 'use_resubmit',
                    ], 409);
                }

                return response()->json(['success' => false, 'message' => "Anda sudah memiliki pengajuan {$existingAbsensi->tipe} pada tanggal {$conflictDate}."], 409);
            }

            $fileBuktiPath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');
            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            if ($halfDayAttendance && $submissionType === 'izin_pulang_cepat') {
                $parentAbsensi = $halfDayAttendance;
                $parentAbsensi->update([
                    'status' => $status,
                    'tipe' => $status,
                    'status_approval' => 'pending',
                    'file_bukti' => $fileBuktiPath,
                    'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                    'submission_type' => $submissionType,
                    'catatan_admin' => $request->catatan_admin,
                    'jam_pulang_rencana' => $jamPulangRencana,
                    'workflow_status' => $workflow,
                    'current_approval_level' => 1,
                ]);
            } else {
                $parentAbsensi = Absensi::create([
                    'hari_potong_cuti' => $hariPotongCuti,
                    'hari_unpaid' => $hariUnpaid,
                    'user_id' => $user->id,
                    'check_in_at' => $startDate,
                    'end_date' => $endDate,
                    'total_days' => $totalDays,
                    'status' => $status,
                    'tipe' => $status,
                    'status_approval' => 'pending',
                    'file_bukti' => $fileBuktiPath,
                    'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                    'submission_type' => $submissionType,
                    'catatan_admin' => $request->catatan_admin,
                    'jam_pulang_rencana' => $jamPulangRencana,
                    'workflow_status' => $workflow,
                    'current_approval_level' => 1,
                    'late_minutes' => 0,
                    'base_salary' => 0,
                    'late_penalty' => 0,
                    'final_salary' => 0,
                ]);
            }

            $childRecords = [];
            foreach ($validDates as $index => $date) {
                if ($index > 0) {
                    $childRecords[] = [
                        'user_id' => $user->id,
                        'parent_id' => $parentAbsensi->id,
                        'check_in_at' => $date->copy()->setTime(8, 0, 0)->toDateTimeString(),
                        'status' => $status,
                        'tipe' => $status,
                        'submission_type' => $submissionType,
                        'status_approval' => 'pending',
                        'file_bukti' => $fileBuktiPath,
                        'keterangan_izin_sakit' => $request->keterangan_izin_sakit,
                        'workflow_status' => json_encode($workflow),
                        'current_approval_level' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }

            if (! empty($childRecords)) {
                Absensi::insert($childRecords);
            }

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Pengajuan '.ucfirst($status).' Berhasil',
                'message' => "Pengajuan {$status} untuk {$totalDays} hari telah diajukan dan menunggu approval.",
                'type' => $status.'_submitted',
                'target_page' => '/'.$status.'_detail',
                'target_id' => $parentAbsensi->id,
            ]);

            DB::commit();

            if ($hariPotongCuti > 0 || $hariUnpaid > 0) {
                Log::info('📝 [CUTI] Pengajuan tercatat, menunggu approval', [
                    'user' => $user->name,
                    'submission_type' => $submissionType,
                    'hari_potong_cuti_rencana' => $hariPotongCuti,
                    'hari_unpaid' => $hariUnpaid,
                ]);
            }

            $parentAbsensi->load('user');
            $parentAbsensi->file_bukti_url = Storage::url($parentAbsensi->file_bukti);

            return response()->json([
                'success' => true,
                'message' => "Pengajuan {$status} berhasil diajukan untuk {$totalDays} hari.".($hariUnpaid > 0 ? " ({$hariPotongCuti} hari potong cuti, {$hariUnpaid} hari unpaid)" : ''),
                'data' => $parentAbsensi,
                'summary' => [
                    'total_days' => $totalDays,
                    'total_records' => 1 + count($childRecords),
                    'parent_id' => $parentAbsensi->id,
                    'date_range' => $startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y'),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($fileBuktiPath)) {
                Storage::disk('public')->delete($fileBuktiPath);
            }
            \Log::error("❌ [API {$defaultStatus}] Error: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server. '.(config('app.debug') ? $e->getMessage() : ''),
            ], 500);
        }
    }

    public function absenLembur(Request $request)
    {
        try {
            $request->validate([
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i',
                'istirahat' => 'required|boolean',
                'keterangan' => 'required|string|max:500',
                'keterangan_goals' => 'nullable|string|max:2000',
                'foto' => 'required|image|max:2048',
                'foto_2' => 'nullable|image|max:2048',
                'foto_3' => 'nullable|image|max:2048',
                'foto_4' => 'nullable|image|max:2048',
                'foto_5' => 'nullable|image|max:2048',
                'foto_6' => 'nullable|image|max:2048',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'is_weekend' => 'nullable|boolean',
                'is_mocked' => 'nullable|boolean',
            ]);

            $jamMulai = Carbon::createFromFormat('H:i', $request->jam_mulai);
            $jamSelesai = Carbon::createFromFormat('H:i', $request->jam_selesai);

            if ($jamSelesai->lte($jamMulai)) {
                return response()->json(['success' => false, 'message' => 'Jam selesai harus lebih dari jam mulai.'], 422);
            }

            if ($jamMulai->diffInHours($jamSelesai) > 5) {
                return response()->json(['success' => false, 'message' => 'Durasi lembur tidak wajar (Maksimal 5 jam sehari). Harap hubungi HR untuk lembur khusus.'], 422);
            }

            $user = Auth::user();
            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if (! $absensi) {
                return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini.'], 400);
            }

            if ($absensi->tipe !== null && $absensi->tipe !== 'hadir') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki pengajuan tipe lain ('.ucfirst($absensi->tipe).') yang sedang diproses. Mohon selesaikan pengajuan tersebut terlebih dahulu.',
                ], 409);
            }

            $alreadyHasLembur = Absensi::where('parent_id', $absensi->id)
                ->where('tipe', 'lembur')
                ->whereIn('status_approval', ['pending', 'approved'])
                ->exists();

            if ($alreadyHasLembur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah mengajukan lembur untuk hari ini.',
                ], 409);
            }

            $fotoPath = $request->file('foto')->store('absensi_foto', 'public');
            $fotoPath2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('absensi_foto', 'public') : null;
            $fotoPath3 = $request->hasFile('foto_3') ? $request->file('foto_3')->store('absensi_foto', 'public') : null;
            $fotoPath4 = $request->hasFile('foto_4') ? $request->file('foto_4')->store('absensi_foto', 'public') : null;
            $fotoPath5 = $request->hasFile('foto_5') ? $request->file('foto_5')->store('absensi_foto', 'public') : null;
            $fotoPath6 = $request->hasFile('foto_6') ? $request->file('foto_6')->store('absensi_foto', 'public') : null;
            $lokasiPulang = $request->lat.','.$request->lng;

            $lemburStart = Carbon::parse($today->format('Y-m-d').' '.$request->jam_mulai);
            $lemburEnd = Carbon::parse($today->format('Y-m-d').' '.$request->jam_selesai);

            $isWeekendOvertime = Absensi::isWeekend($lemburStart);

            $overtimeData = Absensi::calculateOvertimeFromInput(
                $lemburStart,
                $lemburEnd,
                $request->istirahat,
                $isWeekendOvertime
            );

            $kategori = $user->kategori_absensi;
            $lateMinutes = $absensi->late_minutes ?? 0;
            $salaryData = Absensi::calculateSalary(
                $lateMinutes,
                $absensi->status,
                'lembur',
                $isWeekendOvertime,
                $absensi->check_in_at,
                now(),
                $kategori,
                $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN',
                $user->dailyRateAt($absensi->check_in_at),
            );

            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            if (! $absensi->check_out_at) {
                $absensi->update([
                    'check_out_at' => $lemburEnd,
                    'foto_pulang' => $fotoPath,
                ]);
            }

            $childLembur = Absensi::create([
                'user_id' => $user->id,
                'parent_id' => $absensi->id,
                'status' => $absensi->status,
                'check_in_at' => $lemburStart,
                'check_out_at' => $lemburEnd,
                'foto_pulang' => $fotoPath,
                'foto_pulang_2' => $fotoPath2,
                'foto_pulang_3' => $fotoPath3,
                'foto_pulang_4' => $fotoPath4,
                'foto_pulang_5' => $fotoPath5,
                'foto_pulang_6' => $fotoPath6,
                'lokasi_pulang' => $lokasiPulang,
                'tipe' => 'lembur',
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => 1,
                'lembur_start' => $lemburStart,
                'lembur_end' => $lemburEnd,
                'lembur_rest' => $request->istirahat,
                'lembur_keterangan' => $request->keterangan,
                'keterangan_goals' => $request->keterangan_goals,
                'overtime_minutes' => $overtimeData['minutes'],
                'overtime_pay' => $overtimeData['pay'],
                'is_weekend_overtime' => $isWeekendOvertime,
                'is_mocked' => $request->boolean('is_mocked'),
            ]);

            $childLembur->load('user');
            $childLembur->foto_pulang_url = Storage::url($childLembur->foto_pulang);

            return response()->json([
                'success' => true,
                'message' => 'Absensi lembur berhasil diajukan sebagai record baru.'.($isWeekendOvertime ? ' (Weekend - Rate 2x)' : ''),
                'data' => $childLembur,
                'overtime_info' => [
                    'minutes' => $overtimeData['minutes'],
                    'pay' => 'Rp '.number_format($overtimeData['pay'], 0, ',', '.'),
                    'formatted_duration' => floor($overtimeData['minutes'] / 60).' jam '.($overtimeData['minutes'] % 60).' menit',
                    'is_weekend' => $isWeekendOvertime,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server: '.$e->getMessage()], 500);
        }
    }

    public function submitLembur(Request $request)
    {
        try {
            $request->validate([
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i',
                'istirahat' => 'required|boolean',
                'keterangan' => 'required|string|max:500',
                'keterangan_goals' => 'nullable|string|max:2000',
                'foto_bukti' => 'required|image|max:2048',
                'foto_2' => 'nullable|image|max:2048',
                'foto_3' => 'nullable|image|max:2048',
                'foto_4' => 'nullable|image|max:2048',
                'foto_5' => 'nullable|image|max:2048',
                'is_weekend' => 'nullable|boolean',
                'is_mocked' => 'nullable|boolean',
            ]);

            $jamMulai = Carbon::createFromFormat('H:i', $request->jam_mulai);
            $jamSelesai = Carbon::createFromFormat('H:i', $request->jam_selesai);

            if ($jamSelesai->lte($jamMulai)) {
                return response()->json(['success' => false, 'message' => 'Jam selesai harus lebih dari jam mulai.'], 422);
            }

            // ✅ FIX SECURITY #2: Batas Maksimal Lembur 5 Jam
            if ($jamMulai->diffInHours($jamSelesai) > 5) {
                return response()->json(['success' => false, 'message' => 'Durasi lembur tidak wajar (Maksimal 5 jam sehari). Harap hubungi HR untuk lembur khusus.'], 422);
            }

            $user = Auth::user();
            $today = Carbon::today();

            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('check_in_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->whereIn('status_approval', ['pending', 'approved'])
                ->first();

            if (! $absensi) {
                return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini.'], 400);
            }

            if ($absensi->tipe !== null && $absensi->tipe !== 'hadir') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki pengajuan tipe lain ('.ucfirst($absensi->tipe).') yang sedang diproses. Mohon selesaikan pengajuan tersebut terlebih dahulu.',
                ], 409);
            }

            $alreadyHasLembur = Absensi::where('parent_id', $absensi->id)
                ->where('tipe', 'lembur')
                ->whereIn('status_approval', ['pending', 'approved'])
                ->exists();

            if ($alreadyHasLembur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah mengajukan lembur untuk hari ini.',
                ], 409);
            }

            $fotoBukti = $request->hasFile('foto_bukti') ? $request->file('foto_bukti')->store('absensi_foto', 'public') : null;
            $fotoPath2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('absensi_foto', 'public') : null;
            $fotoPath3 = $request->hasFile('foto_3') ? $request->file('foto_3')->store('absensi_foto', 'public') : null;
            $fotoPath4 = $request->hasFile('foto_4') ? $request->file('foto_4')->store('absensi_foto', 'public') : null;
            $fotoPath5 = $request->hasFile('foto_5') ? $request->file('foto_5')->store('absensi_foto', 'public') : null;

            $lemburStart = Carbon::parse($today->format('Y-m-d').' '.$request->jam_mulai);
            $lemburEnd = Carbon::parse($today->format('Y-m-d').' '.$request->jam_selesai);

            $isWeekendOvertime = Absensi::isWeekend($lemburStart);

            $overtimeData = Absensi::calculateOvertimeFromInput(
                $lemburStart,
                $lemburEnd,
                $request->istirahat,
                $isWeekendOvertime
            );

            $kategori = $user->kategori_absensi;
            $lateMinutes = $absensi->late_minutes ?? 0;
            $salaryData = Absensi::calculateSalary(
                $lateMinutes,
                $absensi->status,
                'lembur',
                $isWeekendOvertime,
                $absensi->check_in_at,
                now(),
                $kategori,
                $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN',
                $user->dailyRateAt($absensi->check_in_at),
            );

            $employment = strtolower($user->work_location ?? 'office');
            $workflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];

            if (! $absensi->check_out_at) {
                $absensi->update([
                    'check_out_at' => $lemburEnd,
                    'foto_pulang' => $fotoBukti,
                ]);
            }

            $childLembur = Absensi::create([
                'user_id' => $user->id,
                'parent_id' => $absensi->id,
                'status' => $absensi->status,
                'check_in_at' => $lemburStart,
                'check_out_at' => $lemburEnd,
                'foto_pulang' => $fotoBukti ?? null,
                'foto_pulang_2' => $fotoPath2,
                'foto_pulang_3' => $fotoPath3,
                'foto_pulang_4' => $fotoPath4,
                'foto_pulang_5' => $fotoPath5,
                'tipe' => 'lembur',
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => 1,
                'lembur_start' => $lemburStart,
                'lembur_end' => $lemburEnd,
                'lembur_rest' => $request->istirahat,
                'lembur_keterangan' => $request->keterangan,
                'keterangan_goals' => $request->keterangan_goals,
                'overtime_minutes' => $overtimeData['minutes'],
                'overtime_pay' => $overtimeData['pay'],
                'is_weekend_overtime' => $isWeekendOvertime,
                'is_mocked' => $request->boolean('is_mocked'),
            ]);

            $childLembur->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil diajukan sebagai record baru.'.($isWeekendOvertime ? ' (Weekend - Rate 2x)' : ''),
                'data' => $childLembur,
                'overtime_info' => [
                    'minutes' => $overtimeData['minutes'],
                    'pay' => 'Rp '.number_format($overtimeData['pay'], 0, ',', '.'),
                    'formatted_duration' => floor($overtimeData['minutes'] / 60).' jam '.($overtimeData['minutes'] % 60).' menit',
                    'is_weekend' => $isWeekendOvertime,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server: '.$e->getMessage()], 500);
        }
    }

    public function resubmit(Request $request, $id)
    {
        // 1. Coba cari di Absensi (Sakit, Izin, Lembur, Telat)
        $absensi = Absensi::find($id);
        if ($absensi) {
            if ($absensi->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }

            $type = strtolower($absensi->tipe ?? $absensi->status ?? 'absensi');
            if (in_array($type, ['sakit', 'izin'])) {
                return $this->handleResubmitIzinSakit($request, $id, $type);
            } elseif ($type === 'lembur') {
                return $this->handleResubmitLembur($request, $id);
            } elseif ($type === 'telat') {

                return $this->handleResubmitIzinSakit($request, $id, 'telat');
            }
        }

        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan atau tipe tidak didukung.'], 404);
    }

    private function handleResubmitLembur(Request $request, $id)
    {
        // ... (Logika lama resubmitLembur pindah ke sini)
    }

    // ... (Fungsi resubmitSakit dan resubmitIzin bisa dihapus/dibiarkan sebagai alias)
    public function resubmitSakit(Request $request, $id)
    {
        return $this->resubmit($request, $id);
    }

    public function resubmitIzin(Request $request, $id)
    {
        return $this->resubmit($request, $id);
    }

    private function handleResubmitIzinSakit(Request $request, $id, string $type)
    {
        DB::beginTransaction();
        try {
            if (! is_numeric($id) || $id <= 0) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'ID tidak valid.'], 400);
            }

            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan_izin_sakit' => 'nullable|string|max:500',
                'catatan' => 'nullable|string|max:500', // support both keys
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $absensi = Absensi::find($id);
            if (! $absensi || (int) $absensi->user_id !== (int) Auth::id()) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan atau akses ditolak.'], 404);
            }

            if (! in_array($absensi->status_approval, ['rejected', 'ditolak'])) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            $startDateInput = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::parse($absensi->check_in_at)->startOfDay();
            $endDateInput = $request->end_date ? Carbon::parse($request->end_date)->startOfDay() : ($absensi->end_date ? Carbon::parse($absensi->end_date)->startOfDay() : $startDateInput->copy());

            if ($type === 'izin' && $absensi->submission_type !== 'izin_pulang_cepat') {
                $noticePeriodError = $this->leaveNoticePeriodError($startDateInput);
                if ($noticePeriodError) {
                    DB::rollBack();

                    return $noticePeriodError;
                }
            }

            $calendarCutiTypes = ['cuti_melahirkan', 'cuti_keguguran', 'unpaid_leave'];
            $isCalendarLeave = in_array($absensi->submission_type, $calendarCutiTypes);

            $validDates = [];
            $tempDate = $startDateInput->copy();
            while ($tempDate->lte($endDateInput)) {
                $isWeekend = Absensi::isWeekend($tempDate);
                $isHoliday = Holiday::isHoliday($tempDate);

                if ($isCalendarLeave || (! $isWeekend && ! $isHoliday)) {
                    $validDates[] = $tempDate->copy();
                }
                $tempDate->addDay();
            }

            if (empty($validDates)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Tidak ada hari kerja efektif pada rentang tanggal yang dipilih.'], 422);
            }

            $startDate = $validDates[0];
            $endDate = end($validDates);
            $totalDays = count($validDates);

            if ($totalDays > 30) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Pengajuan ulang maksimal 30 hari.'], 422);
            }

            if ($absensi->file_bukti && Storage::disk('public')->exists($absensi->file_bukti)) {
                Storage::disk('public')->delete($absensi->file_bukti);
            }

            $filePath = $request->file('file_bukti')->store('bukti_sakit_izin', 'public');
            $employment = strtolower($absensi->user->work_location ?? 'office');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $updateData = [
                'check_in_at' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'file_bukti' => $filePath,
                'keterangan_izin_sakit' => $request->keterangan_izin_sakit ?? $request->catatan ?? $absensi->keterangan_izin_sakit,
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => $startLevel,
                'rejected_by' => null,
                'rejected_at' => null,
                'catatan_admin' => null,
                'updated_at' => now(),
            ];

            $absensi->forceFill($updateData)->save();

            Absensi::where('parent_id', $absensi->id)->delete();

            if ($totalDays > 1) {
                $childRecords = [];
                foreach ($validDates as $index => $date) {
                    if ($index > 0) {
                        $childRecords[] = [
                            'user_id' => $absensi->user_id,
                            'parent_id' => $absensi->id,
                            'check_in_at' => $date->copy()->setTime(8, 0, 0)->toDateTimeString(),
                            'status' => $type,
                            'tipe' => $type,
                            'submission_type' => $absensi->submission_type,
                            'status_approval' => 'pending',
                            'file_bukti' => $filePath,
                            'keterangan_izin_sakit' => $updateData['keterangan_izin_sakit'],
                            'workflow_status' => is_array($workflow) ? json_encode($workflow) : $workflow,
                            'current_approval_level' => $startLevel,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                }
                if (! empty($childRecords)) {
                    Absensi::insert($childRecords);
                }
            }

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => 'Pengajuan '.ucfirst($type).' Diajukan Ulang',
                'message' => "Pengajuan kamu telah direvisi (durasi {$totalDays} hari) dan akan direview oleh approver.",
                'type' => $type.'_resubmitted',
                'target_page' => "/{$type}_detail",
                'target_id' => $absensi->id,
            ]);

            DB::commit();
            $absensi->load('user');
            $absensi->file_bukti_url = Storage::url($absensi->file_bukti);

            return response()->json(['success' => true, 'message' => "Pengajuan {$type} berhasil direvisi dan diajukan ulang.", 'data' => $absensi], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Server error. '.(config('app.debug') ? $e->getMessage() : ''),
            ], 500);
        }
    }

    public function resubmitLembur(Request $request, $id)
    {
        try {
            // ✅ VALIDASI ID
            if (! is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID tidak valid.',
                ], 400);
            }

            $request->validate([
                'foto' => 'required|image|max:2048',
                'foto_2' => 'nullable|image|max:2048',
                'foto_3' => 'nullable|image|max:2048',
                'foto_4' => 'nullable|image|max:2048',
                'foto_5' => 'nullable|image|max:2048',
                'foto_6' => 'nullable|image|max:2048',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i',
                'istirahat' => 'required|boolean',
                'keterangan' => 'required|string|max:500',
                'keterangan_goals' => 'nullable|string|max:2000',
                'is_weekend' => 'nullable|boolean',
                'is_mocked' => 'nullable|boolean',
            ]);

            $jamMulai = Carbon::createFromFormat('H:i', $request->jam_mulai);
            $jamSelesai = Carbon::createFromFormat('H:i', $request->jam_selesai);

            if ($jamSelesai->lte($jamMulai)) {
                return response()->json(['success' => false, 'message' => 'Jam selesai harus lebih dari jam mulai.'], 422);
            }

            if ($jamMulai->diffInHours($jamSelesai) > 5) {
                return response()->json(['success' => false, 'message' => 'Durasi lembur tidak wajar (Maksimal 5 jam sehari). Harap hubungi HR untuk lembur khusus.'], 422);
            }

            $absensi = Absensi::find($id);
            if (! $absensi || $absensi->user_id != Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Record tidak ditemukan atau akses ditolak.'], 404);
            }

            if (! in_array($absensi->status_approval, ['rejected', 'ditolak'])) {
                return response()->json(['success' => false, 'message' => 'Hanya pengajuan yang ditolak yang bisa diajukan ulang.'], 409);
            }

            $photoFields = ['foto_pulang', 'foto_pulang_2', 'foto_pulang_3', 'foto_pulang_4', 'foto_pulang_5', 'foto_pulang_6'];
            $requestFields = ['foto', 'foto_2', 'foto_3', 'foto_4', 'foto_5', 'foto_6'];
            $newPaths = [];

            foreach ($photoFields as $index => $field) {
                $reqField = $requestFields[$index];

                if ($request->hasFile($reqField)) {
                    // Delete old
                    if ($absensi->$field && Storage::disk('public')->exists($absensi->$field)) {
                        Storage::disk('public')->delete($absensi->$field);
                    }
                    // Store new
                    $newPaths[$field] = $request->file($reqField)->store('absensi_foto', 'public');
                } else {
                    $newPaths[$field] = $absensi->$field;
                }
            }

            $lokasiPulang = $request->lat.','.$request->lng;
            $baseDate = $absensi->check_in_at ? Carbon::parse($absensi->check_in_at)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            $lemburStart = Carbon::parse($baseDate.' '.$request->jam_mulai);
            $lemburEnd = Carbon::parse($baseDate.' '.$request->jam_selesai);
            $isWeekendOvertime = Absensi::isWeekend($lemburStart);

            $employment = strtolower($absensi->user->work_location ?? 'office');
            $startLevel = $this->determineResubmitLevel($absensi->rejected_by, $absensi->workflow_status);
            $baseWorkflow = $this->workflowTemplates[$employment] ?? $this->workflowTemplates['organik'];
            $workflow = $this->resetWorkflowFromLevel($baseWorkflow, $startLevel, $employment);

            $overtimeData = Absensi::calculateOvertimeFromInput($lemburStart, $lemburEnd, $request->istirahat, $isWeekendOvertime);

            $kategori = $absensi->user->kategori_absensi;
            $salaryData = Absensi::calculateSalary(
                $absensi->late_minutes ?? 0,
                $absensi->status,
                'lembur',
                $isWeekendOvertime,
                $absensi->check_in_at,
                $absensi->check_out_at ?? now(),
                $kategori,
                $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN',
                $absensi->user->dailyRateAt($absensi->check_in_at),
            );

            $absensi->forceFill(array_merge($newPaths, [
                'lokasi_pulang' => $lokasiPulang,
                'lembur_start' => $lemburStart,
                'lembur_end' => $lemburEnd,
                'lembur_rest' => $request->istirahat,
                'lembur_keterangan' => $request->keterangan,
                'keterangan_goals' => $request->keterangan_goals ?? $absensi->keterangan_goals,
                'tipe' => 'lembur',
                'status_approval' => 'pending',
                'workflow_status' => $workflow,
                'current_approval_level' => $startLevel,
                'is_mocked' => $request->boolean('is_mocked'),
                'rejected_by' => null,
                'rejected_at' => null,
                'catatan_admin' => null,
                'check_out_at' => $absensi->check_out_at ?? now(),
                'updated_at' => now(),
                'overtime_minutes' => $overtimeData['minutes'],
                'overtime_pay' => $overtimeData['pay'],
                'base_salary' => $absensi->base_salary ?? $salaryData['base_salary'],
                'late_penalty' => $absensi->late_penalty ?? $salaryData['late_penalty'],
                'final_salary' => $absensi->final_salary ?? $salaryData['final_salary'],
                'is_weekend_overtime' => $isWeekendOvertime,
            ]))->save();

            $absensi->load('user');
            $absensi->foto_pulang_url = Storage::url($absensi->foto_pulang);

            Notification::create([
                'user_id' => $absensi->user_id,
                'title' => 'Pengajuan Lembur Diajukan Ulang',
                'message' => 'Pengajuan lembur kamu telah diajukan ulang dan akan direview oleh approver yang menolak sebelumnya.',
                'type' => 'lembur_resubmitted',
                'target_page' => '/lembur_detail',
                'target_id' => $absensi->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil diajukan ulang.'.($isWeekendOvertime ? ' (Weekend Rate)' : ''),
                'data' => $absensi,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    public function getDetailAbsensi($id)
    {
        try {

            if (! is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID tidak valid.',
                ], 400);
            }

            $absensi = Absensi::with('user')->find($id);

            if (! $absensi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data absensi tidak ditemukan.',
                ], 404);
            }

            if ($absensi->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'absensi' => new AbsensiResource($absensi),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server. '.(config('app.debug') ? $e->getMessage() : ''),
            ], 500);
        }
    }

    public function pengajuanTelat(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'file_bukti' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'keterangan' => 'required|string|max:500',
                'absensi_id' => 'required|integer|exists:absensis,id',
            ]);

            $user = Auth::user();

            $absensi = Absensi::where('id', $request->absensi_id)
                ->where('user_id', $user->id)
                ->first();

            if (! $absensi) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Data absensi tidak ditemukan.',
                ], 404);
            }

            if (! $absensi->check_in_at) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum absen masuk.',
                ], 400);
            }

            if ($absensi->tipe !== null && $absensi->tipe !== 'hadir') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki pengajuan tipe lain ('.ucfirst($absensi->tipe).') yang sedang diproses.',
                ], 409);
            }

            $alreadyHasTelat = Absensi::where('parent_id', $absensi->id)
                ->where('tipe', 'telat')
                ->whereIn('status_approval', ['pending', 'approved'])
                ->exists();

            if ($alreadyHasTelat) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah mengajukan keterangan telat untuk hari ini.',
                ], 409);
            }

            $checkIn = Carbon::parse($absensi->check_in_at);
            $lateMinutes = FreelancePayrollCalculator::lateMinutesFromCheckIn($checkIn);

            if ($lateMinutes === 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak tercatat terlambat.',
                ], 400);
            }

            if ($absensi->file_bukti && Storage::disk('public')->exists($absensi->file_bukti)) {
                Storage::disk('public')->delete($absensi->file_bukti);
            }

            $fileBuktiPath = $request->file('file_bukti')->store('bukti_telat', 'public');

            $workflow = ['hrd' => 'pending'];

            $childTelat = Absensi::create([
                'user_id' => $user->id,
                'parent_id' => $absensi->id,
                'check_in_at' => $absensi->check_in_at,
                'status' => 'hadir',
                'tipe' => 'telat',
                'status_approval' => 'pending',
                'file_bukti' => $fileBuktiPath,
                'keterangan_izin_sakit' => $request->keterangan,
                'workflow_status' => $workflow,
                'current_approval_level' => 1,
                'late_minutes' => $lateMinutes,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Pengajuan Keterangan Telat',
                'message' => "Pengajuan keterangan telat ({$lateMinutes} menit) telah diajukan dan menunggu approval.",
                'type' => 'telat_submitted',
                'target_page' => '/telat_detail',
                'target_id' => $childTelat->id,
            ]);

            DB::commit();

            $childTelat->load('user');
            $childTelat->file_bukti_url = Storage::url($childTelat->file_bukti);

            return response()->json([
                'success' => true,
                'message' => "Pengajuan keterangan telat ({$lateMinutes} menit) berhasil diajukan.",
                'data' => $childTelat,
                'late_minutes' => $lateMinutes,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($fileBuktiPath) && Storage::disk('public')->exists($fileBuktiPath)) {
                Storage::disk('public')->delete($fileBuktiPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server. '.(config('app.debug') ? $e->getMessage() : ''),
            ], 500);
        }
    }
}
