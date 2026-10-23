# Audit Report — Attendance and Payroll

## Status
- Static audit complete against all Markdown specifications in `absensi_payroll_spec/`.
- Baseline test command: `php artisan test` (read-only app database; PHPUnit config uses SQLite `:memory:`).
- No source files or database were changed during the audit stage.

## Existing implementation to preserve
- Attendance and salary components are stored per `absensis` record; `Absensi::calculateSalary()` and `calculateOvertimeFromInput()` are existing calculation foundations.
- Existing API clock-in/out, leave request and multi-day parent/child records, approval workflow, activity log, admin recap and Excel/PDF export are retained as the starting point.
- Existing user/role and employee fields are retained; no destructive database work is planned.

## Confirmed gaps / defects from source inspection
1. `Absensi::calculateSalary()` is static but reads `$this->catatan_admin` when an organic attendance has both timestamps, causing an invalid object-context access.
2. Clock-in/out accept numeric GPS coordinates but do not validate coordinate ranges or distance to an office geofence. No office geofence settings were found; configured coordinates/radius are required before enabling real validation.
3. Overtime calculator does not enforce the specified 30-minute minimum. Overtime APIs trust a client-provided weekend flag; approval recalculation does not pass the stored weekend flag.
4. `absenLembur()` references `$fotoBukti` without defining it. Both API overtime child-record creation paths omit `status`, while the declared attendance schema requires non-null `status`.
5. Payroll recap/export paths have different record scopes. `exportRecap()` references `$lemburUser` outside its scope and omits fields consumed by `AbsensiRekapExport`; it also includes child records although approved overtime may already be rolled into a parent.
6. Some slip/re-cap totals include rejected rows; manual overtime adds to existing `final_salary`, creating a duplicate-addition risk if the same input is submitted again.
7. Existing weekly reporting is calendar-week oriented; no payroll period/run/finalization entity was found in inspected migrations.
8. Approval endpoints rely on broad admin middleware and do not enforce the actor's role against the current approval level in the inspected handler.
9. Existing attendance/payroll-specific automated tests were not found.

## Implemented in this staged pass
- Replaced the invalid static `$this` access in salary calculation with an explicit flag argument; existing auto-checkout salary behavior is retained at relevant call sites.
- Enforced the specified 30-minute overtime minimum in the shared calculator and manual admin overtime entry without changing existing rates or break deduction.
- Overtime API derives weekend status from the server-side date, provides the required `status` on child rows, and fixes the undefined photo variable on one submission path. Approval uses the stored weekend flag.
- Recap payroll amounts now use approved parent attendance records; approved overtime children remain separately visible but are not summed as duplicate net pay. Recap export now defines its overtime datasets and supplies required exporter fields. Excel/PDF slips exclude rejected records.
- User test factory now supplies unique `id_karyawan` values to match the required schema.
- Added `tests/Unit/AbsensiPayrollTest.php` for organic salary calculation, overtime threshold, and recap export composition.
- Files changed: `app/Models/Absensi.php`, `app/Http/Controllers/Api/AbsensiController.php`, `app/Http/Controllers/Admin/ApprovalController.php`, `app/Http/Controllers/Admin/AbsensiAdminController.php`, `database/factories/UserFactory.php`, and `tests/Unit/AbsensiPayrollTest.php`.
- No database/schema, `.env`, or configuration changes were made; no migration was run.

## Test results
- `php artisan test tests/Unit/AbsensiPayrollTest.php`: **3 passed, 11 assertions**.
- Latest `php artisan test`: **4 passed, 2 failed, 22 deprecated**. The new unit tests pass. The two failures are existing web-auth expectations: generic tests assert that a regular employee can remain authenticated, but `AuthenticatedSessionController` logs out non-admin users and redirects them with an admin-only message. This behavior was not changed because it is an explicit existing access policy and needs separate product/authorization approval.
- The suite also reports PDO MySQL SSL constant deprecation notices under the current PHP runtime. This was not changed as it is unrelated to the attendance/payroll implementation.
- Test config uses SQLite in-memory; application database was not modified by the test suite.

## Policy decisions still required
- Rp7,500 lateness application frequency and behavior below 30 minutes.
- Payroll weekly/monthly meaning and cut-off dates (including day 31 and February), per-employee assignment.
- Salary basis/proration and payment for approved sick/leave; unpaid absence calculation details.
- Overtime rate, rounding, break handling, approval procedure and weekend policy.
- GPS office coordinates/radius/accuracy tolerance and exceptions.
- Sick cutoff exceptions; H-7 calendar versus working days and cuti attachment requirement.
- Weekend swap meaning and approval/entitlement rules.
- HRD/HRGA mapping and exact approver permissions for named admins.
- Payroll finalization and correction rules.

## Database and migration risk
- No migration has been run during this work.
- The implemented fixes use existing fields and do not need schema changes.
- Geofence configuration, employee schedules, payroll periods/runs and swap entitlements may need additive schema/configuration work after policy inputs are supplied.
- No destructive migration or data deletion is proposed.

## HRD-managed employee accounts (2026-10-22)
- Public web and API self-registration are disabled. HRD creates employee accounts from the HRD approval page using employee identity, employment, NIK, and bank details.
- NIK and bank fields are encrypted with Laravel encrypted casts and hidden from serialized user responses. Validation failures do not flash those sensitive values back into the session.
- New accounts are created with the ordinary `user` role and remain inactive until a temporary password email is accepted by the configured mail transport. The employee can then log into the attendance API/app; ordinary employee roles remain blocked from web admin.
- If email delivery fails, the account remains pending/inactive; HRD can generate a new temporary password and retry delivery. Passwords are never stored in plaintext or written to application logs.
- The API self-registration endpoint was removed, which is a deliberate API contract change: mobile clients must use HRD-created credentials instead of public registration.
- Added migration `2026_10_22_130000_add_employee_registration_details_to_users_table.php`. It adds `registration_status` (default `approved` for existing accounts) and nullable encrypted identity/bank fields. It was applied to the configured MySQL `followme` database on 2026-10-22 after verifying the target and confirming the columns/migration had not already been applied; no existing account records were deleted.
- Gmail SMTP is documented in `.env.example`. On 2026-10-22 the local `.env` was configured to use `uspeksha7@gmail.com` as SMTP sender via `smtp.gmail.com:587` with STARTTLS and display name `Sistem absensi Followme`; the App Password is configured but must never be disclosed. A malformed unquoted App Password and unsupported `MAIL_SCHEME=tls` were corrected; Laravel's configuration cache was cleared and SMTP authentication succeeded. The code explicitly refuses to report success when the configured mail transport is `log` or `array`.
- PHP syntax checks for the changed PHP files, `git diff --check`, and route checks passed. `/register` has no matching route; HRD employee-create/resend routes and API login remain registered.
- Web admin authorization was tightened to the exact `hrd` role: Manager, Supervisor, HRGA, admin, super_admin, PKL, and employee roles are denied even if corresponding legacy accounts remain in the database. Scheduled-overtime status updates and approval-page access are likewise HRD-only.
- `AdminUserSeeder` now creates/maintains only `hrd@anselmudaberkarya.com` (overridable with `HRD_ADMIN_EMAIL`); initial account creation requires `HRD_ADMIN_PASSWORD` of at least 12 characters. It does not reset an existing HRD password on repeated seeding and no longer creates Manager, Supervisor, or HRGA accounts. Existing legacy accounts/data are not deleted or modified.
- Final focused HRD approval/account/login tests: **13 tests, 85 assertions, exit code 0**. Full `php artisan test`: **4 passed, 32 deprecated, 149 assertions, exit code 0**. Pest reports framework/PDO deprecation notices (`PDO::MYSQL_ATTR_SSL_CA`); there are no failing tests.
- PHP syntax checks for all changed PHP files and `git diff --check` passed. On 2026-10-22, verified the configured connection was MySQL and that `hrd@anselmudaberkarya.com` did not exist, then ran `AdminUserSeeder` with an ephemeral generated password. Verified the created account has role `hrd` and the password hash matches; the password itself is not recorded in this file.
- After correcting SMTP, a new temporary credential email for the pending Putra account was accepted by Gmail SMTP at `muhammadlabibluthfitrpl@gmail.com`; only after acceptance was the account marked approved/active. The temporary password is intentionally not stored in this report.
