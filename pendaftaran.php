<?php
include 'assets/config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);

$daftar_status = array('Menunggu Verifikasi', 'Disetujui', 'Ditolak');

// Jumlah data per halaman khusus halaman ini
define('PER_HALAMAN_PENDAFTARAN', 12);

// Membuat nomor antrean berikutnya: 4 digit (0001, 0002, ...), berlanjut per bulan pendaftaran
function buat_nomor_antrean($koneksi, $tanggal)
{
    $bulan = date('Y-m', strtotime($tanggal));
    $hasil = ambil_satu(
        $koneksi,
        "SELECT MAX(CAST(Nomor_Antrian AS UNSIGNED)) AS terakhir
         FROM pendaftaran
         WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'
         AND Nomor_Antrian IS NOT NULL
         AND Nomor_Antrian != ''"
    );
    $baru = (int)($hasil['terakhir'] ?? 0) + 1;
    return str_pad($baru, 4, '0', STR_PAD_LEFT);
}


// ========== BAGIAN 1: memproses form (UBAH / HAPUS) ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    // alamat tujuan setelah selesai (tetap di halaman & filter yang sama)
    $kembali = 'pendaftaran.php';
    $balik   = ambil_post('kembali');
    if ($balik != '' && preg_match('/^\?[A-Za-z0-9_=&%+.\-]*$/', $balik)) {
        $kembali .= $balik;
    }

    // ----- UBAH data (termasuk memproses status) -----
    if ($aksi == 'simpan' && $id > 0) {
        $nama    = bersih($koneksi, ambil_post('nama'));
        $email   = bersih($koneksi, ambil_post('email'));
        $telepon = bersih($koneksi, ambil_post('telepon'));
        $tanggal = bersih($koneksi, ambil_post('tanggal'));
        $status  = ambil_post('status');

        // jenis layanan diisi oleh pengguna, admin tidak mengubahnya
        $lama = ambil_satu($koneksi, "SELECT nama, status, Nomor_Antrian FROM pendaftaran WHERE id=$id");

        if (!$lama) {
            set_pesan('Data pendaftaran tidak ditemukan.', 'err');

        } elseif ($nama == '' || $tanggal == '') {
            set_pesan('Nama dan tanggal wajib diisi.', 'err');

        } elseif ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_pesan('Format email belum benar.', 'err');

        } elseif (!in_array($status, $daftar_status)) {
            set_pesan('Status tidak valid.', 'err');

        } else {
            // nomor antrean dibuat otomatis saat pertama kali diproses (disetujui / ditolak)
            $set_nomor = '';
            if (in_array($status, array('Disetujui', 'Ditolak')) && empty($lama['Nomor_Antrian'])) {
                $set_nomor = ", Nomor_Antrian='" . buat_nomor_antrean($koneksi, $tanggal) . "'";
            }

            mysqli_query($koneksi, "UPDATE pendaftaran SET
                nama='$nama',
                email='$email',
                telepon='$telepon',
                tanggal='$tanggal',
                status='$status'
                $set_nomor
                WHERE id=$id");

            if ($status != $lama['status'] && $status == 'Disetujui') {
                catat_aktivitas($koneksi, 'check', 'Pendaftaran ' . ambil_post('nama') . ' disetujui');
            } elseif ($status != $lama['status'] && $status == 'Ditolak') {
                catat_aktivitas($koneksi, 'x', 'Pendaftaran ' . ambil_post('nama') . ' ditolak');
            } else {
                catat_aktivitas($koneksi, 'edit', 'Pendaftaran diperbarui: ' . ambil_post('nama'));
            }

            $_SESSION['baris_ubah'] = $id;   // baris ini disorot setelah kembali
            set_pesan('Data pendaftaran berhasil diperbarui.');
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

    pindah($kembali);
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

$halaman = halaman_sekarang($total, PER_HALAMAN_PENDAFTARAN);
$mulai   = ($halaman - 1) * PER_HALAMAN_PENDAFTARAN;


// mengambil data pendaftaran
$data = ambil_semua(
    $koneksi,
    "SELECT * FROM pendaftaran
    WHERE $kondisi
    ORDER BY tanggal DESC, id DESC
    LIMIT $mulai, " . PER_HALAMAN_PENDAFTARAN
);


// baris yang baru saja diubah (untuk disorot sekali)
$baris_ubah = 0;
if (isset($_SESSION['baris_ubah'])) {
    $baris_ubah = (int)$_SESSION['baris_ubah'];
    unset($_SESSION['baris_ubah']);
}


// ========== BAGIAN 3: tampilan (HTML) ==========

$judul_halaman = 'Data Pendaftaran';
$menu_aktif    = 'pendaftaran';
$css_tambahan  = array('pendaftaran.css');
$js_tambahan   = array();   // kode popup ada langsung di bagian bawah halaman ini

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

                <tr<?= ($r['id'] == $baris_ubah) ? ' class="baris-baru"' : '' ?>>
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

    <?= buat_paging($total, $halaman, count($data), PER_HALAMAN_PENDAFTARAN) ?>

</section>


<script>
// ============================================================
// pendaftaran.js
// Popup "Ubah Pendaftaran" + popup konfirmasi "Apakah anda yakin...?"
// Popup LIHAT tetap memakai bukaLihat() bawaan project.
// ============================================================

// Membuat elemen HTML dengan aman (isi teks tidak pernah dibaca sebagai HTML)
function pdEl(tag, kelas, teks) {
    var el = document.createElement(tag);
    if (kelas) el.className = kelas;
    if (teks !== undefined) el.textContent = teks;
    return el;
}

// Membuat satu kolom form: label + input
function pdField(label, input, wajib, penuh) {
    var wrap = pdEl('div', 'fld' + (penuh ? ' full' : ''));
    var lab = pdEl('label', '', label + ' ');
    if (wajib) lab.appendChild(pdEl('i', '', '*'));
    wrap.appendChild(lab);
    wrap.appendChild(input);
    return wrap;
}

function pdInput(tipe, nama, nilai) {
    var el = document.createElement('input');
    el.type = tipe;
    if (nama) el.name = nama;
    el.value = nilai || '';
    return el;
}

// Menutup popup paling atas
function pdTutup(back) {
    if (back && back.parentNode) back.parentNode.removeChild(back);
}

// ---------- Popup konfirmasi: "Apakah anda yakin ingin merubah data tersebut?" ----------
function pdKonfirmasi(pesan, saatIya) {
    var back = pdEl('div', 'mback pd-konfirmasi');
    back.setAttribute('role', 'dialog');
    back.setAttribute('aria-modal', 'true');
    back.style.zIndex = 150;   // di atas popup Ubah

    var box = pdEl('div', 'mbox pd-kecil');
    var head = pdEl('div', 'mhead');
    head.appendChild(pdEl('h3', '', 'Konfirmasi'));
    var body = pdEl('div', 'mbody');
    body.appendChild(pdEl('p', 'confirm', pesan));

    var foot = pdEl('div', 'mfoot');
    var tidak = pdEl('button', 'btn ghost', 'Tidak');
    tidak.type = 'button';
    var iya = pdEl('button', 'btn', 'Iya');
    iya.type = 'button';
    foot.appendChild(tidak);
    foot.appendChild(iya);

    box.appendChild(head);
    box.appendChild(body);
    box.appendChild(foot);
    back.appendChild(box);
    document.body.appendChild(back);
    iya.focus();

    tidak.onclick = function () { pdTutup(back); };
    iya.onclick = function () {
        iya.disabled = true;
        tidak.disabled = true;
        saatIya();
    };
    back.addEventListener('click', function (ev) {
        if (ev.target === back) pdTutup(back);
    });
}

// Popup UBAH pendaftaran
function ubahPendaftaran(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    var back = pdEl('div', 'mback pd-ubah');
    back.setAttribute('role', 'dialog');
    back.setAttribute('aria-modal', 'true');

    var box = pdEl('div', 'mbox');

    // kepala popup
    var head = pdEl('div', 'mhead');
    head.appendChild(pdEl('h3', '', 'Ubah Pendaftaran'));
    var x = pdEl('button', 'pd-x');
    x.type = 'button';
    x.setAttribute('aria-label', 'Tutup');
    x.textContent = '×';
    head.appendChild(x);

    // formulir
    var form = document.createElement('form');
    form.method = 'post';
    form.action = 'pendaftaran.php';
    form.className = 'pd-form';

    var tersembunyi = [['aksi', 'simpan'], ['id', d.id], ['kembali', window.location.search]];
    tersembunyi.forEach(function (h) {
        form.appendChild(pdInput('hidden', h[0], h[1]));
    });

    var body = pdEl('div', 'mbody');
    var grid = pdEl('div', 'fgrid');

    var nama = pdInput('text', 'nama', d.nama);
    nama.required = true;
    nama.placeholder = 'Contoh: Andi Pratama';
    grid.appendChild(pdField('Nama Lengkap', nama, true));

    // Jenis layanan: diisi oleh pengguna saat mendaftar, admin hanya melihat
    var layanan = pdInput('text', '', d.Jenis_Layanan || '-');
    layanan.readOnly = true;
    layanan.tabIndex = -1;
    grid.appendChild(pdField('Jenis Layanan', layanan, false));

    var email = pdInput('email', 'email', d.email);
    email.placeholder = 'nama@email.co.id';
    grid.appendChild(pdField('Email', email, false));

    var telepon = pdInput('tel', 'telepon', d.telepon);
    telepon.placeholder = '0812-0000-0000';
    grid.appendChild(pdField('Telepon', telepon, false));

    var tanggal = pdInput('date', 'tanggal', d.tanggal);
    tanggal.required = true;
    grid.appendChild(pdField('Tanggal Daftar', tanggal, true));

    // Status: di sinilah admin memproses Disetujui / Ditolak / Menunggu Verifikasi
    var status = document.createElement('select');
    status.name = 'status';
    ['Menunggu Verifikasi', 'Disetujui', 'Ditolak'].forEach(function (s) {
        var o = document.createElement('option');
        o.value = s;
        o.textContent = s;
        if (s === d.status) o.selected = true;
        status.appendChild(o);
    });
    grid.appendChild(pdField('Status', status, false));

    // Nomor antrean: otomatis, dibuat saat status pertama kali diproses
    var nomor = pdInput('text', '', d.Nomor_Antrian ? d.Nomor_Antrian : 'Otomatis setelah diproses');
    nomor.readOnly = true;
    nomor.tabIndex = -1;
    grid.appendChild(pdField('Nomor Antrean', nomor, false));

    body.appendChild(grid);
    form.appendChild(body);

    // kaki popup
    var foot = pdEl('div', 'mfoot');
    var kembali = pdEl('button', 'btn ghost', 'Kembali');
    kembali.type = 'button';
    var simpan = pdEl('button', 'btn', 'Simpan Perubahan');
    simpan.type = 'submit';
    foot.appendChild(kembali);
    foot.appendChild(simpan);
    form.appendChild(foot);

    box.appendChild(head);
    box.appendChild(form);
    back.appendChild(box);
    document.body.appendChild(back);
    nama.focus();

    // tutup popup
    x.onclick = kembali.onclick = function () { pdTutup(back); };
    back.addEventListener('mousedown', function (ev) {
        if (ev.target === back) pdTutup(back);
    });
    back.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !document.querySelector('.pd-konfirmasi')) pdTutup(back);
    });

    // Simpan Perubahan -> tanya dulu "Apakah anda yakin...?"
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (!form.reportValidity()) return;
        pdKonfirmasi('Apakah anda yakin ingin merubah data tersebut?', function () {
            form.submit();   // Iya -> simpan, lalu kembali ke Data Pendaftaran
        });
    });
}

// ---------- Popup LIHAT detail ----------
function lihatPendaftaran(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Pendaftaran', [
        ['Nama Lengkap',    d.nama],
        ['Jenis Layanan',   d.Jenis_Layanan],
        ['Nomor Antrean',   d.Nomor_Antrian ? d.Nomor_Antrian : '-'],
        ['Email',           d.email],
        ['Telepon',         d.telepon],
        ['Tanggal Daftar',  tgl(d.tanggal)],
        ['Status',          pill(d.status), true]
    ]);   // hanya tombol "Tutup" (sama seperti popup lihat di halaman Antrian)
}
</script>

<?php include 'includes/footer.php'; ?>