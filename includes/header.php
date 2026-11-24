<?php
require_once __DIR__ . '/../config/koneksi.php';
$admin_login = $admin_login ?? [
    'nama' => 'Admin',
    'peran' => 'Administrator',
    'email' => '',
    'username' => 'admin'
];

$daftar_menu = array(
    array('dashboard.php',  'Dashboard',        'home',   'dashboard'),
    array('pendaftaran.php', 'Data Pendaftaran', 'users',  'pendaftaran'),
    array('antrean.php',    'Nomor Antrian',    'ticket', 'antrean'),
    array('laporan.php',    'Laporan',          'chart',  'laporan'),
    array('admin.php',      'Kelola Admin',     'shield', 'admin')
);

// angka kecil (badge) di samping menu = data yang perlu ditindaklanjuti
$badge = array();
$badge['pendaftaran'] = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE status = 'Menunggu Verifikasi'");
$badge['antrean']     = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM antrean WHERE status = 'Menunggu'");

// huruf awal nama untuk lingkaran avatar (contoh: Admin -> AK)
$nama_admin = $admin_login['nama'] ?? 'Admin';

$inisial = '';
$kata_nama = explode(' ', $nama_admin);

foreach ($kata_nama as $kata) {
    if ($kata != '' && strlen($inisial) < 2) {
        $inisial .= strtoupper(substr($kata, 0, 1));
    }
}
$css_tambahan = $css_tambahan ?? [];

if (!is_array($css_tambahan)) {
    $css_tambahan = [];
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($judul_halaman) ?> - KSOP Kelas III Tanjung Wangi</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="css/style.css">
<?php foreach ($css_tambahan as $file_css) { ?>
<link rel="stylesheet" href="css/<?= $file_css ?>">
<?php } ?>
</head>
<body>

<div class="app" id="app">

    <!-- ============ SIDEBAR ============ -->
    <aside class="sidebar">
        <svg class="sb-wave" viewBox="0 0 256 130" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70 C40 50 70 92 120 72 S210 50 256 74 V130 H0Z" fill="#fff" opacity=".06"/><path d="M0 92 C40 76 80 110 128 94 S216 78 256 96 V130 H0Z" fill="#fff" opacity=".07"/><path d="M0 112 C50 100 90 124 140 112 S220 102 256 114 V130 H0Z" fill="#fff" opacity=".08"/></svg>

        <div class="brand">
            <span class="logo"><?= ikon('anchor') ?></span>
            <div class="brand-t"><b>Pelayanan Jasa</b><small>KSOP Kelas III Tanjung Wangi</small></div>
        </div>

        <nav class="nav" aria-label="Menu utama">
            <?php foreach ($daftar_menu as $menu) { ?>
                <a class="nl<?= ($menu[3] == $menu_aktif) ? ' active' : '' ?>" href="<?= $menu[0] ?>" title="<?= $menu[1] ?>">
                    <?= ikon($menu[2]) ?>
                    <span class="lbl"><?= $menu[1] ?></span>
                    <?php if (isset($badge[$menu[3]]) && $badge[$menu[3]] > 0) { ?>
                        <span class="bdg" title="Perlu ditindaklanjuti"><?= $badge[$menu[3]] ?></span>
                    <?php } ?>
                </a>
            <?php } ?>
        </nav>

        <div class="sb-foot">
            <a class="nl out" href="logout.php" title="Keluar"><?= ikon('logout') ?><span class="lbl">Logout</span></a>
        </div>
    </aside>

    <div class="scrim" onclick="ubahSidebar()"></div>

    <div class="main" id="main">

        <!-- ============ TOPBAR ============ -->
        <header class="topbar">
            <button type="button" class="icon-btn" onclick="ubahSidebar()" aria-label="Buka atau tutup menu"><?= ikon('menu') ?></button>
            <span class="tdate"><?= tanggal_panjang() ?></span>

            <div class="tright">
                <div class="pf">
                    <button type="button" class="pf-btn" id="tombol-profil-menu" onclick="ubahMenuProfil()" aria-haspopup="true" aria-expanded="false">
                        <span class="av"><?= e($inisial) ?></span>
                        <span class="pn"><?= e($admin_login['nama']) ?></span>
                        <?= ikon('down') ?>
                    </button>

                    <div class="pmenu" id="menu-profil" hidden>
                        <div class="who">
                            <b><?= e($admin_login['nama']) ?></b>
                            <small><?= e($admin_login['peran']) ?> · <?= e($admin_login['email'] != '' ? $admin_login['email'] : $admin_login['username']) ?></small>
                        </div>
                        <a href="admin.php?profil=1" class="pmenu-link"><?= ikon('user') ?>Profil Saya</a>
                        <a href="admin.php" class="pmenu-link"><?= ikon('shield') ?>Kelola Admin</a>
                        <a href="logout.php" class="pmenu-link dng"><?= ikon('logout') ?>Keluar</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============ ISI HALAMAN ============ -->
        <main class="content">
