<?php
include 'config/koneksi.php';
include 'includes/fungsi.php';
$admin_login = cek_login($koneksi);


// BAGIAN 1: memproses form (tambah, ubah, hapus)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $aksi = ambil_post('aksi');
    $id   = (int)ambil_post('id');

    // ----- TAMBAH atau UBAH arsip laporan -----
    if ($aksi == 'simpan') {
        $judul   = bersih($koneksi, ambil_post('judul'));
        $dari    = bersih($koneksi, ambil_post('dari'));
        $sampai  = bersih($koneksi, ambil_post('sampai'));
        $catatan = bersih($koneksi, ambil_post('catatan'));

        if ($judul == '' || $dari == '' || $sampai == '') {
            set_pesan('Judul, tanggal awal, dan tanggal akhir wajib diisi.', 'err');
        } elseif ($dari > $sampai) {
            set_pesan('Tanggal akhir harus setelah tanggal awal.', 'err');
        } else {
            // angka laporan dihitung dari data saat ini lalu disimpan
            $s = hitung_statistik($koneksi, $dari, $sampai);

            if ($id > 0) {
                mysqli_query($koneksi, "UPDATE laporan SET judul='$judul', dari='$dari', sampai='$sampai', catatan='$catatan', jml_pendaftaran={$s['pend']}, jml_permohonan={$s['perm']}, jml_disetujui={$s['ok']}, jml_ditolak={$s['no']}, jml_antrean={$s['ant']} WHERE id=$id");
                catat_aktivitas($koneksi, 'edit', 'Laporan diperbarui: ' . ambil_post('judul'));
                set_pesan('Laporan berhasil diperbarui.');
            } else {
                $oleh = bersih($koneksi, $admin_login['nama']);
                mysqli_query($koneksi, "INSERT INTO laporan (judul, dari, sampai, catatan, dibuat, oleh, jml_pendaftaran, jml_permohonan, jml_disetujui, jml_ditolak, jml_antrean) VALUES ('$judul', '$dari', '$sampai', '$catatan', NOW(), '$oleh', {$s['pend']}, {$s['perm']}, {$s['ok']}, {$s['no']}, {$s['ant']})");
                catat_aktivitas($koneksi, 'plus', 'Laporan ditambahkan: ' . ambil_post('judul'));
                set_pesan('Laporan berhasil ditambahkan.');
            }
        }
    }

    // ----- HAPUS arsip laporan -----
    if ($aksi == 'hapus' && $id > 0) {
        $data = ambil_satu($koneksi, "SELECT judul FROM laporan WHERE id=$id");
        if ($data) {
            mysqli_query($koneksi, "DELETE FROM laporan WHERE id=$id");
            catat_aktivitas($koneksi, 'trash', 'Laporan dihapus: ' . $data['judul']);
            set_pesan('Laporan berhasil dihapus.');
        }
    }

    pindah('laporan.php');
}


// ========== BAGIAN 2: mengambil data ==========
// periode laporan (bawaan: 1 Januari tahun ini sampai hari ini)
$dari   = (isset($_GET['dari']) && $_GET['dari'] != '') ? $_GET['dari'] : date('Y') . '-01-01';
$sampai = (isset($_GET['sampai']) && $_GET['sampai'] != '') ? $_GET['sampai'] : date('Y-m-d');

// 4 kartu ringkasan
$s = hitung_statistik($koneksi, $dari, $sampai);
$ringkasan = array(
    array('Total Pendaftaran',    $s['pend'], 'users', 'blue'),
    array('Total Permohonan',     $s['perm'], 'file',  'amber'),
    array('Permohonan Disetujui', $s['ok'],   'check', 'green'),
    array('Permohonan Ditolak',   $s['no'],   'x',     'red')
);

// ----- 5 jenis laporan detail -----
$k = kondisi_tanggal($koneksi, $dari, $sampai);

$laporan_detail = array();

$baris = array();
foreach (ambil_semua($koneksi, "SELECT * FROM pendaftaran WHERE $k ORDER BY tanggal DESC") as $r) {
    $baris[] = array($r['nama'], $r['perusahaan'], tgl($r['tanggal']), $r['status']);
}
$laporan_detail[] = array('Pendaftaran Pengguna Jasa', array('Nama', 'Perusahaan', 'Tanggal', 'Status'), $baris);

$baris = array();
foreach (ambil_semua($koneksi, "SELECT * FROM permohonan WHERE $k ORDER BY tanggal DESC") as $r) {
    $baris[] = array($r['nomor'], $r['pemohon'], $r['layanan'], tgl($r['tanggal']), $r['status']);
}
$laporan_detail[] = array('Permohonan Layanan', array('Nomor', 'Pemohon', 'Layanan', 'Tanggal', 'Status'), $baris);

$baris = array();
foreach (ambil_semua($koneksi, "SELECT * FROM permohonan WHERE $k AND status = 'Disetujui' ORDER BY tanggal DESC") as $r) {
    $baris[] = array($r['nomor'], $r['pemohon'], $r['layanan'], tgl($r['tanggal']));
}
$laporan_detail[] = array('Permohonan Disetujui', array('Nomor', 'Pemohon', 'Layanan', 'Tanggal'), $baris);

$baris = array();
foreach (ambil_semua($koneksi, "SELECT * FROM permohonan WHERE $k AND status = 'Ditolak' ORDER BY tanggal DESC") as $r) {
    $baris[] = array($r['nomor'], $r['pemohon'], $r['layanan'], tgl($r['tanggal']));
}
$laporan_detail[] = array('Permohonan Ditolak', array('Nomor', 'Pemohon', 'Layanan', 'Tanggal'), $baris);

$baris = array();
foreach (ambil_semua($koneksi, "SELECT * FROM antrean WHERE $k AND status = 'Selesai' ORDER BY tanggal DESC") as $r) {
    $baris[] = array($r['nomor'], $r['nama'], $r['layanan'], tgl($r['tanggal']));
}
$laporan_detail[] = array('Antrean Dilayani', array('Nomor', 'Nama', 'Layanan', 'Tanggal'), $baris);

// ----- data grafik 6 bulan terakhir -----
$nama_bulan = array('Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
$bulan_grafik = array();
for ($i = 5; $i >= 0; $i--) {
    $waktu = strtotime(date('Y-m-01') . " -$i month");   // tanggal 1 pada bulan tersebut
    $kunci = date('Y-m', $waktu);
    $bulan_grafik[] = array(
        'label' => $nama_bulan[date('n', $waktu) - 1],
        'pend'  => hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$kunci'"),
        'perm'  => hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM permohonan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$kunci'")
    );
}

// ----- arsip laporan (tabel yang bisa di-CRUD) -----
$q = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';
$kondisi_arsip = "1=1";
if ($q != '') {
    $kondisi_arsip .= " AND (judul LIKE '%$q%' OR catatan LIKE '%$q%' OR oleh LIKE '%$q%')";
}
$total   = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM laporan WHERE $kondisi_arsip");
$halaman = halaman_sekarang($total);
$mulai   = ($halaman - 1) * PER_HALAMAN;
$arsip   = ambil_semua($koneksi, "SELECT * FROM laporan WHERE $kondisi_arsip ORDER BY dibuat DESC, id DESC LIMIT $mulai, " . PER_HALAMAN);

// judul bawaan untuk laporan baru
$judul_baru = 'Laporan ' . tgl($dari) . ' s/d ' . tgl($sampai);

// Fungsi kecil untuk membuat teks CSV (bisa dibuka di Excel)
function buat_csv($kepala, $baris)
{
    $semua = array_merge(array($kepala), $baris);
    $hasil = array();
    foreach ($semua as $b) {
        $kolom = array();
        foreach ($b as $isi_kolom) {
            $kolom[] = '"' . str_replace('"', '""', $isi_kolom) . '"';
        }
        $hasil[] = implode(',', $kolom);
    }
    return implode("\n", $hasil);
}

// ========== BAGIAN 3: tampilan (HTML) ==========
$judul_halaman = 'Laporan';
$menu_aktif    = 'laporan';
$css_tambahan  = array('dashboard.css', 'laporan.css');   // dashboard.css dipakai untuk kartu statistik
$js_tambahan   = array('laporan.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Laporan</h1>
        <p>Ringkasan layanan berdasarkan data yang ada di semua halaman</p>
    </div>
    <div>
        <button type="button" class="btn" onclick="tambahLaporan(<?= e(json_encode(array('judul' => $judul_baru, 'dari' => $dari, 'sampai' => $sampai))) ?>)"><?= ikon('plus') ?>Simpan Laporan</button>
    </div>
</div>

<!-- FILTER PERIODE -->
<section class="card rfilter">
    <form method="get" style="display:contents">
        <div class="fld"><label for="rep-from">Dari tanggal</label><input id="rep-from" type="date" name="dari" value="<?= e($dari) ?>" onchange="this.form.submit()"></div>
        <div class="fld"><label for="rep-to">Sampai tanggal</label><input id="rep-to" type="date" name="sampai" value="<?= e($sampai) ?>" onchange="this.form.submit()"></div>
        <a class="btn ghost" href="laporan.php">Atur Ulang Periode</a>
    </form>
</section>

 <!-- 4 KARTU RINGKASAN -->
<section class="sum">
    <?php foreach ($ringkasan as $r) { ?>
        <div class="stat s-<?= $r[3] ?>">
            <span class="si"><?= ikon($r[2]) ?></span>
            <span class="sb"><small><?= $r[0] ?></small><b><?= $r[1] ?></b></span>
        </div>
    <?php } ?>
</section>

<div class="cols">

    <!-- LAPORAN DETAIL -->
    <section class="card panel">
        <div class="ph"><h2>Laporan Detail</h2></div>
        <div class="tw">
            <table style="min-width:420px">
                <thead><tr><th>No</th><th>Jenis Laporan</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($laporan_detail as $i => $lap) { ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><b><?= $lap[0] ?></b></td>
                        <td><?= count($lap[2]) ?></td>
                        <td>
                            <div class="acts">
                                <button type="button" class="ab view" title="Lihat" aria-label="Lihat <?= $lap[0] ?>" onclick="bukaIsi('<?= $lap[0] ?> (<?= count($lap[2]) ?>)', 'lap-<?= $i ?>')"><?= ikon('eye') ?></button>
                                <button type="button" class="ab edit" title="Salin CSV" aria-label="Salin CSV <?= $lap[0] ?>" onclick="bukaCsv('<?= $lap[0] ?>', 'csv-<?= $i ?>')"><?= ikon('copy') ?></button>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ============ GRAFIK 6 BULAN ============ -->
    <section class="card panel">
        <div class="ph"><h2>Enam Bulan Terakhir</h2></div>
        <div class="legend"><span><i style="background:#121358"></i>Pendaftaran</span><span><i style="background:#8ea4ee"></i>Permohonan</span></div>
        <?php
        // ---- menggambar grafik batang dengan SVG ----
        // nilai tertinggi dibulatkan ke kelipatan 5 supaya garis bantu rapi
        $nilai_max = 5;
        foreach ($bulan_grafik as $b) {
            $nilai_max = max($nilai_max, $b['pend'], $b['perm']);
        }
        $atas = ceil($nilai_max / 5) * 5;

        $lebar = 480; $tinggi = 230;
        $kiri = 34; $kanan = 8; $atas_pad = 14; $bawah = 28;
        $lebar_area  = $lebar - $kiri - $kanan;
        $tinggi_area = $tinggi - $atas_pad - $bawah;
        $lebar_grup  = $lebar_area / 6;
        $lebar_batang = 20;

        // fungsi kecil: mengubah nilai data menjadi posisi Y di gambar
        function posisi_y($nilai, $atas, $atas_pad, $tinggi_area)
        {
            return $atas_pad + $tinggi_area - ($nilai / $atas) * $tinggi_area;
        }
        ?>
        <svg class="chart" viewBox="0 0 <?= $lebar ?> <?= $tinggi ?>" role="img" aria-label="Grafik pendaftaran dan permohonan enam bulan terakhir">
            <?php for ($i = 0; $i <= 5; $i++) {
                $nilai = $i * $atas / 5;
                $y = posisi_y($nilai, $atas, $atas_pad, $tinggi_area);
            ?>
                <line x1="<?= $kiri ?>" x2="<?= $lebar - $kanan ?>" y1="<?= $y ?>" y2="<?= $y ?>" stroke="#e2e5f3"/>
                <text x="<?= $kiri - 8 ?>" y="<?= $y + 4 ?>" text-anchor="end" font-size="11" fill="#60638a"><?= $nilai ?></text>
            <?php } ?>

            <?php foreach ($bulan_grafik as $i => $b) {
                $tengah = $kiri + $i * $lebar_grup + $lebar_grup / 2;
                // dua batang: pendaftaran (kiri) dan permohonan (kanan)
                $batang = array(
                    array($b['pend'], $tengah - $lebar_batang - 2, '#121358'),
                    array($b['perm'], $tengah + 2, '#8ea4ee')
                );
                foreach ($batang as $bt) {
                    if ($bt[0] > 0) {
                        $y = posisi_y($bt[0], $atas, $atas_pad, $tinggi_area);
                        $tinggi_batang = $atas_pad + $tinggi_area - $y;
            ?>
                <rect x="<?= $bt[1] ?>" y="<?= $y ?>" width="<?= $lebar_batang ?>" height="<?= $tinggi_batang ?>" rx="3" fill="<?= $bt[2] ?>"/>
                <text x="<?= $bt[1] + $lebar_batang / 2 ?>" y="<?= $y - 4 ?>" text-anchor="middle" font-size="11" font-weight="700" fill="#181a45"><?= $bt[0] ?></text>
            <?php   }
                } ?>
                <text x="<?= $tengah ?>" y="<?= $tinggi - 8 ?>" text-anchor="middle" font-size="12" fill="#60638a"><?= $b['label'] ?></text>
            <?php } ?>
        </svg>
    </section>
</div>

<!-- ============ ARSIP LAPORAN (CRUD) ============ -->
<div class="phead">
    <div>
        <h2 style="font-size:18px;font-weight:800;color:var(--navy)">Arsip Laporan</h2>
        <p>Laporan yang sudah disimpan, bisa dilihat, diubah, dan dihapus</p>
    </div>
</div>

<section class="card tcard">
    <form class="toolbar" method="get">
        <input type="hidden" name="dari" value="<?= e($dari) ?>">
        <input type="hidden" name="sampai" value="<?= e($sampai) ?>">
        <label class="search"><?= ikon('search') ?><input type="search" name="q" placeholder="Cari laporan..." value="<?= e(isset($_GET['q']) ? $_GET['q'] : '') ?>" aria-label="Cari laporan"></label>
    </form>

    <div class="tw">
        <table>
            <thead>
                <tr><th>No</th><th>Judul Laporan</th><th>Periode</th><th>Dibuat Oleh</th><th>Tanggal Dibuat</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (count($arsip) == 0) { ?>
                <tr><td colspan="6"><div class="empty"><?= ($q != '') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada laporan tersimpan.' ?></div></td></tr>
            <?php } ?>

            <?php $no = $mulai + 1; foreach ($arsip as $r) { ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><b><?= e($r['judul']) ?></b><?= ($r['catatan'] != '') ? '<small>' . e($r['catatan']) . '</small>' : '' ?></td>
                    <td><?= tgl($r['dari']) ?> – <?= tgl($r['sampai']) ?></td>
                    <td><?= e($r['oleh']) ?></td>
                    <td><?= tgl($r['dibuat']) ?></td>
                    <td>
                        <div class="acts">
                            <?= tombol_lihat($r, 'lihatLaporan') ?>
                            <?= tombol_ubah($r, 'ubahLaporan') ?>
                            <?= tombol_hapus('laporan.php', $r['id'], $r['judul']) ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?= buat_paging($total, $halaman, count($arsip)) ?>
</section>

<!-- ============ ISI POPUP (disembunyikan, dibuka lewat tombol) ============ -->
<?php foreach ($laporan_detail as $i => $lap) { ?>
    <!-- tabel laporan untuk tombol "Lihat" -->
    <div id="lap-<?= $i ?>" hidden>
        <?php if (count($lap[2]) == 0) { ?>
            <div class="empty">Tidak ada data pada periode ini.</div>
        <?php } else { ?>
            <div class="tw">
                <table style="min-width:480px">
                    <thead><tr><?php foreach ($lap[1] as $kepala) { echo '<th>' . $kepala . '</th>'; } ?></tr></thead>
                    <tbody>
                    <?php foreach ($lap[2] as $baris_data) { ?>
                        <tr><?php foreach ($baris_data as $sel) { echo '<td>' . e($sel) . '</td>'; } ?></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <!-- teks CSV untuk tombol "Salin CSV" -->
    <div id="csv-<?= $i ?>" hidden>
        <p class="muted">Salin teks di bawah, lalu tempel di Excel atau Notepad.</p>
        <textarea class="csv" readonly><?= e(buat_csv($lap[1], $lap[2])) ?></textarea>
    </div>
<?php } ?>

<?php include 'includes/footer.php'; ?>
