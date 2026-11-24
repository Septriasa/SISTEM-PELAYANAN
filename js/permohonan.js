var jenisLayanan = ['Pelayanan Kapal Masuk', 'Surat Izin Berlayar', 'Sertifikat Keselamatan Kapal', 'Pendaftaran Kapal', 'Pemanduan Kapal', 'Laporan Kegiatan Kapal'];

// daftar isian form (dipakai oleh popup tambah dan popup ubah)
function isianPermohonan() {
    return [
        { nama: 'nomor',      label: 'Nomor Permohonan',   readonly: true },
        { nama: 'pemohon',    label: 'Nama Pemohon',       wajib: true, ph: 'Nama lengkap pemohon' },
        { nama: 'layanan',    label: 'Jenis Layanan',      tipe: 'select', pilihan: jenisLayanan },
        { nama: 'kapal',      label: 'Nama Kapal',         wajib: true, ph: 'Contoh: KM Sinar Bahari' },
        { nama: 'gt',         label: 'Tonase Kapal (GT)',  tipe: 'number', ph: 'Contoh: 520' },
        { nama: 'tanggal',    label: 'Tanggal Permohonan', tipe: 'date', wajib: true },
        { nama: 'status',     label: 'Status',             tipe: 'select', pilihan: ['Diproses', 'Disetujui', 'Ditolak'] },
        { nama: 'keterangan', label: 'Keterangan',         tipe: 'textarea', penuh: true }
    ];
}

// Popup TAMBAH. Nomor baru dikirim dari PHP.
function tambahPermohonan(nomorBaru) {
    var hariIni = new Date().toISOString().substr(0, 10);

    bukaForm({
        judul: 'Tambah Permohonan',
        file: 'permohonan.php',
        id: 0,
        tombol: 'Simpan',
        fields: isianPermohonan(),
        data: { nomor: nomorBaru, layanan: jenisLayanan[0], tanggal: hariIni, status: 'Diproses' }
    });
}

// Popup UBAH
function ubahPermohonan(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaForm({
        judul: 'Ubah Permohonan',
        file: 'permohonan.php',
        id: d.id,
        tombol: 'Simpan Perubahan',
        fields: isianPermohonan(),
        data: d
    });
}

// Popup LIHAT
function lihatPermohonan(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Permohonan', [
        ['Nomor Permohonan',   d.nomor],
        ['Nama Pemohon',       d.pemohon],
        ['Jenis Layanan',      d.layanan],
        ['Nama Kapal',         d.kapal],
        ['Tonase Kapal (GT)',  d.gt],
        ['Tanggal Permohonan', tgl(d.tanggal)],
        ['Status',             pill(d.status), true],
        ['Keterangan',         d.keterangan]
    ], function () {
        ubahPermohonan(tombol);
    });
}
