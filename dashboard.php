<?php
include 'config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$judul_halaman = 'Dashboard';
$menu_aktif    = 'dashboard';
$css_tambahan  = array('dashboard.css');
$js_tambahan   = array('dashboard.js');

// nama depan admin untuk ucapan selamat datang
$nama_depan = explode(' ', $admin_login['nama']);
$nama_depan = $nama_depan[0];

// ---------- Menghitung angka kartu statistik ----------
$bulan_ini = date('Y-m');  

$total_pendaftaran = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran");
$total_menunggu    = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE status = 'Menunggu Verifikasi'");
$total_disetujui   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE status = 'Disetujui'");
$total_ditolak     = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE status = 'Ditolak'");

// tambahan di bulan ini (angka kecil di pojok kartu)
$bln_total     = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'");
$bln_menunggu  = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini' AND status = 'Menunggu Verifikasi'");
$bln_disetujui = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini' AND status = 'Disetujui'");
$bln_ditolak   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini' AND status = 'Ditolak'");

// daftar kartu: [judul, angka, tambahan bulan ini, ikon, warna, filter status]
$kartu = array(
    array('Total Pendaftaran',    $total_pendaftaran, $bln_total,     'users', 'blue',  ''),
    array('Menunggu Verifikasi',  $total_menunggu,    $bln_menunggu,  'clock', 'amber', 'Menunggu Verifikasi'),
    array('Disetujui',            $total_disetujui,   $bln_disetujui, 'check', 'green', 'Disetujui'),
    array('Ditolak',              $total_ditolak,     $bln_ditolak,   'x',     'red',   'Ditolak')
);

// ---------- Aktivitas terbaru ----------
$aktivitas = ambil_semua($koneksi, "SELECT * FROM aktivitas ORDER BY waktu DESC, id DESC LIMIT 60");
$aktivitas_5 = array_slice($aktivitas, 0, 5);

// ---------- Menu cepat ----------
$jml_permohonan = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM permohonan");
$jml_menunggu_antrean = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM antrean WHERE status = 'Menunggu'");
$jml_laporan = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM laporan");

$menu_cepat = array(
    array('pendaftaran.php', 'Data Pendaftaran', 'users',  $total_pendaftaran . ' data'),
    array('permohonan.php',  'Data Permohonan',  'file',   $jml_permohonan . ' data'),
    array('antrean.php',     'Nomor Antrean',    'ticket', $jml_menunggu_antrean . ' menunggu'),
    array('laporan.php',     'Laporan',          'chart',  $jml_laporan . ' tersimpan')
);

include 'includes/header.php';
?>

<!-- ============ BANNER SELAMAT DATANG ============ -->
<section class="hero">
    <div class="hero-t">
        <h1>Selamat Datang, <?= e($nama_depan) ?>!</h1>
        <p>Kelola data dan layanan dengan mudah melalui dashboard ini.</p>
    </div>
    <div class="hero-a">
        <!-- gambar kapal (SVG) -->
        <svg viewBox="0 0 520 240" preserveAspectRatio="xMaxYMax slice" aria-hidden="true">
            <defs>
                <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d6e1fb"/><stop offset="1" stop-color="#f3f6ff"/></linearGradient>
                <linearGradient id="sea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3552c9"/><stop offset="1" stop-color="#121358"/></linearGradient>
            </defs>
            <rect width="520" height="240" fill="url(#sky)"/>
            <circle cx="420" cy="62" r="32" fill="#ffffff" opacity=".75"/>
            <ellipse cx="120" cy="58" rx="46" ry="10" fill="#fff" opacity=".85"/>
            <ellipse cx="158" cy="50" rx="30" ry="8" fill="#fff" opacity=".85"/>
            <path d="M0 156 L60 132 L120 150 L190 124 L270 152 L340 138 L420 156 L520 140 V176 H0Z" fill="#b6c6f0"/>
            <rect y="164" width="520" height="80" fill="url(#sea)"/>
            <path d="M118 150 H442 L412 192 H158Z" fill="#0a0b3a"/>
            <path d="M124 158 H436 L431 166 H129Z" fill="#c93a3a"/>
            <rect x="150" y="112" width="100" height="38" fill="#ffffff"/>
            <rect x="170" y="90" width="60" height="22" fill="#eef1fb"/>
            <rect x="176" y="97" width="48" height="8" fill="#2b4ad0"/>
            <rect x="198" y="70" width="4" height="20" fill="#ffffff"/>
            <rect x="236" y="96" width="16" height="16" fill="#121358"/>
            <rect x="236" y="96" width="16" height="4" fill="#c93a3a"/>
            <?php
            // tumpukan peti kemas di kapal (dibuat dengan perulangan)
            $warna_peti = array('#c93a3a', '#e0a030', '#2b4ad0', '#16875a', '#8ea4ee', '#e8ecf8');
            for ($baris = 0; $baris < 3; $baris++) {
                $jumlah_kolom = ($baris == 2) ? 3 : 5;
                for ($kolom = 0; $kolom < $jumlah_kolom; $kolom++) {
                    $x = 268 + $kolom * 30 + (($baris == 2) ? 30 : 0);
                    $y = 135 - $baris * 17;
                    $warna = $warna_peti[($baris * 2 + $kolom) % 6];
                    echo '<rect x="' . $x . '" y="' . $y . '" width="28" height="15" rx="1.5" fill="' . $warna . '"/>';
                }
            }
            ?>
            <path d="M0 198 Q30 186 60 198 T120 198 T180 198 T240 198 T300 198 T360 198 T420 198 T480 198 T540 198 V240 H0Z" fill="#2b4ad0" opacity=".6"/>
            <path d="M0 214 Q30 204 60 214 T120 214 T180 214 T240 214 T300 214 T360 214 T420 214 T480 214 T540 214 V240 H0Z" fill="#121358" opacity=".85"/>
        </svg>
    </div>
</section>

<!-- ============ 4 KARTU STATISTIK ============ -->
<section class="stats">
    <?php foreach ($kartu as $k) { ?>
        <a class="stat s-<?= $k[4] ?>" href="pendaftaran.php<?= ($k[5] != '') ? '?status=' . urlencode($k[5]) : '' ?>">
            <span class="si"><?= ikon($k[3]) ?></span>
            <span class="sb"><small><?= $k[0] ?></small><b><?= $k[1] ?></b></span>
            <span class="sd">+<?= $k[2] ?> bulan ini</span>
        </a>
    <?php } ?>
</section>

<div class="cols">

    <!-- ============ AKTIVITAS TERBARU ============ -->
    <section class="card panel">
        <div class="ph">
            <h2>Aktivitas Terbaru</h2>
            <button type="button" class="link" onclick="bukaIsi('Semua Aktivitas', 'semua-aktivitas')">Lihat Semua</button>
        </div>
        <ul class="acts-list">
            <?php foreach ($aktivitas_5 as $a) { ?>
                <li>
                    <span class="ai a-<?= e($a['ikon']) ?>"><?= ikon($a['ikon']) ?></span>
                    <span class="at"><?= e($a['teks']) ?></span>
                    <time><?= waktu_lalu($a['waktu']) ?></time>
                </li>
            <?php } ?>
        </ul>
    </section>

    <!-- ============ MENU CEPAT ============ -->
    <section class="card panel">
        <div class="ph"><h2>Menu Cepat</h2></div>
        <div class="qgrid">
            <?php foreach ($menu_cepat as $m) { ?>
                <a class="qtile" href="<?= $m[0] ?>">
                    <span class="qi"><?= ikon($m[2]) ?></span>
                    <b><?= $m[1] ?></b>
                    <small><?= $m[3] ?></small>
                    <span class="go"><?= ikon('arrow') ?></span>
                </a>
            <?php } ?>
        </div>
    </section>
</div>

<!-- ============ INFORMASI SISTEM ============ -->
<section class="card info">
    <span class="ii"><?= ikon('info') ?></span>
    <div>
        <b>Informasi Sistem</b>
        <p>Pelayanan Jasa Maritim KSOP Kelas III Tanjung Wangi. Data tersimpan di database MySQL dan terhubung ke semua halaman.</p>
    </div>
    <svg class="port" viewBox="0 0 240 70" aria-hidden="true"><path d="M0 66H240" stroke="#9fb0ea" stroke-width="2"/><g fill="none" stroke="#5f77d6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M30 66V22H92M30 22L58 6L92 22M78 22V66"/><path d="M130 66V30H186M130 30L156 16L186 30M174 30V66"/></g><rect x="196" y="48" width="14" height="18" fill="#8ea4ee"/><rect x="212" y="48" width="14" height="18" fill="#c93a3a" opacity=".7"/><rect x="204" y="30" width="14" height="18" fill="#121358" opacity=".6"/></svg>
    <button type="button" class="btn ghost sm" onclick="konfirmasiReset()">Atur Ulang Data Contoh</button>
</section>

<!-- Daftar lengkap aktivitas (disembunyikan, ditampilkan lewat tombol "Lihat Semua") -->
<div id="semua-aktivitas" hidden>
    <ul class="acts-list">
        <?php foreach ($aktivitas as $a) { ?>
            <li>
                <span class="ai a-<?= e($a['ikon']) ?>"><?= ikon($a['ikon']) ?></span>
                <span class="at"><?= e($a['teks']) ?></span>
                <time><?= waktu_lalu($a['waktu']) ?></time>
            </li>
        <?php } ?>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
