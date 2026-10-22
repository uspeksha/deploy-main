# Arahan Arsitektur

## Prinsip
Ikuti framework dan pola yang sudah digunakan repository. Jangan mengganti stack hanya demi preferensi agent.

## Batas logika yang disarankan
- Attendance service: validasi clock-in/out, jadwal, geofence, dan keterlambatan.
- Request/approval service: aturan pengajuan, validasi lampiran, workflow, dan audit.
- Overtime service: validasi durasi dan approval.
- Payroll calculation service: menghitung komponen dari data sumber yang tervalidasi.
- Payroll export service: menghasilkan Excel dari hasil kalkulasi backend yang sama.
- Policy/configuration layer: menyimpan aturan yang dapat berubah dan tanggal efektif.
- Authorization layer: memeriksa role/permission di setiap aksi sensitif.

## Konsistensi
- Perhitungan payroll dan tampilan harus memakai satu sumber hasil kalkulasi.
- Gunakan transaksi database untuk proses yang mengubah beberapa record sekaligus.
- Hindari menghitung ulang payroll secara diam-diam setelah status approved/paid.
- Perubahan kebijakan harus memiliki tanggal berlaku dan tidak mengubah periode lama secara retroaktif kecuali ada proses koreksi.
- Gunakan validasi backend untuk semua aturan bisnis.

## Keamanan
- Validasi tipe, ukuran, dan akses lampiran.
- Lindungi endpoint dari akses lintas karyawan.
- Jangan menulis kredensial, data sensitif, atau isi surat sakit ke log umum.
- Terapkan CSRF/session/auth sesuai stack yang ada.
- Audit perubahan data absensi dan payroll.
