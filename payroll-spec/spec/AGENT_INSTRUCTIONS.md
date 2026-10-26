# Instruksi Utama untuk AI Coding Agent

Kamu bekerja pada aplikasi payroll Laravel yang sudah ada dan digunakan/dipersiapkan untuk kebutuhan perusahaan. Prioritas tertinggi adalah **menjaga data, kompatibilitas, dan aturan bisnis yang telah disepakati**.

## Sasaran
Audit lalu tingkatkan keandalan backend payroll generation yang berpusat pada:
- `app/Services/PayrollGenerationService.php`
- `app/Http/Controllers/Admin/PayrollController.php`
- model, migration, policy, request validation, relasi, dan test yang benar-benar terkait.

## Larangan
1. **Jangan mengubah tampilan/UI**, CSS, layout, komponen visual, label, atau desain halaman.
2. Jangan melakukan refactor besar atau mengganti struktur aplikasi hanya demi preferensi pribadi.
3. Jangan mengubah route/API contract, nama field yang dipakai UI, atau alur HR tanpa alasan kuat dan kompatibilitas yang jelas.
4. Jangan mengubah data payroll historis, menghapus record, menjalankan seeder pada data nyata, atau melakukan operasi destruktif.
5. Jangan menambah/mengubah migration, enum, kolom, index, constraint, atau konfigurasi database sebelum memeriksa skema aktual dan menjelaskan kebutuhan serta risikonya.
6. Jangan mengasumsikan arti status, role, jenis pegawai, atau kolom tanggal. Verifikasi dari kode, database schema, dan test.
7. Jangan menonaktifkan validasi, authorization, audit log, atau tes demi membuat alur tampak berhasil.
8. Jangan menyimpan kredensial, data pegawai nyata, atau informasi sensitif di log/test fixture.

## Alur kerja wajib
### Tahap 1 — Audit read-only
- Baca dokumen dalam folder `spec/`.
- Periksa service, controller, model `PayrollPeriod`, `PayrollItem`, `User`, attendance model, relasi lateness, migration, policy/middleware, route, dan test terkait.
- Periksa versi Laravel/PHP dan pola coding yang sudah digunakan.
- Catat temuan dalam `AUDIT_REPORT.md` dengan kategori `Critical`, `High`, `Medium`, `Low`.
- Identifikasi aturan yang sesuai, bertentangan, atau belum dapat dipastikan.
- Jalankan hanya perintah pemeriksaan/test yang aman. Jangan mengubah file atau database pada tahap ini.
- Laporkan file yang akan diubah dan alasan setiap perubahan.

### Tahap 2 — Rencana
Sebelum implementasi, buat `CHANGE_PLAN.md` berisi:
- masalah dan bukti;
- perubahan minimal yang diusulkan;
- file yang terdampak;
- risiko dan mitigasi;
- test yang akan ditambahkan/dijalankan;
- kebutuhan migration (jika ada);
- cara rollback.
Jangan lanjut ke perubahan berisiko atau perubahan aturan bisnis yang belum disetujui.

### Tahap 3 — Implementasi minimal
Setelah rencana disetujui:
- Buat perubahan sekecil mungkin dan fokus pada backend.
- Pertahankan kontrak controller/view dan tampilan yang sudah ada.
- Gunakan transaksi database untuk operasi pembuatan periode dan snapshot jika sesuai dengan struktur aplikasi.
- Cegah dua proses bersamaan membuat periode atau item duplikat; gunakan constraint/locking hanya setelah kondisi skema diverifikasi.
- Pastikan aturan otorisasi, validasi, dan audit trail tetap berjalan.
- Jangan menambahkan dependency baru kecuali benar-benar perlu dan dijelaskan.
- Jangan mengubah payroll lama secara diam-diam.
- Perubahan perilaku perhitungan harus didukung keputusan bisnis dan test eksplisit.

### Tahap 4 — Verifikasi
- Jalankan test relevan dan seluruh suite yang layak dijalankan.
- Tambahkan test unit/integrasi untuk aturan payroll dan edge case.
- Periksa `git diff` untuk memastikan tidak ada perubahan UI yang tidak diminta, file tak terkait, kredensial, atau data nyata.
- Laporkan hasil test, test yang gagal/tidak dapat dijalankan, risiko tersisa, dan instruksi deployment/rollback.
- Jangan mengklaim “siap produksi” hanya karena kode berhasil dijalankan. Kesiapan produksi perlu review manusia, pengujian staging, backup, dan persetujuan pemilik payroll.

## Aturan bisnis dasar
Lihat `BUSINESS_RULES.md`. Jika implementasi saat ini berbeda, **jangan langsung menimpa**. Dokumentasikan perbedaannya dan minta keputusan.

## Format laporan akhir
1. Ringkasan audit.
2. Daftar perubahan per file.
3. Perilaku yang sengaja dipertahankan.
4. Test yang dijalankan dan hasilnya.
5. Risiko/pertanyaan terbuka.
6. Langkah staging, backup, deployment, dan rollback.
