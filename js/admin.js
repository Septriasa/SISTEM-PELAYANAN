// ==========================================================
// File: js/admin.js
// Fungsi: JavaScript khusus halaman Kelola Admin.
// Berisi popup TAMBAH, UBAH, dan LIHAT.
// ==========================================================

// Isian form. Untuk admin yang sudah ada, kolom kata sandi boleh dikosongkan.
function isianAdmin(adminBaru) {
    return [
        { nama: 'nama',     label: 'Nama Lengkap', wajib: true },
        { nama: 'username', label: 'Username',     wajib: true, ph: 'huruf kecil tanpa spasi' },
        { nama: 'email',    label: 'Email',        tipe: 'email' },
        { nama: 'password', label: 'Kata Sandi',   tipe: 'password', wajib: adminBaru, ph: adminBaru ? 'Minimal 6 karakter' : 'Kosongkan jika tidak diubah' },
        { nama: 'peran',    label: 'Peran',        tipe: 'select', pilihan: ['Super Admin', 'Operator'] },
        { nama: 'status',   label: 'Status Akun',  tipe: 'select', pilihan: ['Aktif', 'Nonaktif'] }
    ];
}

// Popup TAMBAH
function tambahAdmin() {
    bukaForm({
        judul: 'Tambah Admin',
        file: 'admin.php',
        id: 0,
        tombol: 'Simpan',
        fields: isianAdmin(true),
        data: { peran: 'Operator', status: 'Aktif' }
    });
}

// Popup UBAH. Kalau yang diubah akun sendiri, judulnya "Profil Saya".
function ubahAdmin(tombol) {
    var d = JSON.parse(tombol.dataset.json);
    var akunSendiri = tombol.dataset.akunSendiri === '1';

    bukaForm({
        judul: akunSendiri ? 'Profil Saya' : 'Ubah Admin',
        file: 'admin.php',
        id: d.id,
        tombol: 'Simpan Perubahan',
        fields: isianAdmin(false),
        data: d
    });
}

// Popup LIHAT (kata sandi tidak ditampilkan)
function lihatAdmin(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Admin', [
        ['Nama Lengkap', d.nama],
        ['Username',     d.username],
        ['Email',        d.email],
        ['Peran',        d.peran],
        ['Status Akun',  pill(d.status), true]
    ], function () {
        ubahAdmin(tombol);
    });
}

// Menu "Profil Saya" di pojok kanan atas membuka halaman ini dengan ?profil=1
// Kalau ada, langsung buka popup ubah untuk akun sendiri.
if (window.location.search.indexOf('profil=1') !== -1) {
    var tombolSendiri = document.querySelector('[data-akun-sendiri="1"]');
    if (tombolSendiri) {
        ubahAdmin(tombolSendiri);
    }
}
