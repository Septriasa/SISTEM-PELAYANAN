LOGIN AWAL
----------
  admin    / admin123
  operator / operator123


STRUKTUR FILE (dipisah per halaman)
-----------------------------------
Halaman     PHP (isi + database)   CSS (tampilan)        JS (interaksi)
---------   --------------------   -------------------   ------------------
Login       login.php              css/login.css         js/login.js
Dashboard   dashboard.php          css/dashboard.css     js/dashboard.js
Pendaftaran pendaftaran.php        css/pendaftaran.css   js/pendaftaran.js
Antrean     antrean.php            css/antrean.css       js/antrean.js
Laporan     laporan.php            css/laporan.css       js/laporan.js
Admin       admin.php              css/admin.css         js/admin.js

File yang dipakai bersama oleh semua halaman:
  css/style.css          -> tampilan umum (sidebar, tombol, tabel, form, popup)
  js/app.js              -> popup (tambah/ubah/lihat/hapus), sidebar, menu profil
  includes/header.php    -> bagian atas halaman (sidebar + topbar)
  includes/footer.php    -> bagian bawah halaman + pesan sukses
  includes/fungsi.php    -> fungsi bantu (database, tanggal, login, paging, ikon)
  config/koneksi.php     -> koneksi ke database
  database/ksop.sql      -> struktur tabel + data contoh
Lainnya:
  index.php (pintu masuk), logout.php (keluar), reset.php (kembalikan data contoh)

FITUR CRUD
----------
Pendaftaran : Lihat, Ubah, Hapus, Setujui/Tolak  (TIDAK ada fitur Tambah)
Antrean     : Tambah, Lihat, Ubah, Hapus, Panggil, Selesai
Laporan     : Tambah, Lihat, Ubah, Hapus (arsip), Salin CSV
Admin       : Tambah, Lihat, Ubah, Hapus

KEAMANAN YANG SUDAH DITERAPKAN (boleh disebut di laporan tugas)
---------------------------------------------------------------
- Password disimpan dengan password_hash() dan dicek dengan password_verify()
- Input dibersihkan dengan mysqli_real_escape_string() (mencegah SQL Injection)
- Output dibersihkan dengan htmlspecialchars() lewat fungsi e() (mencegah XSS)
- Semua halaman dicek login lewat cek_login() (session)
