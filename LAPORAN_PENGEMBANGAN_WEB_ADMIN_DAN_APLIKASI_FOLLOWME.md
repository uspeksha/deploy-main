# Laporan Pengembangan Web Admin dan Aplikasi Followme

**Tanggal rangkuman:** 10 November 2026  
**Nama sistem:** Followme — sistem administrasi SDM, absensi, dan payroll freelance

## 1. Ringkasan

Followme dikembangkan sebagai sistem yang terdiri dari tiga bagian yang saling terhubung:

1. **Web admin dan backend Laravel** untuk pengelolaan karyawan, absensi, pengajuan, persetujuan, laporan, dan payroll.
2. **Aplikasi mobile Flutter** untuk karyawan melakukan absensi dan mengirim pengajuan.
3. **REST API** sebagai penghubung aplikasi mobile dengan backend, termasuk endpoint integrasi ringkasan KPI.

Secara keseluruhan, pekerjaan mencakup alur absensi karyawan dari perangkat mobile hingga pemeriksaan dan persetujuan melalui web admin, pengelolaan data karyawan dan HR, serta proses payroll freelance yang menyimpan snapshot per periode. Repository juga menyediakan otomasi validasi dan konfigurasi dasar untuk build/deployment.

## 2. Web Admin dan Backend

### 2.1 Dashboard dan pengelolaan absensi

- Dashboard admin dengan akses cepat ke rekap, hari libur, persetujuan HRD, payroll, antrean reimbursement/pinjaman, dan log aktivitas sesuai hak akses. HRD melihat jumlah absensi, akun, payroll, dan pengajuan HR yang masih menunggu.
- Halaman pemantauan absensi reguler dan freelance, detail absensi per karyawan, dan rekap bulanan.
- Koreksi waktu masuk dan pulang serta penyesuaian komponen gaji absensi oleh admin yang berwenang.
- Statistik karyawan, termasuk ringkasan jenis kelamin dan peringkat bulanan kategori kehadiran, sakit, terlambat, dan izin.
- Ekspor rekap, data per karyawan, dan data massal ke Excel; tersedia juga ekspor slip gaji PDF pada menu dengan hak akses terkait.

### 2.2 Persetujuan dan akun karyawan

- Halaman HRD untuk meninjau dan memproses pengajuan absensi, termasuk persetujuan massal.
- Alur status dan catatan persetujuan tersimpan pada backend untuk mendukung proses peninjauan.
- Pembuatan akun karyawan dilakukan oleh HRD; pendaftaran mandiri publik tidak digunakan.
- Akun karyawan dapat dikelola, diaktifkan/dinonaktifkan, dan akses awalnya dikirim melalui email kredensial sementara.
- Data HR karyawan mencakup profil pekerjaan, keluarga, riwayat kerja, dan dokumen yang dapat diunduh melalui rute admin.

### 2.3 Data referensi dan HR

- Pengelolaan data perusahaan, cabang beserta koordinat/radius geofence, departemen, jenjang jabatan, posisi, shift, jenis cuti, kategori reimbursement beserta limit bulanan, dan katalog komponen payroll melalui menu master data.
- Antrean HRD untuk menyetujui/menolak reimbursement dan pinjaman. Bukti reimbursement disimpan privat; pembayaran reimbursement dicatat setelah transfer dilakukan dengan referensi pembayaran.
- Kebijakan awal pinjaman: maksimum Rp5.000.000, tenor maksimum 12 bulan, tanpa bunga, cicilan bulanan dibagi merata, dan cicilan pertama jatuh tempo pada akhir bulan berikutnya. HRD mencatat cicilan lunas dengan referensi setelah pembayaran/potongan benar-benar dilakukan di luar aplikasi.
- Pengelolaan kalender hari libur, termasuk sinkronisasi hari libur.
- Pengelolaan profil karyawan dan data ketenagakerjaan dari web admin.

### 2.4 Payroll freelance

- Pembuatan payroll per periode dengan status **Draft → Menunggu Persetujuan → Disetujui → Dibayarkan**.
- Periode payroll membagi bulan menjadi tanggal 1–15 dan tanggal 16 sampai hari terakhir bulan tersebut.
- Perhitungan menggunakan catatan hadir yang disetujui, dengan tarif harian Rp120.000 dan potongan keterlambatan Rp7.500 untuk setiap akumulasi penuh 30 menit yang tercatat.
- Snapshot payroll menyimpan rincian periode dan karyawan; periode yang sudah dibayarkan dikunci.
- Pembatalan payroll yang belum dibayar, pencatatan koreksi, dan rekonsiliasi manual tersedia agar perubahan tidak menimpa slip lama.
- HRD dapat melihat detail, mengekspor data periode ke Excel, melihat/mencetak slip, dan mengunduh slip PDF.

### 2.5 Keamanan dan pemantauan

- Login dan pembatasan akses berbasis peran untuk area admin; sejumlah menu dibatasi khusus HRD atau super admin.
- API menggunakan token Sanctum untuk pengguna yang telah login, serta pembatasan laju pada endpoint penting.
- Alur OTP tersedia untuk lupa kata sandi; pengguna juga dapat memperbarui kata sandi dan profil melalui API.
- Data identitas dan perbankan tertentu disimpan dengan enkripsi Laravel dan tidak disertakan dalam respons serialisasi pengguna.
- Log aktivitas tersedia untuk pemantauan tindakan pengguna admin.
- Terdapat middleware untuk status akun, pembatasan akses, keamanan header, dan proteksi integrasi KPI menggunakan API key.

## 3. Aplikasi Mobile Followme

Aplikasi karyawan dibuat menggunakan Flutter dan menyediakan:

- Login karyawan dan pemulihan kata sandi dengan OTP.
- Beranda/dashboard, riwayat absensi, layanan/pengajuan HR, inbox notifikasi, dan profil.
- Pencatatan absensi masuk dan pulang dengan foto serta data lokasi.
- Pemeriksaan lokasi/geofence dan kualitas akurasi GPS sebelum proses absensi.
- Validasi lokasi memakai koordinat/radius cabang dari server (atau konfigurasi lama bila cabang belum dipilih); jika konfigurasi cabang tidak lengkap, absensi ditolak dengan jelas.
- Pemeriksaan keberadaan satu wajah pada foto di Android. Pemeriksaan ini adalah deteksi keberadaan wajah, bukan pengenalan identitas wajah.
- Pengajuan sakit, cuti atau izin sesuai jenis karyawan, lembur, dan laporan keterlambatan; pengajuan tertentu mendukung lampiran bukti dan pengiriman ulang.
- Riwayat dan notifikasi untuk membantu karyawan memantau status pengajuan.
- Pembaruan profil dan foto profil serta penggantian kata sandi.
- Lihat dan unduh slip gaji milik sendiri yang sudah dibayar.
- Pengajuan reimbursement dengan lampiran bukti, limit kategori, riwayat/status; pengajuan pinjaman dan pemantauan status serta jadwal cicilan.
- Pusat bantuan/FAQ dasar dan catatan penolakan dari HRD pada riwayat pengajuan terkait.
- Notifikasi push, pemeriksaan keamanan perangkat, dan mekanisme pemeriksaan/pemasangan pembaruan aplikasi Android.

Navigasi utama aplikasi terdiri dari **Beranda, Riwayat, Pengajuan, Inbox, dan Profil**. Jenis layanan disesuaikan dengan status karyawan, misalnya pilihan cuti untuk karyawan organik dan izin untuk jenis karyawan lainnya.

## 4. REST API dan Integrasi

- API berversi di bawah prefix `/api/v1` untuk autentikasi, profil, absensi, pengajuan, riwayat, dan notifikasi.
- Endpoint absensi mendukung clock-in, clock-out, lembur, sakit, izin, keterlambatan, serta pengiriman ulang pengajuan yang ditolak/dikembalikan sesuai alur backend.
- Endpoint notifikasi mendukung pengambilan daftar notifikasi dan penandaan notifikasi telah dibaca.
- API karyawan menyediakan slip gaji pribadi yang sudah dibayar, konfigurasi geofence efektif, serta pembuatan dan riwayat pengajuan HR. API memvalidasi limit kategori reimbursement dan kebijakan pinjaman di sisi server.
- Endpoint ringkasan absensi dan karyawan aktif tersedia untuk integrasi KPI dengan perlindungan API key.
- Proses login dan endpoint sensitif memiliki pembatasan permintaan untuk mengurangi penyalahgunaan.

## 5. Teknologi dan Infrastruktur

- **Backend:** PHP 8.2+, Laravel 12, REST API, Laravel Sanctum.
- **Web admin:** Blade, Tailwind CSS, Alpine.js, dan Vite.
- **Aplikasi mobile:** Flutter/Dart, dengan integrasi lokasi, kamera, notifikasi, penyimpanan aman, dan layanan pembaruan.
- **Database:** mendukung MySQL dan PostgreSQL; konfigurasi lokal juga menyediakan SQLite untuk pengembangan/pengujian.
- **Ekspor dokumen:** Excel dan PDF.
- **Deployment:** Docker multi-stage dengan Apache/PHP serta Docker Compose untuk lingkungan lokal.
- **CI:** GitHub Actions menjalankan instalasi dependensi, build aset web, tes Laravel, pemeriksaan format, analisis statis, cache Laravel, dan build image Docker pada branch/PR yang dikonfigurasi.

Dokumentasi repository juga memuat panduan konfigurasi lingkungan produksi, database, mail, backup, migrasi, health check, serta rilis APK Android.

## 6. Pengujian dan Validasi

Repository memiliki tes otomatis untuk area autentikasi, API, absensi/geofence, persetujuan HRD, pengelolaan karyawan, payroll, master data, dan beberapa proses HR lainnya. Pada sesi perubahan ini, suite Flutter lengkap lulus (**46 tes**), `flutter analyze` tidak menemukan masalah, dan hot restart aplikasi Flutter berhasil. Tes terarah Laravel untuk HR, payroll, geofence, master data, serta status reimbursement lulus **41 tes dengan 191 assertion**. Suite Laravel lengkap menghasilkan **218 tes lulus dan 1 tes gagal**: `Tests\Feature\Admin\PayrollTest`, karena assertion lama mengharapkan class layout `min-w-0 flex-1 lg:ml-72` yang tidak terdapat pada HTML saat ini. Kegagalan tersebut tidak terkait dengan alur HR/geofence yang ditambahkan dan tetap perlu ditindaklanjuti terpisah.

Catatan audit repository mencatat bahwa pada **3 November 2026** tes Laravel terakhir yang terdokumentasi lulus sebanyak **121 tes dengan 944 assertion**; build aset, pemeriksaan format, analisis PHPStan, cache produksi, dan smoke test container juga tercatat berhasil pada saat itu. Angka tersebut adalah hasil terdokumentasi sebelumnya, bukan hasil pengujian ulang untuk rangkuman ini atau jaminan status deployment saat ini.

## 7. Batasan dan Pekerjaan Lanjutan

- Konfirmasi pembayaran/potongan cicilan pinjaman dilakukan manual oleh HRD dengan referensi; fitur ini tidak menjalankan pemotongan gaji otomatis. Payroll yang tersedia saat ini menghitung payroll freelance, sehingga integrasi potongan pinjaman otomatis belum diaktifkan untuk menghindari saldo negatif atau potongan ganda.
- Repository memiliki migrasi dan model untuk sejumlah area HR/payroll tambahan. Keberadaan struktur database saja tidak berarti semua area tersebut sudah memiliki alur operasional dan UI admin yang lengkap.
- Sebelum rilis, konfigurasi produksi seperti kredensial, koneksi database, pengiriman email, backup, proxy, penyimpanan berkas, dan status migrasi tetap harus diverifikasi pada server tujuan.
- Perubahan kode yang belum masuk ke branch rilis perlu ditinjau dan diuji sebelum pekerjaan dinyatakan siap deploy. Saat laporan ini disusun, terdapat perubahan lokal yang belum di-commit dan folder aplikasi mobile `followme/` belum tercatat sebagai file terlacak Git; pastikan pengelolaan/versioning-nya dibereskan sesuai proses tim.

## 8. Kesimpulan

Pekerjaan yang sudah tersedia di repository membentuk solusi absensi dan administrasi karyawan yang menghubungkan aplikasi mobile, web admin, dan API. Cakupan utamanya meliputi absensi berbasis lokasi/foto, pengajuan dan persetujuan, pengelolaan data karyawan, laporan/ekspor, serta payroll freelance. Otomasi tes dan panduan Docker/CI juga telah disiapkan untuk mendukung pemeliharaan dan proses rilis. Beberapa layanan HR tambahan dan verifikasi lingkungan produksi masih perlu dituntaskan sebelum seluruh cakupan dapat dinyatakan aktif di produksi.
