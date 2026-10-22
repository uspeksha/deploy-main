# Instruksi untuk AI Coding Agent

## Tujuan
Kembangkan sistem absensi dan payroll berdasarkan kebutuhan client, dengan memanfaatkan kode yang sudah ada di repository.

## Wajib dilakukan sebelum coding
1. Audit struktur repository, framework, route, controller/service, model, migration, autentikasi, middleware, konfigurasi GPS, absensi, pengajuan, dan payroll.
2. Jalankan test/build yang tersedia dan catat kondisi awal.
3. Baca seluruh dokumen spesifikasi di folder ini.
4. Buat ringkasan:
   - fitur yang sudah tersedia;
   - fitur yang belum tersedia;
   - bug yang ditemukan;
   - tabel/database yang terdampak;
   - risiko dan rencana migrasi.
5. Periksa implementasi payroll saat ini dan jelaskan apa yang perlu dipertahankan, diperbaiki, atau ditambah.

## Aturan implementasi
- Jangan membuat ulang aplikasi dari nol.
- Jangan menghapus atau mengganti modul payroll yang sudah ada sebelum membandingkan perilakunya dengan `payroll-rules.md`.
- Utamakan perubahan kecil, bertahap, dan kompatibel dengan data lama.
- Gunakan service/domain logic untuk aturan absensi, approval, lembur, dan payroll; jangan menumpuk seluruh aturan di controller atau tampilan.
- Semua nilai potongan, batas waktu, periode payroll, dan kebijakan harus disimpan sebagai konfigurasi bila memang berpotensi berubah.
- Validasi harus ada di server, bukan hanya di frontend.
- Simpan audit trail untuk perubahan data penting, approval, koreksi absensi, payroll, dan pergantian jadwal/hari.
- Jangan pernah menghitung payroll dari angka yang hanya ditampilkan di browser. Perhitungan final harus berasal dari backend.
- Jangan menampilkan data gaji atau data pribadi ke role yang tidak berwenang.
- Jangan membuat akun karyawan oleh karyawan itu sendiri. Penambahan karyawan hanya dapat dilakukan HRD.
- Approval lampiran hanya dapat dilakukan oleh HRD atau admin yang ditentukan.
- Jangan mengarang kebijakan yang belum dipastikan. Tandai **PERLU KONFIRMASI**, lalu lanjutkan bagian lain yang tidak bergantung pada keputusan itu.
- Jangan mengubah `.env`, kredensial, konfigurasi server, atau data produksi tanpa izin eksplisit.
- Sebelum migration dijalankan, tampilkan nama migration, dampak, backup/rollback plan, dan minta persetujuan bila berisiko.
- Jangan mengklaim pekerjaan selesai jika test gagal atau belum dijalankan.

## Alur kerja
Fase 1: audit dan laporan.
Fase 2: sepakati aturan yang masih ambigu.
Fase 3: desain perubahan dan migration.
Fase 4: implementasi bertahap.
Fase 5: test unit, feature, otorisasi, payroll, dan GPS.
Fase 6: ringkasan file yang berubah, test yang dijalankan, hasil, risiko, serta langkah deploy.

## Format laporan setiap fase
- Status
- Temuan
- File yang akan/ telah diubah
- Keputusan yang dibutuhkan dari client
- Test dan hasil
- Risiko / rollback

## Perlindungan API Lama — WAJIB

1. DILARANG menghapus, mengganti nama, memindahkan, atau mengubah endpoint API yang sudah ada tanpa persetujuan eksplisit dari client.
2. Pertahankan URL, HTTP method, format request dan response, nama serta tipe field, struktur JSON, status code, autentikasi, otorisasi, dan perilaku API yang sudah berjalan.
3. Jangan menghapus atau mengganti field yang digunakan frontend atau integrasi eksternal.
4. Jangan melakukan refactor API massal atau perubahan breaking demi merapikan kode.
5. Utamakan penggunaan API yang sudah ada. Jika fitur baru benar-benar membutuhkan endpoint baru, tambahkan secara kompatibel tanpa merusak kontrak lama.
6. Jika validasi GPS perlu ditambahkan pada API absensi, pertahankan kontrak API yang sudah ada sebisa mungkin dan ikuti format error yang digunakan aplikasi.
7. Sebelum perubahan, inventarisasi endpoint yang terdampak dan cara penggunaannya.
8. Setelah perubahan, lakukan pengujian regresi API.
9. Jika perubahan breaking terhadap API lama benar-benar diperlukan, HENTIKAN bagian tersebut dan minta persetujuan eksplisit client.
10. Jangan menyatakan API tetap kompatibel sebelum melakukan pemeriksaan dan pengujian yang relevan.

## GPS dan Geofencing — WAJIB

1. Clock-in dan clock-out wajib divalidasi di backend.
2. Absensi harus ditolak jika lokasi perangkat berada di luar radius kantor resmi.
3. Jangan hanya mengandalkan JavaScript, tombol frontend, atau validasi browser.
4. Semua endpoint yang dapat membuat atau mengubah catatan absensi harus menjalankan validasi lokasi.
5. Validasi latitude, longitude, rentang koordinat, dan akurasi GPS sesuai konfigurasi.
6. Hitung jarak perangkat ke kantor menggunakan metode geodesik yang sesuai.
7. Koordinat kantor, radius dalam meter, dan batas akurasi harus berasal dari konfigurasi resmi yang disetujui.
8. Jangan mengarang koordinat kantor. Jika konfigurasi belum tersedia, tandai PERLU KONFIRMASI.
9. Jika lokasi tidak valid, tidak tersedia, atau berada di luar radius, backend tidak boleh membuat atau mengubah catatan absensi.
10. Pertahankan kontrak API lama ketika menambahkan validasi GPS. Jangan mengubah format response tanpa persetujuan.
11. Berikan pesan penolakan yang jelas melalui mekanisme error yang kompatibel dengan API yang ada.
12. Jangan mengklaim GPS browser mustahil dipalsukan. Terapkan validasi server dan kontrol keamanan yang sesuai.

## Pengujian Regresi Wajib

- Uji endpoint API yang terdampak sebelum dan sesudah perubahan.
- Pastikan URL, HTTP method, autentikasi, request, dan response lama tetap kompatibel.
- Uji absensi di dalam radius, di luar radius, koordinat tidak valid, lokasi tidak tersedia, dan akurasi buruk.
- Uji bahwa pemanggilan endpoint secara langsung tetap tidak dapat melewati validasi GPS.
- Pastikan payroll dan fitur lama tetap berjalan.
