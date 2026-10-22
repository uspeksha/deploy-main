# Acceptance Criteria dan Skenario Uji

## Geofence
- Clock-in di dalam radius yang dikonfigurasi diterima jika syarat lain valid.
- Clock-in di luar radius ditolak oleh backend.
- Memalsukan koordinat di frontend saja tidak boleh melewati validasi server.
- Lokasi kantor/radius yang tidak dikonfigurasi menghasilkan error yang aman, bukan otomatis mengizinkan absensi.

## Sakit
- Pengajuan tanpa lampiran ditolak.
- Pengajuan sebelum 08.00 mengikuti aturan waktu yang dikonfirmasi.
- Pengajuan pada atau setelah 08.00 ditolak sesuai hasil meeting, kecuali pengecualian yang nanti disetujui client.
- Hanya HRD/admin berizin dapat approve/reject.
- Karyawan tidak dapat menyetujui pengajuannya sendiri.

## Cuti
- Pengajuan minimal H-7 diterima untuk pemeriksaan.
- Pengajuan kurang dari H-7 ditolak sistem.
- Pengajuan yang menunggu HRD tidak dianggap disetujui.
- Kalender hari kerja vs kalender harus mengikuti keputusan client.

## Keterlambatan
- Keterlambatan di bawah 30 menit mengikuti aturan final yang disepakati.
- Keterlambatan 30 menit atau lebih menghasilkan potongan sesuai konfigurasi Rp7.500 dan metode final yang disepakati.
- Uji khusus 29, 30, 59, 60, 89, dan 90 menit.
- Rekap UI, backend, dan Excel menunjukkan angka yang sama.

## Lembur
- Durasi di bawah 30 menit tidak menghasilkan nilai lembur.
- Durasi tepat 30 menit memenuhi ambang, jika aturan final tetap demikian.
- Lembur yang belum disetujui tidak masuk payroll jika approval diwajibkan.
- Tarif dan pembulatan diuji setelah diputuskan client.

## Ketidakhadiran
- Tidak hadir pada hari kerja tanpa izin sah dihitung sebagai unpaid absence sesuai kebijakan.
- Hari libur atau hari istirahat tidak otomatis dihitung absent.
- Tidak terjadi potongan ganda untuk tanggal yang sama.
- Pengajuan pending/rejected tidak diperlakukan sebagai approved leave.

## Payroll
- Siklus payroll mingguan dan bulanan diuji terpisah.
- Periode 1–14 dan 15–akhir bulan diuji setelah definisi final disepakati.
- Tanggal 28/29 Februari dan tanggal 31 ditangani dengan benar.
- Payroll draft dapat dihitung ulang; payroll approved/paid memerlukan proses koreksi tercatat.
- Total komponen sama dengan gaji bersih.
- Ekspor Excel cocok dengan hasil perhitungan backend.
- Hak akses payroll diuji untuk karyawan, HRD, dan kedua admin.

## Kualitas
- Test yang sudah tersedia tetap lulus atau kegagalannya dijelaskan.
- Tidak ada migration destruktif tanpa backup dan persetujuan.
- Tidak ada kredensial atau lampiran sensitif di log.
