<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    private function ensureHrd(): void
    {
        abort_unless(auth()->user()->role === 'hrd', 403);
    }

    public function index(Request $request)
    {
        $this->ensureHrd();

        return redirect()->route('admin.absensi.approval.hrd', [
            ...$request->query(),
            'section' => 'employees',
        ]);
    }

    public function edit(User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        $latestRate = $user->salaryRates()->latest('effective_at')->first();
        $nextEffectiveAt = now()->addMinute();
        if ($latestRate && $nextEffectiveAt->lessThanOrEqualTo($latestRate->effective_at)) {
            $nextEffectiveAt = $latestRate->effective_at->copy()->addMinute();
        }

        return view('admin.employees.edit', [
            'user' => $user,
            'dailyRate' => $user->dailyRateAt(),
            'nextEffectiveAt' => $nextEffectiveAt,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'id_karyawan' => ['required', 'string', 'max:255', Rule::unique('users', 'id_karyawan')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'departemen' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:organik,freelance'],
            'work_location' => ['required', 'in:office,produksi'],
            'nik' => ['required', 'digits:16'],
            'bank_name' => ['required', 'in:BCA,BRI'],
            'bank_account_number' => ['required', 'string', 'max:40'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'daily_rate' => ['required_if:employment_type,freelance', 'nullable', 'integer', 'min:1', 'max:100000000'],
            'effective_at' => ['required_if:employment_type,freelance', 'nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except([
                    'nik',
                    'phone',
                    'address',
                    'bank_name',
                    'bank_account_number',
                    'bank_account_name',
                ]));
        }

        $validated = $validator->validated();
        $dailyRate = $validated['daily_rate'] ?? null;
        $effectiveAt = isset($validated['effective_at'])
            ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['effective_at'])
            : null;
        unset($validated['daily_rate'], $validated['effective_at']);

        $latestRate = $user->salaryRates()->latest('effective_at')->first();
        $saveDailyRate = $dailyRate !== null
            && ($user->employment_type !== 'freelance'
                || ! $latestRate
                || (int) $latestRate->daily_rate !== (int) $dailyRate);

        if ($saveDailyRate && $latestRate && $effectiveAt->lessThanOrEqualTo($latestRate->effective_at)) {
            return back()
                ->withErrors(['effective_at' => 'Tanggal berlaku harus setelah perubahan tarif terakhir.'])
                ->withInput($request->except([
                    'nik',
                    'phone',
                    'address',
                    'bank_name',
                    'bank_account_number',
                    'bank_account_name',
                ]));
        }

        $user->update($validated);

        if ($saveDailyRate) {
            $user->salaryRates()->create([
                'daily_rate' => $dailyRate,
                'effective_at' => $effectiveAt,
                'recorded_by' => auth()->id(),
            ]);
        }

        ActivityLog::log('Update Employee Data', "User: {$user->name}", 'HRD memperbarui data karyawan.');

        return redirect()
            ->route('admin.employees.index')
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    public function deactivate(User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        DB::transaction(function () use ($user): void {
            $user->update(['is_active' => false]);
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        ActivityLog::log('Deactivate Employee', "User: {$user->name}", 'Akun dinonaktifkan; data dan riwayat tetap disimpan.');

        return back()->with('success', "Akun {$user->name} dinonaktifkan. Data dan riwayatnya tetap tersimpan.");
    }

    public function activate(User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        if ($user->registration_status !== 'approved') {
            return back()->with('error', 'Akun belum disetujui. Kirim ulang akses melalui halaman Data Karyawan terlebih dahulu.');
        }

        $user->update(['is_active' => true]);
        ActivityLog::log('Reactivate Employee', "User: {$user->name}", 'HRD mengaktifkan kembali akun karyawan.');

        return back()->with('success', "Akun {$user->name} aktif kembali.");
    }

    public function destroy(User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        if ($user->is_active) {
            return back()->with('error', 'Nonaktifkan akun terlebih dahulu sebelum menghapusnya secara permanen.');
        }

        $employeeName = $user->name;
        $employeeId = $user->id;

        DB::transaction(function () use ($user, $employeeId): void {
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $employeeId)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('payroll_items')->where('user_id', $employeeId)->delete();
            $user->delete();
        });

        ActivityLog::log('Permanently Delete Employee', 'Employee account and related records permanently deleted.', 'HRD menghapus permanen akun dan riwayat terkait.');

        return back()->with('success', "Akun {$employeeName} beserta riwayat absensi dan payroll berhasil dihapus permanen.");
    }
}
