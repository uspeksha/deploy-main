# Dokumen Spesifikasi Sistem Absensi & Payroll

Paket ini menjadi acuan kerja AI coding agent untuk mengembangkan sistem absensi yang sudah ada.

## Urutan baca
1. `agent-instructions.md` — aturan kerja agent.
2. `spesifikasi.md` — kebutuhan utama sistem.
3. `business-rules.md` — aturan bisnis dan perhitungan.
4. `payroll-rules.md` — fokus masalah payroll.
5. `roles-permissions.md` — hak akses.
6. `data-model.md` — rancangan entitas data.
7. `architecture.md` — arahan teknis tanpa memaksakan perubahan stack.
8. `ui-requirements.md` — kebutuhan tampilan dan ekspor Excel.
9. `acceptance-criteria.md` — definisi selesai dan skenario uji.
10. `tasks.md` — urutan implementasi.

## Prinsip penting
- Sistem absensi mengikuti alur dan pengalaman penggunaan yang diminta client, dengan Talenta sebagai referensi perilaku/fitur, bukan menyalin kode atau aset proprietari.
- Payroll harus mempertahankan dan menyesuaikan implementasi yang sudah ada di repository. Jangan mengganti total modul payroll sebelum audit kode dan data.
- Dokumen ini memuat kebutuhan hasil meeting. Hal yang belum jelas ditandai **PERLU KONFIRMASI**, jangan ditebak lalu dijadikan aturan produksi.
- Jangan menjalankan migrasi destruktif, menghapus data, mengganti konfigurasi produksi, atau mengubah kredensial tanpa persetujuan eksplisit.
