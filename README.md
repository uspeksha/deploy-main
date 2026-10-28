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

## Kolaborasi dan Validasi

Jangan commit langsung ke `develop`. Buat branch untuk setiap pekerjaan, lalu ajukan pull request agar perubahan bisa ditinjau sebelum digabung:

```bash
git pull --ff-only origin develop
git switch -c feature/nama-perubahan
# kerjakan dan uji perubahan
git add .
git commit -m "Jelaskan perubahan"
git push -u origin feature/nama-perubahan
```

GitHub Actions menjalankan `npm run build` dan `composer test` pada pull request menuju `develop`. Di pengaturan repository, aktifkan branch protection untuk `develop`: wajibkan pull request, minimal satu persetujuan, dan status check `validate`; larang force-push dan penghapusan branch. Tanpa branch protection, anggota tim masih bisa melewati pemeriksaan dengan push langsung.

Sebelum meminta review, jalankan `npm ci`, `composer install`, `npm run build`, dan `composer test` secara lokal. Jangan commit `.env`, `public/hot`, atau perubahan build yang tidak disengaja. Setiap pull request yang memang mengubah tampilan atau perilaku harus menjelaskan perubahan yang diharapkan dan menyertakan tangkapan layar sebelum/sesudah untuk halaman UI terkait. Build dan tes otomatis menangkap banyak regresi, tetapi tidak dapat menjamin semua perubahan visual atau perilaku tetap sama.

## Payroll Freelance

Payroll is available to HRD accounts from the **Payroll Freelance** sidebar menu. It uses approved, non-mocked `hadir` attendance records and counts at most one approved check-in per employee per local work date. Absences and unapproved records are not paid.

- Minggu 1 covers the 1st–15th; Minggu 2 covers the 16th through the actual last day of the selected month.
- The daily rate is Rp120.000. A full 30 minutes of stored lateness per work date deducts Rp7.500; other deductions are not added automatically.
- The current attendance system stores lateness against its fixed 08:00 baseline. Payroll snapshots this stored value and labels the baseline; it does not recalculate or change attendance records.
- Each period can be generated only once as a snapshot. Drafts move through **Draft → Menunggu Persetujuan → Disetujui → Dibayarkan**. “Dibayarkan” is a manual confirmation with a required reference, not a bank transfer.
- Unpaid periods can be cancelled with a reason and recreated as a new draft for the same period using the **Buat Payroll Baru** action. The cancelled payroll remains in history, and the recreation is recorded automatically. Paid snapshots remain locked; any post-payment difference must be entered and settled through the manual reconciliation log and does not change the original slip or trigger a payment.
- HRD can review details, export the period to Excel, preview/print a slip, and download its PDF. The Excel export includes bank, account number, and account-holder name from the employee profile; handle the file as confidential financial data. Employees cannot access another person’s slip through this admin feature.

## Reverse Proxy Deployment

Set `APP_URL` to the canonical public HTTPS URL before deployment; password-reset links use this configured host rather than request headers. If the application is behind a reverse proxy, set `TRUSTED_PROXIES` to the proxy IP addresses or CIDRs (comma-separated). Do not use `*`; configure the edge proxy to overwrite forwarded headers and block direct public access to the application origin.

The payroll schema is added by `database/migrations/2026_10_23_000000_create_payroll_tables.php`. Before deployment, back up the intended database, verify the deployment environment and connection, then apply migrations through the normal deployment process (`php artisan migrate --force` only against the intended database).

 Arsitektur Singkat
