# Rencana Pengujian Payroll

Agent harus menyesuaikan test dengan framework, database test, factories, dan pola repository yang benar-benar tersedia.

## A. Unit test `periodBounds`
- Tahun minimum 2020 dan maksimum 2100 diterima.
- Tahun di bawah/di atas batas ditolak.
- Bulan 1 dan 12 diterima; 0 dan 13 ditolak.
- Periode 1 menghasilkan tanggal 1–15.
- Periode 2 menghasilkan tanggal 16–akhir bulan.
- Februari tahun kabisat dan non-kabisat.
- Bulan 30 hari dan 31 hari.
- Nomor periode selain 1 atau 2 ditolak.
- Rentang tidak overlap dan tanggal akhir bersifat inklusif.

## B. Unit test `calculateTotals`
Dengan daily rate Rp120.000, blok 30 menit, nilai blok Rp7.500:
- 0 menit: 0 blok.
- 29 menit: 0 blok.
- 30 menit: 1 blok.
- 59 menit: 1 blok.
- 60 menit: 2 blok.
- 90 menit: 3 blok.
- beberapa hari terlambat: pastikan pembulatan per hari atau per periode sesuai keputusan bisnis.
- tidak ada keterlambatan.
- workDays = 0.
- input negatif atau format tidak valid, sesuai kontrak yang disepakati.
- potongan tidak menghasilkan nilai gaji bersih negatif tanpa aturan eksplisit; jika situasi ini mungkin, definisikan perilaku bersama HR.

## C. Integrasi pemilihan pegawai
- freelance + approved diproses.
- pegawai tidak approved dikecualikan.
- jenis employment selain freelance dikecualikan jika aturan ini benar.
- role HRD/admin/manager/supervisor dikecualikan sesuai definisi aktual.
- tidak ada pegawai eligible menghasilkan periode draft yang valid dengan nol item, atau perilaku lain sesuai keputusan yang dikonfirmasi.

## D. Integrasi absensi
- hadir + approved + record root + bukan mock + check-in valid diproses.
- status tidak hadir, approval pending/rejected, mock, child record, dan null check-in dikecualikan sesuai aturan.
- tanggal sebelum/di awal/di akhir/sesudah periode.
- beberapa absensi di tanggal sama menghasilkan satu hari kerja.
- urutan record deterministik.
- koreksi/duplikat absensi tidak menyebabkan hari atau potongan ganda.
- shift melewati tengah malam dan timezone diuji jika berlaku di aplikasi.

## E. Integritas dan transaksi
- kegagalan membuat item/detail tidak meninggalkan data parsial.
- detail keterlambatan mengacu ke item dan attendance yang tepat.
- jumlah total pada item konsisten dengan detailnya.
- generate periode sama dua kali ditangani sesuai aturan.
- simulasi concurrency/unique constraint bila lingkungan test mendukung.
- revisi menyimpan referensi dan alasan koreksi dengan benar.
- periode yang telah approved/paid tidak dapat ditimpa tanpa workflow koreksi yang sah.

## F. Authorization dan audit
- user berwenang dapat menjalankan generate.
- user tanpa permission ditolak server-side.
- aktivitas dicatat menggunakan mekanisme audit yang ada.
- pesan error tidak mengungkap informasi sensitif.

## G. Regresi
- route/controller yang ada tetap bekerja.
- view menerima field yang sama.
- tidak ada perubahan UI/CSS/layout.
- jalankan seluruh test suite yang relevan dan catat test yang tidak bisa dijalankan.

## Test data
Gunakan factory/fixture sintetis. Jangan menggunakan nama, nomor identitas, rekening, atau data gaji pegawai nyata.
