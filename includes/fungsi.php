<?php
define('PER_HALAMAN', 8);
function e($teks)
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// bersih() = membersihkan teks sebelum masuk ke query SQL (mencegah SQL injection)
function bersih($koneksi, $teks)
{
    return mysqli_real_escape_string($koneksi, trim((string)$teks));
}

// membersihkan semua isi $_POST sekaligus
function bersih_semua($koneksi, $data)
{
    $hasil = array();
    foreach ($data as $kunci => $nilai) {
        $hasil[$kunci] = bersih($koneksi, $nilai);
    }
    return $hasil;
}

//databse
function ambil_semua($koneksi, $sql)
{
    $hasil = mysqli_query($koneksi, $sql);
    $data = array();
    while ($baris = mysqli_fetch_assoc($hasil)) {
        $data[] = $baris;
    }
    return $data;
}

// mengambil satu baris data (kalau tidak ada hasilnya null)
function ambil_satu($koneksi, $sql)
{
    $hasil = mysqli_query($koneksi, $sql);
    return mysqli_fetch_assoc($hasil);
}

// menghitung jumlah data. Query harus memakai: COUNT(*) AS jumlah
function hitung($koneksi, $sql)
{
    $baris = ambil_satu($koneksi, $sql);
    return (int)$baris['jumlah'];
}

function set_pesan($pesan, $tipe = 'ok')
{
    $_SESSION['pesan'] = array('teks' => $pesan, 'tipe' => $tipe);
}

function ambil_pesan()
{
    if (isset($_SESSION['pesan'])) {
        $pesan = $_SESSION['pesan'];
        unset($_SESSION['pesan']);   
        return $pesan;
    }
    return null;
}

function cek_login($koneksi)
{
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
    $id = (int)$_SESSION['admin_id'];
    $admin = ambil_satu($koneksi, "SELECT * FROM admin WHERE id = $id AND status = 'Aktif'");
    if (!$admin) {
        // akun sudah dihapus / dinonaktifkan
        session_destroy();
        header("Location: login.php");
        exit;
    }
    rekap_antrian_bulan_lalu($koneksi);   // rekap antrian bulan lalu (lihat fungsi di bawah)
    return $admin;
}

// ---------- 5. Tampilan tanggal dan status ----------

// 2026-11-17  ->  17/11/2026
function tgl($tanggal)
{
    if (!$tanggal) {
        return '-';
    }
    return date('d/m/Y', strtotime($tanggal));
}

// contoh hasil: Selasa, 17 November 2026
function tanggal_panjang()
{
    $hari  = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $bulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    return $hari[date('w')] . ', ' . date('j') . ' ' . $bulan[date('n') - 1] . ' ' . date('Y');
}

// contoh hasil: 2 jam lalu
function waktu_lalu($waktu)
{
    $selisih = time() - strtotime($waktu);
    if ($selisih < 60) {
        return 'baru saja';
    }
    $menit = floor($selisih / 60);
    if ($menit < 60) {
        return $menit . ' menit lalu';
    }
    $jam = floor($menit / 60);
    if ($jam < 24) {
        return $jam . ' jam lalu';
    }
    return floor($jam / 24) . ' hari lalu';
}

// label status berwarna (kelasnya ada di css/style.css)
function pill($status)
{
    $kelas = strtolower(str_replace(' ', '-', $status));
    return '<span class="pill p-' . $kelas . '">' . e($status) . '</span>';
}

// kalau teks kosong tampilkan tanda "-"
function isi($teks)
{
    if ($teks === null || $teks === '') {
        return '-';
    }
    return e($teks);
}

// ---------- 6. Aktivitas terbaru ----------

function catat_aktivitas($koneksi, $ikon, $teks)
{
    $ikon = bersih($koneksi, $ikon);
    $teks = bersih($koneksi, $teks);
    mysqli_query($koneksi, "INSERT INTO aktivitas (ikon, teks, waktu) VALUES ('$ikon', '$teks', NOW())");
}

// ---------- 7. Nomor otomatis ----------

// mencari angka terbesar di kolom nomor, lalu ditambah 1
function nomor_berikutnya($koneksi, $tabel)
{
    $baris = ambil_semua($koneksi, "SELECT nomor FROM $tabel");
    $terbesar = 0;
    foreach ($baris as $b) {
        $angka = (int)preg_replace('/\D/', '', $b['nomor']);   // ambil angkanya saja
        if ($angka > $terbesar) {
            $terbesar = $angka;
        }
    }
    return $terbesar + 1;
}

function nomor_permohonan($koneksi)
{
    return 'PMH-' . str_pad(nomor_berikutnya($koneksi, 'permohonan'), 4, '0', STR_PAD_LEFT);
}

// ---------- Rekap bulanan antrian ----------
// Antrian yang sudah DIPANGGIL tetapi bulannya sudah lewat otomatis dinyatakan "Selesai",
// sehingga masuk ke rekap bulan tersebut di halaman Laporan.
function rekap_antrian_bulan_lalu($koneksi)
{
    $bulan = date('Y-m');
    mysqli_query($koneksi, "UPDATE antrean SET status = 'Selesai' WHERE status = 'Dipanggil' AND DATE_FORMAT(tanggal, '%Y-%m') < '$bulan'");
}

// Nomor antrian: 4 digit tanpa huruf (0001, 0002, 0003, ...).
// Nomor terus berlanjut selama 1 bulan. Kalau bulan berganti, mulai lagi dari 0001.
function nomor_antrean($koneksi)
{
    $bulan = date('Y-m');
    $baris = ambil_semua($koneksi, "SELECT nomor FROM antrean WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'");

    $terbesar = 0;
    foreach ($baris as $b) {
        $angka = (int)preg_replace('/\D/', '', $b['nomor']);   // ambil angkanya saja
        if ($angka > $terbesar) {
            $terbesar = $angka;
        }
    }
    return str_pad($terbesar + 1, 4, '0', STR_PAD_LEFT);
}

// ---------- 8. Statistik untuk laporan ----------

// membuat potongan query "tanggal antara dari dan sampai"
function kondisi_tanggal($koneksi, $dari, $sampai)
{
    $kondisi = "1=1";
    if ($dari != '') {
        $kondisi .= " AND tanggal >= '" . bersih($koneksi, $dari) . "'";
    }
    if ($sampai != '') {
        $kondisi .= " AND tanggal <= '" . bersih($koneksi, $sampai) . "'";
    }
    return $kondisi;
}

function hitung_statistik($koneksi, $dari, $sampai)
{
    $k = kondisi_tanggal($koneksi, $dari, $sampai);

    $hasil = array();

    // Total pendaftaran
    $hasil['pend'] = hitung(
        $koneksi,
        "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE $k"
    );

    // Pendaftaran disetujui
    $hasil['ok'] = hitung(
        $koneksi,
        "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE $k AND status = 'Disetujui'"
    );

    // Pendaftaran ditolak
    $hasil['no'] = hitung(
        $koneksi,
        "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE $k AND status = 'Ditolak'"
    );

    // Antrean selesai
    $hasil['ant'] = hitung(
        $koneksi,
        "SELECT COUNT(*) AS jumlah FROM antrean WHERE $k AND status = 'Selesai'"
    );

    // Dipertahankan untuk kompatibilitas kolom arsip laporan lama
    $hasil['perm'] = 0;

    return $hasil;
}

// ---------- 9. Pagination (nomor halaman di bawah tabel) ----------

// halaman yang sedang dibuka (diambil dari ?hal=2 di alamat web)
function halaman_sekarang($total, $per_halaman = PER_HALAMAN)
{
    $hal = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
    $jumlah_halaman = max(1, ceil($total / $per_halaman));
    if ($hal < 1) {
        $hal = 1;
    }
    if ($hal > $jumlah_halaman) {
        $hal = $jumlah_halaman;
    }
    return $hal;
}

// alamat untuk pindah halaman (filter pencarian tetap dibawa)
function link_halaman($nomor)
{
    $param = $_GET;
    $param['hal'] = $nomor;
    return '?' . http_build_query($param);
}

// membuat bagian bawah tabel: tulisan "Menampilkan ..." dan tombol halaman
function buat_paging($total, $halaman, $jumlah_di_halaman, $per_halaman = PER_HALAMAN)
{
    $jumlah_halaman = max(1, ceil($total / $per_halaman));

    if ($total > 0) {
        // sesuai jumlah data yang tampil di halaman ini (8 data -> "8 data", 2 data -> "2 data")
        $teks = 'Menampilkan ' . (int)$jumlah_di_halaman . ' data';
    } else {
        $teks = 'Tidak ada data';
    }

    // tampilkan paling banyak 5 nomor halaman
    $awal  = max(1, $halaman - 2);
    $akhir = min($jumlah_halaman, $awal + 4);
    $awal  = max(1, $akhir - 4);

    $html  = '<div class="tfoot"><span class="muted">' . $teks . '</span><div class="pager">';

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

// ---------- 10. Ikon (gambar kecil SVG) ----------

function ikon($nama)
{
    $daftar = array(
        'home'   => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14c2.8 0 4.5 1.8 4.5 4.5"/>',
        'file'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'ticket' => '<path d="M3 9a2 2 0 0 0 0 6v3h18v-3a2 2 0 0 1 0-6V6H3z"/><path d="M13 6v12" stroke-dasharray="2 2"/>',
        'chart'  => '<path d="M5 20v-8M12 20V5M19 20v-12"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'menu'   => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'plus'   => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'eye'    => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'edit'   => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash'  => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/>',
        'check'  => '<circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/>',
        'x'      => '<circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>',
        'close'  => '<path d="M6 6l12 12M18 6L6 18"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 7v5l3 2"/>',
        'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
        'info'   => '<circle cx="12" cy="12" r="10"/><path d="M12 11v6M12 7.5v.5"/>',
        'anchor' => '<circle cx="12" cy="5" r="2.5"/><path d="M12 7.5V21M7 11h10M4 15c0 3.5 3.6 6 8 6s8-2.5 8-6M4 15l2-2M20 15l-2-2"/>',
        'left'   => '<path d="M15 6l-6 6 6 6"/>',
        'right'  => '<path d="M9 6l6 6-6 6"/>',
        'down'   => '<path d="M6 9l6 6 6-6"/>',
        'mega'   => '<path d="M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M16 9a4 4 0 0 1 0 6"/>',
        'copy'   => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h8"/>'
    );
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $daftar[$nama] . '</svg>';
}

// ---------- 11. Fungsi bantu untuk form dan tombol ----------

// mengambil isi form (POST) dengan aman. Kalau tidak ada, hasilnya teks kosong.
function ambil_post($nama)
{
    if (isset($_POST[$nama])) {
        return trim($_POST[$nama]);
    }
    return '';
}

// pindah ke halaman lain lalu berhenti
function pindah($file)
{
    header("Location: " . $file);
    exit;
}

// Tombol-tombol kecil di kolom "Aksi" pada tabel.
// Data baris dikirim ke JavaScript lewat atribut data-json.
function tombol_lihat($data, $fungsi_js)
{
    return '<button type="button" class="ab view" title="Lihat" aria-label="Lihat" data-json="' . e(json_encode($data)) . '" onclick="' . $fungsi_js . '(this)">' . ikon('eye') . '</button>';
}

function tombol_ubah($data, $fungsi_js, $tambahan = '')
{
    return '<button type="button" class="ab edit" title="Ubah" aria-label="Ubah" ' . $tambahan . ' data-json="' . e(json_encode($data)) . '" onclick="' . $fungsi_js . '(this)">' . ikon('edit') . '</button>';
}

function tombol_hapus($file, $id, $nama)
{
    return '<button type="button" class="ab del" title="Hapus" aria-label="Hapus" onclick="konfirmasiHapus(\'' . $file . '\', ' . (int)$id . ', ' . e(json_encode($nama)) . ')">' . ikon('trash') . '</button>';
}

// tombol cepat untuk mengubah status (Setujui, Tolak, Panggil, Selesai)

// tombol cepat untuk mengubah status (Setujui, Tolak, Panggil, Selesai)
function tombol_status($id, $nilai, $kelas, $judul, $nama_ikon)
{
    return '<form method="post" style="display:inline-block; margin:0;">'
        . '<input type="hidden" name="aksi" value="status">'
        . '<input type="hidden" name="id" value="' . (int)$id . '">'
        . '<input type="hidden" name="nilai" value="' . e($nilai) . '">'
        . '<button type="submit" class="ab ' . $kelas . '" title="' . $judul . '" aria-label="' . $judul . '">'
        . ikon($nama_ikon)
        . '</button>'
        . '</form>';
}


// kotak pencarian + filter status di atas tabel.
// Form memakai method GET, jadi kata kunci muncul di alamat web (?q=...).
function kotak_pencarian($daftar_status, $placeholder = 'Cari data...')
{
    $q = isset($_GET['q']) ? $_GET['q'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $html  = '<form class="toolbar" method="get">';
    $html .= '<label class="search">' . ikon('search') . '<input type="search" name="q" placeholder="' . $placeholder . '" value="' . e($q) . '" aria-label="Cari data"></label>';
    if (count($daftar_status) > 0) {
        $html .= '<select class="sel" name="status" onchange="this.form.submit()" aria-label="Filter status"><option value="">Semua Status</option>';
        foreach ($daftar_status as $s) {
            $pilih = ($status == $s) ? ' selected' : '';
            $html .= '<option' . $pilih . '>' . e($s) . '</option>';
        }
        $html .= '</select>';
    }
    $html .= '</form>';
    return $html;
}