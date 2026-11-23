<?php
// ==========================================================
// File: login.php
// Fungsi: halaman login admin.
// Username dan password dicek ke tabel "admin" di database.
// ==========================================================
include 'config/koneksi.php';
include 'includes/fungsi.php';

// kalau sudah login, tidak perlu login lagi
if (isset($_SESSION['admin_id'])) {
    pindah('dashboard.php');
}

$error = '';

// kode ini jalan ketika tombol "Masuk" ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = bersih($koneksi, ambil_post('username'));
    $password = ambil_post('password');

    // cari admin dengan username tersebut
    $admin = ambil_satu($koneksi, "SELECT * FROM admin WHERE username = '$username'");

    if (!$admin || !password_verify($password, $admin['password'])) {
        $error = 'Username atau kata sandi salah.';
    } elseif ($admin['status'] != 'Aktif') {
        $error = 'Akun ini nonaktif. Hubungi admin lain.';
    } else {
        // login berhasil: simpan id admin di session
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        pindah('dashboard.php');
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Login - KSOP TanjungWangi</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/login.css">
</head>
<body>
<div class="login">
    <svg class="lw" viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true"><path d="M0 90 C70 60 130 120 200 92 S330 60 400 96 V200 H0Z" fill="#fff" opacity=".06"/><path d="M0 130 C70 106 140 156 210 130 S340 108 400 136 V200 H0Z" fill="#fff" opacity=".08"/></svg>

    <div class="lcard">
        <div class="brand">
            <span class="logo"><?= ikon('anchor') ?></span>
            <div class="brand-t"><b>Dashboard Pelayanan</b><small>KSOP Kelas III Tanjung Wangi</small></div>
        </div>

        <form method="post" id="lf">
            <div class="fld">
                <label for="l-user">Username</label>
                <input id="l-user" name="username" autocomplete="username" placeholder="admin" value="<?= e(ambil_post('username')) ?>" required>
            </div>
            <div class="fld">
                <label for="l-pass">Kata sandi</label>
                <input id="l-pass" name="password" type="password" autocomplete="current-password" placeholder="Kata sandi" required>
            </div>
            <div id="lerr" role="alert"><?= e($error) ?></div>
            <button class="btn" type="submit">Masuk</button>
        </form>
        <p class="hint">Akun awal: admin / admin123</p>
    </div>
</div>
<script src="js/login.js"></script>
</body>
</html>
