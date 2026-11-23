// ==========================================================
// File: js/laporan.js
// Fungsi: JavaScript khusus halaman Laporan.
// Berisi popup TAMBAH, UBAH, LIHAT arsip laporan, dan Salin CSV.
// ==========================================================

function isianLaporan() {
    return [
        { nama: 'judul',   label: 'Judul Laporan', wajib: true, penuh: true },
        { nama: 'dari',    label: 'Dari Tanggal',  tipe: 'date', wajib: true },
        { nama: 'sampai',  label: 'Sampai Tanggal', tipe: 'date', wajib: true },
        { nama: 'catatan', label: 'Catatan',       tipe: 'textarea', penuh: true }
    ];
}

// Popup TAMBAH. Judul dan periode awal diambil dari filter di halaman.
function tambahLaporan(awal) {
    bukaForm({
        judul: 'Simpan Laporan',
        file: 'laporan.php',
        id: 0,
        tombol: 'Simpan',
        fields: isianLaporan(),
        data: awal
    });
}

// Popup UBAH
function ubahLaporan(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaForm({
        judul: 'Ubah Laporan',
        file: 'laporan.php',
        id: d.id,
        tombol: 'Simpan Perubahan',
        fields: isianLaporan(),
        data: d
    });
}

// Popup LIHAT
function lihatLaporan(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Laporan', [
        ['Judul Laporan',        d.judul],
        ['Catatan',              d.catatan],
        ['Periode',              tgl(d.dari) + ' – ' + tgl(d.sampai)],
        ['Total Pendaftaran',    d.jml_pendaftaran],
        ['Total Permohonan',     d.jml_permohonan],
        ['Permohonan Disetujui', d.jml_disetujui],
        ['Permohonan Ditolak',   d.jml_ditolak],
        ['Antrean Selesai',      d.jml_antrean],
        ['Dibuat Oleh',          d.oleh]
    ], function () {
        ubahLaporan(tombol);
    });
}

// Popup Salin CSV: menampilkan teks CSV dan tombol "Salin"
function bukaCsv(judul, idElemen) {
    var isiHtml = '<div class="mbody">' + document.getElementById(idElemen).innerHTML + '</div>';
    var footer = '<button type="button" class="btn ghost" onclick="tutupModal()">Tutup</button>';
    footer += '<button type="button" class="btn" onclick="salinCsv()">Salin CSV</button>';
    bukaModal('Salin CSV: ' + judul, isiHtml, footer, true);

    // teks langsung terpilih supaya tinggal Ctrl+C
    var kotak = document.querySelector('#modal textarea');
    kotak.focus();
    kotak.select();
}

function salinCsv() {
    var kotak = document.querySelector('#modal textarea');
    kotak.select();
    document.execCommand('copy');
    alert('Data laporan disalin dalam format CSV.');
}
