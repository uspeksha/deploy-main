# Batas Arsitektur dan Tanggung Jawab

Dokumen ini menggambarkan batas tanggung jawab yang diharapkan. Cocokkan dengan arsitektur nyata sebelum implementasi.

## PayrollController
Tanggung jawab yang diharapkan:
- validasi request dan otorisasi sesuai pola aplikasi;
- memeriksa aturan duplikasi/status periode jika memang tanggung jawab controller saat ini;
- memanggil service;
- memberi feedback melalui mekanisme redirect/response yang sudah ada;
- menulis audit log sesuai pola aplikasi.

Controller sebaiknya tidak menjadi tempat kalkulasi gaji yang kompleks. Jangan memindahkan logika tanpa kebutuhan nyata.

## PayrollGenerationService
Tanggung jawab yang diharapkan:
- menentukan rentang tanggal;
- mengorkestrasi pembuatan periode;
- memilih pegawai yang eligible;
- membuat snapshot item per pegawai;
- menjalankan kalkulasi melalui aturan yang terpusat;
- menjaga konsistensi transaksi dan penanganan duplikasi.

## calculateTotals
Idealnya berupa kalkulasi deterministik tanpa query/database side effect. Validasi input seperti nilai menit negatif harus diputuskan dan diuji. Jangan mengubah perilaku input edge case sebelum memeriksa data dan aturan bisnis.

## Model dan relasi
Verifikasi relasi aktual untuk:
- `PayrollPeriod`;
- `PayrollItem`;
- model user/employee;
- model attendance;
- relasi `latenessDetails`.

Jangan mengasumsikan nama tabel/kolom atau menambah field yang tidak ada.

## Database dan transaksi
- Gunakan transaksi pada batas unit kerja yang benar.
- Pertimbangkan constraint database untuk aturan unik hanya setelah schema dan data existing diperiksa.
- Periksa kemungkinan record lama yang sudah duplikat sebelum menambah unique constraint.
- Tentukan perilaku ketika satu pegawai gagal dibuat: rollback seluruh periode atau catat kegagalan per pegawai. Ini keputusan produk/operasional, bukan asumsi agent.

## Snapshot dan revisi
Snapshot adalah rekaman nilai saat generate. Koreksi terhadap periode yang sudah disetujui/dibayar harus menggunakan mekanisme revisi yang disetujui, bukan mengubah data lama secara diam-diam.
