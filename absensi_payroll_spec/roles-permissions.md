# Role dan Hak Akses

## HRD
- Membuat, mengubah, menonaktifkan, dan mengelola data karyawan.
- Melihat rekap absensi dan payroll sesuai cakupan kewenangan.
- Meninjau dan menyetujui/menolak pengajuan sakit, cuti, pergantian hari, dan lampiran.
- Mengoreksi data dengan alasan dan audit trail.
- Mengelola kebijakan hanya jika diberi permission konfigurasi.

## Admin
- Hak admin ditetapkan secara eksplisit, bukan otomatis berarti boleh melihat atau mengubah seluruh payroll.
- Admin yang ditunjuk client: Bu Serly dan Pak Ery.
- Dapat memeriksa lampiran dan melakukan approval jika permission diberikan.
- **PERLU KONFIRMASI:** apakah keduanya memiliki hak yang sama, termasuk akses gaji, edit data, dan final approval payroll.

## Karyawan
- Melihat profil dan absensi sendiri.
- Clock-in/out.
- Mengirim pengajuan sesuai batas waktu dan melampirkan dokumen wajib.
- Melihat status dan alasan penolakan pengajuan sendiri.
- Tidak dapat membuat akun karyawan baru.
- Tidak dapat mengubah data absensi final, nilai potongan, payroll, atau menyetujui pengajuan sendiri.

## Kontrol teknis
- Gunakan prinsip least privilege.
- Semua route/API dan tindakan sensitif harus divalidasi di server.
- Cegah IDOR: karyawan tidak boleh mengganti ID pada URL/API untuk membaca data orang lain.
- Aksi admin/HRD penting harus masuk audit log.
- Data lampiran tidak boleh tersedia melalui URL publik tanpa otorisasi.
