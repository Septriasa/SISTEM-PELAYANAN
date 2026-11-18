<?php
// ==========================================================
// File: pendaftaran.php
// Fungsi: halaman Data Pendaftaran.
// Fitur : LIHAT, UBAH, HAPUS, dan SETUJUI / TOLAK pendaftaran.
// (Fitur "Tambah Pendaftaran" sengaja tidak ada.)
// ==========================================================
include 'config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_status = array('Menunggu Verifikasi', 'Disetujui', 'Ditolak');


// ========== BAGIAN 1: memproses form (UBAH / HAPUS / STATUS) ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    // ----- UBAH data -----
    if ($aksi == 'simpan' && $id > 0) {
        $nama       = bersih($koneksi, ambil_post('nama'));
        $perusahaan = bersih($koneksi, ambil_post('perusahaan'));
        $email      = bersih($koneksi, ambil_post('email'));
        $telepon    = bersih($koneksi, ambil_post('telepon'));
        $tanggal    = bersih($koneksi, ambil_post('tanggal'));
        $status     = ambil_post('status');
        $catatan    = bersih($koneksi, ambil_post('catatan'));

        // pemeriksaan isian
        if ($nama == '' || $perusahaan == '' || $tanggal == '') {
            set_pesan('Nama, perusahaan, dan tanggal wajib diisi.', 'err');
        } elseif ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_pesan('Format email belum benar.', 'err');
        } elseif (!in_array($status, $daftar_status)) {
            set_pesan('Status tidak valid.', 'err');
        } else {
            mysqli_query($koneksi, "UPDATE pendaftaran SET nama='$nama', perusahaan='$perusahaan', email='$email', telepon='$telepon', tanggal='$tanggal', status='$status', catatan='$catatan' WHERE id=$id");
            catat_aktivitas($koneksi, 'edit', 'Pendaftaran diperbarui: ' . ambil_post('nama'));
            set_pesan('Pendaftaran berhasil diperbarui.');
        }
    }

    // ----- HAPUS data -----
    if ($aksi == 'hapus' && $id > 0) {
        $data = ambil_satu($koneksi, "SELECT nama FROM pendaftaran WHERE id=$id");
        if ($data) {
            mysqli_query($koneksi, "DELETE FROM pendaftaran WHERE id=$id");
            catat_aktivitas($koneksi, 'trash', 'Pendaftaran dihapus: ' . $data['nama']);
            set_pesan('Pendaftaran berhasil dihapus.');
        }
    }

    // ----- SETUJUI / TOLAK (tombol cepat) -----
    if ($aksi == 'status' && $id > 0) {
        $nilai = ambil_post('nilai');
        $data  = ambil_satu($koneksi, "SELECT nama FROM pendaftaran WHERE id=$id");
        if ($data && ($nilai == 'Disetujui' || $nilai == 'Ditolak')) {
            mysqli_query($koneksi, "UPDATE pendaftaran SET status='$nilai' WHERE id=$id");
            if ($nilai == 'Disetujui') {
                catat_aktivitas($koneksi, 'check', 'Pendaftaran ' . $data['nama'] . ' disetujui');
                set_pesan('Pendaftaran ' . $data['nama'] . ' disetujui.');
            } else {
                catat_aktivitas($koneksi, 'x', 'Pendaftaran ' . $data['nama'] . ' ditolak');
                set_pesan('Pendaftaran ' . $data['nama'] . ' ditolak.');
            }
        }
    }

    pindah('pendaftaran.php');
}


// ========== BAGIAN 2: mengambil data untuk ditampilkan ==========
$q      = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';
$status = isset($_GET['status']) ? bersih($koneksi, $_GET['status']) : '';

// menyusun kondisi WHERE sesuai pencarian dan filter
$kondisi = "1=1";
if ($q != '') {
    $kondisi .= " AND (nama LIKE '%$q%' OR perusahaan LIKE '%$q%' OR email LIKE '%$q%' OR telepon LIKE '%$q%' OR status LIKE '%$q%')";
}
if ($status != '') {
    $kondisi .= " AND status = '$status'";
}

$total   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE $kondisi");
$halaman = halaman_sekarang($total);
$mulai   = ($halaman - 1) * PER_HALAMAN;
$data    = ambil_semua($koneksi, "SELECT * FROM pendaftaran WHERE $kondisi ORDER BY tanggal DESC, id DESC LIMIT $mulai, " . PER_HALAMAN);


// ========== BAGIAN 3: tampilan (HTML) ==========
$judul_halaman = 'Data Pendaftaran';
$menu_aktif    = 'pendaftaran';
$css_tambahan  = array('pendaftaran.css');
$js_tambahan   = array('pendaftaran.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Data Pendaftaran</h1>
        <p>Kelola data pendaftaran pengguna jasa</p>
    </div>
</div>

<section class="card tcard">
    <?= kotak_pencarian($daftar_status) ?>

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Nama Pendaftar</th><th>Perusahaan</th><th>Tanggal Daftar</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($data) == 0) { ?>
                <tr><td colspan="6"><div class="empty"><?= ($q != '' || $status != '') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data pendaftaran.' ?></div></td></tr>
            <?php } ?>

            <?php $no = $mulai + 1; foreach ($data as $r) { ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= e($r['nama']) ?></b></td>
                    <td><?= e($r['perusahaan']) ?></td>
                    <td><?= tgl($r['tanggal']) ?></td>
                    <td><?= pill($r['status']) ?></td>
                    <td>
                        <div class="acts">
                            <?php if ($r['status'] == 'Menunggu Verifikasi') {
                                echo tombol_status($r['id'], 'Disetujui', 'ok', 'Setujui', 'check');
                                echo tombol_status($r['id'], 'Ditolak', 'no', 'Tolak', 'x');
                            } ?>
                            <?= tombol_lihat($r, 'lihatPendaftaran') ?>
                            <?= tombol_ubah($r, 'ubahPendaftaran') ?>
                            <?= tombol_hapus('pendaftaran.php', $r['id'], $r['nama']) ?>
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
