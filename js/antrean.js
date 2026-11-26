// ==========================================================
// File: js/antrean.js
// Fungsi: JavaScript khusus halaman Nomor Antrian.
// Berisi popup LIHAT (ikon mata) saja.
// ==========================================================

// Popup LIHAT
function lihatAntrian(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Antrian', [
        ['Nomor Antrian', d.nomor],
        ['Nama Pemohon',  d.nama],
        ['Jenis Layanan', d.layanan],
        ['Tanggal',       tgl(d.tanggal)],
        ['Status',        pill(d.status), true]
    ]);
}