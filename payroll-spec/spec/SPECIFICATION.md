# Spesifikasi Kebutuhan — Payroll Generation

## 1. Tujuan
Memastikan payroll draft yang dibuat dari absensi approved akurat, konsisten, dapat ditelusuri, dan aman untuk proses review perusahaan, dengan mempertahankan tampilan serta kontrak aplikasi yang sudah ada.

## 2. Cakupan
### Termasuk
- validasi batas periode;
- pemilihan pegawai yang memenuhi syarat;
- pemilihan absensi yang valid dan deduplikasi per hari;
- perhitungan gaji dan potongan keterlambatan;
- penyimpanan `PayrollPeriod`, `PayrollItem`, snapshot absensi, dan detail keterlambatan;
- transaksi, integritas data, penanganan kegagalan, duplikasi, dan audit;
- test unit dan integrasi backend.

### Tidak termasuk tanpa persetujuan terpisah
- redesign UI atau perubahan tampilan;
- perubahan aturan bisnis atau nominal gaji;
- perubahan workflow HR/approval;
- perubahan format slip/field yang dikonsumsi UI;
- pengubahan atau penghapusan payroll historis;
- integrasi bank, perpajakan, BPJS, atau komponen payroll lain yang belum disebutkan.

## 3. Kebutuhan fungsional
### FR-01 — Validasi periode
Service menolak tahun, bulan, atau nomor periode di luar batas yang ditentukan dan menghasilkan rentang inklusif yang benar.

### FR-02 — Pemilihan pegawai
Service hanya memilih pegawai yang memenuhi kriteria kelayakan yang telah diverifikasi. Kriteria tidak boleh berubah diam-diam.

### FR-03 — Pemilihan absensi
Hanya absensi valid sesuai aturan bisnis yang dihitung. Record mock, child/correction record yang dikecualikan, belum approved, tidak hadir, atau tanpa waktu check-in tidak boleh dihitung jika aturan aktual mengonfirmasi filter tersebut.

### FR-04 — Deduplikasi
Setiap pegawai dihitung maksimal satu kali per tanggal kerja sesuai definisi tanggal kerja aplikasi. Pemilihan record yang dipertahankan harus deterministik dan sesuai aturan koreksi absensi.

### FR-05 — Perhitungan
Tarif, blok keterlambatan, nominal potongan, dan nilai bersih harus berasal dari aturan yang disetujui. Hindari angka ajaib tersebar di banyak tempat; jika pemusatan konstanta diusulkan, pertahankan kompatibilitas dan jangan membuat perubahan bisnis.

### FR-06 — Snapshot
Data payroll merekam nilai pada saat generate sehingga perubahan profil atau absensi setelahnya tidak mengubah snapshot yang sudah dibuat secara diam-diam.

### FR-07 — Konsistensi penyimpanan
Pembuatan periode, item, dan detail keterlambatan harus all-or-nothing untuk satu operasi yang memang didefinisikan sebagai satu unit transaksi. Kegagalan harus meninggalkan data dalam kondisi konsisten.

### FR-08 — Idempotensi dan konkurensi
Pengiriman permintaan berulang atau dua proses bersamaan tidak boleh membuat duplikasi yang melanggar aturan unik. Solusi harus cocok dengan database dan schema aktual, bukan sekadar pemeriksaan aplikasi yang rawan race condition.

### FR-09 — Authorization dan audit
Hanya role/permission yang sah dapat menjalankan generate. Aksi dan hasil penting tercatat menggunakan mekanisme audit aplikasi yang ada.

### FR-10 — Kompatibilitas
Controller, route, view, field yang digunakan UI, dan tampilan harus tetap kompatibel kecuali perubahan terpisah telah disetujui.

## 4. Kebutuhan nonfungsional
- **Integritas:** nilai item dan detail konsisten.
- **Determinisme:** input sama menghasilkan hasil sama.
- **Keamanan:** authorization server-side; validasi input; tidak ada data sensitif di log.
- **Auditability:** perubahan dan revisi dapat ditelusuri.
- **Maintainability:** aturan bisnis jelas, test dapat dibaca, dan perubahan minimal.
- **Performance:** hindari query N+1 jika relevan; ukur dahulu sebelum optimasi.
- **Reliability:** transaksi dan penanganan exception sesuai pola proyek.
- **Compatibility:** tidak mengubah UI atau kontrak yang sudah digunakan.

## 5. Kriteria penerimaan
- Semua batas periode dan bulan kalender diuji.
- Semua contoh perhitungan keterlambatan diuji.
- Pegawai/absensi yang tidak memenuhi syarat dikecualikan.
- Satu pegawai tidak dihitung dua kali pada tanggal kerja yang sama.
- Item dan detail keterlambatan tersimpan konsisten.
- Generate ulang dan proses bersamaan ditangani sesuai aturan unik yang disetujui.
- Tidak ada perubahan UI yang tidak diminta.
- Test relevan lulus atau kegagalan dijelaskan dengan bukti.
- Ada laporan audit, daftar perubahan, risiko tersisa, dan langkah rollback.
