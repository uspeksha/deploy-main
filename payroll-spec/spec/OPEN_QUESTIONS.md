# Pertanyaan Bisnis yang Wajib Dipastikan

AI agent harus mencari bukti di repository terlebih dahulu. Jika tidak bisa dipastikan, laporkan untuk keputusan pemilik payroll.

1. **Potongan dihitung per hari atau per periode?** Apakah setiap hari memakai `intdiv(late_minutes, 30)`, atau total menit seluruh periode dibagi 30?
2. **Apa definisi `late_occurrences`?** Hari dengan menit telat > 0, atau jumlah blok potongan?
3. **Bagaimana jika potongan lebih besar dari gaji kotor?** Apakah net pay boleh negatif, dibatasi nol, atau ada proses carry-over?
4. **Apa definisi tanggal kerja?** Tanggal dari `check_in_at` dalam timezone aplikasi, atau field tanggal kerja terpisah? Bagaimana shift lintas tengah malam?
5. **Absensi ganda/koreksi:** jika ada lebih dari satu record pada tanggal sama, record mana yang dipilih? Apakah child correction record selalu dikecualikan?
6. **Payroll tanpa pegawai eligible:** apakah boleh menghasilkan periode draft kosong atau harus ditolak?
7. **Generate ulang:** apa aturan untuk periode yang sudah ada, termasuk status draft, submitted, approved, dan paid?
8. **Revisi:** siapa yang boleh membuat revisi, kapan diperbolehkan, dan apakah alasan koreksi wajib?
9. **Tarif harian:** apakah Rp120.000 selalu berlaku, atau bisa berbeda per pegawai/per kontrak/per periode? Apakah tarif harus di-snapshot?
10. **Status employee:** apakah `approved` benar-benar status pegawai, dan bagaimana role dikecualikan secara otoritatif?
11. **Other deductions:** apakah field ini selalu nol, diisi dari sumber lain, atau dikurangkan dari net pay?
12. **Audit trail:** mekanisme log mana yang diwajibkan dan informasi apa yang harus disimpan?
13. **Perubahan data setelah generate:** apakah draft dapat dibuat ulang, atau hanya melalui revision? Bagaimana menjaga snapshot ketika attendance diedit?
14. **Batas waktu payroll:** apakah akhir periode berdasarkan timezone aplikasi atau timezone lain?
15. **Konfirmasi production:** siapa pemilik bisnis yang menyetujui aturan dan siapa yang memberi persetujuan rilis?
