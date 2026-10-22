# Kebutuhan UI dan Ekspor Excel

## Rekap absensi
- Filter tanggal/periode, karyawan, divisi, status absensi.
- Kolom: tanggal, nama/ID, jadwal, clock-in, clock-out, lokasi/geofence valid, keterlambatan, status, pengajuan terkait.
- Tampilkan alasan penolakan clock-in jika di luar geofence.
- HRD dapat membuka detail pengajuan dan lampiran sesuai hak akses.

## Pengajuan
- Form sakit: tanggal sakit, alasan seperlunya, lampiran wajib, validasi batas sebelum 08.00.
- Form cuti: tanggal mulai/akhir, alasan, lampiran jika diwajibkan kebijakan, validasi H-7.
- Form pergantian hari: tanggal kerja, tanggal pengganti, alasan, dan status approval.
- Tampilkan status yang jelas: draft, diajukan, menunggu HRD, disetujui, ditolak, dibatalkan.
- Jangan menampilkan tombol approve pada role karyawan.

## Payroll spreadsheet
- Tampilan tabel padat dan mudah dipindai seperti spreadsheet.
- Header kolom jelas, format rupiah, filter dan sorting, total di bagian bawah.
- Kolom minimal: ID, nama, periode, hari kerja, hadir, terlambat, menit telat, potongan telat, absen unpaid, potongan absen, lembur disetujui, nilai lembur, pendapatan, potongan lain, total potongan, gaji bersih, status payroll.
- Ekspor Excel `.xlsx` harus mengikuti filter/periode yang dipilih.
- Jika ada banyak karyawan, gunakan pagination atau pemuatan bertahap.
- Berikan indikator draft/review/approved/paid.
- Perubahan angka manual harus dibatasi permission, wajib alasan, dan masuk audit log.

## UX
- Pesan error menyebut alasan dan tindakan yang diperlukan.
- Jangan hanya mengandalkan warna untuk status.
- Pastikan tabel tetap bisa dipakai di layar laptop dan responsif di layar kecil.
