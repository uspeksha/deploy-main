 Absensi Backend & Admin Panel 

Backend service dan web admin untuk **Aplikasi Absensi (Flutter)**.  
Aplikasi Flutter digunakan oleh **user**, sementara project ini handle **API + Web Admin**.

Siap dipakai buat production, scalable, dan gampang diintegrasi.

 Tech Stack

-  Backend: Laravel
-  Client App: Flutter (consume REST API)
-  Database: MySQL / PostgreSQL
-  Authentication: Sanctum (API Token)
-  Web Admin: Blade + Tailwind / Bootstrap

## Payroll Freelance

Payroll is available to HRD accounts from the **Payroll Freelance** sidebar menu. It uses approved, non-mocked `hadir` attendance records and counts at most one approved check-in per employee per local work date. Absences and unapproved records are not paid.

- Minggu 1 covers the 1st–15th; Minggu 2 covers the 16th through the actual last day of the selected month.
- The daily rate is Rp120.000. A full 30 minutes of stored lateness per work date deducts Rp7.500; other deductions are not added automatically.
- The current attendance system stores lateness against its fixed 08:00 baseline. Payroll snapshots this stored value and labels the baseline; it does not recalculate or change attendance records.
- Each period can be generated only once as a snapshot. Drafts move through **Draft → Menunggu Persetujuan → Disetujui → Dibayarkan**. “Dibayarkan” is a manual confirmation with a required reference, not a bank transfer.
- Unpaid periods can be cancelled with a reason and recreated as a new draft for the same period using the **Buat Payroll Baru** action. The cancelled payroll remains in history, and the recreation is recorded automatically. Paid snapshots remain locked; any post-payment difference must be entered and settled through the manual reconciliation log and does not change the original slip or trigger a payment.
- HRD can review details, export the period to Excel, preview/print a slip, and download its PDF. The Excel export includes bank, account number, and account-holder name from the employee profile; handle the file as confidential financial data. Employees cannot access another person’s slip through this admin feature.

The payroll schema is added by `database/migrations/2026_10_23_000000_create_payroll_tables.php`. Before deployment, back up the intended database, verify the deployment environment and connection, then apply migrations through the normal deployment process (`php artisan migrate --force` only against the intended database).

 Arsitektur Singkat
