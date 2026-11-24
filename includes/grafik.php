<?php
/**
 * includes/grafik.php
 * Data + tampilan grafik batang & grafik lingkaran.
 *
 * Dipakai di dashboard.php DAN laporan.php, jadi angkanya selalu sama
 * karena dihitung dari tabel `pendaftaran` dengan cara yang sama.
 *
 * Butuh fungsi dari includes/fungsi.php: ambil_semua(), kondisi_tanggal(), e()
 */

const GRAFIK_BULAN = array(
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
);

/* ============================================================
 * 1. AMBIL DATA
 * ============================================================ */

/**
 * Jumlah pendaftaran Disetujui & Ditolak per bulan (Juli - Desember).
 * Return: [7 => ['label'=>'Juli','disetujui'=>70,'ditolak'=>5], ...]
 */
function grafik_ambil_bulanan($koneksi, $tahun)
{
    $tahun = (int)$tahun;

    $hasil = array();
    foreach (GRAFIK_BULAN as $no => $nama) {
        $hasil[$no] = array('label' => $nama, 'disetujui' => 0, 'ditolak' => 0);
    }

    $baris = ambil_semua($koneksi, "
        SELECT MONTH(tanggal) AS bln, status, COUNT(*) AS jml
        FROM pendaftaran
        WHERE YEAR(tanggal) = $tahun
          AND MONTH(tanggal) BETWEEN 7 AND 12
          AND status IN ('Disetujui', 'Ditolak')
        GROUP BY MONTH(tanggal), status
    ");

    foreach ($baris as $r) {
        $bln = (int)$r['bln'];
        if (!isset($hasil[$bln])) {
            continue;
        }
        $kunci = ($r['status'] == 'Disetujui') ? 'disetujui' : 'ditolak';
        $hasil[$bln][$kunci] = (int)$r['jml'];
    }
    return $hasil;
}

/**
 * Jumlah pendaftaran per status pada periode $dari s/d $sampai.
 * Pakai kondisi_tanggal() yang sama dengan laporan.php.
 */
function grafik_ambil_status($koneksi, $dari, $sampai)
{
    $k = kondisi_tanggal($koneksi, $dari, $sampai);

    $hasil = array('disetujui' => 0, 'ditolak' => 0, 'menunggu' => 0, 'total' => 0);

    $baris = ambil_semua($koneksi, "
        SELECT status, COUNT(*) AS jml
        FROM pendaftaran
        WHERE $k
        GROUP BY status
    ");

    foreach ($baris as $r) {
        $jml = (int)$r['jml'];
        if ($r['status'] == 'Disetujui') {
            $hasil['disetujui'] += $jml;
        } elseif ($r['status'] == 'Ditolak') {
            $hasil['ditolak'] += $jml;
        } elseif ($r['status'] == 'Menunggu Verifikasi') {
            $hasil['menunggu'] += $jml;
        } else {
            continue;
        }
        $hasil['total'] += $jml;
    }
    return $hasil;
}

/* ============================================================
 * 2. GRAFIK BATANG (animasi naik ke atas)
 * ============================================================ */
function grafik_render_batang($data)
{
    $maks = 0;
    foreach ($data as $d) {
        $maks = max($maks, $d['disetujui'], $d['ditolak']);
    }
    $skala = max(5, (int)(ceil($maks / 5) * 5));

    echo '<div class="gb">';

    // sumbu Y
    echo '<div class="gb-y">';
    for ($i = 4; $i >= 0; $i--) {
        echo '<span>' . (int)round($skala * $i / 4) . '</span>';
    }
    echo '</div>';

    echo '<div class="gb-area">';
    echo '<div class="gb-grid">';
    for ($i = 0; $i < 5; $i++) {
        echo '<i></i>';
    }
    echo '</div>';

    echo '<div class="gb-kolom-wrap">';
    $urut = 0;
    foreach ($data as $d) {
        $tinggi_s = $d['disetujui'] / $skala * 100;
        $tinggi_t = $d['ditolak'] / $skala * 100;
        $delay_s  = $urut * 0.12;
        $delay_t  = $delay_s + 0.06;
        $label    = e($d['label']);

        echo '<div class="gb-grup">';
        echo '<div class="gb-batang-wrap">';
        echo '<div class="gb-batang setuju" style="height:' . $tinggi_s . '%;animation-delay:' . $delay_s . 's" title="' . $label . ' - Disetujui: ' . $d['disetujui'] . '"><em>' . $d['disetujui'] . '</em></div>';
        echo '<div class="gb-batang tolak" style="height:' . $tinggi_t . '%;animation-delay:' . $delay_t . 's" title="' . $label . ' - Ditolak: ' . $d['ditolak'] . '"><em>' . $d['ditolak'] . '</em></div>';
        echo '</div>';
        echo '<span class="gb-label">' . $label . '</span>';
        echo '</div>';
        $urut++;
    }
    echo '</div>'; // gb-kolom-wrap
    echo '</div>'; // gb-area
    echo '</div>'; // gb

    echo '<ul class="g-legend">';
    echo '<li><span class="gdot setuju"></span>Disetujui</li>';
    echo '<li><span class="gdot tolak"></span>Ditolak</li>';
    echo '</ul>';
}

/* ============================================================
 * 3. GRAFIK LINGKARAN (persentase + keterangan warna)
 * ============================================================ */
function grafik_render_lingkaran($s)
{
    $total = $s['total'];
    $irisan = array(
        array('kunci' => 'setuju', 'nama' => 'Disetujui',           'jumlah' => $s['disetujui']),
        array('kunci' => 'tolak',  'nama' => 'Ditolak',             'jumlah' => $s['ditolak']),
        array('kunci' => 'tunggu', 'nama' => 'Menunggu Verifikasi', 'jumlah' => $s['menunggu']),
    );

    if ($total <= 0) {
        echo '<p class="g-kosong">Belum ada data pendaftaran untuk ditampilkan.</p>';
        return;
    }

    echo '<div class="gp">';
    echo '<svg class="gp-svg" viewBox="-1.1 -1.1 2.2 2.2" role="img" aria-label="Grafik lingkaran status pendaftaran">';

    $sudut = -M_PI / 2; // mulai dari jam 12
    foreach ($irisan as $i) {
        if ($i['jumlah'] <= 0) {
            continue;
        }

        $persen  = $i['jumlah'] / $total * 100;
        $selisih = $i['jumlah'] / $total * 2 * M_PI;
        $penuh   = ($i['jumlah'] == $total);

        if ($penuh) {
            echo '<circle class="gp-iris ' . $i['kunci'] . '" r="1" cx="0" cy="0"/>';
        } else {
            $x1 = cos($sudut);
            $y1 = sin($sudut);
            $x2 = cos($sudut + $selisih);
            $y2 = sin($sudut + $selisih);
            $besar = ($selisih > M_PI) ? 1 : 0;
            echo '<path class="gp-iris ' . $i['kunci'] . '" d="M0 0 L' . round($x1, 5) . ' ' . round($y1, 5)
               . ' A1 1 0 ' . $besar . ' 1 ' . round($x2, 5) . ' ' . round($y2, 5) . ' Z"/>';
        }

        if ($persen >= 5) {
            $tengah = $sudut + $selisih / 2;
            $tx = $penuh ? 0 : cos($tengah) * 0.62;
            $ty = $penuh ? 0 : sin($tengah) * 0.62;
            echo '<text class="gp-teks" x="' . round($tx, 4) . '" y="' . round($ty, 4) . '">'
               . number_format($persen, 1, ',', '') . '%</text>';
        }
        $sudut += $selisih;
    }
    echo '</svg>';
    echo '</div>';

    // keterangan warna di bawah grafik
    echo '<ul class="g-legend g-legend-lingkaran">';
    foreach ($irisan as $i) {
        $persen = $i['jumlah'] / $total * 100;
        echo '<li><span class="gdot ' . $i['kunci'] . '"></span>'
           . e($i['nama'])
           . ' <b>' . $i['jumlah'] . '</b> (' . number_format($persen, 1, ',', '') . '%)</li>';
    }
    echo '</ul>';
}
