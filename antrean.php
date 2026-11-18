<?php
include 'config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_status  = array('Menunggu', 'Dipanggil', 'Selesai', 'Batal');
$daftar_layanan = array('Pelayanan Kapal Masuk', 'Surat Izin Berlayar', 'Sertifikat Keselamatan Kapal', 'Pendaftaran Kapal', 'Pemanduan Kapal', 'Laporan Kegiatan Kapal');

// Fungsi kecil: antrean yang sedang dipanggil otomatis jadi "Selesai"
// (dipanggil sebelum ada antrean baru yang dipanggil)
function selesaikan_yang_dipanggil($koneksi)
{
    mysqli_query($koneksi, "UPDATE antrean SET status = 'Selesai' WHERE status = 'Dipanggil'");
}


// ========== BAGIAN 1: memproses form ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    // ----- TAMBAH atau UBAH -----
    if ($aksi == 'simpan') {
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

            if ($id > 0) {
                mysqli_query($koneksi, "UPDATE antrean SET nama='$nama', layanan='$layanan', tanggal='$tanggal', status='$status' WHERE id=$id");
                catat_aktivitas($koneksi, 'edit', 'Antrean diperbarui: ' . ambil_post('nama'));
                set_pesan('Antrean berhasil diperbarui.');
            } else {
                $nomor = nomor_antrean($koneksi);   // contoh: A027
                mysqli_query($koneksi, "INSERT INTO antrean (nomor, nama, layanan, tanggal, status) VALUES ('$nomor', '$nama', '$layanan', '$tanggal', '$status')");
                catat_aktivitas($koneksi, 'plus', 'Antrean ditambahkan: ' . $nomor . ' (' . ambil_post('nama') . ')');
                set_pesan('Antrean berhasil ditambahkan.');
            }
        }
    }

    // ----- HAPUS -----
    if ($aksi == 'hapus' && $id > 0) {
        $data = ambil_satu($koneksi, "SELECT nomor FROM antrean WHERE id=$id");
        if ($data) {
            mysqli_query($koneksi, "DELETE FROM antrean WHERE id=$id");
            catat_aktivitas($koneksi, 'trash', 'Antrean dihapus: ' . $data['nomor']);
            set_pesan('Antrean berhasil dihapus.');
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
                catat_aktivitas($koneksi, 'ticket', 'Antrean ' . $data['nomor'] . ' sedang dipanggil');
                set_pesan('Antrean ' . $data['nomor'] . ' sedang dipanggil.');
            } else {
                mysqli_query($koneksi, "UPDATE antrean SET status='Selesai' WHERE id=$id");
                catat_aktivitas($koneksi, 'check', 'Antrean ' . $data['nomor'] . ' selesai dilayani');
                set_pesan('Antrean ' . $data['nomor'] . ' selesai dilayani.');
            }
        }
    }

    // ----- PANGGIL BERIKUTNYA (tombol besar di kartu) -----
    if ($aksi == 'berikutnya') {
        $berikut = ambil_satu($koneksi, "SELECT * FROM antrean WHERE status = 'Menunggu' ORDER BY id ASC LIMIT 1");
        if (!$berikut) {
            set_pesan('Tidak ada antrean yang menunggu.', 'err');
        } else {
            selesaikan_yang_dipanggil($koneksi);
            mysqli_query($koneksi, "UPDATE antrean SET status='Dipanggil' WHERE id=" . (int)$berikut['id']);
            catat_aktivitas($koneksi, 'ticket', 'Nomor antrean ' . $berikut['nomor'] . ' sedang dipanggil');
            set_pesan('Memanggil ' . $berikut['nomor'] . ' (' . $berikut['nama'] . ').');
        }
    }

    pindah('antrean.php');
}


// ========== BAGIAN 2: mengambil data ==========
$hari_ini = date('Y-m-d');

// data untuk kartu nomor antrean di atas
$sedang_dipanggil = ambil_satu($koneksi, "SELECT * FROM antrean WHERE status = 'Dipanggil' LIMIT 1");
$jml_menunggu     = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM antrean WHERE status = 'Menunggu'");
$jml_selesai      = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM antrean WHERE status = 'Selesai' AND tanggal = '$hari_ini'");

// data untuk tabel
$q      = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';
$status = isset($_GET['status']) ? bersih($koneksi, $_GET['status']) : '';

$kondisi = "1=1";
if ($q != '') {
    $kondisi .= " AND (nomor LIKE '%$q%' OR nama LIKE '%$q%' OR layanan LIKE '%$q%' OR status LIKE '%$q%')";
}
if ($status != '') {
    $kondisi .= " AND status = '$status'";
}

$total   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM antrean WHERE $kondisi");
$halaman = halaman_sekarang($total);
$mulai   = ($halaman - 1) * PER_HALAMAN;

// urutan: Dipanggil dulu, lalu Menunggu (yang paling lama di atas), lalu sisanya (yang terbaru di atas)
$urutan = "CASE status WHEN 'Dipanggil' THEN 0 WHEN 'Menunggu' THEN 1 ELSE 2 END, CASE WHEN status IN ('Dipanggil','Menunggu') THEN id ELSE 1000000 - id END";
$data   = ambil_semua($koneksi, "SELECT * FROM antrean WHERE $kondisi ORDER BY $urutan LIMIT $mulai, " . PER_HALAMAN);

$nomor_baru = nomor_antrean($koneksi);


// ========== BAGIAN 3: tampilan (HTML) ==========
$judul_halaman = 'Nomor Antrean';
$menu_aktif    = 'antrean';
$css_tambahan  = array('antrean.css');
$js_tambahan   = array('antrean.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Nomor Antrean</h1>
        <p>Kelola antrean pelayanan hari ini</p>
    </div>
</div>

<!-- ============ KARTU NOMOR ANTREAN SAAT INI ============ -->
<section class="card qcard">
    <div class="qnum">
        <span class="qlbl"><?= ikon('ticket') ?>Nomor Antrean Saat Ini</span>
        <strong><?= $sedang_dipanggil ? e($sedang_dipanggil['nomor']) : '—' ?></strong>
        <span class="qsub"><?= $sedang_dipanggil ? e($sedang_dipanggil['nama']) . ' · ' . e($sedang_dipanggil['layanan']) : 'Belum ada yang dipanggil' ?></span>
    </div>
    <div class="qmeta">
        <div><b><?= $jml_menunggu ?></b><span>Menunggu</span></div>
        <div><b><?= $jml_selesai ?></b><span>Selesai hari ini</span></div>
    </div>
    <div class="qbtns">
        <form method="post">
            <input type="hidden" name="aksi" value="berikutnya">
            <button class="btn" type="submit"><?= ikon('mega') ?>Panggil Berikutnya</button>
        </form>
        <button type="button" class="btn ghost" onclick="tambahAntrean('<?= $nomor_baru ?>')"><?= ikon('plus') ?>Buat Antrean Baru</button>
    </div>
</section>

<section class="card tcard">
    <?= kotak_pencarian($daftar_status) ?>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Nomor</th><th>Nama Pemohon</th><th>Jenis Layanan</th><th>Tanggal</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($data) == 0) { ?>
                <tr><td colspan="7"><div class="empty"><?= ($q != '' || $status != '') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada antrean.' ?></div></td></tr>
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
                            <?= tombol_lihat($r, 'lihatAntrean') ?>
                            <?= tombol_ubah($r, 'ubahAntrean') ?>
                            <?= tombol_hapus('antrean.php', $r['id'], $r['nomor'] . ' (' . $r['nama'] . ')') ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?= buat_paging($total, $halaman, count($data)) ?>
</section>

<?php include 'includes/footer.php'; ?>
