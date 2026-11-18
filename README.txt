DASHBOARD PELAYANAN JASA MARITIM - KSOP Kelas III Tanjung Wangi
=====================================================
Bahasa: HTML, CSS, JavaScript, PHP, MySQL

CARA MENJALANKAN (pakai Laragon + HeidiSQL)
-------------------------------------------
1. Buka Laragon, lalu klik "Start All" (Apache dan MySQL menyala).
2. Salin folder "ksop-php" ke  C:\laragon\www\
3. Buka HeidiSQL (di Laragon: klik tombol "Database", atau menu Laragon > MySQL > HeidiSQL).
   - Klik "Open" pada koneksi yang ada (user: root, password: kosong).
4. Import database dengan file ksop.sql:
   - Di HeidiSQL klik menu  File > Run SQL file...
   - Pilih file  C:\laragon\www\ksop-php\database\ksop.sql  lalu klik Open.
   - Tunggu sampai selesai. Klik kanan daftar database di kiri > Refresh,
     maka database "ksop_db" dengan 6 tabel akan muncul.
5. Buka browser, lalu pilih salah satu alamat:
   - http://localhost/ksop-php/
   - http://ksop-php.test/   (alamat otomatis dari Laragon. Kalau tidak jalan,
     klik kanan Laragon > Apache > Reload)

LOGIN AWAL
----------
  admin    / admin123
  operator / operator123

Kalau pengaturan MySQL berbeda (misal root memakai password), ubah di  config/koneksi.php

MASALAH UMUM
------------
- "Koneksi database gagal"  -> MySQL belum menyala, atau ksop.sql belum dijalankan di HeidiSQL.
- Halaman tidak ditemukan   -> folder ksop-php belum ada di C:\laragon\www\
- Mau data kembali seperti awal -> klik "Atur Ulang Data Contoh" di Dashboard,
  atau jalankan lagi ksop.sql lewat HeidiSQL.

STRUKTUR FILE (dipisah per halaman)
-----------------------------------
Halaman     PHP (isi + database)   CSS (tampilan)        JS (interaksi)
---------   --------------------   -------------------   ------------------
Login       login.php              css/login.css         js/login.js
Dashboard   dashboard.php          css/dashboard.css     js/dashboard.js
Pendaftaran pendaftaran.php        css/pendaftaran.css   js/pendaftaran.js
Permohonan  permohonan.php         css/permohonan.css    js/permohonan.js
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

ALUR SEBUAH HALAMAN (contoh: permohonan.php)
--------------------------------------------
  Bagian 1: kalau ada form dikirim (POST) -> simpan / hapus / ubah status di database
  Bagian 2: ambil data dari database (dengan pencarian, filter, dan halaman)
  Bagian 3: tampilkan HTML (tabel, tombol)
JavaScript hanya membuka popup. Data disimpan oleh PHP ke MySQL.

FITUR CRUD
----------
Pendaftaran : Lihat, Ubah, Hapus, Setujui/Tolak  (TIDAK ada fitur Tambah)
Permohonan  : Tambah, Lihat, Ubah, Hapus, Setujui/Tolak
Antrean     : Tambah, Lihat, Ubah, Hapus, Panggil, Selesai
Laporan     : Tambah, Lihat, Ubah, Hapus (arsip), Salin CSV
Admin       : Tambah, Lihat, Ubah, Hapus

KEAMANAN YANG SUDAH DITERAPKAN (boleh disebut di laporan tugas)
---------------------------------------------------------------
- Password disimpan dengan password_hash() dan dicek dengan password_verify()
- Input dibersihkan dengan mysqli_real_escape_string() (mencegah SQL Injection)
- Output dibersihkan dengan htmlspecialchars() lewat fungsi e() (mencegah XSS)
- Semua halaman dicek login lewat cek_login() (session)
