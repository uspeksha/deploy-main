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
