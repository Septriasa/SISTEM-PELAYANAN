<?php
include 'assets/config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_peran   = array('Super Admin', 'Operator');
$daftar_status  = array('Aktif', 'Nonaktif');


// BAGIAN 1: proses POST (tambah, ubah, hapus)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    if ($aksi == 'simpan') {
        $nama     = bersih($koneksi, ambil_post('nama'));
        $username = bersih($koneksi, ambil_post('username'));
        $email    = bersih($koneksi, ambil_post('email'));
        $password = ambil_post('password');    
        $peran    = ambil_post('peran');
        $status   = ambil_post('status');

        // cek apakah username sudah dipakai admin lain
        $sudah_ada = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM admin WHERE username = '$username' AND id <> $id");

        if ($nama == '' || $username == '') {
            set_pesan('Nama dan username wajib diisi.', 'err');
        } elseif ($sudah_ada > 0) {
            set_pesan('Username sudah dipakai admin lain.', 'err');
        } elseif ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_pesan('Format email belum benar.', 'err');
        } elseif ($id == 0 && $password == '') {
            set_pesan('Kata sandi wajib diisi untuk admin baru.', 'err');
        } elseif ($password != '' && strlen($password) < 6) {
            set_pesan('Kata sandi minimal 6 karakter.', 'err');
        } elseif (!in_array($peran, $daftar_peran) || !in_array($status, $daftar_status)) {
            set_pesan('Peran atau status tidak valid.', 'err');
        } elseif ($id == $admin_login['id'] && $status == 'Nonaktif') {
            set_pesan('Akun yang sedang dipakai tidak bisa dinonaktifkan.', 'err');
        } else {
            if ($id > 0) {
                // UBAH. Password hanya diganti kalau diisi.
                $sql_password = '';
                if ($password != '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql_password = ", password='$hash'";
                }
                mysqli_query($koneksi, "UPDATE admin SET nama='$nama', username='$username', email='$email', peran='$peran', status='$status' $sql_password WHERE id=$id");
                catat_aktivitas($koneksi, 'edit', 'Admin diperbarui: ' . ambil_post('nama'));
                set_pesan('Admin berhasil diperbarui.');
            } else {
                // TAMBAH
                $hash = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($koneksi, "INSERT INTO admin (nama, username, email, password, peran, status) VALUES ('$nama', '$username', '$email', '$hash', '$peran', '$status')");
                catat_aktivitas($koneksi, 'plus', 'Admin ditambahkan: ' . ambil_post('nama'));
                set_pesan('Admin berhasil ditambahkan.');
            }
        }
    }

    // HAPUS
    if ($aksi == 'hapus' && $id > 0) {
        $data = ambil_satu($koneksi, "SELECT nama FROM admin WHERE id=$id");
        $jumlah_admin = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM admin");

        if (!$data) {
            set_pesan('Data admin tidak ditemukan.', 'err');
        } elseif ($id == $admin_login['id']) {
            set_pesan('Akun yang sedang dipakai tidak bisa dihapus.', 'err');
        } elseif ($jumlah_admin <= 1) {
            set_pesan('Minimal harus ada satu admin.', 'err');
        } else {
            mysqli_query($koneksi, "DELETE FROM admin WHERE id=$id");
            catat_aktivitas($koneksi, 'trash', 'Admin dihapus: ' . $data['nama']);
            set_pesan('Admin berhasil dihapus.');
        }
    }

    pindah('admin.php');
}

// BAGIAN 2: ambil data untuk ditampilkan
$q = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';

$kondisi = "1=1";
if ($q != '') {
    $kondisi .= " AND (nama LIKE '%$q%' OR username LIKE '%$q%' OR email LIKE '%$q%' OR peran LIKE '%$q%')";
}

$total   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM admin WHERE $kondisi");
$halaman = halaman_sekarang($total);
$mulai   = ($halaman - 1) * PER_HALAMAN;
$data    = ambil_semua($koneksi, "SELECT id, nama, username, email, peran, status FROM admin WHERE $kondisi ORDER BY id ASC LIMIT $mulai, " . PER_HALAMAN);
// catatan: kolom password sengaja tidak diambil


// ========== BAGIAN 3: tampilan (HTML) ==========
$judul_halaman = 'Kelola Admin';
$menu_aktif    = 'admin';
$css_tambahan  = array('admin.css');
$js_tambahan   = array('admin.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Kelola Admin</h1>
        <p>Tambah, ubah, atau hapus akun yang bisa masuk ke dashboard</p>
    </div>
    <div>
        <button type="button" class="btn" onclick="tambahAdmin()"><?= ikon('plus') ?>Tambah Admin</button>
    </div>
</div>

<section class="card tcard">
    <?= kotak_pencarian(array()) ?>

    <div class="tw">
        <table>
            <thead>
                <tr><th>No</th><th>Nama</th><th>Username</th><th>Email</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (count($data) == 0) { ?>
                <tr><td colspan="7"><div class="empty"><?= ($q != '') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada admin.' ?></div></td></tr>
            <?php } ?>

            <?php $no = $mulai + 1; foreach ($data as $r) { ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= e($r['nama']) ?></b><?= ($r['id'] == $admin_login['id']) ? '<small>Akun Anda</small>' : '' ?></td>
                    <td><?= e($r['username']) ?></td>
                    <td><?= isi($r['email']) ?></td>
                    <td><?= e($r['peran']) ?></td>
                    <td><?= pill($r['status']) ?></td>
                    <td>
                        <div class="acts">
                            <?= tombol_lihat($r, 'lihatAdmin') ?>
                            <?= tombol_ubah($r, 'ubahAdmin', ($r['id'] == $admin_login['id']) ? 'data-akun-sendiri="1"' : '') ?>
                            <?= tombol_hapus('admin.php', $r['id'], $r['nama']) ?>
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
