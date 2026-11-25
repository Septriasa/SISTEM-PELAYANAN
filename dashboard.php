
<?php
include 'config/koneksi.php';
include 'includes/fungsi.php';
include 'includes/grafik.php';

$admin_login = cek_login($koneksi);

$judul_halaman = 'Dashboard';
$menu_aktif    = 'dashboard';
$css_tambahan  = array('dashboard.css', 'grafik.css');
$js_tambahan   = array('dashboard.js');

// Nama depan admin
$nama_depan = explode(' ', $admin_login['nama']);
$nama_depan = $nama_depan[0];

// ==========================================
// DATA GRAFIK
// ==========================================

$dari_grafik   = date('Y') . '-01-01';
$sampai_grafik = date('Y-m-d');
$tahun_grafik  = (int)date('Y');

$grafik_bulanan = grafik_ambil_bulanan($koneksi, $tahun_grafik);
$grafik_status  = grafik_ambil_status(
    $koneksi,
    $dari_grafik,
    $sampai_grafik
);

// ==========================================
// DATA KARTU STATISTIK
// ==========================================

$total_pendaftaran = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM pendaftaran"
);

$total_menunggu = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM pendaftaran
     WHERE status = 'Menunggu Verifikasi'"
);

$total_disetujui = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM pendaftaran
     WHERE status = 'Disetujui'"
);

$total_ditolak = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM pendaftaran
     WHERE status = 'Ditolak'"
);

// Daftar kartu statistik
$kartu = array(
    array('Total Pendaftaran', $total_pendaftaran, 'users', 'blue', ''),
    array('Menunggu Verifikasi', $total_menunggu, 'clock', 'amber', 'Menunggu Verifikasi'),
    array('Disetujui', $total_disetujui, 'check', 'green', 'Disetujui'),
    array('Ditolak', $total_ditolak, 'x', 'red', 'Ditolak')
);

// ==========================================
// AKTIVITAS TERBARU
// ==========================================

$aktivitas = ambil_semua(
    $koneksi,
    "SELECT * FROM aktivitas
     ORDER BY waktu DESC, id DESC
     LIMIT 60"
);

$aktivitas_5 = array_slice($aktivitas, 0, 5);

// ==========================================
// MENU CEPAT
// ==========================================

$jml_menunggu_antrean = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM antrean
     WHERE status = 'Menunggu'"
);

$jml_laporan = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM laporan"
);

$menu_cepat = array(
    array('pendaftaran.php', 'Data Pendaftaran', 'users', $total_pendaftaran . ' data'),
    array('antrean.php', 'Nomor Antrean', 'ticket', $jml_menunggu_antrean . ' menunggu'),
    array('laporan.php', 'Laporan', 'chart', $jml_laporan . ' tersimpan')
);

// ==========================================
// HEADER
// ==========================================

include 'includes/header.php';
?>

<!-- Memastikan CSS grafik dimuat -->
<link rel="stylesheet" href="css/grafik.css?v=2">

<!-- ==========================================
     BANNER SELAMAT DATANG
========================================== -->

<section class="hero">

    <div class="hero-t">
        <h1>Selamat Datang</h1>
        <p>Kelola data dan layanan dengan mudah melalui dashboard ini.</p>
    </div>

    <div class="hero-a">

        <svg viewBox="0 0 520 240"
             preserveAspectRatio="xMaxYMax slice"
             aria-hidden="true">

            <defs>
                <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#d6e1fb"/>
                    <stop offset="1" stop-color="#f3f6ff"/>
                </linearGradient>

                <linearGradient id="sea" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#3552c9"/>
                    <stop offset="1" stop-color="#121358"/>
                </linearGradient>
            </defs>

            <rect width="520" height="240" fill="url(#sky)"/>

            <circle cx="420" cy="62" r="32" fill="#ffffff" opacity=".75"/>
            <ellipse cx="120" cy="58" rx="46" ry="10" fill="#fff" opacity=".85"/>
            <ellipse cx="158" cy="50" rx="30" ry="8" fill="#fff" opacity=".85"/>

            <path d="M0 156 L60 132 L120 150 L190 124 L270 152 L340 138 L420 156 L520 140 V176 H0Z"
                  fill="#b6c6f0"/>

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
            $warna_peti = array(
                '#c93a3a',
                '#e0a030',
                '#2b4ad0',
                '#16875a',
                '#8ea4ee',
                '#e8ecf8'
            );

            for ($baris = 0; $baris < 3; $baris++) {
                $jumlah_kolom = ($baris == 2) ? 3 : 5;

                for ($kolom = 0; $kolom < $jumlah_kolom; $kolom++) {
                    $x = 268 + $kolom * 30 + (($baris == 2) ? 30 : 0);
                    $y = 135 - $baris * 17;
                    $warna = $warna_peti[($baris * 2 + $kolom) % 6];

                    echo '<rect x="' . $x . '" y="' . $y .
                         '" width="28" height="15" rx="1.5" fill="' .
                         $warna . '"/>';
                }
            }
            ?>

            <path d="M0 198 Q30 186 60 198 T120 198 T180 198 T240 198 T300 198 T360 198 T420 198 T480 198 T540 198 V240 H0Z"
                  fill="#2b4ad0" opacity=".6"/>

            <path d="M0 214 Q30 204 60 214 T120 214 T180 214 T240 214 T300 214 T360 214 T420 214 T480 214 T540 214 V240 H0Z"
                  fill="#121358" opacity=".85"/>

        </svg>

    </div>

</section>


<!-- ==========================================
     KARTU STATISTIK
========================================== -->

<section class="stats">

    <?php foreach ($kartu as $k) { ?>

        <a class="stat s-<?= e($k[3]) ?>"
           href="pendaftaran.php<?= ($k[4] != '') ? '?status=' . urlencode($k[4]) : '' ?>">

            <span class="si">
                <?= ikon($k[2]) ?>
            </span>

            <span class="sb">
                <small><?= e($k[0]) ?></small>
                <b><?= (int)$k[1] ?></b>
            </span>

        </a>

    <?php } ?>

</section>

<!-- bagian grafik -->
<section class="grafik-grid">
    <div class="card panel">
        <h2>Pendaftaran per Bulan</h2>
        <p class="sub">
            Juli - Desember <?= $tahun_grafik ?>
        </p>
        <?php
        grafik_render_batang($grafik_bulanan);
        ?>

    </div>

    <div class="card panel">
        <h2>Status Pendaftaran</h2>
        <p class="sub">
            <?= tgl($dari_grafik) ?>
            s/d
            <?= tgl($sampai_grafik) ?>
        </p>
        <?php
        grafik_render_lingkaran($grafik_status);
        ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
