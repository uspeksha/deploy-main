# PERATURAN PROJECT

## 1. Aturan Utama

- Periksa kode yang sudah ada sebelum melakukan perubahan.
- Pahami struktur project, alur program, dan dependensi sebelum mengedit.
- Pertahankan arsitektur, struktur folder, dan pola coding yang sudah digunakan.
- Gunakan pendekatan minimal diff: ubah sesedikit mungkin untuk menyelesaikan masalah.
- Jangan menulis ulang kode yang sudah berfungsi tanpa alasan teknis yang jelas.
- Jangan mengubah kode yang tidak berkaitan dengan tugas.
- Jangan mengarang fitur atau kebutuhan yang tidak diminta.

## 2. Batasan Perubahan Kode

- Jangan mengubah seluruh project hanya untuk memperbaiki satu bug.
- Jangan mengubah controller, model, route, middleware, frontend, atau konfigurasi tanpa kebutuhan yang jelas.
- Jangan mengganti library, framework, atau dependensi tanpa persetujuan.
- Jangan menghapus fungsi, validasi, autentikasi, otorisasi, atau fitur yang sudah ada.
- Pertahankan kompatibilitas dengan versi Laravel, PHP, dan dependensi yang digunakan.
- Ikuti standar penamaan dan gaya coding yang sudah diterapkan.
- Jangan membuat duplikasi fungsi, migration, route, atau komponen.

## 3. Aturan Debugging

Sebelum memperbaiki error:

1. Baca pesan error secara lengkap.
2. Temukan file dan baris kode yang menjadi sumber masalah.
3. Periksa kode terkait dan dependensinya.
4. Tentukan penyebab utama, bukan hanya gejalanya.
5. Tentukan perubahan terkecil yang dapat menyelesaikan masalah.
6. Terapkan perbaikan tanpa mengganggu fitur lain.
7. Jalankan pemeriksaan yang relevan.
8. Laporkan hasil pemeriksaan secara jujur.

Jangan menyatakan masalah selesai jika belum diverifikasi.

## 4. Aturan Database

- Database yang digunakan dapat berupa SQLite atau MySQL/MariaDB.
- Periksa konfigurasi koneksi database sebelum menjalankan operasi database.
- Jangan menganggap database yang digunakan selalu sama.
- Jangan menghapus database, tabel, atau data tanpa persetujuan eksplisit.
- Jangan mengubah data produksi saat melakukan debugging.
- Jangan menjalankan operasi destruktif hanya untuk menghilangkan error.
- Pertahankan integritas data, relasi, indeks, constraint, dan foreign key.
- Jangan mengubah struktur database tanpa memahami dampaknya terhadap kode aplikasi.

## 5. Aturan Laravel Migration

Sebelum mengubah atau menjalankan migration:

1. Periksa file migration yang bermasalah.
2. Periksa migration sebelumnya yang berkaitan.
3. Periksa status migration menggunakan `php artisan migrate:status`.
4. Periksa struktur tabel dan kolom yang relevan.
5. Pastikan penyebab error sudah diketahui.
6. Periksa apakah migration aman dijalankan pada database saat ini.
7. Periksa kompatibilitas operasi schema dengan driver database yang digunakan.

Ketentuan:

- Jangan menganggap status Pending selalu berarti migration rusak.
- Jangan menganggap migration Pending pasti aman dijalankan.
- Jangan menandai migration sebagai selesai tanpa memastikan perubahan database benar-benar sesuai.
- Jangan menghapus atau mengubah riwayat migration secara manual untuk menyembunyikan error.
- Jangan menambahkan kolom yang sudah ada tanpa memeriksa penyebab duplikasi.
- Jangan menghapus kolom atau tabel hanya untuk membuat migration berhasil.
- Pertahankan tujuan, tipe data, default value, nullable, indeks, dan relasi migration.
- Periksa kemungkinan migration gagal setelah sebagian perubahan berhasil diterapkan.
- Pastikan urutan migration dan dependensinya benar.
- Hindari perubahan migration lama yang sudah diterapkan pada database bersama tanpa memahami dampaknya.

### Perintah yang Dilarang Tanpa Persetujuan

Jangan menjalankan perintah berikut tanpa persetujuan eksplisit:

- `php artisan migrate:fresh`
- `php artisan migrate:refresh`
- `php artisan migrate:reset`
- `php artisan db:wipe`
- Perintah SQL `DROP DATABASE`
- Perintah SQL `DROP TABLE`
- Perintah yang menghapus atau mereset data secara massal

Jangan menggunakan perintah alternatif yang memiliki dampak destruktif serupa.

### Prosedur Memperbaiki Migration

Jika migration mengalami error:

1. Identifikasi migration yang gagal.
2. Periksa tabel dan kolom yang terlibat.
3. Periksa status migration serta riwayat perubahan database.
4. Tentukan apakah masalah berasal dari kode migration atau kondisi database.
5. Perbaiki hanya bagian yang diperlukan.
6. Jelaskan risiko jika perubahan berpotensi memengaruhi data.
7. Jalankan migration hanya setelah dipastikan aman.
8. Periksa kembali struktur tabel dan status migration.

Jika kondisi database tidak dapat dipastikan aman, hentikan proses dan laporkan masalahnya.

## 6. Aturan Keamanan

- Jangan menampilkan isi `.env` yang mengandung password, token, atau kredensial.
- Jangan menghapus atau menimpa `.env` tanpa persetujuan.
- Jangan menonaktifkan autentikasi atau otorisasi untuk melewati error.
- Jangan menonaktifkan validasi hanya agar aplikasi terlihat berjalan.
- Jangan mengekspos data sensitif dalam log atau respons aplikasi.
- Jangan mengubah permission file secara berlebihan.
- Jangan menjalankan perintah sistem yang tidak berkaitan dengan tugas.

## 7. Aturan Testing

Setelah melakukan perubahan:

- Jalankan pemeriksaan yang relevan dengan kode yang diubah.
- Gunakan test yang sudah tersedia jika memungkinkan.
- Periksa error syntax, dependensi, dan regresi yang berkaitan.
- Jangan mengubah test hanya agar hasilnya terlihat berhasil.
- Jangan mengabaikan kegagalan test yang berkaitan dengan perubahan.
- Jika test tidak dapat dijalankan, jelaskan alasannya.
- Jangan mengklaim semua fitur berfungsi jika hanya satu bagian yang diperiksa.

## 8. Aturan Penggunaan AI Coding Agent

- Kerjakan tugas sesuai instruksi pengguna.
- Jangan memperluas ruang lingkup pekerjaan tanpa alasan teknis.
- Jangan melakukan refactor besar ketika pengguna hanya meminta perbaikan bug.
- Jangan membuat file baru jika tidak diperlukan.
- Jangan menghapus file yang sudah ada tanpa persetujuan.
- Jangan menginstal dependensi tambahan tanpa alasan yang jelas.
- Jika terdapat beberapa solusi, pilih solusi paling sederhana dan aman.
- Jika keputusan berpotensi merusak data atau mengubah perilaku aplikasi, berhenti dan minta persetujuan sebelum melanjutkan.

## 9. Format Laporan Perubahan

Setelah menyelesaikan tugas, laporkan:

### Masalah
Jelaskan penyebab utama error.

### File yang Diubah
Sebutkan setiap file yang benar-benar diubah.

### Perubahan
Jelaskan perubahan secara ringkas.

### Pengujian
Sebutkan pemeriksaan atau test yang benar-benar dijalankan beserta hasilnya.

### Dampak
Jelaskan dampak terhadap database, fitur, dan kode lain jika ada.

### Status
Nyatakan apakah masalah berhasil diperbaiki, belum terverifikasi, atau masih membutuhkan tindakan lanjutan.

Jangan mengklaim keberhasilan yang belum dibuktikan.

## 10. Prinsip Final

Prioritas pengerjaan:

1. Keamanan data.
2. Ketepatan perbaikan.
3. Stabilitas aplikasi.
4. Perubahan kode seminimal mungkin.
5. Pengujian dan verifikasi.
6. Dokumentasi perubahan.

Prinsip utama:

PERBAIKI PENYEBAB MASALAH, BUKAN MENGACAK-ACAK SELURUH PROJECT.

Jangan mengorbankan data, keamanan, atau fitur yang sudah berjalan demi menghilangkan satu pesan error.