<?php

namespace Database\Seeders;

use App\Models\ApprovalFlow;
use App\Models\ApprovalSubjectType;
use App\Models\BpjsRate;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\Permission;
use App\Models\PtkpStatus;
use App\Models\Role;
use App\Models\Shift;
use App\Models\TaxBracket;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['hrd', 'admin', 'user', 'pkl'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        $absensiFlow = ApprovalFlow::updateOrCreate(
            [
                'name' => 'Absensi (mirror legacy)',
                'subject_type' => ApprovalSubjectType::Absensi->value,
            ],
            ['is_active' => false],
        );
        $absensiFlow->steps()->updateOrCreate(
            ['level' => 1],
            [
                'approver_type' => 'role',
                'role_id' => Role::query()->where('name', 'hrd')->value('id'),
            ],
        );

        $permissions = [
            'absensi.view' => 'absensi',
            'absensi.create' => 'absensi',
            'absensi.approve' => 'absensi',
            'absensi.correct' => 'absensi',
            'cuti.view' => 'cuti',
            'cuti.create' => 'cuti',
            'cuti.approve' => 'cuti',
            'cuti.manage' => 'cuti',
            'payroll.view' => 'payroll',
            'payroll.generate' => 'payroll',
            'payroll.approve' => 'payroll',
            'payroll.manage' => 'payroll',
            'karyawan.view' => 'karyawan',
            'karyawan.create' => 'karyawan',
            'karyawan.update' => 'karyawan',
            'karyawan.deactivate' => 'karyawan',
            'organisasi.manage' => 'organisasi',
            'rbac.manage' => 'rbac',
            'kasbon.manage' => 'kasbon',
            'reimbursement.manage' => 'reimbursement',
            'pengumuman.manage' => 'pengumuman',
        ];

        foreach ($permissions as $code => $module) {
            Permission::firstOrCreate(['code' => $code], ['module' => $module]);
        }

        // These are inactive references to existing PayrollItem/export values only.
        // Taxability and BPJS-base policy are intentionally NULL pending an explicit decision.
        $payrollComponents = [
            'gross_pay' => ['name' => 'Gross Pay', 'type' => 'earning'],
            'late_deduction' => ['name' => 'Late Deduction', 'type' => 'deduction'],
            'other_deductions' => ['name' => 'Other Deductions', 'type' => 'deduction'],
            'overtime' => ['name' => 'Overtime', 'type' => 'earning'],
        ];

        foreach ($payrollComponents as $code => $component) {
            PayrollComponent::firstOrCreate(
                ['code' => $code],
                [
                    ...$component,
                    'calc_type' => 'fixed',
                    'is_taxable' => null,
                    'is_bpjs_base' => null,
                    'is_active' => false,
                ],
            );
        }

        // Official Government Regulation PDFs verified on 2026-11-09:
        // PP 44/2015 (JKK/JKM): https://peraturan.go.id/files/pp44-2015bt.pdf
        // PP 45/2015 (JP): https://peraturan.go.id/files/pp45-2015bt.pdf
        // PP 46/2015 (JHT): https://peraturan.go.id/files/pp46-2015bt.pdf
        // Perpres 64/2020 (Kesehatan): https://peraturan.go.id/files/ps64-2020.pdf
        // JHT/JP/JKM use their 2015 regulations and must be rechecked against amending
        // regulations and BPJS official announcements before any calculation. JKK may also
        // be affected by PP 6/2025; keep JKK employer_rate NULL until verified for this company.
        // Rates are stored as decimal fractions (0.0200 means 2%).
        $bpjsRates = [
            [
                'program' => 'kesehatan',
                'employee_rate' => 0.0100,
                'employer_rate' => 0.0400,
                'salary_cap' => 12000000,
                'effective_at' => '2020-07-01',
            ],
            [
                'program' => 'jht',
                'employee_rate' => 0.0200,
                'employer_rate' => 0.0370,
                'salary_cap' => null,
                'effective_at' => '2015-07-01',
            ],
            [
                'program' => 'jp',
                'employee_rate' => 0.0100,
                'employer_rate' => 0.0200,
                // Wajib diisi manual dari pengumuman BPJS yang berlaku.
                'salary_cap' => null,
                'effective_at' => '2015-07-01',
            ],
            [
                'program' => 'jkk',
                'employee_rate' => 0.0000,
                // Employer rate is NULL until the company's BPJS risk class is determined.
                'employer_rate' => null,
                'salary_cap' => null,
                'effective_at' => '2015-07-01',
            ],
            [
                'program' => 'jkm',
                'employee_rate' => 0.0000,
                'employer_rate' => 0.0030,
                'salary_cap' => null,
                'effective_at' => '2015-07-01',
            ],
        ];

        foreach ($bpjsRates as $rate) {
            $bpjsRate = BpjsRate::firstOrNew([
                'program' => $rate['program'],
                'effective_at' => Carbon::parse($rate['effective_at']),
            ]);
            $seededValues = [
                'employee_rate' => $rate['employee_rate'],
                'employer_rate' => $rate['employer_rate'],
                'salary_cap' => $rate['salary_cap'],
            ];

            foreach ($seededValues as $attribute => $value) {
                if (! $bpjsRate->exists || ($bpjsRate->{$attribute} === null && $value !== null)) {
                    $bpjsRate->{$attribute} = $value;
                }
            }

            $bpjsRate->save();
        }

        // Source: Article 17 of Law No. 7/2021 (Harmonisasi Peraturan Perpajakan),
        // official text accessed 2026-11-09:
        // https://peraturan.go.id/files/uu7-2021bt.pdf
        // The Act's transitional provisions make the relevant PPh changes applicable
        // starting Tax Year 2022. These are annual individual income tax bands,
        // not a decision to use this method for monthly PPh 21 withholding.
        $taxBrackets = [
            ['min_income' => 0, 'max_income' => 60000000, 'rate' => 0.0500],
            ['min_income' => 60000001, 'max_income' => 250000000, 'rate' => 0.1500],
            ['min_income' => 250000001, 'max_income' => 500000000, 'rate' => 0.2500],
            ['min_income' => 500000001, 'max_income' => 5000000000, 'rate' => 0.3000],
            ['min_income' => 5000000001, 'max_income' => null, 'rate' => 0.3500],
        ];

        foreach ($taxBrackets as $bracket) {
            TaxBracket::firstOrCreate(
                [
                    'min_income' => $bracket['min_income'],
                    'effective_at' => Carbon::parse('2022-01-01'),
                ],
                [
                    'max_income' => $bracket['max_income'],
                    'rate' => $bracket['rate'],
                ],
            );
        }

        // PLACEHOLDER reference values only: copied from per-request maxDays in the legacy
        // AbsensiController, not annual entitlements/company policy, and not used by any feature.
        // is_paid is also placeholder metadata (true except unpaid_leave) and is not enforced.
        // All current leave requests require file_bukti.
        $leaveTypes = [
            'cuti_tahunan' => ['default_quota' => 12, 'is_paid' => true],
            'cuti_melahirkan' => ['default_quota' => 90, 'is_paid' => true],
            'cuti_keguguran' => ['default_quota' => 45, 'is_paid' => true],
            'cuti_haji' => ['default_quota' => 60, 'is_paid' => true],
            'cuti_umroh' => ['default_quota' => 60, 'is_paid' => true],
            'cuti_menikah' => ['default_quota' => 3, 'is_paid' => true],
            'cuti_khitanan' => ['default_quota' => 2, 'is_paid' => true],
            'cuti_baptis' => ['default_quota' => 2, 'is_paid' => true],
            'cuti_meninggal' => ['default_quota' => 2, 'is_paid' => true],
            'change_off' => ['default_quota' => 1, 'is_paid' => true],
            'unpaid_leave' => ['default_quota' => 30, 'is_paid' => false],
        ];

        foreach ($leaveTypes as $name => $attributes) {
            LeaveType::firstOrCreate(
                ['name' => $name],
                [
                    ...$attributes,
                    'need_attachment' => true,
                ],
            );
        }

        // Source: PMK 101/PMK.010/2016 and official DJP PTKP guidance:
        // https://www.pajak.go.id/id/penghasilan-tidak-kena-pajak.
        // Verify these annual amounts manually against current DJP/PMK guidance before payroll use.
        $ptkpStatuses = [
            'TK/0' => 54000000,
            'TK/1' => 58500000,
            'TK/2' => 63000000,
            'TK/3' => 67500000,
            'K/0' => 58500000,
            'K/1' => 63000000,
            'K/2' => 67500000,
            'K/3' => 72000000,
        ];

        foreach ($ptkpStatuses as $code => $annualAmount) {
            PtkpStatus::firstOrCreate(
                ['code' => $code, 'effective_at' => Carbon::parse('2016-01-01')],
                ['annual_amount' => $annualAmount],
            );
        }

        // Placeholder examples only. Do not use shift times or tolerance to calculate lateness;
        // attendance and payroll lateness remain governed by the existing fixed 08:00 logic.
        Shift::firstOrCreate(
            ['name' => 'Reguler'],
            [
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'late_tolerance_min' => 0,
                'is_overnight' => false,
            ],
        );

        Shift::firstOrCreate(
            ['name' => 'Malam'],
            [
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'late_tolerance_min' => 0,
                'is_overnight' => true,
            ],
        );
    }
}
