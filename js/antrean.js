// ==========================================================
// File: js/antrean.js
// Fungsi: JavaScript khusus halaman Nomor Antrean.
// Berisi popup TAMBAH, UBAH, dan LIHAT.
// ==========================================================

var jenisLayanan = ['Pelayanan Kapal Masuk', 'Surat Izin Berlayar', 'Sertifikat Keselamatan Kapal', 'Pendaftaran Kapal', 'Pemanduan Kapal', 'Laporan Kegiatan Kapal'];

function isianAntrean() {
    return [
        { nama: 'nomor',   label: 'Nomor Antrean', readonly: true },
        { nama: 'nama',    label: 'Nama Pemohon',  wajib: true, ph: 'Nama lengkap pemohon' },
        { nama: 'layanan', label: 'Jenis Layanan', tipe: 'select', pilihan: jenisLayanan },
        { nama: 'tanggal', label: 'Tanggal',       tipe: 'date', wajib: true },
        { nama: 'status',  label: 'Status',        tipe: 'select', pilihan: ['Menunggu', 'Dipanggil', 'Selesai', 'Batal'] }
    ];
}

// Popup TAMBAH (tombol "Buat Antrean Baru")
function tambahAntrean(nomorBaru) {
    var hariIni = new Date().toISOString().substr(0, 10);

    bukaForm({
        judul: 'Buat Antrean Baru',
        file: 'antrean.php',
        id: 0,
        tombol: 'Simpan',
        fields: isianAntrean(),
        data: { nomor: nomorBaru, layanan: jenisLayanan[0], tanggal: hariIni, status: 'Menunggu' }
    });
}

// Popup UBAH
function ubahAntrean(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaForm({
        judul: 'Ubah Antrean',
        file: 'antrean.php',
        id: d.id,
        tombol: 'Simpan Perubahan',
        fields: isianAntrean(),
        data: d
    });
}

// Popup LIHAT
function lihatAntrean(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Antrean', [
        ['Nomor Antrean', d.nomor],
        ['Nama Pemohon',  d.nama],
        ['Jenis Layanan', d.layanan],
        ['Tanggal',       tgl(d.tanggal)],
        ['Status',        pill(d.status), true]
    ], function () {
        ubahAntrean(tombol);
    });
}
