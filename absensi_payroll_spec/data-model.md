# Rancangan Data Konseptual

Dokumen ini adalah rancangan konsep, bukan instruksi untuk langsung membuat semua tabel. Cocokkan dulu dengan schema yang sudah ada dan hindari duplikasi tabel.

## Entitas yang dibutuhkan
- `employees`: data karyawan, status kerja, divisi/jabatan, relasi akun, konfigurasi siklus payroll bila memang per individu.
- `users` / `roles` / `permissions`: akun dan hak akses, gunakan struktur yang sudah tersedia bila memungkinkan.
- `work_schedules`: jadwal kerja per karyawan/kelompok dan tanggal efektif.
- `office_locations` / `geofence_settings`: koordinat kantor, radius, dan tanggal efektif.
- `attendance_records`: tanggal, clock-in/out, koordinat/hasil geofence, status, menit terlambat, sumber koreksi.
- `attendance_adjustments`: koreksi dengan alasan, pembuat, approver, dan timestamp.
- `requests`: jenis pengajuan, rentang tanggal, alasan, status, pengaju, keputusan.
- `request_attachments`: metadata file privat, checksum opsional, uploader, waktu upload, status pemeriksaan.
- `request_approvals`: approver, keputusan, catatan, waktu.
- `shift_swaps` / `day_off_exchanges`: tanggal kerja dan tanggal pengganti, status approval.
- `overtime_records`: waktu mulai/selesai, durasi tervalidasi, status approval, aturan tarif.
- `payroll_periods`: tanggal mulai/akhir, jenis periode, status, konfigurasi periode.
- `payroll_runs`: satu hasil kalkulasi per versi/periode.
- `payroll_items`: komponen pendapatan/potongan per karyawan dan payroll run.
- `payroll_adjustments`: koreksi manual yang diaudit.
- `audit_logs`: aktor, aksi, entitas, ID, timestamp, alasan, metadata yang aman.

## Prinsip data
- Gunakan foreign key dan indeks sesuai kebutuhan.
- Simpan waktu secara konsisten; tentukan zona waktu aplikasi.
- Simpan snapshot aturan/komponen yang digunakan saat payroll dihitung agar hasil lama dapat direproduksi.
- Jangan menyimpan file lampiran sebagai file publik.
- Hindari menyimpan ulang data yang sudah tersedia dengan sumber kebenaran yang jelas.
- Nama tabel/kolom final harus mengikuti konvensi dan framework repository saat ini.
