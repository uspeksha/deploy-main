<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $id_karyawan
 * @property string|null $departemen
 * @property string $employment_type
 * @property string|null $work_location
 * @property-read 'borongan'|'freelance'|'magang'|'organik' $kategori_absensi
 * @property-read Collection<int, EmployeeSalaryRate> $salaryRates
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'gender',
        'email',
        'password',
        'profile_photo_path',
        'id_karyawan',
        'departemen',
        'employment_type',
        'work_location',
        'phone',
        'address',
        'role',
        'registration_status',
        'is_active',
        'department_id',
        'position_id',
        'role_id',
        'nik',
        'phone',
        'address',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'sisa_cuti',
        'total_cuti_diambil',
        'tahun_cuti',

    ];

    protected $guarded = [
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'nik',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'nik' => 'encrypted',
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'bank_name' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'bank_account_name' => 'encrypted',
        ];
    }

    public function resetCutiTahunan(): void
    {
        $tahunSekarang = (int) date('Y');
        if ((int) $this->tahun_cuti !== $tahunSekarang) {
            $this->update([
                'sisa_cuti' => 12,
                'total_cuti_diambil' => 0,
                'tahun_cuti' => $tahunSekarang,
            ]);
        }
    }

    public static function cutiYangMemotong(): array
    {
        return ['cuti_tahunan', 'cuti_haji', 'cuti_umroh'];
    }

    /**
     * @return HasMany<Absensi, $this>
     */
    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function rbacRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    /**
     * @return HasMany<EmploymentHistory, $this>
     */
    public function employmentHistories(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    /**
     * @return HasMany<EmployeeFamily, $this>
     */
    public function families(): HasMany
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    /**
     * @return HasMany<EmployeeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeComponent::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function approvedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'approved_by');
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function approvedReimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class, 'approved_by');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'approver_id');
    }

    /**
     * @return HasMany<EmployeeSalaryRate, $this>
     */
    public function salaryRates(): HasMany
    {
        return $this->hasMany(EmployeeSalaryRate::class);
    }

    public function profilePhotoDisplayUrl(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return route('profile-photos.show', [
            'filename' => basename($this->profile_photo_path),
        ]);
    }

    public function dailyRateAt(Carbon|string|null $date = null): int
    {
        $effectiveAt = $date instanceof Carbon ? $date : Carbon::parse($date ?? now());
        $rates = $this->relationLoaded('salaryRates')
            ? $this->salaryRates
            : $this->salaryRates()->get();

        $rate = $rates
            ->filter(fn (EmployeeSalaryRate $rate): bool => $rate->effective_at->lte($effectiveAt))
            ->sortByDesc('effective_at')
            ->first();

        return (int) ($rate->daily_rate ?? EmployeeSalaryRate::LEGACY_DAILY_RATE);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'hrd';
    }

    public function isAdminOnly(): bool
    {
        return $this->role === 'admin';
    }

    public function isAnyAdmin(): bool
    {
        return $this->role === 'hrd';
    }

    /**
     * Get employee category for attendance logic.
     * Centralized logic to replace repetitive checks in Controllers.
     */
    public function getKategoriAbsensiAttribute(): string
    {
        $idKaryawan = strtoupper($this->id_karyawan ?? '');
        $empType = strtolower($this->employment_type ?? 'organik');

        // Order is important: check prefixes first
        if (strpos($idKaryawan, 'CS-AMB') === 0 || $empType === 'borongan') {
            return 'borongan';
        }

        if (strpos($idKaryawan, 'MG-AMB') === 0 || $empType === 'magang') {
            return 'magang';
        }

        if (strpos($idKaryawan, 'AMB') === 0 || $empType === 'freelance') {
            return 'freelance';
        }

        return 'organik';
    }
}
