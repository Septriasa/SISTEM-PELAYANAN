
<?php
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
        $nama          = bersih($koneksi, ambil_post('nama'));
        $Jenis_Layanan = bersih($koneksi, ambil_post('Jenis_Layanan'));
        $email         = bersih($koneksi, ambil_post('email'));
        $telepon       = bersih($koneksi, ambil_post('telepon'));
        $tanggal       = bersih($koneksi, ambil_post('tanggal'));
        $status        = ambil_post('status');
        $catatan       = bersih($koneksi, ambil_post('catatan'));

        if ($nama == '' || $Jenis_Layanan == '' || $tanggal == '') {
            set_pesan('Nama, jenis layanan, dan tanggal wajib diisi.', 'err');

        } elseif ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_pesan('Format email belum benar.', 'err');

        } elseif (!in_array($status, $daftar_status)) {
            set_pesan('Status tidak valid.', 'err');

        } else {
            mysqli_query($koneksi, "UPDATE pendaftaran SET
                nama='$nama',
                Jenis_Layanan='$Jenis_Layanan',
                email='$email',
                telepon='$telepon',
                tanggal='$tanggal',
                status='$status',
                catatan='$catatan'
                WHERE id=$id");

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


    // ----- SETUJUI / TOLAK + NOMOR ANTREAN OTOMATIS -----
    if ($aksi == 'status' && $id > 0) {
        $nilai = ambil_post('nilai');

        $data = ambil_satu(
            $koneksi,
            "SELECT nama, status, tanggal, Nomor_Antrian
             FROM pendaftaran
             WHERE id=$id"
        );

        if (
            $data &&
            $data['status'] == 'Menunggu Verifikasi' &&
            in_array($nilai, array('Disetujui', 'Ditolak'))
        ) {

            // ----- JIKA DISETUJUI -----
            if ($nilai == 'Disetujui') {

                // Mengambil bulan dan tahun pendaftaran
                $bulan = date('Y-m', strtotime($data['tanggal']));

                // Membuat nomor antrean jika belum ada
                if (empty($data['Nomor_Antrian'])) {

                    // Mengambil nomor antrean terakhir pada bulan tersebut
                    $hasil_nomor = ambil_satu(
                        $koneksi,
                        "SELECT MAX(CAST(Nomor_Antrian AS UNSIGNED)) AS terakhir
                         FROM pendaftaran
                         WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'
                         AND Nomor_Antrian IS NOT NULL
                         AND Nomor_Antrian != ''"
                    );

                    $nomor_baru = (int)($hasil_nomor['terakhir'] ?? 0) + 1;

                    // Format nomor menjadi 0001, 0002, dan seterusnya
                    $nomor_antrean_baru = str_pad(
                        $nomor_baru,
                        4,
                        '0',
                        STR_PAD_LEFT
                    );

                    mysqli_query(
                        $koneksi,
                        "UPDATE pendaftaran
                         SET status='Disetujui',
                             Nomor_Antrian='$nomor_antrean_baru'
                         WHERE id=$id"
                    );

                } else {

                    // Jika nomor sudah ada, pertahankan nomor sebelumnya
                    mysqli_query(
                        $koneksi,
                        "UPDATE pendaftaran
                         SET status='Disetujui'
                         WHERE id=$id"
                    );
                }

                catat_aktivitas(
                    $koneksi,
                    'check',
                    'Pendaftaran ' . $data['nama'] . ' disetujui'
                );

                set_pesan('Pendaftaran ' . $data['nama'] . ' disetujui.');

            }

            // ----- JIKA DITOLAK -----
            else {

                mysqli_query(
                    $koneksi,
                    "UPDATE pendaftaran
                     SET status='Ditolak'
                     WHERE id=$id"
                );

                catat_aktivitas(
                    $koneksi,
                    'x',
                    'Pendaftaran ' . $data['nama'] . ' ditolak'
                );

                set_pesan('Pendaftaran ' . $data['nama'] . ' ditolak.');
            }
        }
    }

    pindah('pendaftaran.php');
}


// ========== BAGIAN 2: mengambil data untuk ditampilkan ==========

$q = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';

$status = isset($_GET['status'])
    ? bersih($koneksi, $_GET['status'])
    : '';


// menyusun kondisi WHERE sesuai pencarian dan filter
$kondisi = "1=1";

if ($q != '') {
    $kondisi .= " AND (
        nama LIKE '%$q%'
        OR Jenis_Layanan LIKE '%$q%'
        OR Nomor_Antrian LIKE '%$q%'
        OR email LIKE '%$q%'
        OR telepon LIKE '%$q%'
        OR status LIKE '%$q%'
    )";
}

if ($status != '') {
    $kondisi .= " AND status = '$status'";
}


// menghitung jumlah data
$total = hitung(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE $kondisi"
);

$halaman = halaman_sekarang($total);
$mulai   = ($halaman - 1) * PER_HALAMAN;


// mengambil data pendaftaran
$data = ambil_semua(
    $koneksi,
    "SELECT * FROM pendaftaran
    WHERE $kondisi
    ORDER BY tanggal DESC, id DESC
    LIMIT $mulai, " . PER_HALAMAN
);


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
                    <th>No</th>
                    <th>Nama Pendaftar</th>
                    <th>Jenis Layanan</th>
                    <th>Nomor Antrean</th>
                    <th>Tanggal Daftar</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php if (count($data) == 0) { ?>

                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <?= ($q != '' || $status != '')
                                ? 'Tidak ada data yang cocok dengan pencarian.'
                                : 'Belum ada data pendaftaran.' ?>
                        </div>
                    </td>
                </tr>

            <?php } ?>


            <?php
            $no = $mulai + 1;

            foreach ($data as $r) {
            ?>

                <tr>
                    <td><?= $no++ ?></td>

                    <td>
                        <b><?= e($r['nama']) ?></b>
                    </td>

                    <td>
                        <?= e($r['Jenis_Layanan']) ?>
                    </td>

                    <td>
                        <?php if (!empty($r['Nomor_Antrian'])) { ?>
                            <b><?= e($r['Nomor_Antrian']) ?></b>
                        <?php } else { ?>
                            <span style="color:#999;">-</span>
                        <?php } ?>
                    </td>

                    <td>
                        <?= tgl($r['tanggal']) ?>
                    </td>

                    <td>
                        <?= pill($r['status']) ?>
                    </td>

                    <td>
                        <div class="acts">

                            <?php if ($r['status'] == 'Menunggu Verifikasi') { ?>

                                <?= tombol_status(
                                    $r['id'],
                                    'Disetujui',
                                    'ok',
                                    'Setujui',
                                    'check'
                                ) ?>

                                <?= tombol_status(
                                    $r['id'],
                                    'Ditolak',
                                    'no',
                                    'Tolak',
                                    'x'
                                ) ?>

                            <?php } ?>

                            <?= tombol_lihat($r, 'lihatPendaftaran') ?>

                            <?= tombol_ubah($r, 'ubahPendaftaran') ?>

                            <?= tombol_hapus(
                                'pendaftaran.php',
                                $r['id'],
                                $r['nama']
                            ) ?>

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
