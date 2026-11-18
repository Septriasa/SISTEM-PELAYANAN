
<?php
include_once __DIR__ . '/config/koneksi.php';
include_once __DIR__ . '/includes/fungsi.php';

$admin_login = cek_login($koneksi);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lokasi file database SQL
    $file_sql = __DIR__ . '/database/ksop.sql';

    // Pastikan file SQL tersedia
    if (!file_exists($file_sql)) {
        die("Error: File database/ksop.sql tidak ditemukan.");
    }

    // Baca isi file SQL
    $sql = file_get_contents($file_sql);

    if ($sql === false || trim($sql) === '') {
        die("Error: File SQL kosong atau tidak dapat dibaca.");
    }

    // Jalankan perintah SQL
    if (mysqli_multi_query($koneksi, $sql)) {

        do {
            $hasil = mysqli_store_result($koneksi);

            if ($hasil) {
                mysqli_free_result($hasil);
            }

            // Periksa error pada query berikutnya
            if (mysqli_more_results($koneksi)) {
                if (!mysqli_next_result($koneksi)) {
                    die("Gagal menjalankan SQL: " . mysqli_error($koneksi));
                }
            } else {
                break;
            }

        } while (true);

        set_pesan('Data contoh berhasil dikembalikan.');

    } else {
        die("Gagal menjalankan SQL: " . mysqli_error($koneksi));
    }
}

// Kembali ke dashboard
pindah('dashboard.php');
