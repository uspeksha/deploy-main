# Rencana Kerja AI Agent

## Fase 0: Audit tanpa perubahan
- [ ] Petakan framework, versi, folder, route, model, service, migration, dan test.
- [ ] Identifikasi modul absensi dan payroll yang sudah ada.
- [ ] Jalankan test/build yang aman dan catat hasil awal.
- [ ] Periksa schema dan data relasi yang relevan.
- [ ] Buat `AUDIT_REPORT.md` berisi kondisi sekarang, gap, risiko, dan rekomendasi.
- [ ] Jangan mengubah kode atau database pada fase ini.

## Fase 1: Konfirmasi aturan
- [ ] Pastikan makna periode 1–14 dan 15–30.
- [ ] Pastikan metode potongan Rp7.500.
- [ ] Pastikan tarif dan cara hitung lembur.
- [ ] Pastikan perlakuan upah untuk sakit/cuti yang disetujui.
- [ ] Pastikan aturan H-7: hari kalender atau hari kerja.
- [ ] Pastikan definisi pergantian hari Sabtu/Minggu.
- [ ] Pastikan role Bu Serly dan Pak Ery.
- [ ] Pastikan radius GPS dan lokasi kantor.

## Fase 2: Desain
- [ ] Petakan aturan ke struktur database yang sudah ada.
- [ ] Usulkan perubahan schema minimum.
- [ ] Buat rencana migration dan rollback.
- [ ] Buat contoh kalkulasi payroll dengan data fiktif.
- [ ] Minta persetujuan sebelum migration berisiko.

## Fase 3: Implementasi
- [ ] Perbaiki geofence clock-in/out.
- [ ] Implementasikan pengajuan sakit dan lampiran.
- [ ] Implementasikan cuti H-7 dan approval HRD.
- [ ] Implementasikan workflow pergantian hari.
- [ ] Implementasikan aturan lembur minimal 30 menit.
- [ ] Sesuaikan payroll tanpa menghilangkan perilaku lama yang masih dibutuhkan.
- [ ] Buat tampilan rekap spreadsheet dan ekspor Excel.
- [ ] Terapkan authorization dan audit log.

## Fase 4: Verifikasi
- [ ] Unit test aturan bisnis.
- [ ] Feature test role dan approval.
- [ ] Test geofence.
- [ ] Test batas waktu 08.00 dan H-7.
- [ ] Test perhitungan payroll untuk kasus batas.
- [ ] Bandingkan UI dengan ekspor Excel.
- [ ] Jalankan test/build akhir.

## Fase 5: Laporan
- [ ] Daftar file yang berubah.
- [ ] Migration yang dibuat dan statusnya.
- [ ] Test yang dijalankan dan hasilnya.
- [ ] Keputusan yang masih menunggu client.
- [ ] Langkah deployment dan rollback.

## Tugas Tambahan: API dan GPS

- [ ] Inventarisasi endpoint API yang berkaitan dengan absensi.
- [ ] Dokumentasikan URL, HTTP method, request, response, dan autentikasinya.
- [ ] Pastikan perubahan tidak mematahkan kontrak API lama.
- [ ] Konfirmasi koordinat kantor, radius, dan batas akurasi GPS.
- [ ] Implementasikan validasi GPS backend untuk clock-in dan clock-out.
- [ ] Pastikan semua jalur API absensi menjalankan validasi lokasi.
- [ ] Uji lokasi di dalam radius dan di luar radius.
- [ ] Uji koordinat tidak valid, lokasi tidak tersedia, dan akurasi buruk.
- [ ] Jalankan tes regresi API dan fitur lama.
- [ ] Periksa diff sebelum menyatakan pekerjaan selesai.
- [ ] Minta persetujuan sebelum perubahan breaking terhadap API lama.
