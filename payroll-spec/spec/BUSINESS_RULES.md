# Aturan Bisnis Payroll

Status: **berdasarkan ringkasan pengguna; wajib diverifikasi terhadap implementasi aktual dan disetujui pemilik proses payroll.**

## Periode payroll
- Setiap bulan memiliki dua periode:
  - Periode 1: tanggal 1 sampai 15.
  - Periode 2: tanggal 16 sampai hari terakhir bulan tersebut.
- Tahun yang diterima: 2020–2100.
- Bulan yang diterima: 1–12.
- Nomor periode yang diterima: 1 atau 2.
- Rentang tanggal harus inklusif dan tidak boleh overlap.
- Periode Februari dan bulan dengan 30/31 hari harus mengikuti kalender aktual.

## Kelayakan pegawai
- Hanya pegawai berjenis `freelance` dan berstatus `approved` yang masuk proses, sesuai ringkasan saat ini.
- Role HRD/admin/manager/supervisor dan role non-pegawai lain dikecualikan.
- Implementasi harus menggunakan sumber data role/status yang benar-benar ada. Jangan mengandalkan nama role secara rapuh atau membuat daftar pengecualian baru tanpa memeriksa aturan aplikasi.
- Definisi `approved` dan relasi employment type harus diverifikasi.

## Absensi yang dapat dihitung
Ringkasan aturan saat ini menyebut:
- `status = hadir`;
- `status_approval = approved`;
- `parent_id = null`;
- `is_mocked = false`;
- `check_in_at` tidak null dan tanggalnya berada di periode payroll;
- satu hari kerja dihitung paling banyak satu kali per pegawai.

Urutan pemilihan absensi saat ini disebut `check_in_at`, lalu `id`, kemudian deduplikasi per tanggal. Verifikasi apakah pemilihan record pertama memang aturan yang disetujui, khususnya jika ada absensi koreksi, shift lintas tengah malam, atau lebih dari satu record valid di hari yang sama.

## Nilai gaji dan potongan
Konstanta yang dijelaskan:
- Tarif harian: Rp120.000.
- Satu blok potongan: 30 menit.
- Nilai satu blok: Rp7.500.
- Keterlambatan di bawah 30 menit tidak dipotong; setiap blok penuh 30 menit mengurangi Rp7.500 dari tarif harian Rp120.000.
- Setiap hari hadir freelance mendapat tarif dasar Rp120.000, tanpa prorata durasi check-in/check-out. Hari tanpa keterlambatan dibayar penuh Rp120.000.
- Jam keterlambatan dihitung dari pukul 08.00 dan mengecualikan waktu istirahat pukul 12.00–13.00. Untuk check-in pada/ setelah 13.00, satu jam istirahat penuh tidak masuk ke menit telat.
- `gross_pay = work_days × daily_rate`.
- `late_occurrences` = jumlah hari kerja yang terlambat (pastikan definisi aktual).
- `total_late_minutes` = jumlah menit terlambat yang dihitung dari check-in pukul 08.00 dan tidak termasuk waktu istirahat 12.00–13.00.
- `total_penalty_blocks` = jumlah blok hasil penjumlahan per hari sesuai implementasi aktual.
- `late_deduction = total_penalty_blocks × late_block_amount`.
- `net_pay = gross_pay - late_deduction - other_deductions` hanya jika definisi `other_deductions` memang berlaku; jika tidak, pertahankan formula aktual dan tandai sebagai keputusan terbuka.

### Pembulatan keterlambatan
Deskripsi saat ini menyatakan `intdiv(minutes, 30)`:
- 29 menit → 0 blok
- 30 menit → 1 blok
- 59 menit → 1 blok
- 60 menit → 2 blok
- 90 menit → 3 blok

**Penting:** pastikan apakah blok dihitung per hari (`sum(intdiv(menit_hari, 30))`) atau dari total menit periode (`intdiv(total_menit, 30)`). Keduanya berbeda dan tidak boleh dipertukarkan tanpa persetujuan bisnis. Berdasarkan ringkasan, asumsi awal adalah per hari kerja.

## Snapshot payroll
- Item payroll menyimpan snapshot identitas pegawai, hari kerja, tarif, nilai gaji, ringkasan keterlambatan, serta snapshot absensi.
- Snapshot harus stabil untuk review dan approval berikutnya.
- Alur status payroll adalah draft → menunggu persetujuan HRD → disetujui → dibayarkan; ekspor Excel hanya tersedia setelah status disetujui atau dibayarkan.
- Detail keterlambatan menyimpan referensi absensi, tanggal kerja, menit telat, blok potongan, nominal potongan, dan sumber keterlambatan jika field tersebut memang tersedia.
- Pembuatan item dan detail keterlambatan harus konsisten: jangan sampai item tersimpan sebagian tanpa detail atau sebaliknya.

## Revisi dan duplikasi
- Periode memiliki nomor revisi dan opsional referensi ke periode yang dikoreksi serta alasan koreksi.
- Pembuatan ulang untuk periode yang sama tidak boleh diam-diam menggandakan payroll.
- Aturan unik periode, revisi, koreksi, serta status mana yang boleh direvisi harus dikonfirmasi dari implementasi dan keputusan HR.
- Jangan mengubah payroll yang sudah disetujui/dibayar. Koreksi harus melalui mekanisme revisi yang dapat diaudit.

## Audit dan akses
- Hanya pengguna yang berwenang yang boleh membuat periode payroll.
- Aksi generate, revisi, approval, dan perubahan status harus memiliki jejak audit sesuai pola aplikasi.
- Pesan error tidak boleh membocorkan data pribadi atau rincian sensitif.
