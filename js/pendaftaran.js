// ==========================================================
// File: js/pendaftaran.js
// Fungsi: JavaScript khusus halaman Data Pendaftaran.
// Berisi popup UBAH dan popup LIHAT. (Tidak ada popup tambah.)
// Fungsi bukaForm() dan bukaLihat() ada di js/app.js
// ==========================================================

// Popup UBAH. "tombol" adalah tombol yang diklik. Data barisnya
// disimpan PHP di atribut data-json, lalu dibaca di sini.
function ubahPendaftaran(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaForm({
        judul: 'Ubah Pendaftaran',
        file: 'pendaftaran.php',
        id: d.id,
        tombol: 'Simpan Perubahan',
        data: d,
        fields: [
            { nama: 'nama',       label: 'Nama Lengkap',    wajib: true, ph: 'Contoh: Andi Pratama' },
            { nama: 'perusahaan', label: 'Perusahaan',      wajib: true, ph: 'Pengguna Jasa (untuk perorangan)' },
            { nama: 'email',      label: 'Email',           tipe: 'email', ph: 'nama@perusahaan.co.id' },
            { nama: 'telepon',    label: 'Telepon',         tipe: 'tel', ph: '0812-0000-0000' },
            { nama: 'tanggal',    label: 'Tanggal Daftar',  tipe: 'date', wajib: true },
            { nama: 'status',     label: 'Status',          tipe: 'select', pilihan: ['Menunggu Verifikasi', 'Disetujui', 'Ditolak'] },
            { nama: 'catatan',    label: 'Catatan',         tipe: 'textarea', penuh: true }
        ]
    });
}

// Popup LIHAT detail
function lihatPendaftaran(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Pendaftaran', [
        ['Nama Lengkap',   d.nama],
        ['Perusahaan',     d.perusahaan],
        ['Email',          d.email],
        ['Telepon',        d.telepon],
        ['Tanggal Daftar', tgl(d.tanggal)],
        ['Status',         pill(d.status), true],
        ['Catatan',        d.catatan]
    ], function () {
        ubahPendaftaran(tombol);   // tombol "Ubah" di dalam popup lihat
    });
}
