
<?php
include 'config/koneksi.php';
include 'includes/fungsi.php';

$admin_login = cek_login($koneksi);

// BAGIAN 1: PROSES FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = ambil_post('aksi');
    $id = (int)ambil_post('id');

    // TAMBAH / UBAH ARSIP LAPORAN
    if ($aksi == 'simpan') {
        $judul = bersih($koneksi, ambil_post('judul'));
        $dari = bersih($koneksi, ambil_post('dari'));
        $sampai = bersih($koneksi, ambil_post('sampai'));
        $catatan = bersih($koneksi, ambil_post('catatan'));

        if ($judul == '' || $dari == '' || $sampai == '') {
            set_pesan('Judul, tanggal awal, dan tanggal akhir wajib diisi.', 'err');
        } elseif ($dari > $sampai) {
            set_pesan('Tanggal akhir harus setelah tanggal awal.', 'err');
        } else {
            $s = hitung_statistik($koneksi, $dari, $sampai);

            if ($id > 0) {
                mysqli_query($koneksi, "
                    UPDATE laporan SET
                        judul='$judul',
                        dari='$dari',
                        sampai='$sampai',
                        catatan='$catatan',
                        jml_pendaftaran={$s['pend']},
                        jml_permohonan=0,
                        jml_disetujui={$s['ok']},
                        jml_ditolak={$s['no']},
                        jml_antrean={$s['ant']}
                    WHERE id=$id
                ");
                catat_aktivitas($koneksi, 'edit', 'Laporan diperbarui: ' . ambil_post('judul'));
                set_pesan('Laporan berhasil diperbarui.');
            } else {
                $oleh = bersih($koneksi, $admin_login['nama']);
                mysqli_query($koneksi, "
                    INSERT INTO laporan (
                        judul, dari, sampai, catatan, dibuat, oleh,
                        jml_pendaftaran, jml_permohonan, jml_disetujui,
                        jml_ditolak, jml_antrean
                    ) VALUES (
                        '$judul', '$dari', '$sampai', '$catatan', NOW(), '$oleh',
                        {$s['pend']}, 0, {$s['ok']}, {$s['no']}, {$s['ant']}
                    )
                ");
                catat_aktivitas($koneksi, 'plus', 'Laporan ditambahkan: ' . ambil_post('judul'));
                set_pesan('Laporan berhasil ditambahkan.');
            }
        }
    }

    // HAPUS ARSIP LAPORAN
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

// BAGIAN 2: MENGAMBIL DATA
$dari = (isset($_GET['dari']) && $_GET['dari'] != '') ? $_GET['dari'] : date('Y') . '-01-01';
$sampai = (isset($_GET['sampai']) && $_GET['sampai'] != '') ? $_GET['sampai'] : date('Y-m-d');

// RINGKASAN
$s = hitung_statistik($koneksi, $dari, $sampai);
$ringkasan = array(
    array('Total Pendaftaran', $s['pend'], 'users', 'blue'),
    array('Pendaftaran Disetujui', $s['ok'], 'check', 'green'),
    array('Pendaftaran Ditolak', $s['no'], 'x', 'red'),
    array('Antrean Selesai', $s['ant'], 'check', 'amber')
);

// LAPORAN DETAIL
$k = kondisi_tanggal($koneksi, $dari, $sampai);
$laporan_detail = array();

// 1. SEMUA DATA PENDAFTARAN
$baris = array();
$data_pendaftaran = ambil_semua($koneksi, "SELECT * FROM pendaftaran WHERE $k ORDER BY tanggal DESC");
foreach ($data_pendaftaran as $r) {
    $perusahaan = isset($r['perusahaan']) ? $r['perusahaan'] : '-';
    $baris[] = array(
        isset($r['nama']) ? $r['nama'] : '-',
        $perusahaan,
        isset($r['tanggal']) ? tgl($r['tanggal']) : '-',
        isset($r['status']) ? $r['status'] : '-'
    );
}
$laporan_detail[] = array('Pendaftaran Pengguna Jasa', array('Nama', 'Perusahaan', 'Tanggal', 'Status'), $baris);

// 2. PENDAFTARAN DISETUJUI
$baris = array();
$data_disetujui = ambil_semua($koneksi, "SELECT * FROM pendaftaran WHERE $k AND status = 'Disetujui' ORDER BY tanggal DESC");
foreach ($data_disetujui as $r) {
    $perusahaan = isset($r['perusahaan']) ? $r['perusahaan'] : '-';
    $baris[] = array(
        isset($r['nama']) ? $r['nama'] : '-',
        $perusahaan,
        isset($r['tanggal']) ? tgl($r['tanggal']) : '-'
    );
}
$laporan_detail[] = array('Pendaftaran Disetujui', array('Nama', 'Perusahaan', 'Tanggal'), $baris);

// 3. PENDAFTARAN DITOLAK
$baris = array();
$data_ditolak = ambil_semua($koneksi, "SELECT * FROM pendaftaran WHERE $k AND status = 'Ditolak' ORDER BY tanggal DESC");
foreach ($data_ditolak as $r) {
    $perusahaan = isset($r['perusahaan']) ? $r['perusahaan'] : '-';
    $baris[] = array(
        isset($r['nama']) ? $r['nama'] : '-',
        $perusahaan,
        isset($r['tanggal']) ? tgl($r['tanggal']) : '-'
    );
}
$laporan_detail[] = array('Pendaftaran Ditolak', array('Nama', 'Perusahaan', 'Tanggal'), $baris);

// 4. ANTREAN SELESAI
$baris = array();
$data_antrean = ambil_semua($koneksi, "SELECT * FROM antrean WHERE $k AND status = 'Selesai' ORDER BY tanggal DESC");
foreach ($data_antrean as $r) {
    $baris[] = array(
        isset($r['nomor']) ? $r['nomor'] : '-',
        isset($r['nama']) ? $r['nama'] : '-',
        isset($r['layanan']) ? $r['layanan'] : '-',
        isset($r['tanggal']) ? tgl($r['tanggal']) : '-'
    );
}
$laporan_detail[] = array('Antrean Dilayani', array('Nomor', 'Nama', 'Layanan', 'Tanggal'), $baris);

// GRAFIK 6 BULAN TERAKHIR
$nama_bulan = array('Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
$bulan_grafik = array();
for ($i = 5; $i >= 0; $i--) {
    $waktu = strtotime(date('Y-m-01') . " -$i month");
    $kunci = date('Y-m', $waktu);
    $bulan_grafik[] = array(
        'label' => $nama_bulan[date('n', $waktu) - 1],
        'pend' => hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM pendaftaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$kunci'")
    );
}

// ARSIP LAPORAN
$q = isset($_GET['q']) ? bersih($koneksi, $_GET['q']) : '';
$kondisi_arsip = "1=1";
if ($q != '') {
    $kondisi_arsip .= " AND (judul LIKE '%$q%' OR catatan LIKE '%$q%' OR oleh LIKE '%$q%')";
}
$total = hitung($koneksi, "SELECT COUNT(*) AS jumlah FROM laporan WHERE $kondisi_arsip");
$halaman = halaman_sekarang($total);
$mulai = ($halaman - 1) * PER_HALAMAN;
$arsip = ambil_semua($koneksi, "SELECT * FROM laporan WHERE $kondisi_arsip ORDER BY dibuat DESC, id DESC LIMIT $mulai, " . PER_HALAMAN);
$judul_baru = 'Laporan ' . tgl($dari) . ' s/d ' . tgl($sampai);

// FUNGSI CSV
function buat_csv($kepala, $baris) {
    $semua = array_merge(array($kepala), $baris);
    $hasil = array();
    foreach ($semua as $b) {
        $kolom = array();
        foreach ($b as $isi_kolom) {
            $kolom[] = '"' . str_replace('"', '""', (string)$isi_kolom) . '"';
        }
        $hasil[] = implode(',', $kolom);
    }
    return implode("\n", $hasil);
}

// BAGIAN 3: TAMPILAN
$judul_halaman = 'Laporan';
$menu_aktif = 'laporan';
$css_tambahan = array('dashboard.css', 'laporan.css');
$js_tambahan = array('laporan.js');
include 'includes/header.php';
?>

<div class="phead">
    <div>
        <h1>Laporan</h1>
        <p>Ringkasan layanan berdasarkan data yang ada di semua halaman</p>
    </div>
    <div>
        <button type="button" class="btn" onclick="tambahLaporan(<?= e(json_encode(array('judul' => $judul_baru, 'dari' => $dari, 'sampai' => $sampai))) ?>)">
            <?= ikon('plus') ?> Simpan Laporan
        </button>
    </div>
</div>

<!-- FILTER PERIODE -->
<section class="card rfilter">
    <form method="get" style="display:contents">
        <div class="fld">
            <label for="rep-from">Dari tanggal</label>
            <input id="rep-from" type="date" name="dari" value="<?= e($dari) ?>" onchange="this.form.submit()">
        </div>
        <div class="fld">
            <label for="rep-to">Sampai tanggal</label>
            <input id="rep-to" type="date" name="sampai" value="<?= e($sampai) ?>" onchange="this.form.submit()">
        </div>
        <a class="btn ghost" href="laporan.php">Atur Ulang Periode</a>
    </form>
</section>

<!-- KARTU RINGKASAN -->
<section class="sum">
    <?php foreach ($ringkasan as $r) { ?>
        <div class="stat s-<?= $r[3] ?>">
            <span class="si"><?= ikon($r[2]) ?></span>
            <span class="sb">
                <small><?= e($r[0]) ?></small>
                <b><?= e($r[1]) ?></b>
            </span>
        </div>
    <?php } ?>
</section>

<div class="cols">
    <!-- LAPORAN DETAIL -->
    <section class="card panel">
        <div class="ph">
            <h2>Laporan Detail</h2>
        </div>
        <div class="tw">
            <table style="min-width:420px">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Laporan</th>
                        <th>Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporan_detail as $i => $lap) { ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><b><?= e($lap[0]) ?></b></td>
                            <td><?= count($lap[2]) ?></td>
                            <td>
                                <div class="acts">
                                    <button type="button" class="ab view" title="Lihat" aria-label="Lihat <?= e($lap[0]) ?>" onclick="bukaIsi('<?= e($lap[0]) ?> (<?= count($lap[2]) ?>)', 'lap-<?= $i ?>')">
                                        <?= ikon('eye') ?>
                                    </button>
                                    <button type="button" class="ab edit" title="Salin CSV" aria-label="Salin CSV <?= e($lap[0]) ?>" onclick="bukaCsv('<?= e($lap[0]) ?>', 'csv-<?= $i ?>')">
                                        <?= ikon('copy') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- GRAFIK 6 BULAN -->
    <section class="card panel">
        <div class="ph">
            <h2>Enam Bulan Terakhir</h2>
        </div>
        <div class="legend">
            <span><i style="background:#121358"></i> Pendaftaran</span>
        </div>
        <?php
        $nilai_max = 5;
        foreach ($bulan_grafik as $b) {
            $nilai_max = max($nilai_max, $b['pend']);
        }
        $atas = ceil($nilai_max / 5) * 5;
        $lebar = 480;
        $tinggi = 230;
        $kiri = 34;
        $kanan = 8;
        $atas_pad = 14;
        $bawah = 28;
        $lebar_area = $lebar - $kiri - $kanan;
        $tinggi_area = $tinggi - $atas_pad - $bawah;
        $lebar_grup = $lebar_area / 6;
        $lebar_batang = 25;

        function posisi_y($nilai, $atas, $atas_pad, $tinggi_area) {
            return $atas_pad + $tinggi_area - ($nilai / $atas) * $tinggi_area;
        }
        ?>
        <svg class="chart" viewBox="0 0 <?= $lebar ?> <?= $tinggi ?>" role="img" aria-label="Grafik pendaftaran enam bulan terakhir">
            <?php for ($i = 0; $i <= 5; $i++) {
                $nilai = $i * $atas / 5;
                $y = posisi_y($nilai, $atas, $atas_pad, $tinggi_area);
            ?>
                <line x1="<?= $kiri ?>" x2="<?= $lebar - $kanan ?>" y1="<?= $y ?>" y2="<?= $y ?>" stroke="#e2e5f3"/>
                <text x="<?= $kiri - 8 ?>" y="<?= $y + 4 ?>" text-anchor="end" font-size="11" fill="#60638a"><?= $nilai ?></text>
            <?php } ?>

            <?php foreach ($bulan_grafik as $i => $b) {
                $tengah = $kiri + $i * $lebar_grup + $lebar_grup / 2;
                if ($b['pend'] > 0) {
                    $y = posisi_y($b['pend'], $atas, $atas_pad, $tinggi_area);
                    $tinggi_batang = $atas_pad + $tinggi_area - $y;
            ?>
                    <rect x="<?= $tengah - $lebar_batang / 2 ?>" y="<?= $y ?>" width="<?= $lebar_batang ?>" height="<?= $tinggi_batang ?>" rx="3" fill="#121358"/>
                    <text x="<?= $tengah ?>" y="<?= $y - 4 ?>" text-anchor="middle" font-size="11" font-weight="700" fill="#181a45"><?= $b['pend'] ?></text>
                <?php } ?>
                <text x="<?= $tengah ?>" y="<?= $tinggi - 8 ?>" text-anchor="middle" font-size="12" fill="#60638a"><?= e($b['label']) ?></text>
            <?php } ?>
        </svg>
    </section>
</div>

<!-- ARSIP LAPORAN -->
<div class="phead">
    <div>
        <h2 style="font-size:18px;font-weight:800;color:var(--navy)">Arsip Laporan</h2>
        <p>Laporan yang sudah disimpan, bisa dilihat, diubah, dan dihapus</p>
    </div>
</div>

<section class="card tcard">
    <!-- PENCARIAN -->
    <form class="toolbar" method="get">
        <input type="hidden" name="dari" value="<?= e($dari) ?>">
        <input type="hidden" name="sampai" value="<?= e($sampai) ?>">
        <label class="search">
            <?= ikon('search') ?>
            <input type="search" name="q" placeholder="Cari laporan..." value="<?= e(isset($_GET['q']) ? $_GET['q'] : '') ?>" aria-label="Cari laporan">
        </label>
    </form>

    <!-- TABEL ARSIP -->
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Laporan</th>
                    <th>Periode</th>
                    <th>Dibuat Oleh</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($arsip) == 0) { ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty">
                                <?= ($q != '') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada laporan tersimpan.' ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>

                <?php
                $no = $mulai + 1;
                foreach ($arsip as $r) {
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <b><?= e($r['judul']) ?></b>
                            <?= ($r['catatan'] != '') ? '<small>' . e($r['catatan']) . '</small>' : '' ?>
                        </td>
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

<!-- POPUP LAPORAN DETAIL -->
<?php foreach ($laporan_detail as $i => $lap) { ?>
    <!-- ISI TABEL -->
    <div id="lap-<?= $i ?>" hidden>
        <?php if (count($lap[2]) == 0) { ?>
            <div class="empty">Tidak ada data pada periode ini.</div>
        <?php } else { ?>
            <div class="tw">
                <table style="min-width:480px">
                    <thead>
                        <tr>
                            <?php foreach ($lap[1] as $kepala) { ?>
                                <th><?= e($kepala) ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lap[2] as $baris_data) { ?>
                            <tr>
                                <?php foreach ($baris_data as $sel) { ?>
                                    <td><?= e($sel) ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <!-- TEKS CSV -->
    <div id="csv-<?= $i ?>" hidden>
        <p class="muted">Salin teks di bawah, lalu tempel di Excel atau Notepad.</p>
        <textarea class="csv" readonly><?= e(buat_csv($lap[1], $lap[2])) ?></textarea>
    </div>
<?php } ?>

<?php include 'includes/footer.php'; ?>
