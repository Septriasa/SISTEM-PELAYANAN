<?php
include 'assets/config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_status  = array('Menunggu', 'Dipanggil', 'Selesai', 'Di Tolak');
// Jenis layanan (keperluan). Sama dengan pilihan keperluan di website pengguna.
$daftar_layanan = array('Masa Layar', 'Sign On/Off', 'Perpanjang', 'Buku Baru');

// Jumlah antrian per halaman. Kalau sudah 10, antrian ke-11 otomatis pindah ke halaman 2.
define('PER_HALAMAN_ANTRIAN', 10);

// ----- Penyesuaian data lama (aman dijalankan berulang kali) -----
// 1) nomor lama berhuruf (contoh A026) diubah menjadi 4 digit tanpa huruf (0026)
mysqli_query($koneksi, "UPDATE antrean SET nomor = LPAD(SUBSTRING(nomor, 2), 4, '0') WHERE nomor REGEXP '^[A-Za-z]'");
// 2) status lama "Batal" diubah menjadi "Di Tolak"
mysqli_query($koneksi, "UPDATE antrean SET status = 'Di Tolak' WHERE status = 'Batal'");
// 3) nama layanan lama (data contoh) disesuaikan ke jenis layanan yang baru
$layanan_lama = array(
    'Pelayanan Kapal Masuk'        => $daftar_layanan[0],
    'Surat Izin Berlayar'          => $daftar_layanan[1],
    'Sertifikat Keselamatan Kapal' => $daftar_layanan[2],
    'Pendaftaran Kapal'            => $daftar_layanan[3],
    'Pemanduan Kapal'              => $daftar_layanan[0],
    'Laporan Kegiatan Kapal'       => $daftar_layanan[1]
);
$case_layanan = '';
$daftar_lama  = array();
foreach ($layanan_lama as $lama => $baru) {
    $case_layanan .= " WHEN '" . bersih($koneksi, $lama) . "' THEN '" . bersih($koneksi, $baru) . "'";
    $daftar_lama[] = "'" . bersih($koneksi, $lama) . "'";
}
mysqli_query($koneksi, "UPDATE antrean SET layanan = CASE layanan $case_layanan ELSE layanan END WHERE layanan IN (" . implode(',', $daftar_lama) . ")");

// Halaman ini hanya menampilkan antrian BULAN INI.
// Antrian bulan sebelumnya yang sudah dipanggil otomatis masuk rekap bulanan di halaman Laporan.
$bulan_ini     = date('Y-m');
$kondisi_bulan = "DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";

// Fungsi kecil: antrian yang sedang dipanggil otomatis jadi "Selesai"
// (dipanggil sebelum ada antrian baru yang dipanggil)
function selesaikan_yang_dipanggil($koneksi)
{
    mysqli_query($koneksi, "UPDATE antrean SET status = 'Selesai' WHERE status = 'Dipanggil'");
}

// Bagian bawah tabel: tulisan "Menampilkan data ..." dan tombol halaman
function paging_antrian($halaman, $jumlah_halaman, $jumlah_di_halaman)
{
    // tampilkan paling banyak 5 nomor halaman
    $awal  = max(1, $halaman - 2);
    $akhir = min($jumlah_halaman, $awal + 4);
    $awal  = max(1, $akhir - 4);

    $html  = '<div class="tfoot"><span class="muted">Menampilkan ' . $jumlah_di_halaman . ' data</span><div class="pager">';

    if ($halaman <= 1) {
        $html .= '<a class="mati" aria-label="Sebelumnya">' . ikon('left') . '</a>';
    } else {
        $html .= '<a href="' . e(link_halaman($halaman - 1)) . '" aria-label="Sebelumnya">' . ikon('left') . '</a>';
    }

    for ($p = $awal; $p <= $akhir; $p++) {
        $kelas = ($p == $halaman) ? 'cur' : '';
        $html .= '<a class="' . $kelas . '" href="' . e(link_halaman($p)) . '">' . $p . '</a>';
    }

    if ($halaman >= $jumlah_halaman) {
        $html .= '<a class="mati" aria-label="Berikutnya">' . ikon('right') . '</a>';
    } else {
        $html .= '<a href="' . e(link_halaman($halaman + 1)) . '" aria-label="Berikutnya">' . ikon('right') . '</a>';
    }

    $html .= '</div></div>';
    return $html;
}


// ========== BAGIAN 1: memproses form ==========
// Halaman ini hanya untuk memanggil dan melihat antrian (tidak ada ubah / hapus).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');

    // ----- PANGGIL BERIKUTNYA (tombol besar di kartu) -----
    if ($aksi == 'berikutnya') {
        $berikut = ambil_satu($koneksi, "SELECT * FROM antrean WHERE status = 'Menunggu' AND $kondisi_bulan ORDER BY id ASC LIMIT 1");
        if (!$berikut) {
            set_pesan('Tidak ada antrian yang menunggu.', 'err');
        } else {
            selesaikan_yang_dipanggil($koneksi);
            mysqli_query($koneksi, "UPDATE antrean SET status='Dipanggil' WHERE id=" . (int)$berikut['id']);
            catat_aktivitas($koneksi, 'ticket', 'Nomor antrian ' . $berikut['nomor'] . ' sedang dipanggil');
            set_pesan('Memanggil ' . $berikut['nomor'] . ' (' . $berikut['nama'] . ').');
        }
    }

    pindah('antrean.php');
}


// ========== BAGIAN 2: mengambil data ==========

// nomor yang sedang dipanggil (untuk kartu di atas)
$sedang_dipanggil = ambil_satu($koneksi, "SELECT * FROM antrean WHERE status = 'Dipanggil' AND $kondisi_bulan ORDER BY id DESC LIMIT 1");

// data untuk tabel (hanya bulan ini, bisa difilter status)
$status = isset($_GET['status']) ? bersih($koneksi, $_GET['status']) : '';

$kondisi = $kondisi_bulan;
if ($status != '') {
    $kondisi .= " AND status = '$status'";
}

$total          = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM antrean WHERE $kondisi");
$jumlah_halaman = max(1, (int)ceil($total / PER_HALAMAN_ANTRIAN));
$halaman        = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$halaman        = min($jumlah_halaman, max(1, $halaman));
$mulai          = ($halaman - 1) * PER_HALAMAN_ANTRIAN;

// urutan dari nomor terkecil: nomor 1-25 di halaman 1, nomor 26 dst di halaman 2
$data = ambil_semua($koneksi, "SELECT * FROM antrean WHERE $kondisi ORDER BY id ASC LIMIT $mulai, " . PER_HALAMAN_ANTRIAN);


// ========== BAGIAN 3: tampilan (HTML) ==========
$judul_halaman = 'Nomor Antrian';
$menu_aktif    = 'antrean';
$css_tambahan  = array('antrean.css');
$js_tambahan   = array();   // popup LIHAT ditulis langsung di bagian bawah halaman ini
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Nomor Antrian</h1>
        <p>Antrian pelayanan bulan ini. Rekap bulan sebelumnya otomatis masuk ke halaman Laporan.</p>
    </div>
</div>

<!-- ============ KARTU NOMOR ANTRIAN SAAT INI (di tengah) ============ -->
<section class="card qcard">
    <div class="qnum">
        <span class="qlbl"><?= ikon('ticket') ?>Nomor Antrian Saat Ini</span>
        <strong><?= $sedang_dipanggil ? e($sedang_dipanggil['nomor']) : '—' ?></strong>
        <span class="qsub"><?= $sedang_dipanggil ? e($sedang_dipanggil['nama']) . ' · ' . e($sedang_dipanggil['layanan']) : 'Belum ada yang dipanggil' ?></span>
    </div>
    <div class="qbtns">
        <form method="post">
            <input type="hidden" name="aksi" value="berikutnya">
            <button class="btn" type="submit"><?= ikon('mega') ?>Panggil Berikutnya</button>
        </form>
    </div>
</section>

<section class="card tcard">
    <form class="toolbar" method="get">
        <select class="sel" name="status" onchange="this.form.submit()" aria-label="Filter status">
            <option value="">Semua Status</option>
            <?php foreach ($daftar_status as $s) { ?>
                <option<?= ($status == $s) ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php } ?>
        </select>
    </form>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Nomor</th><th>Nama Pemohon</th><th>Jenis Layanan</th><th>Tanggal</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($data) == 0) { ?>
                <tr><td colspan="7"><div class="empty"><?= ($status != '') ? 'Tidak ada data yang cocok dengan filter.' : 'Belum ada antrian bulan ini.' ?></div></td></tr>
            <?php } ?>

            <?php $no = $mulai + 1; foreach ($data as $r) { ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= e($r['nomor']) ?></b></td>
                    <td><?= e($r['nama']) ?></td>
                    <td><?= e($r['layanan']) ?></td>
                    <td><?= tgl($r['tanggal']) ?></td>
                    <td><?= pill($r['status']) ?></td>
                    <td>
                        <div class="acts">
                            <?= tombol_lihat($r, 'lihatDetailAntrian') ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?= paging_antrian($halaman, $jumlah_halaman, count($data)) ?>
</section>

<script>
// Popup LIHAT (ikon mata): hanya ada tombol "Tutup".
// Tidak ada tombol "Ubah" karena halaman ini hanya untuk memanggil antrian.
function lihatDetailAntrian(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Antrian', [
        ['Nomor Antrian', d.nomor],
        ['Nama Pemohon',  d.nama],
        ['Jenis Layanan', d.layanan],
        ['Tanggal',       tgl(d.tanggal)],
        ['Status',        pill(d.status), true]
    ]);
}
</script>

<?php include 'includes/footer.php'; ?>