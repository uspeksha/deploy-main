# Spesifikasi Sistem Absensi dan Payroll

## 1. Latar belakang
Repository sudah memiliki aplikasi absensi dan payroll. Kebutuhan utama client adalah membuat perilaku absensi menyerupai sistem HRIS seperti Talenta, sementara modul payroll yang ada tetap menjadi dasar dan dikustomisasi untuk memenuhi aturan penggajian client.

Referensi Talenta digunakan untuk membandingkan alur dan pengalaman fitur. Jangan menyalin kode, desain eksklusif, atau aset proprietari.

## 2. Tujuan
- Memastikan pencatatan clock-in dan clock-out akurat serta dibatasi GPS kantor.
- Menangani pengajuan sakit, cuti, lampiran, approval HRD, dan pergantian hari.
- Menghitung keterlambatan, ketidakhadiran, lembur, dan periode payroll dengan aturan yang dapat diaudit.
- Menyediakan tampilan rekap seperti spreadsheet/Excel agar HRD dapat memeriksa komponen gaji dan potongan per karyawan.
- Mempertahankan logika payroll yang sudah ada sejauh masih sesuai, lalu mengubah bagian yang bermasalah secara terukur.

## 3. Role
- **HRD**: mengelola data karyawan, memeriksa pengajuan dan lampiran, approval, konfigurasi kebijakan yang diizinkan, serta rekap absensi/payroll.
- **Admin tertentu**: akses sesuai izin yang diberikan; dapat memeriksa lampiran dan melakukan approval jika diberi permission.
- **Karyawan**: clock-in/out, melihat data absensi sendiri, mengajukan sakit/cuti, mengunggah lampiran, melihat status pengajuan, dan mengajukan permintaan sesuai kebijakan.
- **Sistem**: menghitung status absensi, keterlambatan, lembur, dan komponen payroll berdasarkan aturan aktif.

Admin yang disebut client: Bu Serly dan Pak Ery. **PERLU KONFIRMASI:** apakah keduanya akun admin terpisah, apakah salah satunya HRD, dan apakah keduanya sama-sama boleh approve semua jenis pengajuan.

## 4. Modul utama
1. Data karyawan dan status kerja.
2. Clock-in / clock-out dengan geofence GPS.
3. Rekap absensi harian dan bulanan.
4. Pengajuan sakit dengan lampiran wajib dan batas waktu.
5. Pengajuan cuti dengan lampiran/persetujuan HRD dan pengajuan minimal H-7.
6. Pengajuan pergantian hari kerja/libur.
7. Pencatatan dan persetujuan lembur.
8. Perhitungan payroll dan potongan.
9. Tampilan rekap berbentuk tabel spreadsheet serta ekspor Excel.
10. Audit log dan pengaturan kebijakan.

## 5. Prinsip payroll
- Perhitungan harus transparan per karyawan dan per periode.
- Komponen pendapatan, potongan, ketidakhadiran, keterlambatan, lembur, dan penyesuaian ditampilkan terpisah.
- Karyawan tidak masuk tanpa upah untuk hari tersebut sesuai hasil meeting, tetapi definisi hari kerja, status izin, dan basis nilai harian harus dipastikan.
- Periode pembayaran dapat berbeda antar karyawan: mingguan atau bulanan.
- Periode 1–14 disebut “Minggu 1” dan 15–30 disebut “Minggu 2” dalam catatan meeting. Penamaan ini tidak konsisten dengan periode mingguan kalender dan perlu dikonfirmasi sebelum implementasi.
- Jangan mengasumsikan semua karyawan menggunakan periode payroll yang sama.

## 6. Tampilan spreadsheet
Tabel payroll setidaknya memuat:
- ID / nama karyawan;
- jabatan/divisi;
- tanggal atau periode;
- jumlah hari kerja terjadwal;
- hadir, terlambat, izin/sakit/cuti, dan tidak hadir;
- total menit terlambat;
- nilai potongan keterlambatan;
- total jam lembur yang disetujui;
- nilai lembur;
- pendapatan dasar;
- potongan ketidakhadiran;
- potongan/penyesuaian lain;
- total pendapatan;
- total potongan;
- gaji bersih;
- status pemeriksaan/approval payroll.

Nilai perhitungan pada tabel harus sama dengan hasil ekspor Excel dan hasil backend.

## 7. Non-fungsional
- Otorisasi dicek di backend.
- Perubahan penting tercatat dalam audit log.
- Perhitungan payroll dapat ditelusuri sampai ke catatan absensi/pengajuan sumbernya.
- Data payroll yang sudah disetujui tidak berubah diam-diam; koreksi harus melalui proses revisi yang tercatat.
- Zona waktu dan format tanggal harus konsisten dengan lokasi operasional perusahaan.

## Persyaratan Tambahan: Perlindungan API dan GPS

### A. API lama
- API yang sudah digunakan tidak boleh dihapus atau mengalami breaking changes tanpa persetujuan eksplisit client.
- Pertahankan URL, HTTP method, struktur request/response, field, status code, autentikasi, dan otorisasi.
- Endpoint baru boleh ditambahkan jika memang diperlukan, tetapi tidak boleh merusak kontrak API lama.
- Perubahan API harus disertai pemeriksaan kompatibilitas dan pengujian regresi.
- Jika perubahan kontrak API lama tidak dapat dihindari, tandai PERLU PERSETUJUAN dan hentikan perubahan tersebut sampai disetujui.

### B. GPS kantor
- Clock-in dan clock-out hanya berhasil jika koordinat perangkat berada di dalam radius kantor resmi.
- Validasi wajib dilakukan di backend pada setiap jalur yang dapat mencatat atau mengubah absensi.
- Validasi frontend tidak boleh dianggap sebagai pengamanan utama.
- Lokasi kosong, koordinat tidak valid, akurasi yang tidak memenuhi batas, atau lokasi di luar radius harus ditolak.
- Koordinat kantor, radius, dan batas akurasi harus menggunakan konfigurasi resmi. Jangan mengarang nilainya.
- Jika konfigurasi kantor belum tersedia, tandai PERLU KONFIRMASI sebelum aktivasi produksi.
- Penambahan validasi GPS tidak boleh mengubah kontrak API lama secara sepihak.
- Catat alasan penolakan seperlunya dan batasi akses terhadap data lokasi.
