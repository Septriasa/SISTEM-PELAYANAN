<?php
include 'config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_status  = array('Menunggu', 'Dipanggil', 'Selesai', 'Di Tolak');
$daftar_layanan = array('Pelayanan Kapal Masuk', 'Surat Izin Berlayar', 'Sertifikat Keselamatan Kapal', 'Pendaftaran Kapal', 'Pemanduan Kapal', 'Laporan Kegiatan Kapal');

// Jumlah antrian per halaman. Kalau sudah 25, antrian ke-26 otomatis pindah ke halaman 2.
define('PER_HALAMAN_ANTRIAN', 25);

// ----- Penyesuaian data lama (aman dijalankan berulang kali) -----
// 1) nomor lama berhuruf (contoh A026) diubah menjadi 4 digit tanpa huruf (0026)
mysqli_query($koneksi, "UPDATE antrean SET nomor = LPAD(SUBSTRING(nomor, 2), 4, '0') WHERE nomor REGEXP '^[A-Za-z]'");
// 2) status lama "Batal" diubah menjadi "Di Tolak"
mysqli_query($koneksi, "UPDATE antrean SET status = 'Di Tolak' WHERE status = 'Batal'");

// Halaman ini hanya menampilkan antrian BULAN INI.
// Antrian bulan sebelumnya otomatis tersimpan sebagai rekap di halaman Laporan.
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

    $html  = '<div class="tfoot"><span class="muted">Menampilkan data ' . $jumlah_di_halaman . '</span><div class="pager">';

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
// Antrian baru TIDAK dibuat dari halaman ini (nanti masuk dari website pengguna).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    // ----- UBAH -----
    if ($aksi == 'simpan' && $id > 0) {
        $nama    = bersih($koneksi, ambil_post('nama'));
        $layanan = ambil_post('layanan');
        $tanggal = bersih($koneksi, ambil_post('tanggal'));
        $status  = ambil_post('status');

        if ($nama == '' || $tanggal == '') {
            set_pesan('Nama pemohon dan tanggal wajib diisi.', 'err');
        } elseif (!in_array($layanan, $daftar_layanan) || !in_array($status, $daftar_status)) {
            set_pesan('Jenis layanan atau status tidak valid.', 'err');
        } else {
            // kalau status yang disimpan "Dipanggil", yang lain diselesaikan dulu
            if ($status == 'Dipanggil') {
                mysqli_query($koneksi, "UPDATE antrean SET status = 'Selesai' WHERE status = 'Dipanggil' AND id <> $id");
            }

            mysqli_query($koneksi, "UPDATE antrean SET nama='$nama', layanan='$layanan', tanggal='$tanggal', status='$status' WHERE id=$id");
            catat_aktivitas($koneksi, 'edit', 'Antrian diperbarui: ' . ambil_post('nama'));
            set_pesan('Antrian berhasil diperbarui.');
        }
    }

    // ----- HAPUS -----
    if ($aksi == 'hapus' && $id > 0) {
        $data = ambil_satu($koneksi, "SELECT nomor FROM antrean WHERE id=$id");
        if ($data) {
            mysqli_query($koneksi, "DELETE FROM antrean WHERE id=$id");
            catat_aktivitas($koneksi, 'trash', 'Antrian dihapus: ' . $data['nomor']);
            set_pesan('Antrian berhasil dihapus.');
        }
    }

    // ----- PANGGIL / SELESAI (tombol cepat di tabel) -----
    if ($aksi == 'status' && $id > 0) {
        $nilai = ambil_post('nilai');
        $data  = ambil_satu($koneksi, "SELECT nomor FROM antrean WHERE id=$id");
        if ($data && ($nilai == 'Dipanggil' || $nilai == 'Selesai')) {
            if ($nilai == 'Dipanggil') {
                selesaikan_yang_dipanggil($koneksi);
                mysqli_query($koneksi, "UPDATE antrean SET status='Dipanggil' WHERE id=$id");
                catat_aktivitas($koneksi, 'ticket', 'Antrian ' . $data['nomor'] . ' sedang dipanggil');
                set_pesan('Antrian ' . $data['nomor'] . ' sedang dipanggil.');
            } else {
                mysqli_query($koneksi, "UPDATE antrean SET status='Selesai' WHERE id=$id");
                catat_aktivitas($koneksi, 'check', 'Antrian ' . $data['nomor'] . ' selesai dilayani');
                set_pesan('Antrian ' . $data['nomor'] . ' selesai dilayani.');
            }
        }
    }

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
$js_tambahan   = array('antrean.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Nomor Antrian</h1>
        <p>Kelola antrian pelayanan bulan ini. Rekap bulan sebelumnya ada di halaman Laporan.</p>
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
                            <?php if ($r['status'] == 'Menunggu') {
                                echo tombol_status($r['id'], 'Dipanggil', 'view', 'Panggil', 'mega');
                            } elseif ($r['status'] == 'Dipanggil') {
                                echo tombol_status($r['id'], 'Selesai', 'ok', 'Tandai selesai', 'check');
                            } ?>
                            <?= tombol_lihat($r, 'lihatAntrian') ?>
                            <?= tombol_ubah($r, 'ubahAntrian') ?>
                            <?= tombol_hapus('antrean.php', $r['id'], $r['nomor'] . ' (' . $r['nama'] . ')') ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?= paging_antrian($halaman, $jumlah_halaman, count($data)) ?>
</section>

<?php include 'includes/footer.php'; ?>
