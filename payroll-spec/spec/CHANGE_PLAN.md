# Rencana Perubahan Backend Payroll

Status: CP-01 disetujui pengguna dan diimplementasikan; CP-02–CP-04 tetap rencana yang belum disetujui/diimplementasikan.
Ruang lingkup: backend payroll dan test langsung terkait. Tidak mengubah UI, CSS, layout, route, atau kontrak request/view.

## Prinsip dan prasyarat

- Ikuti `AGENT_INSTRUCTIONS.md`, `BUSINESS_RULES.md`, `SPECIFICATION.md`, `ARCHITECTURE.md`, `TEST_PLAN.md`, dan `OPEN_QUESTIONS.md`.
- Jangan mengubah payroll historis, menjalankan generator di production, atau melakukan perubahan database sebelum schema deployment diperiksa.
- Gunakan fixtures sintetis di test dan database test terisolasi.
- Persetujuan HR/pemilik sistem diperlukan untuk nomor slip revisi, sumber rekening export, dan konvensi tanggal/timezone yang belum ditentukan.
- Tahap implementasi dimulai hanya setelah rencana ini ditinjau/disetujui dan prasyarat data/schema sudah dibuktikan.

## Temuan, akar masalah, dan solusi minimal

### CP-01 — Nomor slip untuk revisi bertabrakan

- **Lokasi bukti:** `app/Services/PayrollGenerationService.php:181-186`; `database/migrations/2026_10_23_000000_create_payroll_tables.php:55`; pembuatan revisi di `app/Http/Controllers/Admin/PayrollController.php:130-153`.
- **Akar masalah:** Slip dibentuk dari tahun, bulan, periode, dan ID pegawai tanpa `revision`, sementara constraint unik migration bersifat global. Periode cancelled tetap dipertahankan, sehingga membuat ulang dengan pegawai yang sama mencoba menyisipkan slip yang sudah ada.
- **Solusi minimal:** Atas persetujuan pengguna yang menyatakan persetujuan kebijakan HRD untuk suffix revisi, format revision 1 dipertahankan persis seperti sekarang dan suffix `-R{revision}` ditambahkan hanya pada revision > 1. Implementasi service/test mengadopsi pola ini; nomor slip historis tidak diubah. PDF dan nama file tetap memakai field `PayrollItem.slip_number`, sehingga route/view contract tidak berubah.
- **Risiko/kompatibilitas:** Nomor slip adalah field yang ditampilkan dan dipakai sebagai nama file PDF serta diekspor. Format revisi baru berpotensi memengaruhi pemakai eksternal atau pencocokan otomatis. Verifikasi panjang kolom (100 karakter pada migration) dan konsumennya sebelum menyetujui format.
- **Dampak payroll lama:** Tidak ada update/backfill terhadap nomor slip lama. Revisi baru mendapat identifier berbeda saja.
- **Persetujuan dan deployment:** Persetujuan suffix revisi diberikan oleh pengguna untuk kebijakan HRD. Verifikasi schema hanya tersedia untuk koneksi lokal MariaDB: kolom `slip_number` `varchar(100) NOT NULL` dengan unique global; schema staging/production belum diverifikasi. Jangan deploy ke lingkungan target sebelum DBA memverifikasi panjang kolom dan unique index aktual.
- **File kandidat:** `app/Services/PayrollGenerationService.php`; `tests/Feature/Admin/PayrollTest.php`. Tidak ada migration yang diperkirakan jika hanya format string berubah, tetapi ini bergantung pada schema deployment dan panjang maksimum setelah format dipilih.

### CP-02 — Exception unique constraint menutupi kegagalan pembuatan

- **Lokasi bukti:** `app/Http/Controllers/Admin/PayrollController.php:144-185`; constraint terkait ada di migration payroll.
- **Akar masalah:** Catch menerima semua `UniqueConstraintViolationException`, bukan hanya benturan periode yang dihasilkan proses paralel. Sesudah rollback ia mengambil periode terbaru berdasarkan tahun/bulan/periode tanpa memastikan record itu adalah periode pesaing yang baru berhasil dibuat. Kegagalan nomor slip/detail dapat ditampilkan sebagai warning “baru saja dibuat pengguna lain”.
- **Solusi minimal yang direkomendasikan:** Pertahankan UX warning untuk duplikasi permintaan hanya jika sesudah rollback terbukti ada periode baru yang berhasil dibuat untuk kunci periode yang sama (misalnya status/nomor revisi berbeda dari periode sebelumnya yang dibatalkan). Jika bukti pesaing tidak ada, lempar ulang exception asli. Jika driver deployment memungkinkan identifikasi nama constraint secara andal, gunakan identifikasi yang teruji; jangan bergantung pada format pesan error lintas-driver tanpa verifikasi.
- **Risiko:** Aturan “periode pesaing” harus menangani race condition untuk initial generation dan revision tanpa mengklasifikasikan kegagalan item/detail sebagai sukses. Semantik unique error berbeda antar driver.
- **Dampak payroll lama:** Tidak ada perubahan data. Error yang sebelumnya tersamarkan akan menjadi kegagalan eksplisit dan tercatat oleh jalur exception aplikasi.
- **File kandidat:** `app/Http/Controllers/Admin/PayrollController.php`; `tests/Feature/Admin/PayrollTest.php`. Perubahan pada service tidak diperlukan kecuali hasil penelusuran test menunjukkan transaksi tidak atomik.
- **Migration:** Tidak direncanakan; verifikasi schema tetap wajib sebelum menilai indeks.

### CP-03 — Sumber rekening dalam export belum ditetapkan untuk snapshot

- **Lokasi bukti:** `app/Exports/PayrollSummaryExport.php:28-51`; snapshot item dibentuk di `app/Services/PayrollGenerationService.php:164-180`; rekening disimpan/dienkripsi di `app/Models/User.php`.
- **Akar masalah:** Export menggunakan `User.bank_name`, `bank_account_number`, dan `bank_account_name` pada waktu export, sementara PayrollItem tidak menyimpan nilai tersebut.
- **Solusi minimal yang direkomendasikan:** Jangan mengubah source data sampai HR/Finance memutuskan. Bila export memang harus menunjukkan rekening aktif terkini, dokumentasikan perilaku itu dan tambahkan test yang membuktikan sumber live dipakai. Bila wajib reproduktif per payroll, simpan snapshot rekening untuk payroll baru dengan perlindungan yang sepadan dengan data sensitif (enkripsi, akses, retensi); jangan menyimpan plaintext dalam `attendance_snapshot` JSON.
- **Risiko/kompatibilitas:** Snapshot rekening memerlukan perubahan schema/model/export dan pengelolaan data sensitif. Mengubah export lama dapat mengubah penerima pembayaran atau membuat data historis tidak dapat direproduksi.
- **Dampak payroll lama:** Tidak boleh mengisi snapshot historis dari profil saat ini karena akan menyatakan data sekarang seolah-olah data masa lalu. HR harus menentukan penanganan export payroll lama (gunakan profil terkini, kosongkan snapshot yang tidak tersedia, atau proses lain) sebelum implementasi.
- **File kandidat jika snapshot disetujui:** `database/migrations` (migration baru setelah schema diverifikasi), `app/Models/PayrollItem.php`, `app/Services/PayrollGenerationService.php`, `app/Exports/PayrollSummaryExport.php`, serta test feature/export terkait.
- **Migration:** Bersyarat. Hanya diperlukan jika bisnis memilih snapshot rekening per payroll; harus additive dan tidak mengubah/menghapus nilai lama. Desain kolom, enkripsi, nullability, backup, dan backfill legacy perlu keputusan DBA/HR. Tidak ada migration yang boleh dibuat sebelum verifikasi schema deployment.

### CP-04 — Penyaringan tanggal absensi belum deterministik tanpa konvensi timezone

- **Lokasi bukti:** `config/app.php` menetapkan `Asia/Jakarta`; PHP CLI audit tanpa bootstrap Laravel menggunakan `UTC`; `app/Services/PayrollGenerationService.php:121-138` memakai `whereDate` SQL lalu timezone aplikasi untuk deduplikasi; `database/migrations/2025_08_05_085904_create_absensis_table.php` mendefinisikan `check_in_at` sebagai `timestamp`; `config/database.php` tidak menetapkan timezone koneksi.
- **Akar masalah:** SQL menentukan bagian tanggal dengan semantik database/session/driver, sedangkan deduplikasi menggunakan tanggal lokal aplikasi. Cara timestamp dikonversi saat insert dan dibaca dari DB deployment belum terdokumentasi.
- **Solusi minimal yang direkomendasikan:** Pertama, DBA/pemilik sistem menetapkan format penyimpanan, timezone koneksi, dan definisi “tanggal kerja”. Setelah terverifikasi, ubah hanya filter service bila perlu agar menggunakan rentang timestamp eksplisit yang dibangun dari batas periode pada timezone yang disetujui dan dikonversi ke konvensi storage. Pertahankan deduplikasi per tanggal kerja sesuai keputusan HR. Hindari `whereDate` bila hasilnya tidak konsisten lintas driver.
- **Risiko/kompatibilitas:** Salah konversi dapat memindahkan absensi lintas periode dan mengubah hari kerja/potongan. Dampaknya dapat mengubah hasil payroll baru; tidak boleh dibackfill atau menghitung ulang payroll lama secara diam-diam.
- **Dampak payroll lama:** Tidak ada recalculation. Koreksi historis hanya melalui workflow yang disetujui.
- **File kandidat:** `app/Services/PayrollGenerationService.php`, `tests/Feature/Admin/PayrollTest.php`, dan `tests/Unit/PayrollGenerationServiceTest.php`. Perubahan `config/app.php`/database tidak direncanakan sebelum keputusan timezone; sebaiknya tidak perlu mengubah route atau UI.
- **Migration:** Tidak diperkirakan, kecuali pemeriksaan schema membuktikan tipe kolom/indeks perlu perubahan dan perubahan itu disetujui terpisah.

### CP-05 — Test revisi, kegagalan parsial, export, dan batas waktu belum mengunci perilaku

- **Lokasi bukti:** `tests/Feature/Admin/PayrollTest.php`, `tests/Unit/PayrollGenerationServiceTest.php`.
- **Akar masalah:** Test revisi yang ada tidak mempunyai pegawai eligible sehingga tidak menguji insert PayrollItem; tidak ada test yang membedakan constraint violation item/detail dari kompetisi generate; test export belum memodifikasi rekening setelah snapshot; test tanggal belum mencakup jam batas/timezone.
- **Solusi minimal yang direkomendasikan:** Tambahkan test regresi terarah di test files yang sudah ada, bukan membuat suite baru. Gunakan factory/fixture sintetis, gunakan database test in-memory, dan injeksikan kegagalan secara deterministik hanya jika pola mock/fault injection proyek mengizinkan. Jangan menjalankan generator terhadap database produksi/staging dengan data nyata.
- **File kandidat:** `tests/Feature/Admin/PayrollTest.php`; `tests/Unit/PayrollGenerationServiceTest.php`. Implementasi service/controller/export hanya disentuh sesuai keputusan CP-01–CP-04.
- **Migration:** Tidak ada untuk test saja.

## Test yang wajib ditambahkan/dijalankan

Semua test di bawah direncanakan; belum dijalankan pada audit ini.

1. **Revisi dengan pegawai sama:** Buat payroll pertama dengan pegawai freelance eligible, attendance sintetis, dan item; batalkan lalu buat revisi. Pastikan nomor slip revisi berbeda sesuai format yang disetujui dan tidak melanggar unique global; item periode asal, status, snapshot, serta slip lama tetap utuh.
2. **Relasi/alasan revisi:** Pastikan `revision_of_id`, nomor revision, dan `correction_reason` benar. Verifikasi periode asal tidak berubah setelah revisi berhasil.
3. **Kegagalan item/detail:** Paksa insert item atau detail gagal di database test; pastikan transaksi membatalkan periode, item, detail, dan audit log yang menjadi bagian transaksi. Pastikan response tidak menampilkan success/warning seolah kompetisi sukses dan exception yang bukan konflik pesaing tetap terlihat.
4. **Race/idempotensi:** Simulasikan atau uji unique conflict yang mewakili dua request untuk periode sama pada driver yang didukung. Pastikan hanya konflik dengan periode pesaing sukses yang menjadi warning; konflik slip/detail tanpa periode pesaing harus tetap gagal eksplisit. Jalankan pula pada driver staging yang sebenarnya bila SQLite tidak mewakili semantik deployment.
5. **Export rekening:** Setelah keputusan sumber data ditetapkan, ubah rekening User sesudah generate dan verifikasi hasil export menggunakan sumber yang dipilih. Untuk opsi snapshot, uji data baru dan kebijakan legacy tanpa menggunakan rekening nyata.
6. **Batas periode dan timezone:** Dengan timezone aplikasi dan konvensi DB yang sudah disetujui, uji check-in tepat di awal periode, tepat sebelum awal, di akhir periode, tepat setelah akhir, dan sekitar tengah malam yang dapat mengubah tanggal lokal. Test harus berjalan pada DB engine/config yang relevan, bukan hanya asumsi SQLite.
7. **Regresi kompatibilitas:** Jalankan unit PayrollGenerationService dan Feature Admin Payroll yang sudah ada; verifikasi route, redirect/status, field yang diterima view, nomor slip initial revision, export/PDF, authorization, serta tidak ada perubahan UI/CSS/layout atau kontrak route.

## Daftar file yang diperkirakan berubah

| File | Perubahan yang mungkin | Kondisi |
|---|---|---|
| `app/Services/PayrollGenerationService.php` | Sertakan revisi pada slip revisi; sesuaikan filter timestamp bila timezone terbukti perlu | CP-01 dan CP-04 disetujui |
| `app/Http/Controllers/Admin/PayrollController.php` | Batasi warning duplicate ke konflik periode pesaing yang terbukti; exception lain diteruskan | CP-02 |
| `app/Exports/PayrollSummaryExport.php` | Pilih sumber rekening sesuai keputusan snapshot/live | CP-03 |
| `app/Models/PayrollItem.php` | Cast/access snapshot rekening terenkripsi jika opsi snapshot dipilih | CP-03 bersyarat |
| `database/migrations/<migration-baru>.php` | Tambah field snapshot rekening nullable/terenkripsi jika diperlukan | CP-03 disetujui dan schema deployment sudah diperiksa |
| `tests/Feature/Admin/PayrollTest.php` | Revisi berisi pegawai/item, exception, export, auth/regresi | Implementasi terkait |
| `tests/Unit/PayrollGenerationServiceTest.php` | Edge date/timezone dan format helper jika diperlukan | CP-01/04 |

Tidak ada rencana menyentuh route, Blade/view, CSS, layout, atau konfigurasi aplikasi. Daftar di atas adalah calon file, bukan izin untuk mengubahnya; evaluasi ulang setelah keputusan.

## Schema dan kebutuhan migration

- Migration yang tersedia adalah `database/migrations/2026_10_23_000000_create_payroll_tables.php`. Definisi di repository memberi unique global pada `slip_number`; ini belum membuktikan migration berjalan di deployment.
- Pada audit read-only, `database/database.sqlite` tidak memiliki tabel payroll dan tidak mencatat migration payroll. DB production/staging tidak diperiksa dan engine aktual belum diketahui.
- Solusi nomor slip melalui perubahan format tidak membutuhkan migration pada definisi repository saat ini, tetapi tetap verifikasi panjang/constraint di deployment.
- Perubahan sumber rekening menjadi snapshot memerlukan migration additive jika schema aktual belum memiliki kolom snapshot. Jangan membuat migration sebelum schema aktual diperiksa dan desain penyimpanan terenkripsi/retensi disetujui.
- Solusi timezone diperkirakan hanya query/service/test; jangan ubah tipe kolom atau schema tanpa bukti.
- Jangan jalankan migrate, seed, generate payroll, atau query data sensitif sebagai bagian dari implementasi lokal audit ini.

## Urutan implementasi yang direkomendasikan

1. Keputusan suffix slip revisi sudah diberikan untuk CP-01. Dapatkan keputusan HR/Finance untuk kebijakan rekening export dan histori, serta definisi tanggal kerja/timezone.
2. Dapatkan bukti dari DBA/operator mengenai driver, schema payroll aktual, riwayat migration, tipe kolom, unique constraint, dan timezone session pada staging yang aman; jangan memakai data production langsung sebagai test.
3. Tambahkan test regresi sintetis untuk revisi dengan pegawai sama dan exception rollback/klasifikasi.
4. Implementasikan perbaikan nomor slip dan penanganan exception tanpa mengubah data historis.
5. Implementasikan snapshot rekening hanya jika disetujui; buat migration additive setelah schema review, lalu uji perilaku legacy yang dipilih HR.
6. Perbaiki filter tanggal hanya bila verifikasi membuktikan mismatch; tambahkan test batas pada engine/timezone yang relevan.
7. Jalankan test payroll terarah, kemudian suite regresi yang layak; tinjau seluruh diff dan pastikan UI/route/kontrak tetap.
8. Uji staging dengan backup, data sintetis/masked, pemeriksaan hasil slip/export/audit, serta persetujuan HR/Finance sebelum rilis produksi.

## Rollback

- **Kode:** Revert commit implementasi payroll terkait melalui perubahan terpisah; jangan mengubah/menghapus payroll hasil historis.
- **Nomor slip:** Rollback kode hanya memengaruhi generasi baru. Jangan memaksa format lama untuk revisi baru bila itu kembali menghasilkan duplikasi; hentikan generate revisi sampai format aman tersedia.
- **Exception handling:** Rollback hanya bila perilaku kompatibilitas menuntut, dengan memastikan kegagalan tidak kembali disamarkan. Pertahankan log/error yang dapat ditelusuri.
- **Migration snapshot rekening:** Jika migration additive diperlukan, ambil backup yang disetujui dan siapkan prosedur forward/rollback yang tidak menghapus nilai snapshot yang sudah digunakan. Jangan menjalankan `down` otomatis bila kolom berisi data; rollback data memerlukan keputusan DBA dan backup terverifikasi.
- **Timezone:** Jangan melakukan recalculation historis saat rollback. Perubahan hasil payroll baru harus dihentikan sampai implementasi/query yang konsisten dipulihkan.
- Uji rollback terlebih dahulu di staging; jangan mengeksekusi rollback database produksi tanpa persetujuan pemilik sistem dan DBA.

## Keputusan terbuka dan pemilik

| Keputusan | Pemilik | Dampak bila belum diputuskan |
|---|---|---|
| Format nomor slip revisi, termasuk kompatibilitas file PDF/export dan integrasi eksternal | HR/Finance/pemilik sistem | Blok implementasi CP-01 |
| Rekening export mengikuti profil terkini atau snapshot pada generate/approval; perlakuan export historis | HR/Finance dan pemilik data | Blok perubahan CP-03 |
| Zona waktu dan definisi tanggal kerja (termasuk check-in dekat tengah malam/shift lintas hari) | HR/pemilik payroll + DBA | Blok perubahan query CP-04 |
| Driver/schema deployment, migration yang sudah diterapkan, constraint aktual, dan timezone session DB | DBA/operator deployment | Blok perubahan schema dan validasi concurrency |
| Perilaku bisnis jika potongan melebihi gross pay atau other deductions tetap nol | HR/Finance | Tetap pertahankan implementasi sekarang; bukan cakupan perbaikan tanpa keputusan |
| Apakah periode approved boleh dibatalkan untuk kemudian direvisi | Pemilik proses HR/payroll | Tetap pertahankan workflow sekarang; tidak termasuk perubahan tahap ini |

## Batas kompatibilitas

Perubahan CP-01 tidak mengubah route, request field, nama field UI, layout, CSS, atau workflow status. Perubahan nilai slip revisi telah disetujui pengguna; schema production/staging masih harus diverifikasi sebelum rollout. CP-02–CP-04 tetap tidak diimplementasikan sampai prasyarat/keputusan terkait dipenuhi.

## Perluasan rencana: audit aplikasi menyeluruh — 26 Oktober 2026

Temuan aplikasi lengkap dicatat pada bagian “Audit aplikasi menyeluruh” di `AUDIT_REPORT.md`. Temuan berikut tidak mengubah atau membatalkan rencana payroll di atas.

### Perubahan aman untuk pass ini

#### APP-01 — Hapus `dd()` pada kegagalan halaman activity log

- **Status:** diimplementasikan setelah audit read-only. Full suite lulus (50 tests, 291 assertions), Pint targeted lulus, syntax file lulus, `git diff --check` lulus. Tidak ada migration.
- **Bukti/lokasi:** `app/Http/Controllers/Admin/ActivityLogController.php:14-23`; catch `Throwable` menampilkan pesan, path file, dan baris exception melalui `dd()`.
- **Akar masalah:** kegagalan query/penyajian tidak diteruskan ke exception handler Laravel dan request dihentikan dengan output debug.
- **Perubahan minimal:** hapus catch yang berisi `dd()` dan biarkan exception diteruskan ke handler Laravel yang sudah mengelola logging serta response error. Jalur sukses, route, middleware, view, dan kontrak tidak berubah.
- **Risiko:** exception akan ditangani sesuai konfigurasi global `APP_DEBUG` dan handler Laravel; pastikan production menggunakan `APP_DEBUG=false`. Tidak ada alasan untuk menampilkan detail exception ke halaman.
- **File:** `app/Http/Controllers/Admin/ActivityLogController.php`.
- **Test:** jalankan suite penuh dan pemeriksaan sintaks/diff; tambahkan regression test request sukses bila test fixture/role super-admin dapat digunakan tanpa mengubah policy. Jangan buat test yang menyebabkan `dd()` berjalan di proses test lama karena itu dapat menghentikan seluruh runner.
- **Migration/database:** tidak diperlukan.
- **Rollback:** revert perubahan controller saja; rollback tidak mengubah data. Jangan mengembalikan `dd()` sebagai mekanisme penanganan error.

### Temuan yang belum boleh diimplementasikan tanpa keputusan atau verifikasi

| ID | Temuan | Rekomendasi minimal | Persetujuan/verifikasi yang diperlukan | File kandidat |
|---|---|---|---|---|
| APP-02 | Logout stateful Sanctum memanggil `delete()` pada transient token | Bedakan logout session (logout/invalidate session/CSRF) dan revoke personal access token bearer; pertahankan route/payload | Konfirmasi client SPA dan cakupan logout yang diharapkan; test kedua auth mode | `app/Http/Controllers/Api/AuthController.php`, test API auth |
| APP-03 | Self-service API dapat mengubah employee ID/departemen | HR-managed values; pertahankan payload agar client lama tidak pecah, atau alur permintaan perubahan untuk approval | HR dan pemilik client/API | `app/Http/Controllers/Api/UserProfileController.php`, test API profile |
| APP-04 | OTP tidak memiliki batas percobaan per akun; nilai plaintext/`rand()`; local response dapat memuat OTP | CSPRNG, account throttling, exact expiry, atomic one-time reset, verifier HMAC tanpa migration, response generik, serta penghapusan OTP debug telah diterapkan. Legacy plaintext hanya diterima selama TTL transisi. | Security/product meninjau cooldown, timing mail, dan kebutuhan invalidasi session web; DBA tetap memeriksa schema deployment | `app/Http/Controllers/Api/ForgotPasswordController.php`, tests |
| APP-05 | Email OTP dapat dienumerasi | Response generik yang sama | Security dan pemilik API/client karena error 422 dapat dipakai client | `app/Http/Controllers/Api/ForgotPasswordController.php`, test |
| APP-06 | Reject izin keluar yang masih berjalan dapat nolkan gaji absensi | Guard request harus `selesai` sebelum update; pertahankan aturan gaji untuk request yang selesai | Verifikasi HR atas status/transisi dan test regresi completed case | `app/Http/Controllers/Admin/ApprovalController.php`, feature test |
| APP-07 | API scheduled-lembur melewati efek audit/notifikasi dan dapat membalik status terminal | Gunakan transisi/efek yang sama dengan jalur web, tanpa menghapus route/payload | Pemilik API memastikan client aktif; HR menetapkan transisi legal | `app/Http/Controllers/Api/ScheduledLemburController.php`, approval service/test |
| APP-08 | Bulk detail export dapat memakai record non-approved/penalti non-approved | Tetapkan dahulu aturan pemilihan approved row dan perlakuan hari pending/rejected; sesudah itu filter secara konsisten | HR/Finance/pemilik export | `app/Exports/BulkDetailExport.php`, export feature test |
| APP-09 | Absensi sensitive fields tidak dienkripsi di model meski migration bernama “encrypt” | Rancang konversi kompatibel dan uji baca legacy sebelum encrypted cast | Security/data owner, DBA, verifikasi schema dan enkripsi storage deployment | migration baru/model/export, test konversi |
| APP-10 | Backup dapat mencakup `.env` dan archive password opsional | Evaluasi exclusion `.env`, enkripsi archive, serta cara memulihkan konfigurasi rahasia | Pemilik backup/infra/security memverifikasi restore workflow, secret storage, disk access | `config/backup.php`, test config |
| APP-11 | Sync holiday mengabaikan argumen tahun | Jika hanya data 2026 yang disediakan, tolak tahun lain sebelum write; dukung tahun lain hanya dengan sumber otoritatif | Pemilik kalender/HR memastikan sumber dan tahun yang didukung | `app/Console/Commands/SyncHolidaysCommand.php`, command test |
| APP-12 | Wildcard trusted proxy dan larangan geolocation perlu konteks deployment/client | Pertahankan sampai jalur jaringan/proxy dan penggunaan geolocation dipetakan | Operator deployment dan pemilik client absensi | `bootstrap/app.php`, `app/Http/Middleware/SecurityHeaders.php` |

### Urutan kerja dan gerbang

1. APP-01 sudah diimplementasikan sebagai perbaikan teknis tanpa migration, perubahan API, atau kebijakan bisnis.
2. Tambahkan test regresi untuk APP-01 hanya bila jalur dapat diuji tanpa membawa kembali output debug yang menghentikan runner; jalankan suite penuh setelah edit.
3. Minta pemilik bisnis/security/API menyelesaikan pertanyaan pada APP-02 sampai APP-08 dan APP-11 sebelum perubahan perilaku.
4. Minta DBA/security/infra memverifikasi APP-09, APP-10, APP-12; tidak boleh mengubah migration/data/backup/proxy tanpa hasil verifikasi.
5. Pertahankan seluruh workflow payroll dan keputusan yang belum diputuskan. Khususnya jangan mengubah format slip, ekspor rekening, timezone payroll, atau hasil gaji.

### Dampak, rollback, dan batas

- APP-01 hanya menyentuh controller backend dan menggunakan exception handler framework yang ada. Tidak ada perubahan UI sukses, CSS, layout, route, API, schema, atau data.
- Perbaikan terencana APP-02 sampai APP-08 dan APP-11 dapat mengubah hasil yang dilihat client atau workflow sehingga belum termasuk implementasi tanpa keputusan/pemetaan kontrak.
- APP-09 memerlukan rencana data legacy dan kemungkinan perubahan schema; APP-10/APP-12 bergantung pada konfigurasi deployment yang tidak terdapat di repository.
- Untuk perubahan kode berikutnya, gunakan revert commit/patch yang terisolasi setelah diuji di staging; jangan rollback migration yang berisi data tanpa rencana DBA.
- Lulusnya test SQLite in-memory atau build frontend bukan bukti schema, proxy, backup, enkripsi deployment, atau perilaku client eksternal sudah aman.

### ATT-01 — Validasi filter custom pada detail absensi dan export terkait

- **Status:** diimplementasikan; `php artisan test --compact` lulus (54 tests, 320 assertions), Pint test baru lulus, PHP syntax lulus, dan `git diff --check` lulus.
- **Masalah/akar:** halaman detail dan tiga export terkait hanya menerapkan `whereBetween` jika kedua tanggal ada; parameter custom tidak lengkap karenanya menampilkan cakupan lebih luas tanpa memberi error.
- **Solusi minimal:** validasi `filter_type`; untuk filter custom wajibkan `start_date` dan `end_date` dalam format `Y-m-d` serta `end_date >= start_date`. Jalankan validasi sama pada halaman detail, export detail absensi, export slip Excel, dan export slip PDF. Tidak mengubah filter valid atau cakupan inklusif.
- **File yang akan berubah:** `app/Http/Controllers/Admin/AbsensiAdminController.php`, test feature baru/terkait di `tests/Feature/Admin/AttendanceDetailTest.php`, serta dokumen audit/changelog ini.
- **Risiko kompatibilitas:** request direct custom yang sebelumnya kehilangan filter diam-diam akan memperoleh validation redirect/error. Nama query parameter, route, tampilan, dan request valid tetap sama. Filter lain dipertahankan, termasuk filter tambahan yang didukung export detail.
- **Test:** valid range membatasi record dan summary; missing start; missing end; reversed dates; memastikan jalur export custom juga menolak input invalid; regresi halaman detail tanpa filter.
- **Migration/schema:** tidak diperlukan.
- **Rollback:** revert controller/test saja. Tidak ada perubahan data. Jangan mengganti validasi dengan fallback “semua data”.

### Catatan otorisasi export

Pemeriksaan test mengonfirmasi bahwa middleware `admin` dan `super_admin` sama-sama mengizinkan role `hrd` melalui predicate `User::isAnyAdmin()` dan `User::isSuperAdmin()`. Role string `super_admin` memang ditolak. Ini dipertahankan sesuai pemeriksaan policy yang ada; perubahan nama middleware atau daftar role membutuhkan keputusan pemilik akses.

### ATT-02 — Jaga urutan waktu check-in/check-out

- **Status:** diimplementasikan; feature tests memverifikasi penolakan waktu terbalik tanpa mutation dan tetap menerima waktu valid.
- **Masalah:** endpoint edit hanya memeriksa format `Y-m-d\TH:i`; waktu baru tidak dibandingkan dengan waktu pasangannya yang sudah tersimpan.
- **Solusi minimal:** tolak check-in yang melewati checkout existing dan checkout yang mendahului check-in existing menggunakan validation error server-side. Pertahankan route, form, dan aturan gaji.
- **File:** `app/Http/Controllers/Admin/AbsensiAdminController.php` dan `tests/Feature/Admin/AttendanceDetailTest.php`.
- **Test:** urutan invalid ditolak tanpa perubahan DB; urutan valid disimpan.
- **Risiko/migration/rollback:** low; tidak ada schema/data migration. Rollback controller/test jika regresi, tanpa mengubah attendance historis.

### ATT-03 — Default tanggal untuk edit checkout kosong

- **Status:** diimplementasikan; render test memverifikasi fallback menggunakan tanggal check-in.
- **Masalah:** fallback date di Blade memakai tanggal sekarang, bukan tanggal check-in record.
- **Solusi minimal:** gunakan tanggal check-in untuk nilai tanggal default dan pertahankan jam 17:00 serta tampilan/modal yang sama.
- **File:** `resources/views/admin/absensi/user.blade.php` dan test render di `tests/Feature/Admin/AttendanceDetailTest.php`.
- **Test:** record tanpa checkout menampilkan tanggal check-in-nya pada `data-checkout-time`.
- **Risiko/migration/rollback:** tidak ada perubahan schema/data; rollback Blade/test saja.

### ATT-04 — Kalkulasi gaji setelah edit checkout/check-in (ditahan)

- **Status:** perlu keputusan HR/Finance sebelum implementasi.
- **Masalah:** helper kalkulasi gaji bergantung pada timestamp checkout, tetapi edit checkout hanya mengubah timestamp; edit check-in memakai formula manual yang berbeda.
- **Pilihan yang perlu diputuskan:** helper mana yang menjadi sumber kebenaran, bagaimana weekend/holiday/kategori/late penalty/overtime digabung, apakah gaji yang sudah approved boleh dihitung ulang, dan apakah perubahan waktu harus menghasilkan audit/correction entry.
- **File kandidat setelah persetujuan:** `app/Http/Controllers/Admin/AbsensiAdminController.php`, mungkin `app/Models/Absensi.php`, serta tests feature/unit. Jangan mengubah payroll historical values atau mengganti formula sebelum keputusan.
- **Test:** perubahan check-in/out menghasilkan field gaji tepat menurut formula yang disetujui; test kategori dan boundary; audit trail dan status approval tetap benar.
- **Migration:** belum diketahui; tidak boleh diasumsikan perlu. Jika audit trail tambahan diperlukan, DBA harus memeriksa schema terlebih dahulu.

### P1 konsistensi payroll — dokumentasi keputusan sebelum perubahan nominal

Audit membuktikan bahwa jalur edit admin tidak konsisten dengan `Absensi::calculateSalary()` yang dipakai API, sementara generation payroll memakai aturan tersendiri (Rp120.000/hari dan Rp7.500 per 30 menit penuh dari `late_minutes`). Perubahan nilai absensi setelah generate tidak meng-update item/snapshot lama; revisi baru membaca attendance terkoreksi. Rincian formula dan dampak dicatat dalam `AUDIT_REPORT.md`.

**Tidak ada perubahan nominal yang diizinkan pada pass ini.** Sebelum implementasi perlu keputusan tertulis HR/Finance tentang:

1. Formula attendance salary kanonis untuk kategori organik, freelance, borongan, magang, weekend, dan hari libur.
2. Tarif profil `base_salary_per_day` versus konstanta model Rp150.000/Rp300.000, serta apakah prorata dan baseline jam kerja tetap.
3. Perilaku lembur dan adjustment ketika check-in/out diedit.
4. Apakah edit boleh pada attendance/payroll yang sudah approved/paid, kebutuhan koreksi/revision, serta audit before/after.
5. Apakah payroll generation tetap memakai aturan berbeda dari attendance salary (yang sekarang memang demikian).

Rencana test setelah keputusan: membuktikan hitungan per kategori dan time boundary; menguji overtime; memastikan edit meng-update field salary dengan formula disetujui; memastikan PayrollItem lama tetap identik; memastikan revision baru mengambil perubahan; dan memastikan audit trail tercatat. Jangan mengubah payroll historis atau menjalankan generate memakai data produksi.

### Prioritas keamanan tanpa perubahan kontrak

Temuan logout Sanctum stateful pada audit aplikasi bersifat bug teknis yang terisolasi dari aturan payroll. Kandidat fix adalah menghapus access token hanya bila current token merupakan personal access token, dan untuk transient session token melakukan logout/invalidation session web. Test perlu mencakup bearer token ter-revoke dan stateful session yang tidak lagi terautentikasi sesudah logout. Pertahankan endpoint/payload. Perubahan hanya setelah test dapat merepresentasikan kedua mode dengan nyata.

**Status:** implementasi dan test sudah selesai. `AuthController::logout()` membedakan `PersonalAccessToken` dari transient token. Stateful logout mengakhiri guard web, menginvalidasi session, dan memperbarui CSRF token; bearer logout hanya menghapus access token saat ini. Kontrak route dan response tidak berubah. Test terarah di `tests/Feature/Api/AuthLogoutTest.php`.

## Perluasan: OTP, ekspor, dan kesiapan deployment — 25 Oktober 2026

### Perbaikan aman yang sudah diimplementasikan

#### APP-OTP — Penguatan verifikasi/reset OTP

- **Temuan/bukti:** `ForgotPasswordController` sebelumnya memakai `rand()`, menyimpan OTP plaintext, menerima OTP non-digit 6 karakter, membandingkan umur dengan `diffInMinutes() > 10`, tidak membatasi percobaan per akun, mengungkap enumerasi email, dan mengembalikan sukses tanpa menghapus OTP jika email gagal. Reset password/token/OTP tidak atomik.
- **Perubahan minimal:** gunakan `random_int`; simpan keyed HMAC verifier enam karakter base36 yang muat kolom repository dan bandingkan constant-time; validasi kode numerik; expiry tepat pada 10 menit; limiter 5 kegagalan per email/10 menit dan 3 send/menit per email; serialisasi penerbitan/reset dengan row lock user; konsumsi reset atomik; generik response send untuk email unknown/known/mail failure; hapus field debug dan jangan menaruh detail exception/secret di response/log.
- **File:** `app/Http/Controllers/Api/ForgotPasswordController.php`; `tests/Feature/Api/ForgotPasswordOtpTest.php`.
- **Kompatibilitas:** route/method/status sukses tetap HTTP 200 dengan bentuk JSON yang sama; pesan dibuat generik. Client tidak boleh bergantung pada `otp_debug` yang sebelumnya hanya ada di `local`. Error SMTP tidak dibedakan dari email tak dikenal untuk mencegah enumerasi; error dicatat dengan exception type saja. OTP plaintext yang telah terbit sebelum deploy tetap diterima selama TTL maksimum 10 menit, lalu hanya verifier HMAC yang berlaku.
- **Schema:** migration tidak diperlukan berdasarkan definisi repository yang memakai `string(6)` dan inspeksi read-only SQLite lokal yang menunjukkan kolom `otp` tersedia (SQLite tidak menerapkan batas panjang). Schema deployment harus diverifikasi oleh DBA sebelum deploy; payroll tables tidak ada pada SQLite lokal.
- **Risiko:** limiter per akun dapat dipakai untuk lockout sementara alamat yang diketahui. Send email sinkron menciptakan kemungkinan perbedaan timing. HMAC verifier memakai `APP_KEY`; rotasi APP_KEY segera memutus OTP yang masih aktif (maksimum TTL 10 menit). Pencabutan seluruh sesi web setelah password reset belum diimplementasikan.
- **Test:** verifier tersimpan bukan kode email; kode email berhasil verifikasi/reset satu kali; legacy plaintext diterima selama TTL; malformed/reuse/expiry; resend; account limiter lintas IP untuk verify/send; generic response email unknown/mail failure; tidak ada OTP tersisa jika gagal.
- **Rollback:** jangan rollback ke penyimpanan plaintext tanpa penilaian security. Kode kompatibel membaca OTP plaintext legacy selama TTL, tetapi kode lama tidak dapat membaca verifier HMAC baru. Rollback aplikasi setelah deploy harus menahan/menghentikan request forgot-password sampai versi compatible kembali aktif; OTP aktif yang disimpan HMAC akan gagal pada kode lama dan user perlu meminta OTP baru setelah fix.

#### APP-EXPORT — Validasi export recap dan keselarasan jumlah status

- **Temuan/bukti:** endpoint `exportRecap()` menerima type/range/month/year/week tanpa validasi dan custom range dapat fallback jika tanggal kurang. Query `total_absensi` sebelumnya menyertakan `pending`, sedangkan halaman recap menghitung total dari `approved` dan `rejected`.
- **Perubahan minimal:** validasi kategori/rentang/periode dan nomor minggu bila diberikan; custom range harus punya tanggal lengkap dan berurutan; weekly tanpa week tetap kompatibel dengan perilaku controller sebelumnya. Export bulk detail/simple/PDF juga menolak employee IDs invalid dan rentang tanggal terbalik. Status yang masuk total disamakan dengan halaman recap. Tidak mengubah format file, data rekening, kategori kalkulasi, dan akses route.
- **File:** `app/Http/Controllers/Admin/AbsensiAdminController.php`; `tests/Feature/Admin/AttendanceDetailTest.php`.
- **Risiko/kompatibilitas:** permintaan export dengan filter invalid yang sebelumnya menghasilkan default/hasil kosong kini mendapat validation error. Parameter UI yang valid tetap didukung. Test menegaskan invalid filters ditolak; file export valid masih perlu validasi data hasil di staging.
- **Schema/migration:** tidak diperlukan.
- **Rollback:** revert validasi dan perubahan status query hanya bila pemilik data memutuskan halaman dan export memang sengaja memakai cakupan berbeda; jangan rollback ke default tanggal diam-diam.

### Temuan yang tetap ditahan

| Temuan | Tindakan yang dibutuhkan | File/area |
|---|---|---|
| Schema `password_resets` deployment belum diverifikasi; local SQLite tidak menegakkan panjang varchar dan tidak membuktikan engine/row lock produksi | DBA memeriksa metadata schema, index, engine, transaksi, dan row-lock behavior secara read-only di staging sebelum deploy; tidak ada migration direncanakan saat ini | `password_resets` migration, deployment |
| OTP baru disimpan sebagai verifier HMAC enam karakter; APP_KEY rotation memutus OTP aktif dan legacy plaintext hanya didukung selama TTL transisi | Operator mengoordinasikan deploy/rotasi key; security memvalidasi rate limit bersama, mail timeout, serta bahwa client tidak bergantung pada `otp_debug` | OTP controller, cache, mail, deployment |
| Reset password hanya mencabut bearer tokens, bukan sesi web yang sudah aktif | Tentukan apakah semua SPA/web sessions harus dibatalkan; verifikasi session driver dan pilih mekanisme kompatibel sebelum perubahan | `ForgotPasswordController`, session/auth infrastructure |
| role `admin` hanya dipakai `NotificationController`, sementara middleware `admin`/`super_admin` sebenarnya menerima role `hrd` | Pemilik proses menyetujui role-permission matrix; kemudian test tiap aksi ekspor/approval/payroll per role | `User`, middleware, routes, controllers |
| Rekening export payroll memakai profil terkini | HR/Finance menentukan live versus snapshot dan perlakuan historis; DBA/security menyetujui storage sensitif sebelum migration | payroll export/service/model |
| Nomor slip revisi, formula absensi, dan timezone | Ikuti keputusan serta verifikasi yang tercatat di bagian payroll; tidak diubah pada pass ini | payroll service, attendance |

### Checklist staging/produksi

Sebelum deploy, checklist wajib pada bagian “Checklist verifikasi staging dan produksi” di `AUDIT_REPORT.md` harus dilengkapi oleh DBA/operator. Prioritas untuk perubahan pass ini:

1. Verifikasi tabel `password_resets` benar-benar memiliki `email`, `otp` minimal 6 karakter, `created_at`, serta index email; migration repository yang melewati create jika tabel sudah ada tidak membuktikan schema aktual. OTP disimpan sebagai enam karakter base36 HMAC, bukan teks OTP.
2. Uji rate limiter lintas instance dengan cache bersama, batas waktu OTP pada clock yang sama, pengiriman SMTP, resend, dua send bersamaan, dan dua reset bersamaan menggunakan akun sintetis.
3. Verifikasi DB engine/index row locking serta bahwa transaksi menggulung balik perubahan password, token, dan OTP bersama.
4. Uji export recap dengan kategori/range valid dan invalid; bandingkan total page/export memakai attendance sintetis per status.
5. Selesaikan checklist APP/DB/infra (HTTPS/proxy, Sanctum/session, timezone, secret, queue, backup/restore) tanpa menyalin secret/PII ke artefak audit.

Test lokal bukan bukti bahwa staging atau produksi telah diverifikasi. Tidak ada migration, data payroll, atau database deployment yang disentuh.

## Status final review — 25 Oktober 2026

| Status | Kesimpulan |
|---|---|
| `FIXED` | OTP baru tersimpan sebagai verifier keyed HMAC, reset atomik, OTP dikonsumsi setelah sukses, expiry diperiksa, kegagalan mail menghapus verifier dan memberlakukan cooldown terpisah 10 menit, respons send known/unknown/failure generik, limiter verify/reset account-wide, dan logout membedakan SPA session dari bearer. Check/hit limiter untuk akun terdaftar diserialisasi di dalam row lock user. Regression test mencakup reset-password invalid attempts lintas IP dan cooldown SMTP. |
| `FIXED` | Filter/detail dan ekspor absensi divalidasi, total rekap ekspor selaras dengan halaman, edit timestamp menolak urutan check-in/check-out yang terbalik. Payroll revision suffix dan rollback tetap diuji dengan data sintetis. |
| `BLOCKED` | Keputusan HR/Finance atas formula perhitungan edit absensi, koreksi payroll approved/paid, sumber rekening export, dan perlakuan reset terhadap sesi web yang masih aktif. Tidak ada formula atau data historis yang diubah. |
| `BLOCKED` | Pemilik proses harus menyetujui matriks role aktual; middleware bernama `super_admin` saat ini memakai predicate HRD. Jangan ubah authorization sebelum persetujuan. |
| `DEPLOYMENT CHECK` | DBA/ops harus memverifikasi schema/index/constraint, driver/engine dan row locks termasuk concurrent resend/verify/reset, cache limiter bersama, SMTP/timeout, APP_KEY, session/Sanctum, timezone, HTTPS/proxy, backup/restore, serta jalur rollback pada staging. |
| `FOLLOW-UP` | Evaluasi side-channel timing email, account-lockout UX, dan pengiriman mail asynchronous setelah hasil pengukuran staging dan keputusan security/product. |

Checklist operasional dan kriteria PASS/FAIL staging ada pada bagian “Final review dan status kesiapan” di `AUDIT_REPORT.md`. Final review mengubah OTP controller hanya untuk menyerialisasi account limiter di row lock yang sudah digunakan; tidak ada akses staging/production atau operasi database.

## Hasil validasi staging — 25 Oktober 2026

- **Status:** `BLOCKED`; tidak ada target/akses staging yang dapat diverifikasi. `.env.staging` tidak ada dan variabel environment sesi tidak menunjukkan konfigurasi staging. `.env` lokal tidak dibaca dan tidak digunakan untuk koneksi.
- **Status eksekusi:** seluruh pengujian deployment wajib `NOT RUN` karena prasyarat akses, data sintetis, worker/cache/SMTP staging, dan DB read-only tidak tersedia. Tidak ada staging/production endpoint, database, SMTP, storage, backup, atau restore yang diuji.
- **Tindakan:** pemilik sistem/ops menyediakan staging terisolasi dan akun sintetis; DBA menyediakan pemeriksaan schema read-only; security/ops menyediakan cache/session/mail dan dua worker; HR/Finance/pemilik proses menyetujui expected cases untuk formula, payroll revision, rekening export, role, dan reset-session policy.
- Detail setiap area, bukti, kriteria PASS, serta data yang aman disampaikan tercatat pada bagian “Validasi staging terisolasi” di `AUDIT_REPORT.md`.
- **Gate:** belum ada dasar untuk menyatakan staging lulus atau release candidate siap. Tidak ada perubahan schema, migration, atau database produksi.

## Bukti validasi lokal lanjutan

- Regression OTP memastikan kegagalan pencabutan token terjadi setelah password berubah di dalam transaksi dan membuktikan password, token, serta verifier OTP kembali ke kondisi semula setelah rollback.
- Regression payroll mencakup pegawai yang sama hingga revisi ketiga, keunikan suffix slip, relasi/alasan revisi, preservasi snapshot/slip revisi terdahulu, dan penolakan role `admin` pada seluruh route payroll terlindungi saat ini (termasuk transisi, koreksi, export, preview/download slip); state payroll dipastikan tidak berubah.
- Characterization absensi memastikan edit checkout admin tidak menghitung ulang salary tersimpan meski helper kalkulasi menghasilkan nilai berbeda untuk timestamp baru. Edit check-in memakai formula manual tersendiri, jalur API checkout memanggil helper, dan generator payroll membuat snapshot dari kehadiran approved serta `late_minutes` tersimpan. Tidak ada formula yang diubah; keputusan tetap memerlukan HR/Finance.
- Hasil aktual: test OTP **10 passed, 78 assertions**; test fokus **43 passed, 379 assertions**; suite penuh **73 passed, 478 assertions**; Pint, syntax PHP, dan `git diff --check` pada file test terkait lulus.
- Tidak ada perubahan produksi/configuration/schema/database. SQLite lokal bukan bukti perilaku staging; semua uji staging/produksi tetap `BLOCKED (NOT RUN)`. Rencana validasi dua worker, row lock, cache bersama, SMTP, schema aktual, dan backup/restore tidak berubah.
