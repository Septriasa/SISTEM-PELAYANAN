<?php
// ==========================================================
// File: permohonan.php
// Fungsi: halaman Data Permohonan.
// Fitur : TAMBAH, LIHAT, UBAH, HAPUS, dan SETUJUI / TOLAK.
// ==========================================================
include 'config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_status  = array('Diproses', 'Disetujui', 'Ditolak');
$daftar_layanan = array('Pelayanan Kapal Masuk', 'Surat Izin Berlayar', 'Sertifikat Keselamatan Kapal', 'Pendaftaran Kapal', 'Pemanduan Kapal', 'Laporan Kegiatan Kapal');


// ========== BAGIAN 1: memproses form (TAMBAH / UBAH / HAPUS / STATUS) ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    // ----- TAMBAH atau UBAH -----
    if ($aksi == 'simpan') {
        $pemohon    = bersih($koneksi, ambil_post('pemohon'));
        $layanan    = ambil_post('layanan');
        $kapal      = bersih($koneksi, ambil_post('kapal'));
        $gt         = ambil_post('gt');
        $tanggal    = bersih($koneksi, ambil_post('tanggal'));
        $status     = ambil_post('status');
        $keterangan = bersih($koneksi, ambil_post('keterangan'));

        // pemeriksaan isian
        if ($pemohon == '' || $kapal == '' || $tanggal == '') {
            set_pesan('Nama pemohon, nama kapal, dan tanggal wajib diisi.', 'err');
        } elseif ($gt != '' && !((int)$gt > 0)) {
            set_pesan('Tonase kapal harus berupa angka lebih dari 0.', 'err');
        } elseif (!in_array($layanan, $daftar_layanan) || !in_array($status, $daftar_status)) {
            set_pesan('Jenis layanan atau status tidak valid.', 'err');
        } else {
            $gt_sql = ($gt == '') ? 'NULL' : (int)$gt;

            if ($id > 0) {
                // UBAH data yang sudah ada
                mysqli_query($koneksi, "UPDATE permohonan SET pemohon='$pemohon', layanan='$layanan', kapal='$kapal', gt=$gt_sql, tanggal='$tanggal', status='$status', keterangan='$keterangan' WHERE id=$id");
                catat_aktivitas($koneksi, 'edit', 'Permohonan diperbarui: ' . ambil_post('pemohon'));
                set_pesan('Permohonan berhasil diperbarui.');
            } else {
                // TAMBAH data baru. Nomor dibuat otomatis (PMH-0001, dst)
                $nomor = nomor_permohonan($koneksi);
                mysqli_query($koneksi, "INSERT INTO permohonan (nomor, pemohon, layanan, kapal, gt, tanggal, status, keterangan) VALUES ('$nomor', '$pemohon', '$layanan', '$kapal', $gt_sql, '$tanggal', '$status', '$keterangan')");
                catat_aktivitas($koneksi, 'plus', 'Permohonan ditambahkan: ' . $nomor . ' (' . ambil_post('pemohon') . ')');
                set_pesan('Permohonan berhasil ditambahkan.');
            }
        }
    }

    // ----- HAPUS -----
    if ($aksi == 'hapus' && $id > 0) {
        $data = ambil_satu($koneksi, "SELECT nomor FROM permohonan WHERE id=$id");
        if ($data) {
            mysqli_query($koneksi, "DELETE FROM permohonan WHERE id=$id");
            catat_aktivitas($koneksi, 'trash', 'Permohonan dihapus: ' . $data['nomor']);
            set_pesan('Permohonan berhasil dihapus.');
        }
    }

    // ----- SETUJUI / TOLAK (tombol cepat) -----
    if ($aksi == 'status' && $id > 0) {
        $nilai = ambil_post('nilai');
        $data  = ambil_satu($koneksi, "SELECT nomor FROM permohonan WHERE id=$id");
        if ($data && ($nilai == 'Disetujui' || $nilai == 'Ditolak')) {
            mysqli_query($koneksi, "UPDATE permohonan SET status='$nilai' WHERE id=$id");
            if ($nilai == 'Disetujui') {
                catat_aktivitas($koneksi, 'check', 'Permohonan ' . $data['nomor'] . ' disetujui');
                set_pesan('Permohonan ' . $data['nomor'] . ' disetujui.');
            } else {
                catat_aktivitas($koneksi, 'x', 'Permohonan ' . $data['nomor'] . ' ditolak');
                set_pesan('Permohonan ' . $data['nomor'] . ' ditolak.');
            }
        }
    }

    pindah('permohonan.php');
}


// ========== BAGIAN 2: mengambil data untuk ditampilkan ==========
$q      = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';
$status = isset($_GET['status']) ? bersih($koneksi, $_GET['status']) : '';

$kondisi = "1=1";
if ($q != '') {
    $kondisi .= " AND (nomor LIKE '%$q%' OR pemohon LIKE '%$q%' OR layanan LIKE '%$q%' OR kapal LIKE '%$q%' OR status LIKE '%$q%')";
}
if ($status != '') {
    $kondisi .= " AND status = '$status'";
}

$total   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM permohonan WHERE $kondisi");
$halaman = halaman_sekarang($total);
$mulai   = ($halaman - 1) * PER_HALAMAN;
$data    = ambil_semua($koneksi, "SELECT * FROM permohonan WHERE $kondisi ORDER BY tanggal DESC, id DESC LIMIT $mulai, " . PER_HALAMAN);

// nomor yang akan dipakai kalau menambah data baru (ditampilkan di form)
$nomor_baru = nomor_permohonan($koneksi);


// ========== BAGIAN 3: tampilan (HTML) ==========
$judul_halaman = 'Data Permohonan';
$menu_aktif    = 'permohonan';
$css_tambahan  = array('permohonan.css');
$js_tambahan   = array('permohonan.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Data Permohonan</h1>
        <p>Kelola permohonan layanan kapal dan pelayaran</p>
    </div>
    <div>
        <button type="button" class="btn" onclick="tambahPermohonan('<?= $nomor_baru ?>')"><?= ikon('plus') ?>Tambah Permohonan</button>
    </div>
</div>

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
                <tr><td colspan="7"><div class="empty"><?= ($q != '' || $status != '') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data permohonan.' ?></div></td></tr>
            <?php } ?>

            <?php $no = $mulai + 1; foreach ($data as $r) { ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($r['nomor']) ?></td>
                    <td><b><?= e($r['pemohon']) ?></b></td>
                    <td><?= e($r['layanan']) ?><small><?= e($r['kapal']) ?><?= $r['gt'] ? ' · ' . e($r['gt']) . ' GT' : '' ?></small></td>
                    <td><?= tgl($r['tanggal']) ?></td>
                    <td><?= pill($r['status']) ?></td>
                    <td>
                        <div class="acts">
                            <?php if ($r['status'] == 'Diproses') {
                                echo tombol_status($r['id'], 'Disetujui', 'ok', 'Setujui', 'check');
                                echo tombol_status($r['id'], 'Ditolak', 'no', 'Tolak', 'x');
                            } ?>
                            <?= tombol_lihat($r, 'lihatPermohonan') ?>
                            <?= tombol_ubah($r, 'ubahPermohonan') ?>
                            <?= tombol_hapus('permohonan.php', $r['id'], $r['nomor'] . ' (' . $r['pemohon'] . ')') ?>
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
