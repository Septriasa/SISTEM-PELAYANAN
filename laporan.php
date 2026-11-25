
<?php

include 'config/koneksi.php';
include 'includes/fungsi.php';

$admin_login = cek_login($koneksi);

// ==========================================
// 1. FILTER TANGGAL
// ==========================================

if (!empty($_GET['dari']) && !empty($_GET['sampai'])) {
    $_SESSION['filter_dari'] = $_GET['dari'];
    $_SESSION['filter_sampai'] = $_GET['sampai'];
}

if (isset($_GET['reset'])) {
    unset($_SESSION['filter_dari'], $_SESSION['filter_sampai']);
}

$dari = $_SESSION['filter_dari'] ?? date('Y') . '-01-01';
$sampai = $_SESSION['filter_sampai'] ?? date('Y-m-d');

// ==========================================
// 2. STATISTIK
// ==========================================

$s = hitung_statistik($koneksi, $dari, $sampai);

$ringkasan = array(
    array('Total Pendaftaran', $s['pend'], 'users', 'blue'),
    array('Total Antrean', $s['ant'], 'ticket', 'amber'),
    array('Pendaftaran Disetujui', $s['ok'], 'check', 'green'),
    array('Pendaftaran Ditolak', $s['no'], 'x', 'red')
);

$k = kondisi_tanggal($koneksi, $dari, $sampai);

$laporan_detail = array();

// ==========================================
// 3. LAPORAN PENDAFTARAN PENGGUNA JASA
// ==========================================

$baris = array();

$data = ambil_semua(
    $koneksi,
    "SELECT * FROM pendaftaran
     WHERE $k
     ORDER BY tanggal DESC"
);

foreach ($data as $r) {

    $baris[] = array(
        $r['nama'] ?? '-',
        $r['perusahaan'] ?? '-',
        !empty($r['tanggal']) ? tgl($r['tanggal']) : '-',
        $r['status'] ?? '-'
    );
}

$laporan_detail[] = array(
    'Pendaftaran Pengguna Jasa',
    array('Nama', 'Perusahaan', 'Tanggal', 'Status'),
    $baris
);

// ==========================================
// 4. LAPORAN PENDAFTARAN DISETUJUI
// ==========================================

$baris = array();

$data = ambil_semua(
    $koneksi,
    "SELECT * FROM pendaftaran
     WHERE $k AND status = 'Disetujui'
     ORDER BY tanggal DESC"
);

foreach ($data as $r) {

    $baris[] = array(
        $r['nama'] ?? '-',
        $r['perusahaan'] ?? '-',
        !empty($r['tanggal']) ? tgl($r['tanggal']) : '-'
    );
}

$laporan_detail[] = array(
    'Pendaftaran Disetujui',
    array('Nama', 'Perusahaan', 'Tanggal'),
    $baris
);

// ==========================================
// 5. LAPORAN PENDAFTARAN DITOLAK
// ==========================================

$baris = array();

$data = ambil_semua(
    $koneksi,
    "SELECT * FROM pendaftaran
     WHERE $k AND status = 'Ditolak'
     ORDER BY tanggal DESC"
);

foreach ($data as $r) {

    $baris[] = array(
        $r['nama'] ?? '-',
        $r['perusahaan'] ?? '-',
        !empty($r['tanggal']) ? tgl($r['tanggal']) : '-'
    );
}

$laporan_detail[] = array(
    'Pendaftaran Ditolak',
    array('Nama', 'Perusahaan', 'Tanggal'),
    $baris
);

// ==========================================
// 6. LAPORAN SEMUA ANTREAN
// ==========================================

$baris = array();

$data = ambil_semua(
    $koneksi,
    "SELECT * FROM antrean
     WHERE $k
     ORDER BY tanggal DESC"
);

foreach ($data as $r) {

    $baris[] = array(
        $r['nomor'] ?? '-',
        $r['nama'] ?? '-',
        $r['layanan'] ?? '-',
        !empty($r['tanggal']) ? tgl($r['tanggal']) : '-',
        $r['status'] ?? '-'
    );
}

$laporan_detail[] = array(
    'Data Antrean',
    array('Nomor Antrean', 'Nama', 'Layanan', 'Tanggal', 'Status'),
    $baris
);

// ==========================================
// 7. LAPORAN ANTREAN SELESAI
// ==========================================

$baris = array();

$data = ambil_semua(
    $koneksi,
    "SELECT * FROM antrean
     WHERE $k AND status = 'Selesai'
     ORDER BY tanggal DESC"
);

foreach ($data as $r) {

    $baris[] = array(
        $r['nomor'] ?? '-',
        $r['nama'] ?? '-',
        $r['layanan'] ?? '-',
        !empty($r['tanggal']) ? tgl($r['tanggal']) : '-'
    );
}

$laporan_detail[] = array(
    'Antrean Selesai',
    array('Nomor Antrean', 'Nama', 'Layanan', 'Tanggal'),
    $baris
);

// ==========================================
// 8. FUNGSI CSV
// ==========================================

function buat_csv($kepala, $baris)
{
    $semua = array_merge(array($kepala), $baris);
    $hasil = array();

    foreach ($semua as $b) {

        $kolom = array();

        foreach ($b as $isi) {

            $isi = (string) $isi;

            $kolom[] = '"' . str_replace('"', '""', $isi) . '"';
        }

        $hasil[] = implode(',', $kolom);
    }

    return implode("\n", $hasil);
}

// ==========================================
// 9. UNDUH LAPORAN
// ==========================================

if (
    isset($_GET['unduh']) &&
    isset($laporan_detail[(int) $_GET['unduh']])
) {

    $lap = $laporan_detail[(int) $_GET['unduh']];

    $nama_file = trim(
        preg_replace(
            '/[^a-z0-9]+/',
            '-',
            strtolower($lap[0] . ' ' . $dari . ' sd ' . $sampai)
        ),
        '-'
    ) . '.csv';

    header('Content-Type: text/csv; charset=utf-8');

    header(
        'Content-Disposition: attachment; filename="' .
        $nama_file . '"'
    );

    echo "\xEF\xBB\xBF" . buat_csv($lap[1], $lap[2]);

    exit;
}

// ==========================================
// 10. TAMPILAN
// ==========================================

$judul_halaman = 'Laporan';
$menu_aktif = 'laporan';

$css_tambahan = array(
    'dashboard.css',
    'laporan.css'
);

$js_tambahan = array('laporan.js');

include 'includes/header.php';

?>

<div class="phead">
    <div>
        <h1>Laporan</h1>
        <p>Ringkasan layanan berdasarkan data yang ada di semua halaman</p>
    </div>
</div>

<!-- FILTER TANGGAL -->

<section class="card rfilter">

    <form method="get" style="display:contents">

        <div class="fld">
            <label for="rep-from">Dari tanggal</label>
            <input
                id="rep-from"
                type="date"
                name="dari"
                value="<?= e($dari) ?>"
            >
        </div>

        <div class="fld">
            <label for="rep-to">Sampai tanggal</label>
            <input
                id="rep-to"
                type="date"
                name="sampai"
                value="<?= e($sampai) ?>"
            >
        </div>

        <button type="submit" class="btn">
            <?= ikon('search') ?> Cari
        </button>

        <a href="laporan.php?reset=1" class="btn">
            Reset
        </a>

    </form>

</section>

<!-- KARTU RINGKASAN -->

<section class="sum">

    <?php foreach ($ringkasan as $r) { ?>

        <div class="stat s-<?= e($r[3]) ?>">

            <span class="si">
                <?= ikon($r[2]) ?>
            </span>

            <span class="sb">
                <small><?= e($r[0]) ?></small>
                <b><?= e($r[1]) ?></b>
            </span>

        </div>

    <?php } ?>

</section>

<!-- TABEL LAPORAN DETAIL -->

<section class="card panel">

    <div class="ph">
        <h2>Laporan Detail</h2>
    </div>

    <div class="tw">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis Laporan</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($laporan_detail as $i => $lap) { ?>

                    <tr>

                        <td><?= $i + 1 ?></td>

                        <td>
                            <b><?= e($lap[0]) ?></b>
                        </td>

                        <td>
                            <?= count($lap[2]) ?>
                        </td>

                        <td>

                            <div class="acts">

                                <button
                                    type="button"
                                    class="ab view"
                                    title="Lihat"
                                    aria-label="Lihat"
                                    onclick="bukaIsi('<?= e($lap[0]) ?> (<?= count($lap[2]) ?>)', 'lap-<?= $i ?>')"
                                >
                                    <?= ikon('eye') ?>
                                </button>

                                <button
                                    type="button"
                                    class="ab edit"
                                    title="Unduh"
                                    aria-label="Unduh"
                                    onclick="location.href='laporan.php?unduh=<?= $i ?>&amp;dari=<?= urlencode($dari) ?>&amp;sampai=<?= urlencode($sampai) ?>'"
                                >
                                    <svg
                                        class="ic"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M12 4v11"/>
                                        <path d="M7 11l5 5 5-5"/>
                                        <path d="M5 20h14"/>
                                    </svg>
                                </button>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</section>

<!-- ISI POPUP -->

<?php foreach ($laporan_detail as $i => $lap) { ?>

    <div id="lap-<?= $i ?>" hidden>

        <?php if (count($lap[2]) == 0) { ?>

            <div class="empty">
                Tidak ada data pada periode ini.
            </div>

        <?php } else { ?>

            <div class="tw">

                <table style="min-width:480px">

                    <thead>
                        <tr>

                            <?php foreach ($lap[1] as $kepala) { ?>
                                <th><?= e($kepala) ?></th>
                            <?php } ?>

                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($lap[2] as $baris_data) { ?>

                            <tr>

                                <?php foreach ($baris_data as $sel) { ?>
                                    <td><?= e($sel) ?></td>
                                <?php } ?>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } ?>

    </div>

<?php } ?>

<?php include 'includes/footer.php'; ?>
