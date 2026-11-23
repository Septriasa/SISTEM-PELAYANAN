// ==========================================================
// File: js/antrean.js
// Fungsi: JavaScript khusus halaman Nomor Antrian.
// Berisi popup UBAH dan LIHAT.
// (Antrian baru tidak dibuat dari halaman ini.)
// ==========================================================

var jenisLayanan = ['Pelayanan Kapal Masuk', 'Surat Izin Berlayar', 'Sertifikat Keselamatan Kapal', 'Pendaftaran Kapal', 'Pemanduan Kapal', 'Laporan Kegiatan Kapal'];

function isianAntrian() {
    return [
        { nama: 'nomor',   label: 'Nomor Antrian', readonly: true },
        { nama: 'nama',    label: 'Nama Pemohon',  wajib: true, ph: 'Nama lengkap pemohon' },
        { nama: 'layanan', label: 'Jenis Layanan', tipe: 'select', pilihan: jenisLayanan },
        { nama: 'tanggal', label: 'Tanggal',       tipe: 'date', wajib: true },
        { nama: 'status',  label: 'Status',        tipe: 'select', pilihan: ['Menunggu', 'Dipanggil', 'Selesai', 'Di Tolak'] }
    ];
}

// Popup UBAH
function ubahAntrian(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaForm({
        judul: 'Ubah Antrian',
        file: 'antrean.php',
        id: d.id,
        tombol: 'Simpan Perubahan',
        fields: isianAntrian(),
        data: d
    });
}

// Popup LIHAT
function lihatAntrian(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Antrian', [
        ['Nomor Antrian', d.nomor],
        ['Nama Pemohon',  d.nama],
        ['Jenis Layanan', d.layanan],
        ['Tanggal',       tgl(d.tanggal)],
        ['Status',        pill(d.status), true]
    ], function () {
        ubahAntrian(tombol);
    });
}
