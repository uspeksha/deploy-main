# Aturan Payroll dan Kustomisasi

## Fokus
Payroll adalah area masalah utama. Pertahankan modul payroll yang ada sebagai titik awal, audit perhitungannya, lalu ubah hanya bagian yang tidak memenuhi kebutuhan client.

## 1. Siklus pembayaran
- Setiap karyawan dapat memiliki siklus pembayaran: mingguan atau bulanan.
- Siklus dan tanggal cut-off harus dapat diatur per karyawan atau kelompok karyawan.
- Catatan meeting: tanggal 1–14 disebut “Minggu 1”; tanggal 15–30 disebut “Minggu 2”.
- **PERLU KONFIRMASI KRITIS:** apakah maksudnya periode payroll dua kali sebulan (1–14 dan 15–akhir bulan), bukan mingguan kalender? Bagaimana perlakuan tanggal 31, Februari, dan periode yang melintasi bulan?
- Jangan hard-code label “mingguan” sebelum arti periode dipastikan.

## 2. Komponen perhitungan
Setiap slip/rekap payroll perlu memisahkan:
1. gaji pokok atau upah dasar;
2. upah harian/jam jika digunakan;
3. pendapatan tetap dan tidak tetap;
4. potongan ketidakhadiran;
5. potongan keterlambatan;
6. lembur yang disetujui;
7. potongan lain yang disahkan;
8. penyesuaian manual dengan alasan dan audit trail;
9. total pendapatan;
10. total potongan;
11. gaji bersih.

Rumus konseptual:
`gaji_bersih = total_pendapatan - total_potongan`

Rumus rinci upah harian, upah prorata, dan tarif lembur **PERLU KONFIRMASI** dan harus mengikuti kebijakan perusahaan serta ketentuan ketenagakerjaan yang berlaku.

## 3. Tidak hadir dan upah
- Hari tidak hadir tanpa izin yang sah: tidak ada upah untuk hari tersebut, berdasarkan hasil meeting.
- Perhitungan harus berdasarkan jadwal kerja dan basis upah karyawan, bukan sekadar jumlah tanggal dalam bulan.
- Hindari potongan ganda, misalnya hari yang sama sudah diperlakukan sebagai unpaid absence lalu dikurangi lagi melalui komponen potongan lain.
- Pengajuan sakit/cuti yang belum disetujui tidak otomatis dianggap hadir atau izin berbayar.

## 4. Potongan terlambat
- Nilai awal dari meeting: Rp7.500 ketika terlambat 30 menit atau lebih.
- Konfigurasi harus mencakup ambang menit, nilai potongan, metode penerapan, periode efektif, dan kemungkinan pengecualian.
- Tabel payroll harus menunjukkan jumlah kejadian, menit total, aturan yang dipakai, dan nilai potongan.
- **PERLU KONFIRMASI:** Rp7.500 per kejadian atau per kelipatan waktu; apa yang terjadi jika telat 60, 90, atau 120 menit.

## 5. Lembur
- Ambang minimal 30 menit.
- Durasi yang tidak mencapai ambang tidak menambah upah lembur.
- Tarif, pembulatan, hari kerja/libur, dan persetujuan lembur harus dikonfirmasi.
- Catatan lembur sumber harus bisa ditelusuri dari rekap payroll.

## 6. Review dan penguncian
- Sediakan status payroll: draft, reviewed, approved, paid, corrected/adjusted.
- Payroll yang telah disetujui/dibayar tidak boleh berubah tanpa revisi yang tercatat.
- Ekspor Excel harus mencerminkan versi payroll yang dipilih dan mencantumkan periode serta waktu ekspor.
- Penyesuaian manual wajib memiliki alasan dan identitas pelaku.

## 7. Rekonsiliasi
Untuk setiap periode, sistem harus dapat membandingkan:
- jumlah karyawan yang masuk perhitungan;
- total hari hadir/absen/izin;
- total potongan keterlambatan;
- total potongan absensi;
- total lembur;
- total pendapatan, potongan, dan gaji bersih.

Perhitungan payroll wajib dites menggunakan kasus contoh dan hasil yang disepakati HRD sebelum dipakai untuk pembayaran nyata.
