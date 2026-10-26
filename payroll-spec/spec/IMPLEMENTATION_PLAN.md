# Rencana Implementasi Aman

## Tahap 0 — Persiapan
- Pastikan branch kerja terpisah dan working tree dipahami.
- Catat commit awal.
- Pastikan tidak bekerja langsung pada production.
- Jangan menjalankan command yang mengubah data nyata.

## Tahap 1 — Audit tanpa perubahan
1. Baca service dan controller yang disebutkan.
2. Telusuri model, relasi, migration, routes, policies/middleware, tests, dan schema.
3. Cocokkan implementasi dengan `BUSINESS_RULES.md`.
4. Catat temuan dan bukti dalam `AUDIT_REPORT.md`.
5. Tandai setiap ketidakpastian sebagai pertanyaan, bukan asumsi.

## Tahap 2 — Rencana perubahan
Buat `CHANGE_PLAN.md`. Untuk setiap perubahan, tulis:
- masalah yang dibuktikan;
- dampak ke data dan workflow;
- file terkait;
- pendekatan minimal;
- test;
- rollback;
- apakah butuh persetujuan HR.

## Tahap 3 — Test terlebih dahulu
- Tambahkan atau perbaiki test yang menangkap perilaku sekarang dan aturan yang sudah dikonfirmasi.
- Jangan membuat test sekadar agar implementasi baru lulus jika aturan bisnisnya belum benar.
- Pisahkan bug teknis dari perubahan aturan bisnis.

## Tahap 4 — Perbaikan minimal
Urutan prioritas:
1. bug integritas/perhitungan yang terkonfirmasi;
2. atomicity/transaksi dan penanganan kegagalan;
3. duplikasi dan race condition;
4. validasi dan authorization;
5. auditability;
6. maintainability/performance jika bukti mendukung.

Tidak semua poin harus mengubah kode. Jika sudah benar, dokumentasikan bukti dan pertahankan.

## Tahap 5 — Verifikasi regresi
- Jalankan test terkait.
- Jalankan suite penuh jika layak.
- Periksa diff file satu per satu.
- Pastikan UI, CSS, layout, route, dan kontrak view tidak berubah.
- Periksa migration dan data compatibility.
- Catat hasil serta batasan test.

## Tahap 6 — Staging dan deployment
- Backup database sesuai prosedur perusahaan.
- Deploy ke staging dengan data sintetis/masked.
- Verifikasi generate, review, approval, slip, laporan, dan audit.
- Dapatkan persetujuan HR/finance sebelum production.
- Siapkan rollback dan pemantauan.
- Jangan pernah menguji generate payroll pada production dengan pegawai atau periode nyata tanpa prosedur yang disetujui.
