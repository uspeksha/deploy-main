<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Notification;
use App\Models\ScheduledLembur;
use App\Models\IzinKeluar;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\EmployeeRegistrationMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApprovalController extends Controller
{
    protected $workflowMap = [
        'produksi' => [1 => 'HRD'],
        'office' => [1 => 'HRD'],
    ];

    private $workflowTemplates = [
        'produksi' => ['hrd' => 'pending'],
        'office' => ['hrd' => 'pending'],
    ];

    private function ensureHrdApprover(): void
    {
        abort_unless(auth()->user()->role === 'hrd', 403);
    }

    public function index()
    {
        return redirect()->route('admin.absensi.approval.hrd');
    }

    private function getSubmissions(Request $request, string $status = 'pending')
    {
        $query = Absensi::with('user')->whereIn('status_approval', ['pending', 'approved', 'rejected']);

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status_approval', $status);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_karyawan', 'like', "%{$search}%");
            });
        }

        return $query->where(function ($query) {
            $query->whereNull('parent_id')
                ->orWhereIn('absensis.tipe', ['telat', 'lembur']);
        })
            ->join('users', 'absensis.user_id', '=', 'users.id')
            ->select('absensis.*')
            ->orderBy('users.name')
            ->orderByDesc('absensis.check_in_at')
            ->get();
    }

    public function hrd(Request $request)
    {
        $this->ensureHrdApprover();
        $status = $request->input('status', 'pending');
        $submissions = $this->getSubmissions($request, $status);
        $scheduledLembur = ScheduledLembur::where('status', 'pending')->with('user')->orderBy('tanggal_lembur')->get();
        $izinKeluar = IzinKeluar::where('status_izin', 'selesai')->where('status_approval', 'pending')
            ->with('user')->orderBy('waktu_keluar')->get();
        $pendingAccounts = auth()->user()->role === 'hrd'
            ? User::where('registration_status', 'pending')
                ->where('is_active', false)
                ->orderBy('created_at')
                ->get()
            : collect();

        return view('admin.absensi.approval.hrd', compact(
            'submissions',
            'scheduledLembur',
            'izinKeluar',
            'status',
            'pendingAccounts',
        ));
    }

    public function createEmployeeAccount(
        Request $request,
        EmployeeRegistrationMailer $registrationMailer,
    ) {
        abort_unless(auth()->user()->role === 'hrd', 403);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'id_karyawan' => ['required', 'string', 'max:255', 'unique:users,id_karyawan'],
            'departemen' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:organik,freelance'],
            'work_location' => ['required', 'in:office,produksi'],
            'nik' => ['required', 'digits:16'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:40'],
            'bank_account_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except([
                    'nik',
                    'bank_name',
                    'bank_account_number',
                    'bank_account_name',
                ]));
        }

        $validated = $validator->validated();
        $temporaryPassword = Str::random(32);
        $user = User::create([
            ...$validated,
            'password' => Hash::make($temporaryPassword),
            'role' => 'user',
            'registration_status' => 'pending',
            'is_active' => false,
        ]);

        $emailSent = $registrationMailer->sendTemporaryPassword($user, $temporaryPassword);
        if ($emailSent) {
            $user->update([
                'registration_status' => 'approved',
                'is_active' => true,
            ]);
        }

        ActivityLog::log(
            'Create Employee Account',
            "User: {$user->name}",
            $emailSent
                ? 'HRD membuat akun karyawan dan mengirim password sementara melalui email.'
                : 'HRD membuat akun karyawan; pengiriman password sementara gagal.',
        );

        return back()->with(
            $emailSent ? 'success' : 'warning',
            $emailSent
                ? 'Akun berhasil dibuat dan password sementara dikirim ke email karyawan.'
                : 'Akun tersimpan tetapi belum aktif karena email gagal dikirim. Konfigurasi SMTP lalu gunakan tombol kirim ulang.',
        );
    }

    public function resendEmployeeCredentials(
        User $user,
        EmployeeRegistrationMailer $registrationMailer,
    ) {
        abort_unless(auth()->user()->role === 'hrd', 403);

        if ($user->registration_status !== 'pending' || $user->is_active) {
            return back()->with('error', 'Akun ini tidak menunggu pengiriman akses.');
        }

        $temporaryPassword = Str::random(32);
        $user->update(['password' => Hash::make($temporaryPassword)]);

        if (!$registrationMailer->sendTemporaryPassword($user, $temporaryPassword)) {
            return back()->with('warning', 'Email gagal dikirim. Akun tetap tidak aktif; periksa konfigurasi SMTP lalu coba lagi.');
        }

        $user->update([
            'registration_status' => 'approved',
            'is_active' => true,
        ]);

        ActivityLog::log(
            'Resend Employee Credentials',
            "User: {$user->name}",
            'HRD mengirim ulang password sementara dan mengaktifkan akun karyawan.',
        );

        return back()->with('success', 'Password sementara berhasil dikirim dan akun karyawan sudah aktif.');
    }

    public function supervisor(Request $request)
    {
        return redirect()->route('admin.absensi.approval.hrd');
    }

    public function manager(Request $request)
    {
        return redirect()->route('admin.absensi.approval.hrd');
    }

    public function hrga(Request $request)
    {
        return redirect()->route('admin.absensi.approval.hrd');
    }

    public function handleAction(Request $request, Absensi $absensi, string $action)
    {
        $this->ensureHrdApprover();

        if ($absensi->status_approval !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }

        $userTipe = $absensi->user->work_location ?? 'office';
        $currentLevel = $absensi->current_approval_level ?? 1;
        $workflowMap = $this->workflowMap[$userTipe] ?? $this->workflowMap['office'];
        $currentApprover = 'HRD';
        $workflowKey = 'hrd';

        $workflowStatus = ['hrd' => 'pending'];

        $submissionType = $absensi->tipe ?? 'absensi';
        $targetPageMap = [
            'lembur'  => '/lembur_detail',
            'sakit'   => '/sakit_detail',
            'izin'    => '/izin_detail',
            'absensi' => '/absensi_detail',
        ];
        $targetPage = $targetPageMap[$submissionType] ?? '/absensi';

        // =====================================================
        // 🟥 REJECT ACTION
        // =====================================================
        if ($action === 'reject') {
    $request->validate(['catatan_admin' => 'required|min:5']);
    $workflowStatus[$workflowKey] = 'rejected';
    $rejectedBy = $workflowKey;
    $resubmitLevel = $this->determineResubmitLevel($rejectedBy, $workflowStatus);
    $resetWorkflow = $this->resetWorkflowFromLevel($workflowStatus, $resubmitLevel, $userTipe);

    // ✅ CEK APAKAH INI IZIN/SAKIT (GAK ADA GAJI)
    $isLeaveType = in_array(strtolower($absensi->status ?? ''), ['izin', 'sakit']) ||
                   in_array(strtolower($absensi->tipe ?? ''), ['izin', 'sakit']);

    Log::info('🔍 [REJECT] Processing rejection', [
        'id' => $absensi->id,
        'tipe' => $absensi->tipe,
        'status' => $absensi->status,
        'is_leave_type' => $isLeaveType,
    ]);

    // ✅ HITUNG GAJI BERDASARKAN TIPE
    if ($isLeaveType) {
        // IZIN/SAKIT → Gak ada gaji
        $finalSalary = 0;
        $baseSalary = 0;
        $latePenalty = 0;

        Log::info('💰 [REJECT] Leave type - No salary');

    } else {
        // HADIR/LEMBUR → Ada gaji pokok, tapi lembur gak masuk

        // Pastikan base_salary udah ada (kalo belum, hitung dulu)
        if ($absensi->base_salary === null) {
            $salaryData = \App\Models\Absensi::calculateSalary(
                $absensi->late_minutes ?? 0,
                $absensi->status,
                null, // Bukan lembur
                $absensi->is_weekend_overtime ?? false
            );

            $baseSalary = $salaryData['base_salary'];
            $latePenalty = $salaryData['late_penalty'];

            Log::info('💰 [REJECT] Calculated base salary', [
                'base_salary' => $baseSalary,
                'late_penalty' => $latePenalty,
            ]);

        } else {
            // Udah ada, pake yang ada
            $baseSalary = $absensi->base_salary;
            $latePenalty = $absensi->late_penalty ?? 0;

            Log::info('💰 [REJECT] Using existing base salary', [
                'base_salary' => $baseSalary,
                'late_penalty' => $latePenalty,
            ]);
        }

        // FINAL SALARY = Gaji Pokok - Potongan (TANPA LEMBUR)
        $finalSalary = $baseSalary - $latePenalty;

        Log::info('💰 [REJECT] Final salary (no overtime)', [
            'final_salary' => $finalSalary,
        ]);
    }

    // ✅ UPDATE DATABASE
    $absensi->forceFill([
        'status_approval' => 'rejected',
        'catatan_admin' => $request->catatan_admin,
        'rejected_by' => $rejectedBy,
        'rejected_at' => now(),
        'workflow_status' => $resetWorkflow,
        'current_approval_level' => $resubmitLevel,

        // ✅ ACTIVITY LOG
        // Log penolakan absensi
        
        // ✅ RESET LEMBUR (karena lembur ditolak)
        'overtime_minutes' => 0,
        'overtime_pay'     => 0,

        // ✅ TETEP KASIH GAJI POKOK (kalo bukan izin/sakit)
        'base_salary'   => $baseSalary,
        'late_penalty'  => $latePenalty,
        'final_salary'  => $finalSalary, // ✅ Gaji pokok - potongan (tanpa lembur)
    ])->save();

    \App\Models\ActivityLog::log('Reject Pengajuan', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name}", "Alasan: {$request->catatan_admin}");

    Log::info('✅ [REJECT] Updated main record', [
        'id' => $absensi->id,
        'final_salary' => $finalSalary,
    ]);

    // ✅ UPDATE CHILDREN (kalo ada)
    if ($absensi->children()->exists()) {
        $absensi->children()->update([
            'status_approval' => 'rejected',
            'workflow_status' => $resetWorkflow,
            'current_approval_level' => $resubmitLevel,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'catatan_admin' => $request->catatan_admin,
            'approved_at' => null,
            'overtime_minutes' => 0,
            'overtime_pay' => 0,
            'base_salary' => $baseSalary,
            'late_penalty' => $latePenalty,
            'final_salary' => $finalSalary,
        ]);

        Log::info("✅ Synced REJECT status to " . $absensi->children()->count() . " child records");
    }

    // ✅ NOTIFIKASI
    Notification::create([
        'user_id' => $absensi->user_id,
        'title' => "Pengajuan " . ucfirst($submissionType) . " Ditolak ❌",
        'message' => "Pengajuan kamu ditolak oleh $currentApprover. Alasan: " . $request->catatan_admin,
        'type' => "{$submissionType}_rejected",
        'target_page' => $targetPage,
        'target_id' => $absensi->id,
    ]);

    return back()->with('success', 'Pengajuan ditolak. Gaji pokok tetap dihitung.');
}

        // =====================================================
        // ✅ APPROVE ACTION
        // =====================================================
        if ($action === 'approve') {
            $maxLevel = count($workflowMap);
            $workflowStatus[$workflowKey] = 'approved';

            if ($currentLevel >= $maxLevel) {
                // FINAL APPROVAL (HRGA)

                    // 🆕 INTEGRASI KOREKSI ABSENSI
                    if ($absensi->tipe === 'koreksi lupa absen') {
                        DB::transaction(function () use ($absensi, $workflowStatus, $currentApprover, $submissionType, $targetPage) {
                            $updateData = [
                                'status' => 'hadir',
                                'tipe' => 'normal',
                                'status_approval' => 'approved',
                                'approved_at' => now(),
                                'workflow_status' => $workflowStatus,
                                'rejected_by' => null,
                                'rejected_at' => null,
                            ];

                            if ($absensi->user->employment_type === 'organik') {
                                $updateData['base_salary'] = 0;
                                $updateData['final_salary'] = 0;
                            } else {
                                $salaryData = \App\Models\Absensi::calculateSalary(
                                    0,
                                    'hadir',
                                    'normal',
                                    \App\Models\Absensi::isWeekend($absensi->check_in_at),
                                    $absensi->check_in_at,
                                    $absensi->check_out_at,
                                    $absensi->user->employment_type,
                                    $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN'
                                );
                                $updateData['base_salary'] = $salaryData['base_salary'];
                                $updateData['final_salary'] = $salaryData['final_salary'];
                            }

                            $absensi->update($updateData);

                            Notification::create([
                                'user_id' => $absensi->user_id,
                                'title' => "Pengajuan Koreksi Disetujui ✅",
                                'message' => "Pengajuan koreksi absen kamu telah disetujui oleh $currentApprover.",
                                'type' => "koreksi_approved",
                                'target_page' => '/absensi',
                                'target_id' => $absensi->id,
                            ]);

                            \App\Models\ActivityLog::log('Approve Koreksi Final', "Koreksi ID: {$absensi->id} dari {$absensi->user->name}", "Persetujuan final oleh: {$currentApprover}");
                        });
                        return back()->with('success', 'Koreksi berhasil disetujui.');
                    }

                    // 🔥 FIX: CEK APAKAH INI IZIN/SAKIT (BUKAN LEMBUR/HADIR)
                    $isLeaveType = in_array(strtolower($absensi->status ?? ''), ['izin', 'sakit']) ||
                                   in_array(strtolower($absensi->tipe ?? ''), ['izin', 'sakit']);

                Log::info('🔍 [APPROVAL] Processing final approval', [
                    'id' => $absensi->id,
                    'tipe' => $absensi->tipe,
                    'status' => $absensi->status,
                    'is_leave_type' => $isLeaveType,
                    'has_children' => $absensi->children()->exists(),
                ]);

                // 🔥 FIX: JANGAN HITUNG GAJI UNTUK IZIN/SAKIT
                // 🔥 MODIFIKASI FINAL APPROVAL UNTUK IZIN/SAKIT 🔥
                if ($isLeaveType) {
                    Log::info('⏭️ [FINAL APPROVAL] Leave type processing');

                    DB::transaction(function () use ($absensi, $workflowStatus, $currentApprover, $submissionType, $targetPage) {
                        // 1. UPDATE DATA UTAMA (PARENT)
                        $updateData = [
                            'status_approval' => 'approved',
                            'approved_at' => now(),
                            'workflow_status' => $workflowStatus,
                            'rejected_by' => null,
                            'rejected_at' => null,
                            'overtime_minutes' => 0,
                            'overtime_pay' => 0,
                            'base_salary' => 0,
                            'late_penalty' => 0,
                            'final_salary' => 0,
                        ];

                        // 🆕 LOGIKA IZIN SETENGAH HARI
                        if ($absensi->jam_pulang_rencana) {
                            $checkInDate = \Carbon\Carbon::parse($absensi->check_in_at)->format('Y-m-d');
                            $checkOutDateTime = \Carbon\Carbon::parse($checkInDate . ' ' . $absensi->jam_pulang_rencana);
                            $updateData['check_out_at'] = $checkOutDateTime;
                            
                            // Hitung gaji ulang karena jam pulang berubah
                            $salaryData = \App\Models\Absensi::calculateSalary(
                                $absensi->late_minutes ?? 0,
                                'hadir', // Anggap hadir karena kerja setengah hari
                                null,
                                $absensi->is_weekend_overtime ?? false,
                                $absensi->check_in_at,
                                $checkOutDateTime,
                                $absensi->user->kategori_absensi,
                                $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN'
                            );
                            
                            $updateData['status'] = 'hadir'; // Ubah status jadi hadir tapi durasi kurang
                            $updateData['base_salary'] = $salaryData['base_salary'];
                            $updateData['late_penalty'] = $salaryData['late_penalty'];
                            $updateData['final_salary'] = $salaryData['final_salary'];
                        }

                        $absensi->update($updateData);

                        
$user = \App\Models\User::find($absensi->user_id);


if ($user && $absensi->parent_id === null && $absensi->hari_potong_cuti > 0) {
    $hariCuti = $absensi->hari_potong_cuti;
    
    $user->update([
        'sisa_cuti' => max(0, $user->sisa_cuti - $hariCuti),
        'total_cuti_diambil' => $user->total_cuti_diambil + $hariCuti,
    ]);
    Log::info('✅ [CUTI] Berhasil potong saldo saat approve', [
        'user' => $user->name,
        'hari_potong' => $hariCuti,
        'hari_unpaid' => $absensi->hari_unpaid,
        'sisa_cuti_sekarang' => $user->fresh()->sisa_cuti,
    ]);
} else {
    Log::info('⏭️ [CUTI] Skip potong — bukan parent atau tidak ada hari yang dipotong.');
}

                        // 3. UPDATE SEMUA ANAKNYA (CHILD RECORDS) JIKA MULTI-DAY
                        if ($absensi->children()->exists()) {
                            $absensi->children()->update([
                                'status_approval' => 'approved',
                                'workflow_status' => $workflowStatus,
                                'current_approval_level' => $absensi->current_approval_level,
                                'approved_at' => now(),
                                'rejected_by' => null,
                                'rejected_at' => null,
                                'base_salary' => 0,
                                'late_penalty' => 0,
                                'final_salary' => 0,
                            ]);
                            Log::info("✅ [SYNC] Anak record ikut di-approve");
                        }

                        // 4. KIRIM NOTIFIKASI KE USER
                        \App\Models\Notification::create([
                            'user_id' => $absensi->user_id,
                            'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                            'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                            'type' => "{$submissionType}_approved",
                            'target_page' => $targetPage,
                            'target_id' => $absensi->id,
                        ]);

                        \App\Models\ActivityLog::log('Approve Pengajuan Final', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name}", "Persetujuan final oleh: {$currentApprover}");
                    });

                } else {

                    // Detect kategori
                    $idKaryawan = $absensi->user->id_karyawan ?? '';
                    $kategori = strtolower($absensi->user->employment_type ?? 'organik');
                    if (strpos($idKaryawan, 'CS-AMB') === 0) $kategori = 'borongan';
                    elseif (strpos($idKaryawan, 'MG-AMB') === 0) $kategori = 'magang';
                    elseif (strpos($idKaryawan, 'AMB') === 0) $kategori = 'freelance';

                    // Pastikan base_salary & late_penalty ada
                    if ($absensi->base_salary === null) {
                        $salaryData = Absensi::calculateSalary(
                            $absensi->late_minutes ?? 0, 
                            $absensi->status, 
                            $absensi->tipe,
                            false,
                            $absensi->check_in_at,
                            $absensi->check_out_at,
                            $kategori,
                            $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN'
                        );
                        $absensi->base_salary = $salaryData['base_salary'];
                        $absensi->late_penalty = $salaryData['late_penalty'];
                        $absensi->save();
                        $absensi->refresh();
                    }

                    $overtimeMinutes = 0;
                    $overtimePay = 0;

                    // HITUNG LEMBUR (hanya untuk tipe lembur yang punya data jam)
                    if (strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
                        try {
                            $overtimeData = Absensi::calculateOvertimeFromInput(
                                $absensi->lembur_start,
                                $absensi->lembur_end,
                                (bool) $absensi->lembur_rest,
                                (bool) $absensi->is_weekend_overtime
                            );

                            $overtimeMinutes = $overtimeData['minutes'];
                            $overtimePay = $overtimeData['pay'];

                            Log::info('✅ [OVERTIME] Calculated', [
                                'minutes' => $overtimeMinutes,
                                'pay' => $overtimePay
                            ]);

                        } catch (\Exception $e) {
                            Log::error('❌ Gagal kalkulasi lembur saat approval', [
                                'absensi_id' => $absensi->id,
                                'error' => $e->getMessage()
                            ]);
                            $overtimeMinutes = 0;
                            $overtimePay = 0;
                        }
                    }

                    // HITUNG FINAL SALARY
                    $gajiPokok = $absensi->base_salary ?? 0;
                    $potongan  = $absensi->late_penalty ?? 0;
                    $newFinalSalary = ($gajiPokok - $potongan) + $overtimePay;

                    Log::info('💰 [SALARY] Calculation', [
                        'base' => $gajiPokok,
                        'penalty' => $potongan,
                        'overtime' => $overtimePay,
                        'final' => $newFinalSalary
                    ]);

                    
                        DB::transaction(function () use ($absensi, $workflowStatus, $overtimeMinutes, $overtimePay, $newFinalSalary, $currentApprover, $submissionType, $targetPage) {

                            // 🔥 FIX: JIKA INI RECORD ANAK, UPDATE DATA KE PARENT
                            if ($absensi->parent_id) {
                                $parent = \App\Models\Absensi::find($absensi->parent_id);
                                if ($parent) {
                                    // Jika anak adalah 'telat', reset parent's late_minutes ke 0 DAN JAM MASUK KE 08:00
                                    if ($absensi->tipe === 'telat') {
                                        $checkInDate = \Carbon\Carbon::parse($parent->check_in_at)->format('Y-m-d');
                                        $parent->update([
                                            'check_in_at' => $checkInDate . ' 08:00:00', // <--- Pindah ke parent!
                                            'late_minutes' => 0,
                                            'late_penalty' => 0,
                                            'final_salary' => $parent->base_salary + ($parent->overtime_pay ?? 0),
                                        ]);
                                    }
                                    // Jika anak adalah 'lembur', update parent's overtime
                                    if ($absensi->tipe === 'lembur') {
                                        $parent->update([
                                            'overtime_minutes' => ($parent->overtime_minutes ?? 0) + $overtimeMinutes,
                                            'overtime_pay' => ($parent->overtime_pay ?? 0) + $overtimePay,
                                            'final_salary' => ($parent->base_salary ?? 0) - ($parent->late_penalty ?? 0) + (($parent->overtime_pay ?? 0) + $overtimePay),
                                        ]);
                                    }
                                }
                            }

                            // ✅ KALAU TIPE TELAT DAN DIAPPROVE → STATUS APPROVED
                            $updateData = [
                                'status_approval' => 'approved',
                                'approved_at'     => now(),
                                'workflow_status' => $workflowStatus,
                                'rejected_by'     => null,
                                'rejected_at'     => null,
                                'overtime_minutes'=> $overtimeMinutes,
                                'overtime_pay'    => $overtimePay,
                                'final_salary'    => $newFinalSalary,
                            ];

                            if (strtolower($absensi->tipe ?? '') === 'telat') {
                                // Tipe tetap telat di child, tapi parent sudah direset
                                $updateData['tipe'] = 'telat'; 
                                
                                Log::info('✅ [TELAT APPROVED] Parent record updated', [
                                    'id' => $absensi->id,
                                    'parent_id' => $absensi->parent_id
                                ]);
                            }

                            $absensi->update($updateData);

                            Log::info('✅ [APPROVED] Attendance/Overtime record updated', [
                                'id' => $absensi->id,
                                'final_salary' => $newFinalSalary
                            ]);

                            // Notifikasi
                            Notification::create([
                                'user_id' => $absensi->user_id,
                                'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                                'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                                'type' => "{$submissionType}_approved",
                                'target_page' => $targetPage,
                                'target_id' => $absensi->id,
                            ]);

                            \App\Models\ActivityLog::log('Approve Pengajuan Final', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name} (Gaji Dihitung)", "Persetujuan final oleh: {$currentApprover}");
                        });
                }

            } else {
                // Belum level terakhir → lanjut ke level berikutnya
                $absensi->update([
                    'current_approval_level' => $currentLevel + 1,
                    'workflow_status' => $workflowStatus,
                ]);

                if ($absensi->children()->exists()) {
                    $absensi->children()->update([
                        'current_approval_level' => $currentLevel + 1,
                        'workflow_status' => $workflowStatus,
                    ]);

                    Log::info("✅ Synced approval level to " . $absensi->children()->count() . " child records");
                }

                \App\Models\ActivityLog::log('Approve Pengajuan (Bukan Final)', "Pengajuan ID: {$absensi->id} dari {$absensi->user->name}", "Naik level ke: " . ($currentLevel + 1));
            }

            return back()->with('success', 'Berhasil disetujui.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }

    private function determineResubmitLevel($rejectedBy, $workflowStatus)
    {
        return 1;
    }

    private function resetWorkflowFromLevel($workflow, $startLevel, $employment)
    {
        return ['hrd' => 'pending'];
    }

    public function handleScheduledLemburAction(Request $request, $id, $action)
    {
        $this->ensureHrdApprover();
        $record = ScheduledLembur::with('user')->findOrFail($id);

        if ($action === 'approve') {
            $record->update(['status' => 'approved', 'current_approval_level' => 1]);
                
                $tanggal = \Carbon\Carbon::parse($record->tanggal_lembur)->translatedFormat('d F Y');
                \App\Models\Notification::create([
                    'user_id'     => $record->user_id,
                    'title'       => 'Lembur Terjadwal Disetujui ✅',
                    'message'     => "Lembur terjadwal kamu pada $tanggal telah disetujui sepenuhnya.",
                    'type'        => 'scheduled_lembur_approved',
                    'target_page' => '/jadwal_lembur',
                    'target_id'   => $record->id,
                ]);
                
                \App\Models\ActivityLog::log('Approve Jadwal Lembur HRD', "Jadwal ID: {$record->id} dari {$record->user->name}", "Persetujuan HRD");
                return back()->with('success', 'Lembur terjadwal berhasil disetujui oleh HRD.');
        }

        if ($action === 'reject') {
            $request->validate(['catatan_admin' => 'required|min:5']);
            $record->update(['status' => 'rejected']);
            $tanggal = \Carbon\Carbon::parse($record->tanggal_lembur)->translatedFormat('d F Y');
            \App\Models\Notification::create([
                'user_id'     => $record->user_id,
                'title'       => 'Lembur Terjadwal Ditolak ❌',
                'message'     => "Lembur terjadwal kamu pada $tanggal ditolak. Alasan: {$request->catatan_admin}",
                'type'        => 'scheduled_lembur_rejected',
                'target_page' => '/jadwal_lembur',
                'target_id'   => $record->id,
            ]);
            
            \App\Models\ActivityLog::log('Reject Jadwal Lembur', "Jadwal ID: {$record->id} dari {$record->user->name}", "Alasan: {$request->catatan_admin}");
            return back()->with('success', 'Lembur terjadwal berhasil ditolak.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }
    public function handleIzinKeluarAction(Request $request, $id, $action)
    {
        $this->ensureHrdApprover();
        $record = IzinKeluar::with('user')->findOrFail($id);

        if ($action === 'approve') {
            $record->update([
                'status_approval' => 'approved',
                'current_approval_level' => 1,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

                \App\Models\Notification::create([
                    'user_id'     => $record->user_id,
                    'title'       => 'Izin Keluar Disetujui ✅',
                    'message'     => 'Izin Keluar kamu telah disetujui sepenuhnya, gaji hari ini tetap dibayar penuh.',
                    'type'        => 'izin_keluar_approved',
                    'target_page' => '/izin-keluar',
                    'target_id'   => $record->id,
                ]);

                \App\Models\ActivityLog::log('Approve Izin Keluar HRD', "Izin ID: {$record->id} dari {$record->user->name}", "Persetujuan HRD");
                return back()->with('success', 'Izin Keluar berhasil disetujui HRD.');
        }

        if ($action === 'reject') {
            $request->validate(['catatan_admin' => 'required|min:5']);

            $record->update([
                'status_approval' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Potong gaji pokok sehari penuh (overtime tetap aman)
            $absensi = Absensi::where('user_id', $record->user_id)
                ->whereDate('check_in_at', \Carbon\Carbon::parse($record->waktu_keluar)->toDateString())
                ->first();

            if ($absensi) {
                $absensi->update([
                    'base_salary' => 0,
                    'late_penalty' => 0,
                    'final_salary' => 0,
                ]);
            }

            \App\Models\Notification::create([
                'user_id'     => $record->user_id,
                'title'       => 'Izin Keluar Ditolak ❌',
                'message'     => "Izin Keluar kamu ditolak. Gaji hari ini tidak dibayar. Alasan: {$request->catatan_admin}",
                'type'        => 'izin_keluar_rejected',
                'target_page' => '/izin-keluar',
                'target_id'   => $record->id,
            ]);

            \App\Models\ActivityLog::log('Reject Izin Keluar', "Izin ID: {$record->id} dari {$record->user->name}", "Alasan: {$request->catatan_admin}, gaji hari itu dinolkan");
            return back()->with('success', 'Izin Keluar berhasil ditolak, gaji hari itu tidak dibayar.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }

    public function handleBulkAction(Request $request)
    {
        $this->ensureHrdApprover();
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $count = 0;
        foreach ($ids as $id) {
            $absensi = Absensi::find($id);
            if ($absensi && $absensi->status_approval === 'pending') {
                // Gunakan logic yang sama dengan handleAction
                // Untuk mempermudah, kita panggil logic approval di sini
                // Kita asumsikan action adalah 'approve' karena tombolnya 'Approve Terpilih'
                
                $this->approveSingleRecord($absensi);
                $count++;
            }
        }

        return back()->with('success', "Berhasil menyetujui {$count} pengajuan sekaligus.");
    }

    private function approveSingleRecord(Absensi $absensi)
    {
        $userTipe = $absensi->user->work_location ?? 'office';
        $currentLevel = $absensi->current_approval_level ?? 1;
        $currentApprover = 'HRD';
        $maxLevel = 1;
        $workflowKey = 'hrd';

        $workflowStatus = ['hrd' => 'pending'];

        $workflowStatus[$workflowKey] = 'approved';
        $submissionType = $absensi->tipe ?? 'absensi';
        $targetPageMap = [
            'lembur'  => '/lembur_detail',
            'sakit'   => '/sakit_detail',
            'izin'    => '/izin_detail',
            'absensi' => '/absensi_detail',
        ];
        $targetPage = $targetPageMap[$submissionType] ?? '/absensi';

        if ($currentLevel >= $maxLevel) {
            // FINAL APPROVAL
            $isLeaveType = in_array(strtolower($absensi->status ?? ''), ['izin', 'sakit']) ||
                           in_array(strtolower($absensi->tipe ?? ''), ['izin', 'sakit']);

            if ($isLeaveType) {
                DB::transaction(function () use ($absensi, $workflowStatus, $currentApprover, $submissionType, $targetPage) {
                    // Cek jika izin pulang cepat
                    $updateData = [
                        'status_approval' => 'approved',
                        'approved_at' => now(),
                        'workflow_status' => $workflowStatus,
                        'rejected_by' => null,
                        'rejected_at' => null,
                        'overtime_minutes' => 0,
                        'overtime_pay' => 0,
                    ];

                    if ($absensi->submission_type === 'izin_pulang_cepat' && $absensi->jam_pulang_rencana) {
                        $checkIn = \Carbon\Carbon::parse($absensi->check_in_at);
                        $checkOut = \Carbon\Carbon::parse($absensi->check_in_at)->setTimeFromTimeString($absensi->jam_pulang_rencana);
                        
                        $updateData['check_out_at'] = $checkOut;
                        
                        $isWeekend = \App\Models\Absensi::isWeekend($checkIn);
                        $kategori = $absensi->user->kategori_absensi ?? 'organik';
                        $lateMinutes = $absensi->late_minutes ?? 0;
                        
                        $salaryData = \App\Models\Absensi::calculateSalary(
                            $lateMinutes,
                            'hadir',
                            null,
                            $isWeekend,
                            $checkIn,
                            $checkOut,
                            $kategori,
                            $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN'
                        );
                        
                        $updateData['base_salary'] = $salaryData['base_salary'];
                        $updateData['late_penalty'] = $salaryData['late_penalty'];
                        $updateData['final_salary'] = $salaryData['final_salary'];
                    } else {
                        $updateData['base_salary'] = 0;
                        $updateData['late_penalty'] = 0;
                        $updateData['final_salary'] = 0;
                    }

                    $absensi->update($updateData);

                    $user = \App\Models\User::find($absensi->user_id);
                    if ($user && $absensi->parent_id === null && $absensi->hari_potong_cuti > 0) { 
                        $hariCuti = $absensi->hari_potong_cuti;
                        $user->update([
                            'sisa_cuti' => $user->sisa_cuti - $hariCuti,
                            'total_cuti_diambil' => $user->total_cuti_diambil + $hariCuti,
                        ]);
                        Log::info('✅ [CUTI] Berhasil potong saldo (bulk approval)', [
                            'user' => $user->name,
                            'submission_type' => $absensi->submission_type,
                            'hari' => $hariCuti
                        ]);
                    }

                    if ($absensi->children()->exists()) {
                        $absensi->children()->update([
                            'status_approval' => 'approved',
                            'workflow_status' => $workflowStatus,
                            'current_approval_level' => $absensi->current_approval_level,
                            'approved_at' => now(),
                            'rejected_by' => null,
                            'rejected_at' => null,
                            'base_salary' => 0,
                            'late_penalty' => 0,
                            'final_salary' => 0,
                        ]);
                    }

                    Notification::create([
                        'user_id' => $absensi->user_id,
                        'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                        'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                        'type' => "{$submissionType}_approved",
                        'target_page' => $targetPage,
                        'target_id' => $absensi->id,
                    ]);

                    \App\Models\ActivityLog::log('Approve Pengajuan Final (Bulk)', "Pengajuan ID: {$absensi->id}", "Persetujuan final oleh: {$currentApprover}");
                });
            } else {
                // HADIR/LEMBUR
                $idKaryawan = $absensi->user->id_karyawan ?? '';
                $kategori = strtolower($absensi->user->employment_type ?? 'organik');
                if (strpos($idKaryawan, 'CS-AMB') === 0) $kategori = 'borongan';
                elseif (strpos($idKaryawan, 'MG-AMB') === 0) $kategori = 'magang';
                elseif (strpos($idKaryawan, 'AMB') === 0) $kategori = 'freelance';

                if ($absensi->base_salary === null) {
                    $salaryData = Absensi::calculateSalary($absensi->late_minutes ?? 0, $absensi->status, $absensi->tipe, false, $absensi->check_in_at, $absensi->check_out_at, $kategori, $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN');
                    $absensi->base_salary = $salaryData['base_salary'];
                    $absensi->late_penalty = $salaryData['late_penalty'];
                    $absensi->save();
                    $absensi->refresh();
                }

                $overtimePay = 0;
                $overtimeMinutes = 0;
                if (strtolower($absensi->tipe ?? '') === 'lembur' && $absensi->lembur_start && $absensi->lembur_end) {
                    $overtimeData = Absensi::calculateOvertimeFromInput($absensi->lembur_start, $absensi->lembur_end, (bool) $absensi->lembur_rest, (bool) $absensi->is_weekend_overtime);
                    $overtimeMinutes = $overtimeData['minutes'];
                    $overtimePay = $overtimeData['pay'];
                }

                $gajiPokok = $absensi->base_salary ?? 0;
                $potongan  = $absensi->late_penalty ?? 0;
                $newFinalSalary = ($gajiPokok - $potongan) + $overtimePay;

                DB::transaction(function () use ($absensi, $workflowStatus, $overtimeMinutes, $overtimePay, $newFinalSalary, $currentApprover, $submissionType, $targetPage) {
                    $updateData = [
                        'status_approval' => 'approved',
                        'approved_at'     => now(),
                        'workflow_status' => $workflowStatus,
                        'rejected_by'     => null,
                        'rejected_at'     => null,
                        'overtime_minutes'=> $overtimeMinutes,
                        'overtime_pay'    => $overtimePay,
                        'final_salary'    => $newFinalSalary,
                    ];

                    if (strtolower($absensi->tipe ?? '') === 'telat') {
                        $checkInDate = \Carbon\Carbon::parse($absensi->check_in_at)->format('Y-m-d');
                        $updateData['check_in_at']  = $checkInDate . ' 08:00:00';
                        $updateData['late_minutes'] = 0;
                        $updateData['rounded_late_minutes'] = 0;
                        $updateData['late_penalty'] = 0;
                        $updateData['tipe']         = null;
                        $updateData['final_salary'] = $absensi->base_salary ?? $newFinalSalary;
                    }

                    $absensi->update($updateData);

                    Notification::create([
                        'user_id' => $absensi->user_id,
                        'title' => "Pengajuan " . ucfirst($submissionType) . " Disetujui ✅",
                        'message' => "Pengajuan kamu telah disetujui penuh oleh $currentApprover.",
                        'type' => "{$submissionType}_approved",
                        'target_page' => $targetPage,
                        'target_id' => $absensi->id,
                    ]);

                    \App\Models\ActivityLog::log('Approve Pengajuan Final (Bulk)', "Pengajuan ID: {$absensi->id}", "Persetujuan final oleh: {$currentApprover}");
                });
            }
        } else {
            // BUKAN FINAL
            $absensi->update([
                'current_approval_level' => $currentLevel + 1,
                'workflow_status' => $workflowStatus,
            ]);

            if ($absensi->children()->exists()) {
                $absensi->children()->update([
                    'current_approval_level' => $currentLevel + 1,
                    'workflow_status' => $workflowStatus,
                ]);
            }

            \App\Models\ActivityLog::log('Approve Pengajuan (Bulk)', "Pengajuan ID: {$absensi->id}", "Naik level ke: " . ($currentLevel + 1));
        }
    }
}
