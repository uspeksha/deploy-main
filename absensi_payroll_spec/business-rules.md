# Aturan Bisnis Absensi

## 1. Clock-in dan clock-out
- Karyawan dapat melakukan clock-in dan clock-out.
- Absensi hanya diterima jika koordinat berada dalam radius geofence kantor yang dikonfigurasi.
- Jika berada di luar geofence, server menolak absensi dan memberi pesan alasan yang jelas.
- Radius geofence, koordinat kantor, toleransi GPS, serta penanganan GPS tidak akurat **PERLU KONFIRMASI**.
- Catat waktu server, koordinat yang diperlukan untuk validasi, hasil validasi, dan sumber perangkat sesuai kebijakan privasi perusahaan.
- Jangan mempercayai koordinat atau waktu dari browser tanpa validasi backend.

## 2. Keterlambatan
- Catatan meeting menyebut potongan Rp7.500 untuk keterlambatan 30 menit atau lebih (“30 menit ++”).
- **PERLU KONFIRMASI:** apakah Rp7.500 dikenakan sekali setiap kejadian, setiap hari, atau setiap kelipatan 30 menit; apakah keterlambatan di bawah 30 menit tidak dipotong; dan apakah ada jam masuk resmi serta masa toleransi.
- Tampilkan menit keterlambatan dan nilai potongan sebagai kolom terpisah.
- Simpan aturan potongan sebagai konfigurasi efektif berdasarkan tanggal, agar perubahan kebijakan tidak mengubah payroll lama.

## 3. Tidak hadir
- Hasil meeting menyebut ketidakhadiran tanpa izin dibayar Rp0 untuk hari yang tidak masuk.
- Bedakan status `absent_unexcused`, `sick_approved`, `leave_approved`, `holiday`, `rest_day`, dan status lain yang relevan.
- Jangan menganggap pengajuan yang masih menunggu approval sebagai izin yang sudah disetujui.
- **PERLU KONFIRMASI:** apakah sakit/cuti yang disetujui dibayar, mengurangi saldo cuti, atau memiliki perlakuan lain.

## 4. Pengajuan sakit
- Lampiran wajib.
- Pengajuan sakit yang dikirim pada pukul 08.00 tepat atau setelahnya ditolak oleh sistem, berdasarkan catatan “lampirin jam 8 itu ngga bisa harus di bawah jam 8”.
- Implementasi awal: pengajuan hanya dapat dikirim sebelum pukul 08.00 waktu lokal pada tanggal sakit. Batas waktu ini **PERLU KONFIRMASI** untuk sakit mendadak, rawat inap, atau kondisi darurat.
- Lampiran harus divalidasi jenis dan ukurannya, disimpan secara privat, dan hanya dapat diakses role yang berwenang.
- Status: draft, submitted, approved, rejected, cancelled (sesuai alur yang disepakati).

## 5. Pengajuan cuti
- Pengajuan cuti harus diajukan minimal H-7 dari tanggal mulai cuti.
- Pengajuan yang kurang dari 7 hari sebelum cuti tidak dapat diajukan/diterima sistem.
- Pengajuan wajib masuk ke HRD untuk diperiksa sebelum dianggap sah di sistem.
- HRD menyetujui atau menolak dengan alasan; sistem mencatat approver dan waktu keputusan.
- **PERLU KONFIRMASI:** apakah lampiran juga wajib untuk cuti, apakah H-7 berarti 7 hari kalender atau hari kerja, dan apakah tanggal pengajuan dihitung sebagai hari pertama.

## 6. Lampiran dan approval
- Pengajuan yang mensyaratkan lampiran tidak dapat dikirim tanpa lampiran.
- Lampiran mempunyai status pemeriksaan/approval.
- Approval hanya dapat dilakukan HRD atau admin yang mendapat izin khusus.
- Karyawan tidak dapat menyetujui pengajuan miliknya sendiri.
- Semua tindakan approval/rejection wajib tercatat dengan pengguna, waktu, dan catatan keputusan.

## 7. Pergantian hari kerja
- Catatan meeting menyebut Sabtu/Minggu pukul 08.00–13.00 dihitung 1 hari, sedangkan pukul 13.00–16.00 dicatat sebagai 2 hari dan “ditukar hari”.
- Makna aturan ini belum cukup jelas untuk langsung dijadikan logika produksi.
- Buat kebutuhan fitur **permintaan pergantian hari kerja/libur** dengan tanggal kerja pengganti, tanggal libur pengganti, alasan, lampiran opsional/ wajib sesuai keputusan, dan approval HRD.
- **PERLU KONFIRMASI:** apakah rentang 13.00–16.00 benar-benar menghasilkan 2 hari pengganti, atau merupakan cara mencatat kompensasi tertentu; apakah aturan berlaku Sabtu dan Minggu; serta bagaimana perlakuan jika jam melewati rentang tersebut.
- Jangan otomatis mengubah jadwal atau payroll sebelum permintaan disetujui.

## 8. Lembur
- Lembur dihitung jika durasinya minimal 30 menit.
- Lembur kurang dari 30 menit tidak dihitung.
- **PERLU KONFIRMASI:** apakah durasi dihitung dari clock-out aktual dikurangi jadwal, apakah wajib diajukan sebelum/sesudah lembur, siapa approver, tarif per jam, pembulatan, dan apakah jam istirahat dikurangi.
- Hanya lembur yang valid dan disetujui yang masuk ke payroll, sesuai keputusan final client.

## 9. Jadwal dan hari libur
- Jadwal kerja, hari istirahat, hari libur nasional/perusahaan, dan jadwal khusus harus dimodelkan terpisah.
- Jangan menyimpulkan seseorang absen jika tanggal tersebut bukan hari kerja terjadwal.
