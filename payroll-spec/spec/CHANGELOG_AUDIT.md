# Changelog Audit dan Hardening

## Validasi lokal lanjutan

### Cakupan dan perubahan test

- Memperkuat `tests/Feature/Api/ForgotPasswordOtpTest.php` agar test rollback membuktikan kegagalan pencabutan bearer token terjadi setelah password baru tersimpan, lalu memastikan hash password kembali persis ke nilai semula, token masih ada, dan verifier OTP tidak berubah.
- Memperluas `tests/Feature/Admin/PayrollTest.php` untuk revisi berulang sampai revisi ketiga bagi pegawai yang sama; menguji suffix `-R2`/`-R3`, relasi ke periode asal, alasan koreksi, dan preservasi slip serta snapshot revisi terdahulu. Matriks test tambahan memeriksa role `admin` ditolak pada semua route payroll yang dilindungi, termasuk aksi status, koreksi, export, dan slip, serta memastikan state payroll tidak berubah. Ini menegaskan middleware saat ini, bukan persetujuan matriks role baru.
- Menambahkan characterization test di `tests/Feature/Admin/AttendanceDetailTest.php`: edit check-out admin menyimpan timestamp baru tanpa menghitung ulang field gaji tersimpan, sementara hasil helper kalkulasi untuk timestamp baru berbeda. Test ini merekam perilaku aktual, bukan menentukan formula yang benar.
- Tidak ada perubahan kode produksi, konfigurasi, API, UI, route, schema, migration, atau database.

### Verifikasi aktual

- Test OTP: `php artisan test --compact tests/Feature/Api/ForgotPasswordOtpTest.php` — **10 passed, 78 assertions**.
- Test terarah OTP, logout, absensi, payroll, approval HRD, dan unit kalkulasi: **43 passed, 379 assertions**.
- Suite penuh `php artisan test --compact`: **73 passed, 478 assertions**.
- Pint pada tiga file test yang diubah, `php -l` pada ketiganya, dan `git diff --check`: **lulus**.
- Hasil ini adalah validasi lokal dengan konfigurasi test SQLite `:memory:`/service test; bukan bukti concurrency dua worker, row lock database deployment, cache bersama, SMTP, atau schema staging.
- Edit check-in admin memakai kalkulasi manual menit telat yang dibulatkan ke 15 menit dan `base_salary_per_day / 480`, lalu memperbarui penalti/final salary tanpa memanggil helper; edit check-out hanya mengubah timestamp. Jalur API checkout memanggil `Absensi::calculateSalary()`. Generator payroll mengambil absensi approved dan nilai `late_minutes` yang sudah tersimpan ke snapshot, tidak menghitung ulang salary attendance. Perbedaan ini belum diseragamkan; HR/Finance harus menyetujui formula dan dampak koreksi.
- Keputusan HR/Finance tentang formula/correction payroll tetap dibutuhkan. Seluruh pemeriksaan staging/produksi tetap `BLOCKED (NOT RUN)`; tidak ada akses deployment dilakukan.

## 26 Oktober 2026 — Audit aplikasi menyeluruh

### Perubahan

- Diperbarui `AUDIT_REPORT.md` dengan temuan payroll yang dipertahankan serta bukti audit autentikasi/API, approval, export, activity log, backup, holiday sync, deployment boundary, dan hasil verifikasi.
- Diperbarui `CHANGE_PLAN.md` dengan temuan aplikasi, pemisahan tindakan aman dari keputusan bisnis/keamanan/DBA, daftar file kandidat, test, risiko, dan rollback.
- Mengubah `app/Http/Controllers/Admin/ActivityLogController.php`: menghapus penanganan exception yang menjalankan `dd()` dan menampilkan pesan/path/baris internal. Exception sekarang diteruskan ke handler Laravel, tanpa perubahan pada jalur sukses, route, middleware, atau view.
- Tidak mengubah sumber data export, approval workflow, perhitungan payroll, format slip, timezone, API contract, migration, schema, atau database.

### Verifikasi aktual

- `php artisan test --compact` — **50 passed, 291 assertions**.
- `vendor/bin/pint --test app/Http/Controllers/Admin/ActivityLogController.php` — lulus setelah formatting terarah pada file yang sama.
- `php -l app/Http/Controllers/Admin/ActivityLogController.php` — lulus.
- Pemeriksaan syntax seluruh file PHP pada `app`, `routes`, `database/migrations`, dan `tests` — lulus sebelum perubahan; file controller yang diubah diperiksa ulang sesudahnya.
- `git diff --check` — lulus.
- `composer validate --no-check-publish` — valid; tetap ada warning bahwa `maatwebsite/excel` menggunakan constraint exact/dev `4.x-dev`.
- `npm run build` — lulus pada audit ini setelah dependency lokal dipasang dari lockfile; warning Browserslist tentang data browser yang lama tetap ada. Artefak build yang terbentuk selama pemeriksaan dipulihkan.
- `vendor/bin/pint --test` untuk seluruh repository — gagal karena banyak temuan formatter yang telah ada di file lain. Tidak dilakukan auto-fix global.
- Output suite test masih menampilkan dua baris komentar dari migration `2025_10_23_134528_add_target_id_to_notifications_table.php`; test tetap lulus. Ini belum diperbaiki karena tidak berkaitan dengan hardening yang dikerjakan.

### Batas dan pekerjaan lanjutan

- Tidak ada test baru khusus untuk cabang exception ActivityLog. Full suite tidak memaksa kegagalan database pada halaman tersebut; men-trigger versi lama berisiko menghentikan proses test karena `dd()` melakukan exit. Perubahan tetap diverifikasi dengan syntax, format, diff, dan suite regresi.
- Temuan yang memerlukan keputusan bisnis/client/security atau verifikasi DBA/infra tercatat di `AUDIT_REPORT.md` dan `CHANGE_PLAN.md`; belum diimplementasikan.
- Test memakai SQLite `:memory:`. Schema deployment, konfigurasi `APP_DEBUG`, proxy, backup/password, enkripsi database, API client eksternal, dan timezone session DB tidak diverifikasi.
- Tidak dijalankan migration, seeder, payroll generation, operasi destructif, atau query isi record payroll produksi.
- Perubahan worktree yang telah ada (termasuk penghapusan di `absensi_payroll_spec/`, perubahan payroll sebelumnya, dan dokumen untracked di `payroll-spec/spec/`) dipertahankan.
- Perbaikan APP-01 tidak memerlukan migration dan dapat dibatalkan dengan revert perubahan controller saja; tidak ada data yang perlu di-rollback.

## 25 Oktober 2026 — Audit detail absensi

### Perubahan

- Menambahkan `validateAttendanceDetailFilters()` di `app/Http/Controllers/Admin/AbsensiAdminController.php`.
- Menerapkan validasi pada halaman detail absensi serta export absensi, slip Excel, dan slip PDF yang menggunakan filter halaman tersebut.
- Filter custom kini menolak tanggal awal/akhir yang hilang, format selain `Y-m-d`, dan rentang terbalik alih-alih mengabaikan filter dan berpotensi mengambil cakupan data yang lebih luas.
- Parameter tahun/bulan yang disediakan divalidasi agar bukan nilai kalender tidak masuk akal. Filter UI yang valid dan nama parameter tetap sama.
- Menambahkan `tests/Feature/Admin/AttendanceDetailTest.php` untuk verifikasi rentang valid, ringkasan sesuai rentang, input invalid, serta validasi export terkait.
- Memperbarui `AUDIT_REPORT.md` dan `CHANGE_PLAN.md` dengan bukti, solusi, hasil test, dan batasan.
- Tidak mengubah UI, route, aturan bisnis, status absensi, schema, migration, atau data.

### Verifikasi aktual

- Test terarah: `php artisan test --compact tests/Feature/Admin/AttendanceDetailTest.php tests/Feature/Admin/HrdApprovalTest.php tests/Unit/AbsensiPayrollTest.php` — **11 passed, 88 assertions**.
- Suite penuh: `php artisan test --compact` — **54 passed, 320 assertions**.
- `vendor/bin/pint --test tests/Feature/Admin/AttendanceDetailTest.php` — lulus.
- `php -l app/Http/Controllers/Admin/AbsensiAdminController.php` dan `php -l tests/Feature/Admin/AttendanceDetailTest.php` — lulus.
- `git diff --check` — lulus.
- Pint pada seluruh `AbsensiAdminController.php` melaporkan banyak formatting issues yang telah ada di controller; tidak dilakukan Pint auto-fix pada controller besar demi menghindari perubahan tak terkait.

### Batas audit

- Test export menguji penolakan filter invalid sebelum pembuatan file; tidak membaca file hasil export.
- Seluruh test memakai database SQLite in-memory, bukan deployment. Tidak dilakukan migration atau akses data absensi/payroll produksi.
- Role middleware `super_admin` memakai predicate model yang saat ini menerima role `hrd`; semantik ini dicatat, bukan diubah.

### Tambahan audit/edit waktu absensi

- `updateCheckIn()` dan `updateCheckOut()` sekarang menolak timestamp yang membuat urutan absensi terbalik; timestamp valid tetap dapat disimpan.
- Tombol edit checkout memakai tanggal `check_in_at` sebagai tanggal default bila belum ada checkout, bukan tanggal hari saat ini.
- Menambah test untuk fallback tersebut, penolakan check-in melewati checkout, penolakan checkout sebelum check-in, dan penerimaan urutan timestamp yang valid.
- Temuan penting yang ditahan: perubahan waktu checkout tidak menghitung ulang nilai gaji meskipun helper salary memakai durasi check-in/check-out. Check-in edit juga memakai kalkulasi manual berbeda. Ini berpotensi menciptakan nilai tersimpan yang tidak konsisten, namun koreksi memerlukan keputusan HR/Finance atas formula dan perlakuan payroll approved; tidak diubah.
- Verifikasi akhir setelah seluruh perubahan detail absensi: `php artisan test --compact` — **57 passed, 336 assertions**; `vendor/bin/pint --test tests/Feature/Admin/AttendanceDetailTest.php`, PHP syntax checks, dan `git diff --check` lulus.

## 25 Oktober 2026 — Logout Sanctum stateful dan audit konsistensi payroll

### Perubahan

- `app/Http/Controllers/Api/AuthController.php`: logout sekarang mencabut hanya personal access token untuk autentikasi bearer. Untuk token transient/session, controller melakukan logout guard web, invalidasi session, dan regenerasi CSRF token. Route dan response JSON tetap kompatibel.
- `tests/Feature/Api/AuthLogoutTest.php`: menguji alur session login web → API logout stateful → API protected menolak sesi; serta menguji revoke bearer token.
- Audit payroll/attendance menemukan edit check-in memakai kalkulasi manual berbeda dari helper/API dan edit check-out tidak menghitung ulang nilai salary yang bergantung pada durasi. **Tidak ada perubahan formula gaji** karena pilihan formula kanonis, kategori, weekend/libur, lembur, dan dampak pada approval memerlukan persetujuan HR/Finance.
- Dokumentasi `AUDIT_REPORT.md` dan `CHANGE_PLAN.md` diperluas dengan formula yang ada, perbedaannya, dampak edit terhadap snapshot payroll lama dan revisi baru, test plan, serta keputusan yang masih diperlukan.

### Verifikasi logout

- Sebelum fix: test session-based mendapat HTTP 500 karena `TransientToken` tidak memiliki `delete()`.
- Setelah fix: test stateful dan bearer menguji sesi tidak terautentikasi setelah logout, token bearer saat ini dicabut, dan token lain milik user tetap aktif. Test meniru batas request dengan me-reset guard sebelum memverifikasi session.
- Verifikasi akhir: `php artisan test --compact` — **59 passed, 344 assertions**; `vendor/bin/pint --test tests/Feature/Admin/AttendanceDetailTest.php tests/Feature/Api/AuthLogoutTest.php`, syntax check PHP untuk kedua controller dan kedua test, serta `git diff --check` — **lulus**.
- Suite menggunakan konfigurasi test lokal. Ini tidak memverifikasi schema, session store, proxy, timezone, maupun konfigurasi database deployment.

## 25 Oktober 2026 — Penguatan OTP dan ekspor rekap

### Perubahan

- `app/Http/Controllers/Api/ForgotPasswordController.php`: CSPRNG (`random_int`), keyed HMAC-SHA256 verifier enam karakter untuk penyimpanan OTP (sesuai panjang kolom repository), validasi OTP numerik, expiry tepat 10 menit, batas verify 5/10 menit serta send 3/menit per email lintas IP. Reset password/token/OTP atomik; send/reset serialisasi dengan row lock user. Respons send generik untuk unknown/known/mail failure; tidak ada OTP debug atau detail exception pada response/log. Legacy plaintext hanya diterima selama TTL transisi.
- `tests/Feature/Api/ForgotPasswordOtpTest.php`: menguji bahwa DB menyimpan verifier, kode email berhasil reset sekali, legacy plaintext transisi, expiry/null timestamp, resend, limit verify/send lintas IP, response email generik, dan kegagalan SMTP.
- Schema dibaca read-only dari file SQLite lokal: tabel `password_resets` dan email index tersedia, migration tercatat; tabel payroll serta catatan migrasi payroll tidak ada. Ini bukan schema staging/produksi dan bukan konfirmasi migration deployment.
- `app/Http/Controllers/Admin/AbsensiAdminController.php`: export recap memvalidasi filter type/range/periode/week dan custom dates. Total absensi pada file kini mengikuti status yang tampil pada halaman recap (approved/rejected), bukan menambahkan pending. Bulk export detail/simple/PDF memakai validasi bersama untuk ID pegawai dan tanggal berurutan, sambil menerima format tanggal UI.
- `tests/Feature/Admin/AttendanceDetailTest.php`: menambahkan regresi invalid type, custom range tidak lengkap, total export tanpa pending, dan rentang bulk yang terbalik. Mode weekly tanpa nomor minggu tetap mengikuti perilaku lama.
- `payroll-spec/spec/AUDIT_REPORT.md` dan `CHANGE_PLAN.md`: mencatat bukti OTP, matriks role yang dapat dipastikan dari source, ekspor, checklist staging/produksi dan hal yang masih menunggu keputusan/DBA.

### Verifikasi aktual

- Baseline sebelum final-review fixes: test fokus OTP/logout/export/payroll **32 passed, 261 assertions**.
- Final review memindahkan check/hit account limiter send/verify/reset untuk akun terdaftar ke dalam transaksi saat row user terkunci, menghindari check-then-hit race untuk request akun yang sama. Untuk email yang tidak dikenal, IP/account limiter tetap berjalan tanpa row user karena tidak ada record untuk dikunci.
- Final review juga memakai cooldown kegagalan mail 10 menit pada cache key terpisah; key terpisah diperlukan karena limiter send biasa sudah memakai TTL 60 detik. Test memastikan resend lintas IP ditahan selama cooldown dan kembali diizinkan sesudahnya.
- Test final menambahkan cakupan failed reset OTP lintas IP dan memperjelas nama test mail failure agar response generik tidak keliru disebut non-success.
- Hasil test lokal dari run sebelumnya setelah semua fix: test fokus `php artisan test --compact tests/Feature/Api/ForgotPasswordOtpTest.php tests/Feature/Api/AuthLogoutTest.php tests/Feature/Admin/AttendanceDetailTest.php tests/Feature/Admin/PayrollTest.php tests/Unit/PayrollGenerationServiceTest.php` — **33 passed, 276 assertions**.
- Hasil test lokal dari run sebelumnya setelah semua fix: suite penuh `php artisan test --compact` — **70 passed, 434 assertions**. Test tidak dijalankan ulang pada penutupan persiapan ini.
- `vendor/bin/pint --test` pada `ForgotPasswordOtpTest.php` dan `AttendanceDetailTest.php` — **lulus**. `php -l` untuk `ForgotPasswordController`, `AbsensiAdminController`, `AuthController`, dan dua test terkait — **lulus**. `git diff --check` — **lulus**.
- Runtime lokal PHP **8.5.4** menyediakan `Pdo\Mysql::ATTR_SSL_CA` dan konstanta legacy; ini hanya memvalidasi branch runtime lokal, bukan seluruh versi PHP/PDO deployment.
- Tidak dilakukan auto-format massal pada controller besar; tidak ada staging/production test atau migration. Uji concurrency row-lock tetap perlu dijalankan dengan dua worker dan engine/cache staging sebenarnya; test SQLite lokal tidak membuktikannya.
- Output suite masih menampilkan dua komentar migration yang sudah ada; tidak memengaruhi status test.

## 25 Oktober 2026 — Percobaan validasi staging

- Pemeriksaan akses aman: tidak ditemukan variabel environment sesi terkait koneksi staging; `.env.staging` tidak tersedia. `.env` ada tetapi tidak dibaca dan tidak digunakan. Tidak ada endpoint, kredensial staging terverifikasi, akun sintetis, DB read-only, SMTP/cache/session staging, atau bukti backup/restore yang tersedia.
- Semua uji deployment yang diminta (OTP concurrency, DB lock/transactions/schema, shared limiter, SMTP, Sanctum/session, role/export, edit absensi/filter, payroll/revision/rollback, timezone/proxy, backup/restore) berstatus **`BLOCKED (NOT RUN)`**. Tidak ada koneksi atau operasi ke staging/produksi; tidak ada migration.
- Perincian alasan dan PASS criteria per area, serta akses minimum yang dibutuhkan, tercatat pada “Validasi staging terisolasi” di `AUDIT_REPORT.md`.
- Status gate: staging belum tervalidasi; tidak ada klaim release candidate atau production readiness. Worktree yang sudah ada tidak dibersihkan atau di-reset.

## 25 Oktober 2026 — Penguatan validasi lokal

- Pemetaan test menandai konkurensi OTP dua worker sebagai `NOT COVERED`: konfigurasi PHPUnit memakai SQLite `:memory:` dan cache/session/mail `array`, sehingga tidak merepresentasikan database/cache bersama atau row lock deployment. Cakupan reset-session, role-export lengkap, dan multi-revision juga masih parsial.
- Tambah satu regression test di `tests/Feature/Api/ForgotPasswordOtpTest.php`: trigger SQLite sintetis memaksa revoke token gagal setelah password sudah diperbarui; hasil yang diharapkan dan teruji adalah transaksi rollback sehingga password, token, dan OTP lama tetap ada.
- Test dijalankan: `php artisan test --compact tests/Feature/Api/ForgotPasswordOtpTest.php` — **10 passed, 76 assertions**. `vendor/bin/pint --test tests/Feature/Api/ForgotPasswordOtpTest.php`, `php -l tests/Feature/Api/ForgotPasswordOtpTest.php`, dan `git diff --check` — **lulus**.
- Suite penuh **70 passed, 434 assertions** adalah hasil dari run sebelumnya; tidak diulang karena perubahan hanya menambahkan test terisolasi. Hasil baru tidak boleh ditafsirkan sebagai full-suite count terkini.
- Tidak ada kode produksi, konfigurasi, migration, atau database yang diubah. Test SQLite ini bukan bukti staging, row lock MySQL, concurrency, atau SMTP nyata.

### Batas dan risiko

- Tidak ada migration atau koneksi staging/production. OTP verifier HMAC muat pada kolom repository `string(6)` dan kompatibel dengan schema lokal yang tersedia; schema staging/production tetap perlu verifikasi. `otp_debug` telah dihapus dari response termasuk pada `APP_ENV=local`; production tetap harus memakai `APP_ENV=production` dan `APP_DEBUG=false`.
- Limit account-wide mengurangi brute-force lintas IP tetapi alamat email yang diketahui dapat mengalami lockout sementara. Keputusan UX/cooldown perlu ditinjau security/product.
- Format email yang invalid masih menghasilkan 422, tetapi valid email yang unknown, known, maupun gagal mail mendapat response status/pesan generik yang sama. Sinkronisasi mail masih menyisakan side-channel timing.
- Matriks akses aktual tetap HRD untuk middleware `admin`/`super_admin`; role `admin` pada `NotificationController` tidak sama. Tidak mengubah semantik akses tanpa persetujuan pemilik proses.
- Password reset saat ini mencabut bearer token tetapi tidak invalidasi seluruh session cookie SPA yang sudah aktif; mekanisme global bergantung pada session store dan perlu desain/keputusan tersendiri.
- Rekening export payroll tetap bersumber dari profil terkini. Schema payroll, indeks aktual, timezone DB, proxy, session store, cache lintas instance, queue, backup/restore, serta deployment secret belum diverifikasi; checklist wajib ada di `AUDIT_REPORT.md`.
