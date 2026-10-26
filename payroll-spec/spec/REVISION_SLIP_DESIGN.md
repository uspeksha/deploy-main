# Analisis Desain Nomor Slip Payroll Revisi

Status: Opsi A disetujui pengguna untuk implementasi: format revision 1 tetap dan suffix `-R{revision}` untuk revision > 1. Implementasi lokal tidak memerlukan perubahan schema berdasarkan metadata MariaDB lokal; schema staging/production tetap belum diverifikasi.
Tanggal: 25 Oktober 2026

## Ringkasan keputusan teknis

Pada skema yang didefinisikan repository, `payroll_items.slip_number` memiliki unique constraint global. Sebelum perubahan, pembentukan nomor slip service tidak memasukkan revision atau ID period. Karena pembatalan mempertahankan item periode asal, pembuatan revisi untuk pegawai yang sama mencoba memasukkan nomor slip yang sama dan gagal.

**Opsi A telah dipilih dan disetujui pengguna:** format lama dipertahankan untuk revision 1 dan nomor revisi yang dapat dibaca manusia ditambahkan hanya untuk revision > 1. Opsi B lebih kuat secara teknis terhadap benturan karena memakai identitas record period, tetapi kurang mudah dibaca dan membawa dependensi pada identifier internal. Opsi C tidak diterapkan: mengubah constraint saja tidak menyelesaikan duplikasi nomor yang terlihat atau nama file PDF.

Opsi A telah disetujui pengguna untuk implementasi. Persetujuan ini tidak membuktikan schema staging/production; DBA harus memverifikasi constraint dan panjang kolom sebelum rollout.

## Bukti alur aktual di repository

### Pembuatan payroll awal dan revisi

1. `PayrollController::store()` memvalidasi tahun, bulan, dan nomor periode, lalu mencari periode terakhir dengan kunci tahun+bulan+nomor periode, diurutkan berdasarkan revision menurun. Lihat `app/Http/Controllers/Admin/PayrollController.php:116-142`.
2. Bila periode terakhir ada dan statusnya bukan cancelled, controller tidak menghitung ulang; ia mengarahkan kembali ke periode itu dengan warning. Lihat `PayrollController.php:130-137`.
3. Bila periode terakhir cancelled, controller menetapkan revision menjadi `previousPeriod.revision + 1`, dan `revision_of_id` ke ID periode terakhir tersebut. Alasan koreksi otomatis menyebut ID periode yang dibatalkan. Lihat `PayrollController.php:139-153`.
4. Controller membungkus pemanggilan service dan audit log dalam `DB::transaction()`. Service juga menjalankan transaksi yang mencakup insert period, pembuatan item seluruh pegawai eligible, dan detail keterlambatan. Lihat `PayrollController.php:144-169` dan `app/Services/PayrollGenerationService.php:21-58`.
5. Pembatalan hanya mengubah status serta metadata cancellation pada period; controller tidak menghapus period, item, atau detail. Status yang bisa dibatalkan adalah draft, pending approval, dan approved; paid tidak dapat dibatalkan melalui jalur ini. Lihat `PayrollController.php:242-270`.

**Implikasi lineage:** alur controller memilih period terbaru sebagai `previousPeriod`, lalu membuat revision berikutnya menunjuk langsung ke period terbaru yang cancelled. Dengan demikian, rangkaian normal berbentuk revision 1 → revision 2 → revision 3. Controller tidak membuat dua cabang revision yang sama-sama menunjuk ke revision 1. Apakah bisnis menghendaki lineage langsung ini atau semua revision menunjuk ke revision akar tidak diputuskan di sini.

### Pembentukan item dan nomor slip

- `PayrollGenerationService::createPeriod()` memilih user berjenis freelance, registration approved, dan mengecualikan daftar role tertentu, kemudian memprosesnya berkelompok. Lihat `PayrollGenerationService.php:44-53`.
- Satu `PayrollItem` dibuat per pegawai terpilih; item memuat payroll period, identitas snapshot, nilai perhitungan, attendance snapshot, serta nomor slip. Lihat `PayrollGenerationService.php:113-190`.
- Format saat ini: `FM-{tahun 4 digit}{bulan 2 digit}-W{nomor periode}-{user ID 6 digit}`. Lihat `PayrollGenerationService.php:181-187`. Nilai tersebut tidak memakai `revision` ataupun `payroll_period_id`.
- Setelah item tercipta, setiap baris keterlambatan disimpan sebagai detail relasi item. Pembuatan item dan detail berada di transaksi service.

### Constraint yang didefinisikan migration

`database/migrations/2026_10_23_000000_create_payroll_tables.php` mendefinisikan:

- Unique period: `(year, month, period_number, revision)` — baris 34.
- Global unique `payroll_items.slip_number` — baris 55.
- Unique pegawai per period: `(payroll_period_id, user_id)` — baris 58.
- Unique detail tanggal per item: `(payroll_item_id, work_date)` — baris 73.
- `revision_of_id` adalah nullable self-reference ke `payroll_periods` — baris 17.

### Kesimpulan benturan

Pada format sekarang, pegawai yang sama dalam tahun/bulan/nomor periode yang sama menghasilkan string slip yang sama, tanpa bergantung pada period atau revision. Item periode asal tetap ada sesudah cancellation. Maka bila pegawai itu masuk kembali ke revision baru, insert `PayrollItem` baru berpotensi melanggar global unique constraint `slip_number`. Ini **terbukti sebagai konflik antara format kode dan constraint migration repository**.

### Batas bukti deployment

Migration adalah definisi schema yang diharapkan dalam repository, bukan bukti schema aktif di deployment. Database lokal yang sebelumnya diperiksa adalah SQLite dan tidak memiliki tabel payroll atau catatan migration payroll. Engine, constraint aktual, versi migration, dan data historis staging/production belum diverifikasi. Tidak ada query terhadap database production dilakukan untuk dokumen ini. Jangan menganggap constraint repository telah diterapkan dengan bentuk yang sama pada deployment.

## Dampak pengguna nomor slip

Pencarian kode repository menemukan konsumen ini:

- PDF menampilkan nomor slip pada judul dan isi: `resources/views/admin/payroll/slip.blade.php`.
- Preview menampilkan nomor slip: `resources/views/admin/payroll/preview.blade.php`.
- Nama file unduhan PDF dibentuk dari nomor slip: `PayrollController.php:393`.
- Export Excel menampilkan nomor slip: `app/Exports/PayrollSummaryExport.php:44,62`.
- Test export/slip mengandalkan nilai nomor slip untuk nama file: `tests/Feature/Admin/PayrollTest.php:536`.

Tidak ditemukan penggunaan lain dalam source/routes yang diperiksa. Namun, repository search tidak dapat membuktikan tidak ada integrasi eksternal, rekonsiliasi keuangan, prosedur manual, atau skrip deployment yang bergantung pada pola lama. HR/Finance dan pemilik sistem perlu memeriksa konsumen tersebut.

## Perbandingan opsi

### Opsi A — Masukkan nomor revisi ke format slip

**Format yang disetujui:** revision 1 tetap `FM-202610-W1-000123`; revision 2 menjadi `FM-202610-W1-000123-R2`.

- **Kode/database:** service membentuk suffix revision saat `revision > 1`; revision 1 mempertahankan string persis seperti sekarang. Tidak diperlukan perubahan schema pada migration repository karena kolom string memiliki panjang 100. Panjang maksimum dan tipe kolom deployment tetap harus diverifikasi.
- **Payroll lama:** tidak berubah; semua nomor slip yang sudah tersimpan tetap persis sama.
- **Risiko duplikat:** rendah untuk jalur normal karena revision unik untuk setiap tuple tahun+bulan+periode di migration. Dengan user ID dan revision ikut di string, revision berbeda tidak menghasilkan slip yang sama. Jaminan ini mengandalkan revision yang benar-benar dipasok dari period serta constraint period unik yang aktif.
- **Export/laporan/pencarian/integrasi:** field tetap sama; output lama tidak berubah. Slip revisi memiliki nilai baru sehingga PDF filename tidak bertabrakan antarrevisi. Konsumen eksternal yang memvalidasi pola atau panjang nomor harus diperbarui/disetujui.
- **Kompatibilitas historis:** sangat baik; hanya item baru dengan revision > 1 yang memakai format baru. Tidak perlu backfill.
- **Migration/deployment:** tidak perlu migration bila schema aktual menampung string tersebut. Perlu verifikasi panjang field dan unique constraint aktual. Rollout code harus dilakukan setelah persetujuan format.
- **Test:** revision 1 mempertahankan string exact lama; revision 2+ menghasilkan nomor berbeda dan stabil; revisi dengan pegawai sama tersimpan bersama item asal; banyak pegawai dalam revisi; beberapa revision berantai; seluruh slip unik; PDF filename dan export berisi slip yang sama dengan item; panjang tidak melebihi batas.
- **Kelebihan:** mudah dibaca dan menjelaskan versi koreksi pada dokumen, dengan dampak minimal ke data lama.
- **Kekurangan:** mengubah format publik untuk dokumen revisi dan perlu persetujuan HR/Finance. Jika kebutuhan bisnis mendefinisikan nomor slip sebagai identitas karyawan-periode yang sama tanpa varian revision, perubahan semantik ini harus disetujui.

### Opsi B — Gunakan identifier unik period/revisi sambil mempertahankan metadata

**Contoh bentuk untuk diskusi:** `FM-202610-W1-000123-P000456`, dengan `000456` berasal dari primary key period revision. Format ini tetap menampilkan tahun, bulan, periode, dan pegawai, lalu menambahkan identitas period yang unik.

- **Kode/database:** service menyertakan `PayrollPeriod.id` atau identifier unik lain dari record revision pada string slip. Memakai primary key period yang sudah ada tidak memerlukan kolom baru; identifier yang dibuat sebelum item dapat dipakai saat item dibuat. Bila identifier acak/UUID dipilih, perlu dipastikan schema period sudah menyediakan UUID; migration dapat diperlukan jika belum.
- **Payroll lama:** tidak berubah. Item revision baru memakai suffix/komponen identitas period; revision 1 dapat dipertahankan persis atau seluruh nomor baru memakai bentuk ini, tetapi pilihan kedua memperluas dampak dan tidak direkomendasikan tanpa persetujuan.
- **Risiko duplikat:** paling kuat bila identifier berasal dari PK period yang unik dan selalu disertakan bersama identitas pegawai. Keunikan tidak bergantung pada nomor revision yang terbentuk benar. Masih bergantung pada constraint `slip_number` aktif di deployment serta tidak adanya ID period yang sama (yang dijamin PK dalam schema normal).
- **Export/laporan/pencarian/integrasi:** perubahan format lebih panjang dan menyertakan identifier internal; PDF, nama file, Excel, dan konsumen eksternal tetap perlu mengakomodasi. ID berurutan dapat mengungkap urutan/volume record internal dan dapat berbeda setelah migrasi/import lingkungan.
- **Kompatibilitas historis:** data lama dapat tetap dibaca tanpa perubahan; field tetap string. Namun, pencarian manual atau validasi format eksternal mungkin lebih terganggu daripada suffix revision ordinal.
- **Migration/deployment:** tidak perlu jika menggunakan PK period yang sudah tersedia dan panjang aktual mencukupi. Bisa perlu jika ingin UUID khusus atau key publik yang stabil. Skema deployment harus diperiksa dahulu.
- **Test:** revision 1 compat; nomor revisi memiliki identifier berbeda berdasarkan period; semua metadata (tahun/bulan/periode/user) tetap terwakili; item pegawai sama di period lain tetap unik; perbedaan ID lingkungan tidak merusak operasi export/PDF; seluruh `slip_number` unik.
- **Kelebihan:** keunikan terkait record period itu sendiri, sehingga tidak bergantung hanya pada angka urutan revisi.
- **Kekurangan:** nomor lebih teknis/panjang, mengekspos identifier internal, dan tidak langsung memberi makna revision ordinal kepada Finance. Menggunakan ID internal dapat menyulitkan identifikasi jika data diekspor/import ulang dengan primary key berbeda.

### Opsi C — Ubah desain identitas slip atau constraint database

Opsi ini mencakup dua desain yang harus dibedakan:

1. **Hanya melonggarkan global unique `slip_number`** dan mengandalkan unique `(payroll_period_id, user_id)` yang sudah ada; atau
2. **Mendesain ulang identitas**, misalnya menambah identifier slip global immutable (UUID/ULID) dan menjadikan nomor yang terlihat sebagai label non-unik/unik hanya dalam scope tertentu.

- **Kode/database:** (1) membutuhkan migration untuk melepas unique global dan memeriksa/mempertahankan unique item-per-period yang sudah ada; tetapi tidak membuat nomor tampilan revisi unik. (2) membutuhkan migration additive, pembentukan/backfill identifier untuk item historis, penyesuaian model/service/export/download filename/pencarian, dan keputusan unique constraint/index baru.
- **Payroll lama:** migration constraint saja tidak mengubah nilai lama, tetapi membuka peluang slip sama pada item lintas revisi. Desain UUID dapat mempertahankan label lama dan menyimpan identifier tambahan, tetapi backfill historis harus deterministik dan tidak boleh mengganti referensi yang sudah dipakai eksternal.
- **Risiko duplikat:** melonggarkan constraint menghilangkan perlindungan global tanpa mencegah nilai slip duplikat. Karena filename saat ini memakai `slip_number`, PDF revisi dapat memiliki nama file sama. UUID global mencegah duplikasi identifier internal, tetapi label yang tampil tetap berpotensi sama kecuali format label atau konteks download dibedakan.
- **Export/laporan/pencarian/integrasi:** dapat mengubah key ekspor, nama file, URL/identitas yang dicari, dan proses rekonsiliasi. Harus diputuskan mana yang merupakan nomor slip manusia dan mana yang merupakan ID teknis.
- **Kompatibilitas historis:** paling kompleks. Constraint existing harus diverifikasi; data historis perlu dipertahankan persis. Backfill identifier tambahan hanya jika aman dan tidak menyamarkan fakta historis.
- **Migration/deployment:** paling tinggi kompleksitasnya. Perlu inventaris engine dan versi DB, validasi duplikat sebelum perubahan constraint, backup, staged deployment, migration forward-compatible, serta rencana rollback. Jangan menjalankan migration sebelum schema deployment dan data yang relevan diperiksa.
- **Test:** schema/constraint pada engine deployment; uniqueness internal/label sesuai keputusan; backfill idempotent di salinan staging; filename export/PDF; pencarian; pembacaan data lama; rollback/forward migration; dua request konkuren; dan larangan perubahan payroll historis.
- **Kelebihan:** memisahkan identitas teknis immutable dari label bisnis bila memang kebutuhan perusahaan berkembang ke beberapa jenis slip, integrasi, atau versioning.
- **Kekurangan:** terlalu luas untuk menyelesaikan satu benturan format jika kebutuhan utama hanya membedakan revisi; risiko migration/deployment dan kompatibilitas terbesar. Melonggarkan constraint saja tidak menyelesaikan benturan semantik atau file.

## Rekomendasi

Implementasi yang disetujui adalah **Opsi A**:

1. Pertahankan nilai slip revision 1 identik dengan format existing.
2. Tambahkan suffix revisi ordinal hanya untuk revision > 1.
3. Pertahankan global unique constraint `slip_number`; jangan melonggarkannya untuk menutupi benturan.
4. Jangan mengubah nilai slip, item, atau detail payroll historis.
5. Sebelum coding, verifikasi skema aktual di staging/salinan data yang diizinkan, khususnya unique constraint dan panjang kolom.

Alasan: opsi ini mempertahankan nomor lama, membuat revisi dapat dikenali manusia, sesuai model revision ordinal yang sudah tersimpan, tidak memerlukan migration menurut schema repository, dan membuat PDF/export memakai nilai baru melalui field yang sama. Ini bukan dipilih hanya karena implementasinya ringan; faktor pembeda utamanya adalah keterbacaan/audit koreksi dan kompatibilitas historis yang lebih baik daripada identifier internal.

**Opsi B** layak dipilih bila pemilik payroll mengutamakan keunikan berbasis identitas record period daripada nomor revision yang mudah dibaca, atau bila ditemukan revision ordinal yang tidak dapat dipercaya. Sebelum itu, dampak paparan ID internal dan stabilitas ID lintas lingkungan perlu dinilai.

**Opsi C** hanya layak bila ada kebutuhan jangka panjang untuk memisahkan ID slip teknis dari nomor dokumen bisnis atau mendukung label non-unik secara eksplisit. Tidak disarankan sebagai hotfix untuk masalah saat ini.

## Keputusan yang diperlukan

### HR/Finance/pemilik proses payroll

- Apakah nomor slip revision > 1 boleh memiliki suffix revision dan apakah slip awal harus mempertahankan format exact?
- Apakah slip revisi harus menjadi dokumen baru yang dapat dibedakan dari slip periode asal?
- Apakah pembatalan lalu pembuatan ulang selalu merupakan revision baru, dan apakah nomor revision ditampilkan pada slip/export?
- Apakah beberapa revision boleh menjadi cabang dari periode akar, atau harus selalu menunjuk revision sebelumnya? Implementasi controller saat ini membentuk rantai.
- Apakah format nomor slip yang ada dipakai oleh bank, finance system, rekonsiliasi, laporan, atau prosedur manual di luar repository?
- Apakah file export/unduhan lama harus tetap bernama dengan pola saat ini?

### DBA/operator deployment

- Driver database, versi engine, schema aktual `payroll_periods`/`payroll_items`, dan riwayat migration payroll.
- Apakah constraint global pada `slip_number` benar-benar terpasang; nama/index aktual dan panjang/tipe kolom.
- Apakah constraint period revision unik serta unique `(payroll_period_id, user_id)` terpasang.
- Apakah data staging yang dimasking menunjukkan duplicate `slip_number` existing atau panjang/format berbeda dari migration repository?
- Apakah pendekatan migration (bila Opsi C akhirnya dipilih) aman untuk engine, ukuran tabel, lock duration, dan prosedur rollback.

## Test plan

Gunakan data sintetis di test database terisolasi. Jangan membaca atau mengubah database production.

1. **Payroll awal, beberapa pegawai:** Buat payroll revision 1 untuk sedikitnya tiga pegawai eligible sintetis. Pastikan jumlah item satu per pegawai, nilai slip revision 1 exact mengikuti format lama, detail keterlambatan sesuai fixture, dan seluruh nomor slip unik.
2. **Revisi dengan pegawai sama:** Batalkan payroll awal sesuai workflow, buat revision berikutnya dengan dua pegawai yang sama, dan pastikan item lama tidak berubah, item revisi tersimpan, `revision_of_id`/alasan koreksi benar, serta semua slip tetap unik.
3. **Revisi lebih dari satu item:** Buat revision dengan beberapa pegawai, termasuk pegawai yang sama dengan periode asal; pastikan tidak ada partial success dan nomor berbeda untuk setiap pegawai/revision.
4. **Dua revisi pada lineage yang sama:** Simulasikan revision 1 cancelled → revision 2 cancelled → revision 3. Pastikan nomor revision dan slip tidak berulang, link revision menunjuk parent yang sesuai aturan yang disetujui, dan seluruh period/item lama tetap utuh. Jika bisnis meminta dua child langsung terhadap revision 1, buat skenario terpisah; controller saat ini tidak mengimplementasikan cabang tersebut sehingga harus ada keputusan perubahan workflow terlebih dahulu.
5. **Kegagalan pembuatan item/rollback:** Paksa insert item gagal setelah period dibuat. Pastikan period revision baru, item, detail, dan audit log dalam transaksi dibatalkan; data historis period asal tidak berubah.
6. **Keunikan global lintas period/revision:** Buat beberapa period/revision dengan user yang sama dan user berbeda; query test memastikan tidak ada duplicate `slip_number`, serta database constraint menolak duplicate yang disuntikkan langsung.
7. **Kompatibilitas historis:** Simpan nomor slip fixture lama, buat revisi menggunakan format yang disetujui, dan pastikan nomor/filename/export record lama tidak berubah. Pastikan revision 1 baru tetap menggunakan pola lama bila itulah keputusan HR.
8. **Konsumen slip:** Pastikan preview/PDF menampilkan slip dari item, file PDF revision baru berbeda nama dari file revision asal, export Excel memuat nomor yang sama dengan item, dan nilai lama tetap dapat diunduh.
9. **Request berulang/konkurensi:** Uji dua request mencoba membuat revision/period yang sama pada driver staging yang digunakan deployment. Pastikan unique period revision tetap mencegah duplikasi dan hanya konflik yang benar-benar pesaing menjadi warning; constraint slip/item/detail lain tidak disamarkan.
10. **Batas panjang:** Pastikan format hasil dan kemungkinan user/period ID maksimum tetap berada di batas kolom aktual; jangan mengandalkan hanya batas 100 dari migration repository.

## Rencana implementasi bertahap

1. Dapatkan persetujuan tertulis HR/Finance untuk Opsi A atau opsi lain; sepakati format exact, perilaku revision 1, lineage, filename, dan konsumen eksternal.
2. Minta DBA memverifikasi schema/index/constraint deployment serta panjang kolom pada staging atau salinan aman yang dimasking. Jangan mengasumsikan migration repository sudah diterapkan.
3. Inventarisasi penggunaan nomor slip di repository dan validasi integrasi eksternal. Catat bahwa nomor slip revisi adalah nilai dokumen, bukan hanya detail internal.
4. Tambahkan test revision dengan pegawai sama dan beberapa item sebelum atau bersama implementasi, menggunakan test DB sintetis. Tambahkan test kompatibilitas format lama, unique global, lineage, dan rollback.
5. Implementasikan perubahan minimal pada generator saja bila Opsi A/B dipilih; pertahankan schema/constraint dan kontrak field. Untuk Opsi C, buat rencana migration terpisah yang disetujui dan jangan menggabungkannya diam-diam dengan hotfix.
6. Jalankan test payroll terarah, suite regresi yang relevan, formatter, dan review diff untuk memastikan hanya file terkait berubah.
7. Deploy ke staging setelah backup staging yang sesuai; uji generate awal, cancel, revision, slip PDF/preview, export, audit log, serta dua request paralel dengan data sintetis/masked.
8. Dapatkan sign-off HR/Finance dan DBA setelah hasil staging diverifikasi; produksi memerlukan backup sesuai prosedur perusahaan, monitoring, serta jendela deployment yang disetujui.

## Rollback dan perlindungan historis

- Untuk Opsi A/B tanpa migration, rollback kode tidak memerlukan rollback database. Namun, versi kode lama yang membentuk slip tanpa revision dapat kembali menyebabkan benturan; setelah rollback, hentikan generate revision sampai hotfix yang kompatibel dipulihkan.
- Jangan mengganti nomor slip yang sudah tersimpan, menghapus payroll item, atau menghitung ulang payroll historis sebagai bagian rollback.
- Slip revisi yang sudah dibuat dengan format baru harus tetap dapat dibaca/diunduh oleh kode aplikasi. Karena field tetap `slip_number`, perubahan presentasi harus terus memakai field tersebut dan diuji kompatibilitasnya sebelum deploy.
- Untuk Opsi C, rollback migration bukan otomatis aman: perubahan constraint/kolom dapat gagal bila data baru telah memakai desain tersebut. Backup, strategi forward-fix, dan keputusan DBA diperlukan; jangan menjalankan `down` yang menghapus identifier terisi.
- Jika validasi staging menemukan duplicate historis, hentikan rollout dan minta keputusan pemilik data. Jangan otomatis mengubah nilai lama untuk membuat constraint lolos.

## Status batasan pekerjaan ini

- Opsi A disetujui pengguna dan diimplementasikan pada generator/test: revision 1 mempertahankan format lama; revision > 1 memakai suffix `-R{revision}`.
- Tidak ada nomor slip, item, atau detail historis yang diubah.
- Tidak ada perubahan UI, route, schema, migration, atau database.
- Metadata schema terverifikasi hanya pada koneksi lokal MariaDB; staging/production dan integrasi eksternal belum diverifikasi.
- Validasi DBA pada lingkungan target tetap menjadi prasyarat deployment.
