<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\PtkpStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    private function employeeListQuery(Request $request): array
    {
        $employeeStatus = $request->query('employee_status');
        $employeeSearch = $request->query('employee_search');
        $employeePage = filter_var($request->query('employee_page'), FILTER_VALIDATE_INT);

        return array_filter([
            'section' => 'employees',
            'employee_status' => in_array($employeeStatus, ['active', 'inactive', 'all'], true)
                ? $employeeStatus
                : null,
            'employee_search' => is_string($employeeSearch) && trim($employeeSearch) !== ''
                ? trim($employeeSearch)
                : null,
            'employee_page' => $employeePage !== false && $employeePage > 0
                ? $employeePage
                : null,
        ], fn ($value) => $value !== null);
    }

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

    public function edit(Request $request, User $user)
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
            'employeeListQuery' => $this->employeeListQuery($request),
            'branches' => Branch::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'positions' => Position::query()->with('department')->orderBy('name')->get(),
            'profile' => $user->profile,
            'ptkpStatuses' => PtkpStatus::query()->effectiveOn(today())->orderBy('code')->get(),
            'families' => $user->families()->orderBy('id')->get(),
            'employmentHistories' => $user->employmentHistories()->orderByDesc('start_date')->get(),
            'documents' => $user->documents()->orderByDesc('created_at')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'id_karyawan' => ['required', 'string', 'max:255', Rule::unique('users', 'id_karyawan')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'departemen' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
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
            return redirect()
                ->route('admin.employees.edit', ['user' => $user] + $this->employeeListQuery($request))
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
            return redirect()
                ->route('admin.employees.edit', ['user' => $user] + $this->employeeListQuery($request))
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
            ->route('admin.absensi.approval.hrd', $this->employeeListQuery($request))
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    public function deactivate(Request $request, User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        DB::transaction(function () use ($user): void {
            $user->update(['is_active' => false]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        ActivityLog::log('Deactivate Employee', "User: {$user->name}", 'Akun dinonaktifkan; data dan riwayat tetap disimpan.');

        return redirect()
            ->route('admin.absensi.approval.hrd', $this->employeeListQuery($request))
            ->with('success', "Akun {$user->name} dinonaktifkan. Data dan riwayatnya tetap tersimpan.");
    }

    public function activate(Request $request, User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        if ($user->registration_status !== 'approved') {
            return redirect()
                ->route('admin.absensi.approval.hrd', $this->employeeListQuery($request))
                ->with('error', 'Akun belum disetujui. Kirim ulang akses melalui halaman Data Karyawan terlebih dahulu.');
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->update(['is_active' => true]);
        });
        ActivityLog::log('Reactivate Employee', "User: {$user->name}", 'HRD mengaktifkan kembali akun karyawan.');

        return redirect()
            ->route('admin.absensi.approval.hrd', $this->employeeListQuery($request))
            ->with('success', "Akun {$user->name} aktif kembali.");
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureHrd();
        abort_unless($user->role === 'user', 404);

        if ($user->is_active) {
            return redirect()
                ->route('admin.absensi.approval.hrd', $this->employeeListQuery($request))
                ->with('error', 'Nonaktifkan akun terlebih dahulu sebelum menghapusnya secara permanen.');
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

        return redirect()
            ->route('admin.absensi.approval.hrd', $this->employeeListQuery($request))
            ->with('success', "Akun {$employeeName} beserta riwayat absensi dan payroll berhasil dihapus permanen.");
    }
}
