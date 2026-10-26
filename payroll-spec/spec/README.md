# Payroll Generation Service — Specification Pack

Dokumen ini menjadi sumber aturan untuk AI coding agent yang mengaudit dan menyempurnakan backend payroll Laravel.

## Tujuan
Membuat proses generate payroll lebih aman, konsisten, dapat diaudit, dan siap diuji untuk penggunaan perusahaan **tanpa merusak perilaku aplikasi yang sudah berjalan atau mengubah tampilan/UI**.

## Cara pakai
1. Salin folder `spec/` ini ke root repository.
2. Minta AI agent membaca `spec/AGENT_INSTRUCTIONS.md` terlebih dahulu.
3. Pastikan agent melakukan audit dan menyusun rencana sebelum mengubah file.
4. Review hasil audit dan tes sebelum merge/deploy.

## Batasan penting
- Dokumen ini dibuat berdasarkan deskripsi service yang diberikan, bukan hasil inspeksi repository.
- Nama model, kolom, enum, relasi, dan perilaku aktual harus diverifikasi dari kode serta skema database.
- Jangan mengarang kolom atau mengubah skema tanpa bukti dan rencana migrasi.
- Jangan mengubah tampilan, layout, route publik, atau alur UI yang sudah ada.
- Jangan menghapus data, mengubah payroll historis, atau menjalankan migrasi/command destruktif.
- Aturan bisnis yang belum pasti harus ditandai sebagai `OPEN QUESTION`, bukan ditebak.

## Dokumen
- `BUSINESS_RULES.md`: aturan bisnis yang diketahui.
- `SPECIFICATION.md`: kebutuhan fungsional dan nonfungsional.
- `ARCHITECTURE.md`: batas tanggung jawab service dan integrasi.
- `TEST_PLAN.md`: cakupan pengujian wajib.
- `IMPLEMENTATION_PLAN.md`: urutan audit dan implementasi yang aman.
- `AGENT_INSTRUCTIONS.md`: instruksi utama untuk AI coding agent.
- `OPEN_QUESTIONS.md`: keputusan bisnis yang perlu dikonfirmasi.
