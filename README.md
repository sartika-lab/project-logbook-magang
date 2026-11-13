# Logbook Magang PT Telkom Akses Malang

## Menyiapkan database

Jalankan `schema.sql` melalui phpMyAdmin atau MySQL. Skrip membuat database dan tabel yang diperlukan, menambahkan kolom yang belum ada pada skema lama, serta memasukkan Sartika dan Nadirah tanpa menghapus data kegiatan yang sudah ada. Tetap lakukan backup database sebelum migrasi.

## Konfigurasi

Konfigurasikan environment PHP berikut pada XAMPP, panel hosting, atau konfigurasi PHP-FPM:

- `LOGBOOK_DB_HOST` (default `localhost`)
- `LOGBOOK_DB_PORT` (default `3306`)
- `LOGBOOK_DB_NAME` (default `db_logbook_magang`)
- `LOGBOOK_DB_USER` (default `root`)
- `LOGBOOK_DB_PASSWORD` (default kosong, hanya sesuai untuk instalasi lokal)
- `LOGBOOK_ADMIN_PASSWORD` (wajib; gunakan kata sandi admin yang kuat)

Admin menolak login jika `LOGBOOK_ADMIN_PASSWORD` belum diatur. Jangan gunakan akun database `root` di hosting publik; buat user MySQL khusus dan batasi hak aksesnya pada database logbook.

Unggah file PHP, `assets/`, `uploads/`, dan `schema.sql` ke document root hosting. Pastikan PHP memiliki ekstensi `mysqli` dan `fileinfo`, serta folder `uploads/` dan `assets/img/` dapat ditulis oleh proses PHP agar dokumentasi dan screenshot portofolio dapat disimpan. Folder `uploads/` berisi dokumentasi yang sudah ada dan tidak perlu dikosongkan. Tabel `portofolio` dibuat oleh `schema.sql` atau otomatis saat halaman admin/portofolio dibuka.

## Halaman

- `index.php`: portofolio publik baca-saja dengan filter, pencarian, dan detail dokumentasi.
- `profil.php`: profil mahasiswa magang, pembimbing, lokasi industri, dan proyek tugas akhir.
- `portofolio.php`: hasil kerja dashboard, digitalisasi administrasi, dan audit infrastruktur yang dibaca dari tabel `portofolio`.
- `admin.php`: login privat dan pengelolaan kegiatan, peserta, dokumentasi, serta foto absensi. Satu kegiatan dapat memiliki lebih dari satu kategori.
