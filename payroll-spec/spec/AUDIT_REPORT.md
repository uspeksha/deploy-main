# Laporan Audit Backend Payroll

Tanggal audit: 24 Oktober 2026
Status: Tahap 1 (audit baca-saja) dan Tahap 2 (laporan audit); belum ada perubahan implementasi.

## Ringkasan

Alur payroll sudah memiliki validasi rentang periode, penyaringan absensi, snapshot item, pembatasan akses HRD, audit log, transaksi, dan beberapa unique index. Namun, ada satu masalah berisiko tinggi yang membuat revisi periode dengan item pegawai tidak dapat dibuat: nomor slip revisi tetap sama, sedangkan skema mewajibkan nomor slip unik global. Penanganan exception di controller kemudian berpotensi menyamarkan kegagalan ini sebagai payroll yang baru dibuat pengguna lain.

Ekspor payroll juga mengambil data rekening terbaru dari model `User`, bukan snapshot pada saat payroll dibuat. Hal ini dapat membuat isi ekspor berubah setelah payroll disetujui/dibayar. Dampaknya terhadap proses bisnis perlu diputuskan HR/Finance sebelum implementasi.

## Cakupan dan batas audit

Ditelaah:

- Dokumen spesifikasi dalam `payroll-spec/spec/`, termasuk instruksi agent, aturan bisnis, spesifikasi, rencana uji, arsitektur, rencana implementasi, dan pertanyaan terbuka.
- Service, controller, model payroll, model `User`/`Absensi`, export payroll, middleware/route otorisasi, migration payroll dan field relevan, serta test payroll.
- Versi runtime yang tersedia: PHP 8.5.4; `composer.json` mensyaratkan PHP `^8.2` dan Laravel `^12.0`.
- Konfigurasi test menggunakan SQLite `:memory:`.

Tidak dilakukan:

- Tidak ada kode aplikasi, konfigurasi, migration, atau data yang diubah.
- Tidak ada migration, seeder, atau proses generate payroll yang dijalankan.
- Test tidak dijalankan agar tahap audit tidak mengubah database test.
- Tidak dibaca isi record pegawai atau data sensitif.

Repository tidak memiliki direktori `spec/` di root. Dokumen yang diminta ada di `payroll-spec/spec/`; laporan ini disimpan bersebelahan dengan dokumen tersebut. Worktree saat audit sudah menunjukkan file lama di `absensi_payroll_spec/` terhapus dan `payroll-spec/` belum terlacak. Perubahan pra-eksisting itu tidak disentuh.

Konfigurasi repository mendukung SQLite, MySQL/MariaDB, PostgreSQL, dan SQL Server melalui environment. Pemeriksaan read-only terhadap `database/database.sqlite` menemukan tabel payroll belum ada dan tabel `migrations` tidak mencatat migration payroll. Karena itu, audit skema memakai definisi migration sebagai skema yang diharapkan saja; skema database deployment aktual dan apakah migration tersebut sudah diterapkan di deployment tidak dapat dipastikan dari repository atau database lokal.

## Temuan

### High

#### H-01 — Nomor slip tidak unik antar revisi; revisi dengan pegawai yang sama gagal

- **Bukti:** `app/Services/PayrollGenerationService.php:181-186` membentuk `slip_number` dari tahun, bulan, periode, dan ID pegawai, tanpa nomor revisi.
- **Bukti skema:** `database/migrations/2026_10_23_000000_create_payroll_tables.php:55` memberi unique constraint global pada `payroll_items.slip_number`. Payroll lama tetap disimpan ketika periode dibatalkan.
- **Bukti test:** `tests/Feature/Admin/PayrollTest.php` menguji pembuatan revisi, tetapi test tersebut tidak membuat pegawai freelance eligible sehingga revisi tidak memiliki `PayrollItem`. Jalur yang menabrak unique constraint belum teruji.
- **Dampak:** Ketika periode yang dibatalkan dibuat ulang dan masih memiliki pegawai yang sama, item revisi memakai slip yang sama dengan item periode lama. Insert item akan gagal dan transaksi revisi dibatalkan.
- **Risiko:** Revisi payroll operasional dapat gagal total meskipun periode awal berhasil dibuat.
- **Keputusan sebelum implementasi:** Tentukan bagaimana nomor slip revisi harus dibedakan tanpa merusak format yang dipakai UI, PDF, nama file unduhan, atau proses finance.

### Medium

#### M-01 — Penanganan unique-constraint exception terlalu luas dan dapat menyamarkan kegagalan

- **Bukti:** `app/Http/Controllers/Admin/PayrollController.php:144-185` menjalankan transaksi dan menangkap semua `UniqueConstraintViolationException` dari proses penuh. Catch mengambil periode terbaru untuk tahun/bulan/periode lalu mengembalikan peringatan seolah periode baru dibuat oleh pengguna lain.
- **Dampak:** Exception dapat berasal dari unique constraint periode+revisi, item per periode+pegawai, nomor slip global, atau detail per item+tanggal. Jika pembuatan gagal dan transaksi rollback, query fallback masih dapat menemukan periode lama (khususnya periode cancelled), lalu kegagalan riil menjadi warning/redirect ke payroll lama alih-alih dilaporkan sebagai kegagalan.
- **Hubungan:** Ini juga menyamarkan kegagalan H-01 sebagai kemungkinan konflik/periode yang sudah dibuat.
- **Batas implementasi:** Laravel exception yang ditangkap tidak dibedakan menurut nama constraint di controller. Menentukan konflik unik yang diharapkan perlu pendekatan yang kompatibel dengan driver aktual atau pemeriksaan pasca-rollback bahwa memang sudah ada periode pesaing yang berhasil dibuat.
- **Arah perbaikan untuk rencana berikutnya:** Hanya perlakukan sebagai permintaan duplikat bila ada bukti periode pesaing yang berhasil dibuat untuk kunci periode tersebut; jika tidak, teruskan exception asli agar kegagalan terlihat. Verifikasi pada driver deployment sebelum memilih cara identifikasi konflik.

#### M-02 — Sumber data ekspor rekening bersifat live, bukan snapshot payroll

- **Bukti:** `app/Exports/PayrollSummaryExport.php:28-29` memuat relasi `employee`; baris 49-51 mengambil bank, nomor rekening, dan nama pemilik dari model `User` ketika ekspor dijalankan.
- **Bukti snapshot:** `PayrollGenerationService.php:164-180` menyimpan identitas, tarif, nilai gaji, serta ID/tanggal absensi di payroll item, tetapi tidak menyimpan data rekening.
- **Sumber setiap kolom export:** A (`employee_id`), B (`employee_name`), F (`employment_type`), G (`department`), H (`work_days`), I (`daily_rate`), J (`gross_pay`), K (`late_occurrences`), L (`total_late_minutes`), M (`late_deduction`), N (`other_deductions`), O (`net_pay`), dan P (`slip_number`) diambil dari `PayrollItem`; C (`bank_name`), D (`bank_account_number`), dan E (`bank_account_name`) berasal dari relasi `User` saat export; Q (`period label`) dan R (`status`) berasal dari `PayrollPeriod` pada waktu export. Baris total dihitung dari collection item export saat itu.
- **Dampak:** Ekspor untuk periode yang sama dapat berisi data rekening berbeda jika profil pegawai berubah setelah payroll dibuat. Ini membatasi kemampuan mereproduksi persis dokumen yang digunakan saat pembayaran.
- **Kontrol akses:** Ekspor saat ini berada di route payroll yang dibatasi middleware HRD dan controller juga memanggil pemeriksaan HRD; isu yang ditemukan adalah konsistensi/audit snapshot, bukan akses tanpa otorisasi.
- **Keputusan sebelum implementasi:** HR/Finance perlu menentukan apakah ekspor pembayaran memang harus memakai rekening terkini atau rekening yang dibekukan pada saat payroll dibuat/disetujui. Jangan menambah snapshot data rekening sensitif tanpa keputusan, pemeriksaan retensi, dan persetujuan skema. Tidak ada bukti bahwa snapshot rekening diperlukan untuk semua export (misalnya export ulang administratif), sehingga jangan mengubah semantik tanpa keputusan.

#### M-03 — Filter tanggal dan tanggal kerja menggunakan jalur timezone berbeda

- **Bukti:** `config/app.php` menetapkan timezone aplikasi `Asia/Jakarta`. PHP CLI di lingkungan audit, sebelum bootstrap Laravel, melaporkan default `UTC`. Laravel menjalankan aplikasi dengan konfigurasi timezone-nya, tetapi SQL `whereDate(check_in_at, ...)` pada `PayrollGenerationService.php:121-122` mengekstrak tanggal dari nilai kolom sesuai semantik driver/kolom/session DB; deduplikasi dan `work_date` pada baris 127-138 secara eksplisit mengonversi Carbon ke `config('app.timezone')`.
- **Bukti storage:** `check_in_at` didefinisikan sebagai `timestamp` nullable pada migration absensi. `config/database.php` menyediakan beberapa driver namun tidak menetapkan timezone koneksi untuk driver tersebut. Tidak ditemukan dokumentasi deployment yang menetapkan konvensi timestamp, timezone session DB, atau konversi saat insert.
- **Dampak bersyarat:** Filter SQL bisa memilih tanggal calendar berbeda dari tanggal kerja Jakarta untuk timestamp dekat tengah malam bila zona waktu/konversi DB dan aplikasi berbeda. Dalam kasus itu, absensi tanggal lokal awal/akhir periode bisa terlewat atau masuk periode lain. Belum cukup bukti untuk menyatakan ada salah hitung produksi karena database deployment tidak tersedia.
- **Tindak lanjut:** Sebelum mengubah query, pemilik sistem/DBA harus menetapkan apakah timestamp disimpan sebagai UTC atau waktu lokal, timezone session, dan definisi tanggal kerja. Setelah itu, rancang query batas yang deterministik terhadap konvensi terverifikasi. Jangan memilih UTC/local hanya dari default PHP CLI.

#### M-04 — Test revisi tidak mencakup kasus yang memicu benturan nomor slip; uji atomicity dan concurrency juga tidak ditemukan

- **Bukti:** Test revisi di `tests/Feature/Admin/PayrollTest.php` hanya membuat akun HRD, membatalkan periode, lalu memastikan revisi terbentuk. Tidak ada employee/item pada test tersebut.
- **Bukti cakupan yang sudah ada:** Test generation menguji filter approved/pending/mock, batas tanggal, satu duplikat tanggal, tarif, potongan, dan snapshot. Unit test menguji sebagian batas periode dan contoh blok keterlambatan.
- **Celah test:** Tidak ditemukan test yang memaksa kegagalan pembuatan detail lalu memastikan seluruh transaksi rollback, menguji konflik pembuatan serentak, atau menguji revisi dengan item pegawai yang mempertahankan data periode lama.
- **Dampak:** Test saat ini tidak akan menangkap kegagalan H-01 ataupun membuktikan perilaku rollback dan race condition yang diharapkan.

### Batas verifikasi schema deployment

- Migration `database/migrations/2026_10_23_000000_create_payroll_tables.php` mendefinisikan skema yang diharapkan, termasuk unique global pada `slip_number`, unique periode+revisi, unique item per periode+pegawai, dan unique detail per item+tanggal.
- File `database/database.sqlite` lokal tidak mempunyai tabel `payroll_periods`, `payroll_items`, `payroll_lateness_details`, atau `payroll_corrections`; daftar tabel migration lokal juga tidak mencatat migration payroll.
- Ini hanya membuktikan kondisi file SQLite lokal, bukan keadaan staging/production. Driver dan skema deployment aktual belum diberikan dan tidak boleh dianggap sama dengan migration atau SQLite lokal.
- Sebelum migration/perubahan constraint diputuskan, DBA/operator harus memeriksa schema aktual, versi migration, indeks/constraint, dan kemungkinan data yang sudah ada pada salinan staging/backup yang aman. Audit ini tidak membaca data pegawai atau payroll deployment.

### Low

#### L-01 — Referensi detail keterlambatan ke absensi tidak memiliki foreign key

- **Bukti:** Migration `2026_10_23_000000_create_payroll_tables.php:65-73` menyimpan `attendance_id` sebagai unsigned integer dan memberi index, tetapi tidak mendefinisikan foreign key ke `absensis`.
- **Dampak:** `attendance_id` dapat menunjuk record yang sudah tidak ada. Detail payroll tetap menyimpan tanggal, menit, blok, dan nominal potongan sehingga snapshot historis masih bisa dibaca.
- **Catatan:** Tidak dapat disimpulkan bahwa foreign key harus ditambahkan. Tidak adanya cascade dapat disengaja agar penghapusan absensi tidak merusak payroll historis; perubahan schema juga memerlukan pemeriksaan database/data aktual terlebih dahulu.

## Hal yang sudah konsisten atau terlindungi

- `periodBounds()` memvalidasi tahun 2020–2100, bulan 1–12, dan periode 1/2; periode pertama tanggal 1–15 dan periode kedua tanggal 16–akhir bulan. Unit test sudah mencakup bulan 30 hari dan Februari kabisat/non-kabisat.
- `calculateTotals()` menghitung potongan per tanggal dengan `intdiv(minutes, 30)`, tarif harian Rp120.000, dan Rp7.500 per blok penuh 30 menit. Test unit mengunci contoh boundary yang tersedia. Tidak ada perubahan aturan yang diusulkan oleh audit ini.
- Kandidat pegawai dipilih dengan `employment_type = freelance`, `registration_status = approved`, dan pengecualian role yang disebut dalam query service. Migration mendefinisikan registration status dan pilihan employment type; status `is_active` adalah kolom terpisah dan tidak dipakai oleh query payroll.
- Absensi difilter berdasarkan status hadir/approved, root record (`parent_id` null), bukan mock, check-in non-null, serta rentang periode. Query diurutkan sebelum deduplikasi berdasarkan tanggal lokal aplikasi.
- `PayrollGenerationService::createPeriod()` membungkus pembuatan periode, item, dan detail dalam transaksi. `PayrollController::store()` membungkus panggilan service beserta audit log dalam transaksi luar. Secara desain, exception saat pembuatan item/detail seharusnya membatalkan transaksi, walau jalur kegagalan tersebut belum diuji.
- Migration memiliki unique constraint untuk identitas periode+revisi, item per periode+pegawai, detail per item+tanggal, dan nomor slip. Constraint membantu integritas serta mitigasi duplikasi, tetapi tidak menggantikan penanganan exception yang tepat.
- Route payroll memakai `auth`, middleware admin, middleware activity log, dan middleware `super_admin`; middleware serta controller membatasi akses ke role `hrd`. Test feature sudah memverifikasi setidaknya halaman payroll ditolak untuk role admin biasa.
- Transisi status menggunakan transaksi, `lockForUpdate()`, validasi status asal, dan ActivityLog. Pembatalan tidak menghapus payroll item; pembuatan revisi dilakukan untuk periode yang dibatalkan.
- Controller memvalidasi input periode, alasan pembatalan/koreksi, referensi pembayaran, serta memeriksa item agar slip/export tidak menerima item dari periode lain.

## Aturan/keputusan bisnis yang belum boleh diasumsikan

1. Format/keunikan nomor slip untuk revisi dan kompatibilitas dengan nama file slip.
2. Apakah data rekening pada ekspor harus menjadi snapshot payroll atau sengaja mengikuti profil terbaru.
3. Apakah pegawai `freelance` dengan `is_active = false` tetap berhak masuk payroll jika `registration_status = approved`.
4. Apakah nilai bersih boleh negatif bila potongan melebihi gaji kotor; kolom `net_pay` bertipe signed dan perhitungan saat ini tidak membatasi nilai minimum.
5. Apakah `other_deductions` memang selalu nol dan formula net pay hanya gross dikurangi potongan telat.
6. Definisi tanggal kerja dan sumber timezone timestamp, termasuk shift/check-in dekat tengah malam.
7. Record absensi mana yang harus dipilih bila beberapa record root valid ada pada hari yang sama; implementasi kini memilih yang paling awal menurut `check_in_at`, lalu `id`.
8. Apakah cancel terhadap payroll berstatus `approved` lalu membuat revisi diizinkan oleh proses HR; controller saat ini mengizinkan pembatalan status draft, pending approval, dan approved, tetapi bukan paid.
9. Apakah periode tanpa pegawai eligible memang boleh dibuat sebagai draft kosong; test generation saat ini menghasilkan item bernilai nol untuk pegawai eligible tanpa absensi, namun tidak memastikan semantik ketika tidak ada pegawai eligible sama sekali.
10. Apakah referensi absensi detail perlu dijamin FK atau sengaja dipertahankan sebagai identifier historis yang tidak menghalangi penghapusan absensi.

## Kesimpulan audit

Audit payroll di atas tetap menjadi temuan spesifik payroll. Hasil audit seluruh aplikasi terbaru tercatat di bawah. Selama tahap audit read-only, kode produksi belum diubah. Schema deployment tetap belum dapat dipastikan dari repository atau SQLite lokal.

## Audit aplikasi menyeluruh — 26 Oktober 2026

### Cakupan dan verifikasi

- Ditelusuri autentikasi, profil API, OTP, approval absensi/lembur, payroll, export, activity log, backup, command sinkronisasi hari libur, route, migration, dan test yang terkait.
- Repository yang tersedia adalah aplikasi Laravel; tidak ditemukan direktori aplikasi mobile/web client terpisah pada root. Keberadaan client eksternal yang memakai API tidak dapat disimpulkan dari repository.
- Test suite: `php artisan test --compact` — **50 passed, 291 assertions**.
- `composer validate --no-check-publish` — valid; warning tersisa: dependency `maatwebsite/excel` memakai constraint exact/dev `4.x-dev`.
- `npm run build` — lulus setelah dependency lokal dipasang dari lockfile; warning Browserslist menyebut data browser lama. Build artifact yang dihasilkan selama pemeriksaan telah dipulihkan.
- `vendor/bin/pint --test` seluruh repository gagal karena temuan formatter yang sudah ada di banyak file. Pint terarah pada file payroll/config yang relevan lulus.
- Pemeriksaan `php -l` untuk PHP di `app`, `routes`, `database/migrations`, dan `tests` lulus. `git diff --check` lulus.
- Test memakai SQLite `:memory:`. Tidak ada migration, seeder, generator payroll, atau operasi tulis ke database aplikasi/production. Tidak ada isi record pegawai/payroll yang dibaca.

Sesudah tahap audit read-only dan setelah rencana perubahan diperbarui, APP-01 di bawah telah diperbaiki pada `ActivityLogController.php`. Perubahan tersebut tidak memengaruhi database.

### Temuan aplikasi berdasarkan tingkat risiko

#### High

**A-01 — Field absensi sensitif belum dibuktikan terenkripsi pada level aplikasi**

- **Bukti kode:** `database/migrations/2026_04_24_110938_encrypt_sensitive_fields_in_absensis_table.php:14-27` mengubah tipe kolom menjadi `TEXT`, tetapi tidak mengenkripsi nilai. `app/Models/Absensi.php:38-39,84-100` tidak memiliki encrypted casts untuk field lokasi; field nominal tetap diperlakukan numerik.
- **Fakta:** migration tersebut sendiri tidak melakukan enkripsi; model yang diaudit tidak menerapkan enkripsi aplikasi pada field tersebut.
- **Belum terverifikasi:** enkripsi disk/database yang mungkin diterapkan oleh operator deployment.
- **Risiko:** lokasi/field sensitif dapat tersimpan terbaca melalui koneksi database atau backup. Penambahan cast langsung dapat merusak pembacaan nilai legacy plaintext; pengubahan field nominal juga berisiko mematahkan query dan kalkulasi.
- **Keputusan:** perlu klasifikasi data oleh security/data owner dan desain konversi kompatibel sebelum perubahan model/schema atau konversi data.

**A-02 — Backup berpotensi menyertakan `.env` tanpa enkripsi arsip**

- **Bukti kode:** `config/backup.php:18-35` memasukkan root aplikasi dan tidak mengecualikan `.env`; tujuan lokal/S3 diatur pada `:83-85`; password arsip opsional (`BACKUP_ARCHIVE_PASSWORD`) pada `:98`; backup dijadwalkan di `routes/console.php:56-58`.
- **Fakta:** konfigurasi mengizinkan backup memuat `.env` dan password arsip kosong.
- **Kondisional:** belum ada bukti apakah password terisi, siapa yang dapat membaca disk lokal/S3, atau apakah deployment menerapkan enkripsi tambahan.
- **Risiko:** jika password kosong dan akses storage tidak cukup dibatasi, arsip dapat mengungkap credential aplikasi/database.
- **Keputusan:** operator/security perlu menetapkan retensi konfigurasi rahasia, enkripsi arsip, pengelolaan password, dan hak akses storage sebelum perubahan kebijakan backup.

#### Medium

**A-03 — Pengguna API dapat mengubah employee ID dan departemen sendiri**

- **Bukti kode:** route profil memakai autentikasi Sanctum (`routes/api.php:62-64`); `UserProfileController.php:59-60,90-91` menerima dan menyimpan `id_karyawan` serta `departemen`. Prefix employee ID turut menentukan kategori absensi (`User.php:106-120`, `AbsensiController.php:104,177`).
- **Dampak:** metadata yang semestinya menentukan kategori absensi dan laporan dapat diubah oleh pemilik akun tanpa alur HR.
- **Keputusan/kompatibilitas:** HR perlu menentukan apakah perubahan self-service memang diizinkan. Cara kompatibel adalah tetap menerima payload tetapi tidak mengubah nilai aktif, atau menampung perubahan untuk review HR; client eksternal dan kontrak yang berjalan perlu dikonfirmasi.

**A-04 — Logout API gagal untuk autentikasi Sanctum stateful**

- **Bukti kode:** `bootstrap/app.php:21` mengaktifkan `statefulApi()`, route logout berada di `auth:sanctum` (`routes/api.php:55-59`), dan `AuthController.php:74-76` selalu memanggil `delete()` pada current access token. Token stateful adalah `TransientToken` yang tidak memiliki `delete()`, berbeda dari personal access token bearer.
- **Dampak:** logout dari sesi SPA/cookie dapat error; logout bearer-token berfungsi melalui token yang dapat dihapus.
- **Status/perbaikan:** ditangani di `AuthController::logout()`: personal access token bearer dihapus, sedangkan token transient stateful memanggil logout guard web, invalidasi session, dan regenerasi CSRF token. Route dan response tetap sama.
- **Test aktual:** `tests/Feature/Api/AuthLogoutTest.php` menjalankan session login web → API stateful logout → protected API menjadi unauthorized, serta bearer logout dan token revocation. Suite penuh dijalankan ulang.
- **Catatan:** test memastikan dua perilaku yang berbeda; cakupan logout session mengikuti sesi web, sedangkan bearer hanya mencabut token yang dipakai.

**A-05 — Alur OTP pemulihan password rawan tebakan online**

- **Bukti kode:** `ForgotPasswordController.php:27-35` menggunakan `rand()` dan menyimpan OTP plaintext; verifikasi/reset (`:87-108,139-161`) tidak menerapkan penghitung percobaan per akun. Throttle route (`routes/api.php:40-48`) membatasi request, bukan tebakan terdistribusi per akun.
- **Kondisional:** response mengembalikan OTP ketika `APP_ENV=local` (`ForgotPasswordController.php:60-61`); konsekuensi bypass terjadi hanya jika deployment yang dapat diakses publik keliru memakai environment tersebut.
- **Risiko:** kontrol saat ini tidak membuktikan serangan praktis, tetapi tidak cukup untuk menahan tebakan terdistribusi. Batas percobaan/cooldown berpengaruh ke UX dan memerlukan keputusan security/product.
- **Tindak lanjut:** evaluasi CSPRNG, hash OTP, konsumsi sekali, batas percobaan per akun, serta pastikan debug OTP tidak pernah aktif pada deployment eksternal.
- **Status terbaru:** CSPRNG, limit kegagalan per email, expiry batas 10 menit, transaksi sekali pakai saat reset, dan penanganan kegagalan mail telah diperbaiki pada 25 Oktober 2026. Penyimpanan OTP plaintext, debug OTP khusus environment `local`, serta email enumeration masih terbuka; lihat bagian audit terbaru.

**A-06 — Penolakan request izin keluar yang belum selesai dapat mengubah gaji absensi menjadi nol**

- **Bukti kode:** antrean HRD hanya menampilkan request selesai (`ApprovalController.php:71`), sedangkan handler reject tidak memastikan `status_izin` masih `selesai` sebelum mengubah status dan nilai `base_salary`/`final_salary` absensi menjadi nol (`:737-780`).
- **Dampak:** request `berjalan` yang ditolak langsung dapat mengubah nilai gaji hari terkait meskipun belum mencapai keadaan yang ditampilkan dalam antrean.
- **Perbaikan aman:** tolak pemrosesan request non-selesai sebelum mengubah status, absensi, atau notifikasi; ini menyelaraskan handler dengan syarat queue.
- **Keputusan HR:** konsekuensi gaji untuk penolakan request yang sudah selesai tetap belum diputuskan; jangan ubah bagian tersebut.

**A-07 — API status scheduled overtime dapat melewati transisi, audit, dan notifikasi web**

- **Bukti kode:** `PUT /absensi/scheduled-lembur/{id}/status` ada pada `routes/api.php:86`; `ScheduledLemburController.php:66-82` menerima status `pending`, `approved`, atau `rejected` dan memperbarui langsung tanpa pengecekan status asal maupun efek audit/notifikasi yang ada pada jalur web (`ApprovalController.php:696-730`).
- **Risiko:** API dapat mengembalikan status terminal ke pending atau mengubahnya tanpa jejak/notification yang konsisten.
- **Belum terverifikasi:** apakah API ini dipakai client/deployment sebagai jalur approval alternatif.
- **Keputusan:** pemilik API/HR perlu memastikan kontrak client serta transisi status yang sah; jangan menghapus route atau memutus payload existing.

**A-08 — Export detail massal dapat memilih absensi non-approved dan menghitung potongan non-approved**

- **Bukti kode:** `app/Exports/BulkDetailExport.php:31-32,77-94` memuat beberapa status approval, mempertahankan record ID tertinggi per hari, serta menjumlahkan penalti/adjustment tanpa filter approval.
- **Dampak:** record pending/rejected yang lebih baru dapat menggantikan record approved dan potongan yang belum disetujui dapat memengaruhi hasil export.
- **Keputusan:** owner payroll harus mengonfirmasi definisi export dan perlakuan hari yang hanya memiliki record pending/rejected sebelum mengubah perhitungan.

**A-09 — Activity log melakukan `dd()` pada exception dan menampilkan detail internal**

- **Bukti kode:** `app/Http/Controllers/Admin/ActivityLogController.php:14-23` menangkap `Throwable` lalu menjalankan `dd()` berisi pesan, path file, dan nomor baris exception.
- **Dampak:** request berhenti mendadak, pesan teknis/path internal ditampilkan, dan framework tidak dapat menangani exception melalui jalur logging/error standar.
- **Status:** bug teknis berkeyakinan tinggi; route dibatasi middleware `auth`, `admin`, serta `super_admin` di `routes/web.php:42,61-74`. Perbaikan minimal adalah menghapus catch dan membiarkan Laravel melaporkan/merender kegagalan sesuai handler global, tanpa mengubah route atau tampilan normal.

#### Low

**A-10 — Endpoint OTP memungkinkan enumerasi email**

- **Bukti kode:** `ForgotPasswordController.php:19-21,64-69` mengembalikan error “Email tidak ditemukan” untuk alamat yang tidak terdaftar, berbeda dari alamat terdaftar.
- **Dampak:** pihak eksternal dapat menguji keberadaan akun.
- **Keputusan/kompatibilitas:** response generik mengurangi enumerasi tetapi dapat memengaruhi client yang mengandalkan 422/error saat ini; perlu koordinasi security dan pemilik API.

**A-11 — Perintah sync holiday mengabaikan tahun yang diminta**

- **Bukti kode:** `SyncHolidaysCommand.php:20-24,56-67` menerima/mencatat tahun parameter tetapi selalu memproses daftar tetap tahun 2026 dan melaporkan sukses untuk 2026.
- **Dampak:** menjalankan command untuk tahun lain memberi hasil sukses yang menyesatkan tanpa menyinkronkan tahun tersebut.
- **Perbaikan minimal:** jika sumber resmi hanya tersedia untuk 2026, tolak tahun lain secara eksplisit sebelum menulis; mendukung tahun lain memerlukan kalender terpelihara dan sumber otoritatif.

### Area yang perlu verifikasi, bukan kesimpulan bug

- `bootstrap/app.php` mempercayai proxy (`trustProxies(at: '*')`). Keamanannya bergantung pada jaringan deployment dan apakah app dapat dicapai langsung tanpa proxy tepercaya; belum ada konfigurasi infra untuk menyimpulkan exploit.
- `SecurityHeaders` menetapkan `Permissions-Policy: geolocation=()`. Perlu dicocokkan dengan apakah client web memakai geolocation; keberadaan penggunaan GPS melalui API tidak otomatis berarti policy browser ini memblokir API.
- Metadata schema payroll dan migrasi dalam repository bukan bukti schema staging/production. Engine, versi migration, index aktual, timezone session DB, backup encryption, dan konfigurasi proxy deployment belum diverifikasi.
- Tidak ditemukan jalur aktif approval supervisor/manager di route web yang diperiksa; legacy URLs mengarah ke HRD. Tidak ada perubahan alur approval yang dilakukan.

### Pemisahan temuan teknis, keputusan, dan batas test

- **Terbukti sebagai cacat teknis:** `ActivityLogController::dd()`; logout stateful mencoba menghapus `TransientToken`; sync holiday melaporkan sukses untuk tahun yang tidak disinkronkan.
- **Risiko yang dibuktikan kode tetapi perbaikannya memengaruhi kebijakan/kontrak:** perubahan employee ID/departemen, OTP attempts/response, API status overtime, ekspor detail, dan efek gaji penolakan izin keluar.
- **Memerlukan verifikasi deployment/data owner:** enkripsi at rest, backup archive password/storage access, trusted proxy, schema payroll, timezone.
- Test green pada SQLite in-memory tidak membuktikan concurrency, schema, konfigurasi backup, atau autentikasi session/cookie pada deployment aktual.

### Audit lanjutan: detail absensi per karyawan

**ATT-01 — Rentang tanggal custom yang tidak lengkap/terbalik melewati filter — diperbaiki**

- **Bukti kode:** `AbsensiAdminController::show()` membaca `start_date` dan `end_date` lalu hanya menambahkan `whereBetween` bila keduanya truthy (`app/Http/Controllers/Admin/AbsensiAdminController.php:377-389`). Cabang custom yang sama di `exportUser()`, `exportSlipGaji()`, dan `exportSlipGajiPdf()` juga mengabaikan rentang bila salah satu tanggal kosong (`:738-746`, `:778-790`, `:861-880`).
- **Dampak:** permintaan custom yang tidak lengkap jatuh kembali ke seluruh data (atau seluruh data approved pada slip) dan tidak menjelaskan bahwa filter tidak diterapkan. Tanggal berformat tidak valid dapat menyebabkan parse error; tanggal awal setelah tanggal akhir dapat menghasilkan hasil kosong yang tidak informatif.
- **Kepastian:** bug perilaku terbukti pada kode; tidak memerlukan interpretasi aturan gaji. Form halaman memang menyediakan dua input tanggal dan mengirim filter sama ke tombol export.
- **Perbaikan:** `AbsensiAdminController` kini memvalidasi filter pada halaman detail, export absensi, export slip Excel, dan export slip PDF. Filter custom mewajibkan tanggal `Y-m-d` yang valid serta rentang tidak terbalik; tahun/bulan yang diberikan juga harus bernilai kalender. UI, route, nama field, dan rentang inklusif satu hari penuh tidak berubah.
- **Test aktual:** `tests/Feature/Admin/AttendanceDetailTest.php` memverifikasi record dan statistik pada rentang valid, tanggal hilang/terbalik, format tanggal dan jenis filter invalid, serta penolakan rentang tidak lengkap pada ketiga export. Semua dijalankan memakai fixture sintetis dan SQLite `:memory:`.
- **Hasil regresi:** `php artisan test --compact` — **54 passed, 320 assertions**. Pint untuk test baru, syntax PHP, dan `git diff --check` lulus. Pint pada keseluruhan `AbsensiAdminController` tidak lulus karena banyak formatter findings existing pada file tersebut; tidak dilakukan autoformat massal.

Catatan audit ini tidak menyimpulkan bahwa rumus/statistik absensi yang memasukkan status rejected atau mengecualikan pending salah; perlakuan status tersebut merupakan aturan bisnis yang harus dikonfirmasi. Detail menunjuk record induk saja (`parent_id` null), sedangkan implementasi statistik juga menggunakan parent record; perubahan itu tidak direncanakan pada pass ini.

**ATT-02 — Edit waktu memungkinkan urutan check-in/check-out yang tidak valid — diperbaiki**

- **Bukti kode:** `updateCheckIn()` dan `updateCheckOut()` hanya memvalidasi format `datetime-local` (`AbsensiAdminController.php:1110-1113,1168-1171`) dan tidak membandingkan waktu baru dengan timestamp pasangannya.
- **Dampak:** HRD dapat menyimpan check-in setelah check-out atau check-out sebelum check-in. Ini melanggar urutan kronologis record dan dapat memengaruhi perhitungan durasi.
- **Perbaikan:** endpoint edit kini menolak check-in baru setelah checkout tersimpan dan checkout baru sebelum check-in tersimpan, melalui validation error server-side.
- **Test aktual:** kedua urutan invalid ditolak dan timestamp tetap; urutan valid masih tersimpan. Dicakup di `AttendanceDetailTest.php`.

**ATT-03 — Default edit pulang memakai tanggal hari ini saat checkout kosong — diperbaiki**

- **Bukti kode:** tombol pada `resources/views/admin/absensi/user.blade.php:455-458` menggunakan `now()->format('Y-m-d')` jika `check_out_at` null, padahal tanggal baris absensi tersedia dari `check_in_at`.
- **Dampak:** membuka edit checkout untuk absensi lama mengisi tanggal hari ini, sehingga penyimpanan tanpa koreksi manual dapat menghasilkan checkout pada hari yang salah.
- **Perbaikan:** fallback menggunakan tanggal `check_in_at` record tersebut; jam default 17:00 dan tampilan tetap.
- **Test aktual:** render data sintetis tanpa checkout memastikan `data-checkout-time` mengikuti tanggal check-in.

**ATT-04 — Perubahan checkout tidak menghitung ulang nilai gaji yang tergantung durasi**

- **Bukti kode:** `Absensi::calculateSalary()` menghitung `worked_minutes` dari `check_in_at` dan `check_out_at` dan menghasilkan `base_salary`/`final_salary` (`app/Models/Absensi.php:153-183`). Jalur edit checkout di `AbsensiAdminController::updateCheckOut()` hanya memperbarui `check_out_at` (`:1163-1193`), tanpa memanggil kalkulasi. Jalur check-in edit juga menggunakan formula manual yang berbeda dari helper model.
- **Dampak:** timestamp dapat berubah sementara nilai gaji tersimpan tetap; bila gaji ditampilkan/diekspor dari nilai tersimpan, nilainya tidak lagi sejalan dengan durasi baru.
- **Batas keputusan:** memperbaikinya dengan helper pusat berpotensi mengubah hasil gaji, kategori, potongan, weekend/holiday, overtime, dan perilaku check-in edit. Nominal/rumus adalah aturan payroll dan tidak boleh ditebak.
- **Status:** belum diperbaiki; HR/Finance perlu menyetujui formula sumber dan perlakuan overtime/weekend/holiday serta cara menangani histori sebelum kalkulasi edit diseragamkan.

### Audit konsistensi Absensi → Payroll — 25 Oktober 2026

#### Perhitungan absensi yang sedang berjalan

- `Absensi::calculateSalary()` memakai konstanta attendance model: gaji harian Rp150.000 atau Rp300.000 pada weekend/libur, per menit Rp312,50 atau Rp625, pembulatan telat ke kelipatan 15 menit ke atas, gaji pokok prorata dari menit kerja, dan gaji bersih minimal nol sebelum tambahan lembur. Durasi mengurangi istirahat 12:00–13:00. Jika checkout tidak ada, helper menganggap 480 menit kerja.
- `Absensi::calculateOvertimeFromInput()` menghitung durasi, mengembalikan 0 untuk durasi di bawah 30 menit, mengurangi 30 menit bila istirahat, lalu menggunakan tarif per jam Rp18.750/Rp37.500. Helper menangkap exception dan mengembalikan hasil nol. Pemanggil dan kebijakan terhadap rentang waktu terbalik perlu konsisten diperiksa sebelum perubahan; method memakai `abs()` sehingga end sebelum start berpotensi menjadi durasi positif.
- API check-in menyimpan keluaran `calculateSalary()`; kalkulasi late minutes API memakai baseline 08:00 dan mengecualikan kategori organik/magang.
- API check-out memanggil `calculateSalary()` dengan check-in/out aktual dan kategori user. Gaji lembur dapat ditambahkan kemudian melalui alur approval/record lembur.
- Persetujuan absensi tertentu hanya mengisi salary bila nilai sebelumnya null, sehingga tidak selalu menghitung ulang record lama.
- Edit check-in admin saat ini memakai kalkulasi manual: baseline 08:00 untuk semua kategori, pembulatan 15 menit, tarif denda dari `User.base_salary_per_day / 480`, mempertahankan base salary sebelumnya, lalu mengurangi penalti dan menambah overtime pay. Formula ini berbeda dari API/model.
- Edit check-out admin hanya mengubah timestamp check-out; tidak menghitung ulang `base_salary`, `late_penalty`, `final_salary`, atau field overtime.

#### Perbedaan yang berdampak pada konsistensi

1. `calculateSalary()` menghitung ulang base pay dari durasi kerja sedangkan edit check-in tidak.
2. Perhitungan lateness edit admin tidak mengecualikan organik/magang seperti API check-in, dan memakai tarif profile per hari sedangkan helper menggunakan konstanta serta multiplier weekend/holiday.
3. Edit checkout mengubah input durasi tanpa menghitung ulang salary yang bergantung pada durasi.
4. `PayrollGenerationService` tidak memakai `Absensi.base_salary`, `late_penalty`, `final_salary`, atau `calculateSalary()`. Ia memakai jumlah attendance hadir+approved sebagai hari kerja, `late_minutes` tersimpan sebagai input potongan, tarif payroll Rp120.000/hari, potongan Rp7.500 per blok 30 menit penuh per hari, serta snapshots attendance IDs/work dates/late source. Karena itu attendance salary lama dan payroll generation memang memiliki formula/tujuan berbeda dalam source; jangan menyamakan nominal kedua modul.

#### Dampak pada payroll yang sudah dibuat

- Item payroll menyimpan angka gaji, hari kerja, keterlambatan, identitas, dan attendance snapshot pada saat generate. Service tidak meng-update item payroll yang sudah ada ketika absensi kemudian diedit; perubahan waktu tidak mengubah snapshot payroll lama secara otomatis.
- Generate/revisi baru membaca `late_minutes` attendance yang saat itu tersimpan dan membuat snapshot baru. Jadi edit check-in sebelum revisi berpotensi mengubah keterlambatan dan nilai revisi, sementara item periode sebelumnya tetap seperti semula.
- Ini menjaga histori secara teknis tetapi tidak menyelesaikan kebutuhan audit koreksi: saat ini snapshot mencatat attendance IDs/tanggal dan sumber late minutes, bukan salinan timestamp sebelum/sesudah edit. Dampak terhadap payroll approved/paid dan kewajiban koreksi harus ditetapkan oleh HR/Finance.

#### Keputusan implementasi

- Perbedaan salary editing terbukti; namun memilih helper mana sebagai sumber perhitungan akan mengubah angka, terutama untuk kategori organik/magang, rate profile berbeda, weekend/libur, overtime, dan payroll yang sudah disetujui. **Tidak dilakukan recalculation sampai HR/Finance menyetujui formula kanonis dan kebijakan koreksi.**
- Test sintetis yang diperlukan setelah keputusan: check-in edit sebelum/sesudah baseline per kategori; check-out edit memengaruhi durasi dan payroll attendance amounts sesuai helper yang disahkan; overtime tetap konsisten; nilai payroll item lama tetap tidak berubah; revision baru mengambil data attendance terkoreksi; audit log menyimpan perubahan.
- Tidak ada payroll production yang dihitung ulang dan tidak ada data historis yang diubah pada audit ini.

### Catatan pemeriksaan middleware route

`IsAdmin::handle()` menggunakan `User::isAnyAdmin()`, dan middleware alias `super_admin` menggunakan `User::isSuperAdmin()`. Kedua predicate model saat ini menerima role `hrd` saja. Karena itu role `hrd` dapat melewati route detail dan export slip; test awal yang menggunakan role string `super_admin` mendapat penolakan seperti yang dikonfigurasi. Nama alias middleware berpotensi membingungkan, tetapi tidak ada perubahan authorization yang dilakukan karena akses role adalah keputusan kebijakan.

## Audit OTP, akses, ekspor, dan deployment — 25 Oktober 2026

### OTP dan autentikasi

- **Fakta awal:** `routes/api.php` menerapkan `throttle:api` 60 request/menit per user/IP; endpoint send OTP dibatasi 3/menit, verify 5/menit, reset 3/menit oleh middleware route. Batas tersebut berbasis IP, bukan akun.
- **Fakta awal:** OTP dibuat dengan `rand()`, disimpan plaintext pada `password_resets.otp` yang didefinisikan repository sebagai `string(6)`, dan dikirim melalui email. Pengiriman email yang gagal sebelumnya dicatat sebagai error tetapi endpoint tetap mengembalikan sukses dan OTP aktif tetap tersimpan.
- **Fakta awal:** usia OTP dibandingkan dengan selisih integer menit menggunakan `> 10`, sehingga aturan batas tepat 10 menit tidak eksplisit/tepat. Verifikasi dan reset terpisah; reset menghapus OTP setelah sukses, tetapi perubahan password, pencabutan token, dan penghapusan OTP belum berada dalam transaksi atomik sehingga pemakaian bersamaan berpotensi berlomba.
- **Perbaikan aman pada pass ini:** gunakan `random_int()`, validasi OTP tepat enam digit numerik, expiry pada batas `created_at + 10 menit` (timestamp null expired), pembatasan 5 kegagalan per email selama 10 menit lintas IP (key hanya berisi hash email), dan reset password/token/OTP dalam transaksi dengan row lock. Kegagalan email menghapus OTP baru dan mencatat jenis exception saja; respons tetap generik untuk mencegah enumerasi.
- **Perbaikan terbaru:** OTP baru disimpan sebagai keyed verifier HMAC-SHA256 yang dipadatkan menjadi enam karakter base36 agar muat pada kolom repository `string(6)`; verifikasi memakai `hash_equals()`. Nilai OTP mentah hanya dikirim email dan tidak dicantumkan di response, log, atau database untuk penerbitan baru. Record plaintext lama tetap diterima hanya selama masa berlakunya (maksimal 10 menit) agar deploy tidak memutus OTP yang sedang berjalan. Pembaruan pengiriman email diserialisasi dengan row lock user, dan lock dipertahankan sampai send sinkron selesai agar resend bersamaan tidak mengirim kode lama belakangan.
- **Schema lokal yang diperiksa read-only:** `database/database.sqlite` berisi `password_resets(email varchar NOT NULL, otp varchar NOT NULL, created_at datetime nullable)` dan non-unique index pada email; migration OTP tercatat. SQLite tidak menegakkan panjang `varchar(6)` sebagaimana MySQL umumnya, sehingga hasil ini tidak membuktikan schema deployment. Tabel payroll tidak ada pada SQLite lokal dan tidak ditemukan catatan migration payroll di tabel `migrations`; schema deployment tetap belum terverifikasi.
- **Risiko tersisa:** account limiter mengurangi tebakan lintas IP tetapi alamat yang diketahui dapat mengalami lockout sementara. Send OTP sekarang memberi status/pesan yang sama untuk email terdaftar/tidak terdaftar dan jika mail delivery gagal, sehingga mengurangi enumerasi eksplisit; karena pengiriman mail masih sinkron, timing request dapat berbeda dan tidak diklaim sepenuhnya bebas side-channel. Validasi format email tetap mengembalikan 422 untuk input malformed. Route dan bentuk JSON tetap, pesan sukses digenerikkan, dan field `otp_debug` dihapus.
- **Catatan operasional:** pengiriman SMTP berada dalam transaksi yang menahan lock baris user untuk mengurutkan resend. Ini mencegah race kode baru/lama tetapi dapat menahan transaksi lebih lama bila mail server lambat; staging harus menguji timeout, lock wait, dan load pada rate-limit normal.
- **Sesi setelah reset:** kode reset menghapus Sanctum personal access tokens, tetapi tidak membatalkan sesi web yang sudah aktif. Karena session store deployment dapat berupa database/file/Redis dan tidak ada session-version mechanism, invalidasi semua SPA session memerlukan keputusan arsitektur serta verifikasi session store; belum diubah.
- **Autentikasi:** API login membuat bearer token Sanctum; middleware `auth:sanctum` juga menerima sesi SPA stateful berdasar konfigurasi `SANCTUM_STATEFUL_DOMAINS` dan guard `web`. Logout stateful versus bearer telah diperbaiki dan diuji terpisah pada pass sebelumnya. Konfigurasi domain/session deployment belum diverifikasi.

### Role dan authorization

- Route web `/admin` dibatasi oleh `auth`, middleware `admin`, dan pada payroll/route ekspor slip ditambah `super_admin`. `IsAdmin` memanggil `User::isAnyAdmin()` dan `IsSuperAdmin` memanggil `User::isSuperAdmin()`; keduanya saat ini hanya menerima role `hrd`. Controller payroll dan approval juga memiliki guard eksplisit `role === 'hrd'`.
- `User::isAdminOnly()` mengembalikan true untuk role `admin`, tetapi `isAnyAdmin()` tidak menerima role tersebut. Ini adalah perilaku aktual, bukan bukti apakah role `admin` seharusnya berwenang. Nama alias `super_admin` juga tidak sama dengan role yang diterima. Perubahan akses memerlukan persetujuan pemilik proses dan matriks role resmi.
- API absensi detail memeriksa `absensi.user_id === Auth::id()`; daftar absensi memfilter user aktif. API scheduled overtime memeriksa HRD sebelum perubahan status. API notification store memiliki pemeriksaan role `admin` sendiri; notification read dibatasi pada notifikasi milik user.
- Belum ada bukti pada repository ini bahwa setiap endpoint/role memiliki matrix test menyeluruh. Tidak mengubah workflow approval atau role pada audit ini.

### Ekspor absensi dan payroll

- Route export absensi berada di dalam middleware `auth` + `admin` dan activity logging; slip gaji/PDF massal mendapat tambahan middleware `super_admin` (yang secara aktual juga role HRD). Payroll export memanggil `ensureHrd()` di controller.
- Rekap absensi mengekspor kategori lewat `AbsensiRekapExport`; export class memfilter kategori sebelum membentuk baris. Endpoint export recap sebelumnya belum memvalidasi type/range/tanggal dan untuk custom range dapat default ke bulan berjalan saat tanggal tidak lengkap. Ini berisiko menghasilkan cakupan berbeda dari yang diminta/label export.
- Endpoint recap tampilan menghitung `total_absensi` atas status approved/rejected; endpoint export sebelumnya juga memasukkan pending ke kumpulan yang menjadi total count. Ini membuat total pada file dan halaman berbeda. Pembayaran/nominal recap tetap menjumlahkan approved attendance.
- Export massal detail/simple/PDF sebelumnya menerima tanggal tidak lengkap/terbalik atau hanya melakukan parse saat berjalan; rentang terbalik dapat menghasilkan file kosong atau label yang membingungkan.
- Export payroll menempatkan bank name, nomor rekening, dan nama pemilik dari relasi `User` saat export; informasi payroll/gaji berasal dari `PayrollItem`. Data bank dideklarasikan encrypted cast di model `User` dan hidden untuk serialisasi umum, tetapi secara sengaja dibaca saat ekspor. Akses ekspor dibatasi HRD; semantik rekening live versus snapshot tetap perlu keputusan HR/Finance.
- **Perbaikan aman:** endpoint export recap kini memvalidasi bulan/tahun, kategori, range, week, dan pasangan custom dates; status parent attendance yang dipakai untuk total disamakan dengan halaman recap (approved/rejected). Export massal detail/simple/PDF kini memvalidasi employee IDs dan tanggal berurutan dengan format tanggal yang kompatibel. Tidak mengubah format export, kategori, payroll rate, sumber rekening, atau akses role.

### Slip/revisi payroll

- Repository migration mensyaratkan `payroll_items.slip_number` unik global serta unique period `(year, month, period_number, revision)`; service generator menambahkan suffix `-R{revision}` hanya untuk revision > 1. Perubahan format telah tercatat sebagai keputusan yang diberikan pengguna pada dokumen desain.
- Bukti tersebut hanya menggambarkan kode dan migration repository. Schema/constraint aktual staging dan produksi belum diverifikasi. Tidak ada data historis yang diubah dan tidak ada migration dijalankan.
- Export rekening tetap memakai profil pegawai terkini; payroll item mempertahankan snapshot angka/identitas saat generate. Perbedaan formula edit absensi versus payroll dan ketidakpastian timezone tetap merupakan temuan terbuka.

### Checklist verifikasi staging dan produksi

Checklist berikut **belum dijalankan**; pemilik deployment/DBA perlu mengisi bukti tanpa menaruh credential atau PII di laporan:

1. Catat engine, versi server, versi driver PHP/PDO, collation, isolation level, dan hasil `migrate:status` pada staging. Bandingkan schema aktual payroll, `password_resets`, users, token, index/constraint dengan migration release; pastikan `slip_number` global unique dan panjang kolom memadai. Jangan jalankan migration di produksi sebagai bagian audit.
2. Verifikasi timezone `config('app.timezone')`, timezone PHP worker/CLI, timezone session DB, cara penyimpanan dan pembacaan timestamp, serta batas awal/akhir payroll memakai data sintetis di staging.
3. Pastikan `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` tersedia dan dikelola secret manager, file `.env`/backup tidak dapat diunduh publik, serta nilai secret tidak dicetak dalam log/tiket.
4. Cocokkan `SESSION_DRIVER`, lifetime/encryption, table/cache backend, domain/secure/same-site cookie, HTTPS, dan `SANCTUM_STATEFUL_DOMAINS` dengan host SPA/API. Uji CSRF, stateful logout, bearer revoke, dan sesi sesudah deploy.
5. Tinjau trusted proxy/load balancer dan forwarded headers; hanya proxy resmi yang dipercaya. Pastikan aplikasi memaksa HTTPS pada jalur eksternal dan limiter mendapat IP client yang benar tanpa mempercayai header spoofable.
6. Pastikan store cache yang dipakai rate limiter tersedia bersama di seluruh worker/instance; jangan memakai cache `array` di deployment multi-instance. Verifikasi queue driver, worker aktif, retry, failed-jobs monitoring, dan mail transport bila queue digunakan.
7. Verifikasi backup DB dan storage, enkripsi/akses/retensi, keberhasilan restore latihan di lingkungan terisolasi, serta prosedur point-in-time recovery bila tersedia. Jangan mencantumkan password backup/DB di checklist.
8. Uji ekspor/revisi dengan pegawai sintetis atau data masked; validasi akses role HRD dan penolakan role lain, nilai file terhadap halaman, proteksi nomor rekening, constraint, rollback, dan jejak audit.
9. Persetujuan HR/Finance atas sumber rekening ekspor, koreksi payroll, dan formula absensi harus dicatat. DBA/operator menandatangani hasil schema, backup/restore, dan rollback staging sebelum release.
10. Verifikasi OTP pada staging dengan input sintetis: pastikan kolom menyimpan keyed verifier enam karakter (bukan OTP email), kode lama masih bekerja hanya di jendela transisi TTL, resend mengganti kode, brute-force lintas IP ditolak, gagal mail tidak meninggalkan verifier aktif, dan account limiter memakai cache bersama. Jangan masukkan OTP pengujian ke log/tiket.

#### Prosedur pemeriksaan schema read-only untuk DBA

Jalankan hanya pada koneksi staging yang disetujui dengan akun database read-only. Query berikut membaca metadata, bukan isi data payroll/pegawai. Jangan menyertakan host, username, password, atau hasil row bisnis dalam laporan:

```sql
SELECT VERSION(), @@session.time_zone, @@global.time_zone, @@transaction_isolation;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name IN ('password_resets', 'payroll_periods', 'payroll_items',
                     'payroll_lateness_details', 'sessions', 'personal_access_tokens');

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN ('password_resets', 'payroll_periods', 'payroll_items',
                     'payroll_lateness_details');

SELECT migration, batch
FROM migrations
WHERE migration LIKE '%password_resets%'
   OR migration LIKE '%payroll%';
```

Bandingkan kolom dan index dengan migration release, bukan hanya versi lokal. Untuk PostgreSQL/SQL Server/SQLite, gunakan katalog metadata read-only ekuivalen dan dokumentasikan driver/version. Jangan menjalankan `migrate`, `EXPLAIN ANALYZE`, atau query yang membaca nomor rekening/nominal pegawai saat langkah ini.

Tidak ada staging atau produksi yang diakses dalam audit ini. Test lokal tidak membuktikan kesiapan deployment.

### Verifikasi lokal untuk perubahan 25 Oktober 2026

- Hasil pengujian lokal dari run sebelumnya: test fokus `php artisan test --compact tests/Feature/Api/ForgotPasswordOtpTest.php tests/Feature/Api/AuthLogoutTest.php tests/Feature/Admin/AttendanceDetailTest.php tests/Feature/Admin/PayrollTest.php tests/Unit/PayrollGenerationServiceTest.php` — **33 passed, 276 assertions**.
- Hasil pengujian lokal dari run sebelumnya: suite penuh `php artisan test --compact` — **70 passed, 434 assertions**. Tidak dijalankan ulang pada penutupan tahap persiapan ini karena tidak ada perubahan kode/konfigurasi.
- `vendor/bin/pint --test` pada OTP dan attendance tests, syntax PHP untuk controller OTP/absensi/auth dan dua test terkait, serta `git diff --check` — **lulus**.
- Tidak ada migration, test staging/production, atau akses database deployment. Output suite sebelumnya menampilkan dua komentar migration yang sudah ada; tidak menggagalkan suite.

### Pemetaan cakupan test lokal

Penilaian berikut berdasarkan test yang tersedia saat ini dan bukti hasil run lokal; bukan bukti staging:

| Area | Status | Bukti test yang ditemukan | Batas cakupan |
|---|---|---|---|
| OTP resend, expiry, reset satu kali, kegagalan email | `COVERED` | `tests/Feature/Api/ForgotPasswordOtpTest.php`: verifier tersimpan, OTP baru/legacy, resend mengganti kode, batas expiry, reset/reuse, email failure/cooldown, limit verify/reset lintas IP, dan rollback ketika revoke token gagal. Test OTP terbaru **10 passed, 76 assertions**. | Pengiriman SMTP nyata tidak dilakukan; `Mail` dimock. |
| Logout session web dan bearer token | `COVERED` | `tests/Feature/Api/AuthLogoutTest.php` menguji logout SPA stateful lalu protected API unauthorized, dan bearer token saat ini dicabut tanpa mencabut token lain. | Tidak menguji session aktif setelah reset password. |
| Sesi setelah reset password | `PARTIAL` | Reset berhasil menghapus bearer tokens dibuktikan oleh OTP feature test. | Sesi cookie web/SPA setelah reset belum diuji; kebijakan invalidasinya belum diputuskan pemilik sistem. |
| OTP resend/verify/reset paralel dan race | `NOT COVERED` | Tidak ada test proses/worker paralel. | `phpunit.xml` menggunakan SQLite `:memory:`, cache/session `array`, dan mail `array`; process terpisah tidak berbagi DB/cache dan SQLite tidak membuktikan `lockForUpdate` staging. Uji dua worker harus menunggu staging. |
| Filter/edit absensi dan validasi waktu | `COVERED` | `tests/Feature/Admin/AttendanceDetailTest.php` menguji rentang custom, filter invalid, check-in/check-out terbalik/valid, dan ekspor recap. | Belum membuktikan konsistensi hasil perhitungan absensi terhadap payroll setelah edit; kebijakan/formula masih terbuka. |
| Otorisasi role fitur sensitif | `PARTIAL` | `tests/Feature/Admin/PayrollTest.php` menolak akses role `admin` ke payroll dan akses download slip pegawai lain; `tests/Feature/Admin/HrdApprovalTest.php` menguji larangan role non-HRD pada halaman approval. | Belum ada matriks allow/deny lengkap per role untuk setiap endpoint ekspor/approval/revisi; matriks role belum disahkan. |
| Payroll slip/revisi/snapshot/rollback | `PARTIAL` | `tests/Feature/Admin/PayrollTest.php` menguji slip awal dan satu revisi pegawai sama, relasi/alasan asal, rollback pembuatan item/detail dan snapshot payroll. | Test berjalan pada SQLite lokal; tidak memvalidasi constraint deployment, multi-revision berulang, ataupun lock/unique race DB aktual. |
| Test yang bergantung deployment | `NOT COVERED` | Tidak ada endpoint/config staging yang dapat diuji pada sesi ini. | Cache bersama, SMTP, engine/row lock, timezone DB/PHP, proxy/TLS, backup/restore tetap `BLOCKED (NOT RUN)` sesuai tabel validasi staging. |

Penambahan test paling bernilai lokal pada pass ini adalah rollback reset OTP jika penghapusan bearer token gagal: test memakai trigger SQLite sintetis untuk memaksa kegagalan setelah update password dan membuktikan password lama, token, dan OTP tetap ada. Ini membuktikan transaksi pada SQLite test saja, bukan perilaku engine staging.

## Final review dan status kesiapan

### Status temuan

| Status | Temuan/tindakan | Penutupan atau bukti |
|---|---|---|
| `FIXED` | OTP baru memakai keyed verifier, kedaluwarsa pada batas 10 menit, dihapus setelah reset sukses/expired, dan reset password/token/OTP berada dalam transaksi dengan row lock user. | Test OTP memverifikasi stored verifier berbeda dari kode email, berhasil reset satu kali, expiry, resend, dan penghapusan OTP. |
| `FIXED` | Kegagalan mail menghapus verifier baru dan mengaktifkan cooldown account 10 menit pada limiter terpisah; respons send tetap generik untuk email known/unknown/failure. | Test membuktikan resend lintas IP ditahan selama cooldown dan diizinkan setelahnya; verifier tidak tertinggal dan log hanya memuat exception type. Timing tetap berpotensi berbeda. |
| `FIXED` | Untuk akun yang ada, pengecekan dan pencatatan limiter send/verify/reset kini dilakukan saat row user terkunci; dua request akun yang sama diserialisasi oleh transaksi. | Test lokal menguji batas sequential lintas IP. Locking dan cache lintas instance tetap harus diuji pada engine/cache staging sebenarnya. |
| `FIXED` | Logout stateful menginvalidasi sesi; logout bearer mencabut token aktif saja. Filter/export absensi dan urutan timestamp edit check-in/out divalidasi. | Test logout dan `AttendanceDetailTest`; perubahan timestamp tetap memakai logika kalkulasi yang sudah ada. |
| `FIXED` | Payroll revisi memberi suffix nomor slip revision dan menjaga periode asal; failure saat pembuatan item/detail harus rollback. | Feature tests repository membuktikan data sintetis dan constraint SQLite lokal; constraint deployment belum terbukti. |
| `BLOCKED` | Reset password belum mengakhiri seluruh sesi browser/SPA yang telah aktif. | Pemilik proses/security harus menetapkan apakah semua sesi wajib dicabut; implementasi bergantung pada session store/strategi versioning. |
| `BLOCKED` | Perhitungan edit check-in berbeda dari `Absensi::calculateSalary`; edit check-out hanya menyimpan timestamp. Payroll memakai attendance snapshot dan kalkulasi payroll tersendiri. | HR/Finance harus menetapkan kebijakan formula dan koreksi historis, terutama payroll approved/paid. Jangan recalculation historis otomatis. |
| `BLOCKED` | Ekspor rekening payroll memakai profil User terkini; role middleware bernama `super_admin` secara aktual menerima role HRD. | HR/Finance menyetujui sumber rekening; pemilik proses mengesahkan matriks role dan tindakan ekspor. |
| `DEPLOYMENT CHECK` | Schema/index/constraint, engine, transaksi, row lock, cache bersama, session/Sanctum, SMTP, timezone, HTTPS/proxy, backup/restore. | Tidak ada bukti staging/produksi saat ini. Gunakan prosedur operasional di bawah sebelum menyatakan staging lulus. |
| `FOLLOW-UP` | Validasi kesetaraan timing anti-enumerasi, dampak lockout akun, dan opsi email asynchronous. | Tidak menghalangi test lokal; ukur di staging dan minta keputusan security/product jika UX/API berubah. |

### Checklist staging yang operasional

Gunakan release candidate pada staging terisolasi dengan akun pegawai sintetis atau data masked. Catat hasil PASS/FAIL, versi aplikasi, driver/engine, waktu pemeriksaan, dan penanggung jawab; jangan mencatat OTP, token, nomor rekening, kredensial, atau nilai gaji personal.

1. **Persiapan dan pemulihan**
   - DBA/operator memastikan backup database dan storage terbaru tersedia dan menjalankan restore drill ke lingkungan terisolasi.
   - Pastikan prosedur rollback aplikasi dan siapa yang berwenang menghentikan deploy telah disepakati. Jangan menjalankan migration di produksi sebagai bagian checklist.
   - **Lulus bila:** restore drill selesai, integritas backup tercatat, dan rollback dapat dilakukan tanpa menghapus data.
2. **Schema/migration/constraint (read-only sebelum perubahan)**
   - Catat engine/version, PDO driver, isolation level, timezone session/global, hasil status migration, kolom/index `password_resets`, `payroll_periods`, `payroll_items`, lateness detail, sessions, serta Sanctum tokens.
   - Gunakan query metadata read-only di bagian sebelumnya dan bandingkan dengan migration yang akan dirilis. Pastikan `slip_number` unique global, kolom menampung suffix revisi, dan index periode/revision sesuai.
   - **Lulus bila:** DBA menandatangani perbandingan schema aktual dan release; jangan menganggap SQLite lokal membuktikan MySQL deployment.
3. **Transaksi dan konkurensi**
   - Dengan dua worker dan akun sintetis, kirim resend OTP bersamaan dan percobaan verify/reset melewati ambang limiter secara bersamaan; pastikan limit akun tidak terlewati karena check/hit berlomba.
   - Pastikan setelah resend bersamaan hanya kode pada email terakhir yang dapat memverifikasi, tidak ada record duplikat aktif, dan reset password dua kali dengan kode sama menghasilkan paling banyak satu reset sukses.
   - Pastikan token bearer lama dicabut serta kegagalan transaksi tidak meninggalkan password/OTP parsial.
   - Paksa kegagalan pembuatan payroll item/detail pada database staging terisolasi; pastikan periode/item/detail yang baru dibuat seluruhnya rollback dan periode historis tetap sama.
   - **Lulus bila:** hasil tercapai pada engine/cache deployment yang sebenarnya. SQLite `:memory:` mengabaikan sebagian semantics `lockForUpdate` dan bukan bukti uji konkurensi staging maupun produksi.
4. **OTP, email, dan limit**
   - Uji email known/unknown, kode salah/expired/reused, resend, kegagalan SMTP dan cooldown 10 menit, batas send dan gagal verify/reset dari beberapa IP/worker; periksa response tanpa menyalin kode email ke log.
   - Pastikan rate limiter memakai cache bersama yang mendukung operasi deployment dan `APP_KEY` stabil selama OTP aktif.
   - **Lulus bila:** email valid hanya diterima satu kali, email failure tidak meninggalkan OTP aktif, response body/status known/unknown/failure konsisten, limiter efektif lintas instance, serta timeout/lock wait SMTP dapat diterima.
5. **Sanctum/session/HTTPS**
   - Verifikasi `SESSION_DRIVER`, backend, cookie secure/httpOnly/same-site/domain, session lifetime, `SANCTUM_STATEFUL_DOMAINS`, HTTPS termination, serta trusted proxy/forwarded-header allowlist.
   - Uji login/logout SPA-stateful dan bearer, CSRF, token lama setelah logout/reset, dan hasil sesi aktif setelah reset sesuai keputusan policy.
   - **Lulus bila:** hanya host/proxy resmi dipercaya dan semua hasil sesuai konfigurasi yang disetujui. Reset-session policy harus diputuskan sebelum release.
6. **Timezone dan payroll**
   - Catat `config('app.timezone')`, timezone PHP worker/CLI dan DB session. Buat absensi sintetis sebelum, tepat pada, dan setelah batas periode; bandingkan hasil query dengan tanggal lokal yang disepakati.
   - Generate payroll draft sintetis untuk pegawai sama pada payroll awal/revisi; verifikasi slip unik, relasi asal/alasan koreksi, lateness details, snapshot, rollback, dan tidak ada perubahan pada payroll asal.
   - **Lulus bila:** hasil sama dengan expected cases yang disetujui HR/Finance. Jangan mengubah formula atau approve/pay data uji untuk mengklaim hasil.
7. **Role, ekspor, dan data sensitif**
   - Jalankan matriks request langsung ke endpoint (bukan hanya menu UI) untuk tiap role yang disetujui: view, approve, cancel/revise, export attendance, payroll/slip, dan baca data milik sendiri/lain.
   - Cocokkan file export dengan filter/period/status yang ditampilkan; periksa akses nomor rekening, lokasi penyimpanan file, retention, dan log audit.
   - **Lulus bila:** semua allow/deny cocok dengan matriks role tertulis; HR/Finance menyetujui apakah rekening memakai profil terkini atau snapshot payroll.
8. **Keputusan Go/No-Go**
   - DBA mengisi bukti schema, lock, cache, timezone, backup/restore; security/ops mengisi HTTPS/session/secret/mail; HR/Finance menyetujui formula/revisi/rekening; pemilik proses menyetujui matriks role.
   - **Lulus staging bila:** tidak ada FAIL kritis yang belum ditangani dan semua persetujuan tercatat. Staging pass tidak otomatis menyatakan produksi siap; ulangi pemeriksaan read-only dan backup/rollback pada jendela produksi yang disetujui.

### Hasil diff yang ditinjau dan batas cakupan

- Perubahan tracked yang ditinjau mencakup validasi/ekspor/detail absensi, batas urutan timestamp edit, handling konflik periode payroll, suffix nomor slip revisi, rollback item/detail, logout Sanctum, OTP, fallback konstanta SSL PDO, penanganan error activity log, serta tombol/aksi payroll di Blade. Final review juga memindahkan pemeriksaan/pencatatan limiter untuk akun terdaftar ke dalam row lock yang sama untuk menutup check-then-hit race pada request paralel. Tidak ada route atau migration diubah.
- File `absensi_payroll_spec/` berstatus terhapus pada worktree dan `payroll-spec/` serta test terkait berstatus untracked. Perubahan ini dipertahankan apa adanya; tidak dipulihkan, dihapus, atau diasumsikan berasal dari pemeriksaan final ini. Tinjau ownership/kelengkapan dokumen sebelum commit/release.
- `ActivityLogController` menghilangkan `dd()` catch-all yang sebelumnya menampilkan detail exception; kegagalan kini mengikuti error handling Laravel normal. `config/database.php` memakai konstanta PDO SSL baru bila tersedia dan fallback untuk runtime lama; kompatibilitas PHP/PDO target perlu masuk matrix staging.
- Review final adalah validasi perubahan terkait dan kesiapan rilis; bukan audit ulang seluruh aplikasi dan bukan verifikasi deployment.

## Validasi staging terisolasi — 25 Oktober 2026

### Pemeriksaan ketersediaan akses

- **Hasil:** tidak ada konfigurasi/akses staging yang dapat diverifikasi pada sesi kerja ini. Tidak ditemukan variabel environment yang mengindikasikan koneksi staging; `.env.staging` tidak tersedia. File `.env` lokal memang ada, tetapi nilainya tidak dibaca dan tidak dipakai untuk menguji endpoint atau database.
- Tidak ada host staging, kredensial yang disetujui, akun sintetis, akses operator/DBA, SMTP staging, cache bersama, atau bukti backup/restore yang disediakan/dapat dikonfirmasi dalam sesi ini.
- Tidak ada koneksi database, endpoint aplikasi, SMTP, backup, atau storage yang dicoba. Database produksi tidak diakses. Tidak ada migration/perubahan schema yang dijalankan.
- Karena target staging belum dapat dibuktikan aman dan terisolasi, seluruh uji deployment di bawah berstatus **`BLOCKED (NOT RUN)`**, bukan PASS atau FAIL. Checklist generik di bagian sebelumnya adalah prosedur yang harus dijalankan setelah akses resmi tersedia, bukan bukti pelaksanaan.

### Hasil wajib per area

| Area uji | Prasyarat | Langkah dan expected result | Bukti aman / kriteria PASS | Status |
|---|---|---|---|---|
| Resend, verify/reset OTP paralel dua worker | URL/build staging terisolasi; akun/email sintetis; 2 worker; cache bersama; inbox uji. | Jalankan resend bersamaan; verifikasi hanya kode terakhir; coba verify/reset salah bersamaan melewati ambang dan reset OTP benar bersamaan. Expected: resend diserialisasi; reset satu kali; limiter tidak terlewati. | Simpan timestamp/response code tersanitasi, jumlah email, hasil akhir status OTP tanpa OTP mentah; PASS bila aturan tersebut benar pada kedua worker. | `BLOCKED (NOT RUN)` |
| DB aktual: schema, migration, unique slip, row lock, transaksi/rollback | DBA read-only; engine/driver staging; DB uji terisolasi untuk forced failure; release migration list. | Cocokkan metadata dengan migration; verifikasi index unique global; pada DB uji paksa gagal saat item/detail dan saat reset. Expected: transaksi baru rollback seluruhnya, histori tetap utuh. | Simpan engine/version, nama kolom/index, isolation/lock outcome dan row counts agregat sintetis; PASS setelah DBA menyetujui kecocokan schema dan rollback. | `BLOCKED (NOT RUN)` |
| Rate limiter bersama dan cooldown email | Cache backend staging multi-instance; 2 worker; akun sintetis. | Picu send/fail send, coba lintas worker/IP selama dan sesudah cooldown; picu verify/reset salah. Expected: batas konsisten, cooldown 10 menit, lalu request kembali diizinkan. | Simpan nama driver cache (tanpa endpoint/credential), counter outcome dan waktu tersanitasi; PASS bila batas konsisten di semua worker. | `BLOCKED (NOT RUN)` |
| SMTP sukses/gagal dan enumerasi email | SMTP sandbox/mail sink; dua inbox uji known/unknown; akun sintetis. | Bandingkan status/body known/unknown/failure; injeksikan mail failure; resend. Expected: response generik, tidak ada OTP aktif setelah gagal, dan email hanya ke akun known. | Simpan status/body yang sudah disanitasi, mail sink delivery count dan keberadaan verifier (boolean); jangan simpan OTP. PASS bila semua kondisi dipenuhi dan timeout/lock wait diterima. | `BLOCKED (NOT RUN)` |
| Login/logout/reset, Sanctum bearer dan web session | Akun sintetis; domain SPA staging; konfigurasi session/cookie dan Sanctum disetujui; keputusan session invalidation setelah reset. | Uji login, CSRF, logout tiap mode, reset password, akses ulang dengan token/sesi lama. Expected: bearer logout mencabut token; web logout mengakhiri session; reset sesuai policy. | Simpan hasil HTTP status per aksi, tanpa token/cookie; PASS bila sesuai policy dan deployment config. | `BLOCKED (NOT RUN)` |
| Matriks role, approval, akses ekspor dan rekening | Role test account; matriks role disahkan pemilik proses; file ekspor sintetis/masked. | Panggil endpoint langsung tiap role untuk view/approve/cancel/revise/export; coba baca data pegawai lain; bandingkan file dengan filter halaman. Expected: allow/deny sesuai matriks dan rekening terlindungi. | Simpan role alias, endpoint name, expected/actual allow-deny dan kolom export yang diuji (tanpa nilai); PASS bila tidak ada akses berlebih atau kebocoran. | `BLOCKED (NOT RUN)` |
| Filter/detail absensi, edit check-in/out, ekspor | Akun HRD sintetis; absensi sintetis di dalam/luar range; export staging. | Uji filter valid/invalid/incomplete, timestamp check-in setelah checkout dan checkout sebelum check-in, lalu bandingkan export/halaman. Expected: input invalid ditolak, timestamp tersimpan tetap kronologis, totals/filter cocok. | Simpan IDs sintetis, request case, HTTP/session result dan count agregat; PASS bila tidak ada perubahan untuk request invalid dan export konsisten. | `BLOCKED (NOT RUN)` |
| Payroll awal/revisi, snapshot, slip, lateness, rollback | DB uji staging; pegawai freelance sintetis; expected cases HR/Finance; tidak memakai payroll produksi. | Buat payroll awal multipegawai, revisi pegawai sama dan lebih dari satu kali, lalu paksa gagal item/detail. Expected: slip unik, relasi/alasan benar, snapshot asal tidak berubah, transaksi gagal rollback. | Simpan period/item synthetic IDs, slip sintetis, status/count, hash snapshot non-PII; PASS bila semua constraint dan histori memenuhi test plan. | `BLOCKED (NOT RUN)` |
| Timezone aplikasi/PHP/DB dan batas payroll | Akses config read-only; DB session metadata; accepted timezone dan expected boundary cases. | Catat timezone app/PHP/DB; uji absensi sintetis sebelum, tepat di, dan sesudah batas periode. Expected: bucket tanggal/periode mengikuti aturan HR. | Simpan nama timezone, timestamp sintetis, hasil periode; PASS bila sama dengan expected cases tertulis. | `BLOCKED (NOT RUN)` |
| HTTPS, proxy, forwarded headers | URL staging; diagram ingress/proxy; allowlist proxy dan akses eksternal terotorisasi. | Periksa redirect HTTPS; uji header forwarded melalui proxy resmi dan jalur tidak tepercaya. Expected: hanya proxy allowlisted mengubah IP/scheme yang dipercaya. | Simpan status TLS/redirect dan hasil IP/scheme tersanitasi; PASS bila HTTPS diwajibkan dan header spoof tidak diterima. | `BLOCKED (NOT RUN)` |
| Backup dan restore drill | Backup DB+storage; operator berwenang; target restore terisolasi; prosedur rollback. | Pulihkan backup ke lingkungan terisolasi, validasi integritas dan akses data sintetis; uji prosedur rollback. Expected: restore usable tanpa menyentuh produksi. | Simpan waktu backup/restore, checksum/hasil integritas, approver; jangan simpan backup/secret/PII pada laporan. PASS bila restore dan rollback disahkan operator. | `BLOCKED (NOT RUN)` |

### Akses dan bukti minimum sebelum mengulang validasi

1. Pemilik sistem menyediakan URL staging yang terverifikasi terisolasi dari produksi, build/release candidate, jendela uji, dan kontak operator yang berwenang. Jangan mengirim secret melalui chat atau memasukkannya ke dokumentasi.
2. DBA menjalankan pemeriksaan metadata read-only dengan akun least-privilege; kirimkan hasil yang sudah disanitasi (engine/version, migration status, nama kolom/index/constraint, isolation level, timezone), tanpa host, user, credential, PII, rekening, atau nilai gaji.
3. Ops menyediakan akun sintetis per role, cache/session/mail staging, inbox uji, dua worker, serta prosedur memicu kegagalan terkontrol pada DB uji. Pastikan tidak ada koneksi ke host/database produksi.
4. DBA/ops menyediakan bukti backup terakhir dan lingkungan restore terisolasi. Payroll QA/HR/Finance menyediakan expected cases yang disetujui untuk formula, periode, revisi, sumber rekening, dan session revocation; tanpa persetujuan, skenario tersebut tetap BLOCKED.
5. Simpan bukti hasil PASS/FAIL per baris, versi build/engine/driver, waktu dan penanggung jawab. Jangan menyimpan OTP/token/secret/PII dalam tiket atau laporan.

**Keputusan tahap ini:** staging **belum divalidasi** dan belum ada dasar untuk menyatakan kandidat rilis staging lulus. Lanjut ke validasi staging hanya setelah akses/isolasi dan data sintetis di atas tersedia. Produksi tetap di luar cakupan.

## Validasi lokal lanjutan

- Test rollback OTP sekarang membuktikan urutan kegagalan secara eksplisit: trigger SQLite membuat revoke token gagal setelah password baru terlihat di dalam transaksi; sesudah rollback hash password persis kembali ke nilai awal, bearer token tetap ada, dan verifier OTP belum dikonsumsi.
- Regression test payroll kini meliputi pegawai yang sama pada revisi 2 dan 3. Suffix slip berbeda dan unik di database test; revisi ketiga tetap merujuk periode asal serta memiliki alasan koreksi; nomor slip dan snapshot item revisi sebelumnya tidak berubah. Matriks tambahan menguji seluruh route payroll yang berada dalam route group terlindungi terhadap role `admin` (index/generate, submit, approve, paid, cancel, koreksi, export, preview/download slip) dan memastikan periode/item/koreksi tidak berubah. Ini hanya menegaskan predicate middleware aktual; tidak mewakili matriks seluruh role maupun endpoint aplikasi.
- Characterization test absensi membuktikan edit check-out admin memperbarui timestamp tanpa menghitung ulang gaji tersimpan, sementara `Absensi::calculateSalary()` menghasilkan nilai berbeda untuk waktu baru. Penelusuran kode juga memastikan edit check-in menghitung penalti secara manual (pembulatan 15 menit dan tarif berbasis `base_salary_per_day / 480`), sedangkan checkout admin tidak menghitung ulang gaji/lembur; API checkout memakai helper, dan generator payroll menggunakan `late_minutes` tersimpan untuk snapshot payroll. Test dan bukti ini tidak merekomendasikan formula; HR/Finance tetap perlu menetapkan perhitungan kanonis dan penanganan payroll approved/paid. Payroll yang sudah dibuat tetap mempertahankan snapshot; revisi baru dapat mengambil absensi yang sudah diedit sesuai filter yang ada.
- Verifikasi lokal terbaru: OTP **10 passed, 78 assertions**; test terarah **43 passed, 379 assertions**; suite penuh `php artisan test --compact` **73 passed, 478 assertions**. Pint pada test yang diubah, syntax PHP, dan `git diff --check` lulus.
- Perubahan hanya berupa regression/characterization test. Tidak ada kode produksi, schema, migration, atau database yang diubah. Database SQLite test tidak membuktikan unique index, row lock, isolation, atau concurrency engine staging.
- Semua validasi deployment pada checklist di atas tetap **`BLOCKED (NOT RUN)`**. Hasil lokal ini tidak mengubah status kesiapan staging/produksi.
