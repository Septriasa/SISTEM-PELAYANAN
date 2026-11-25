// ==========================================================
// File: js/app.js
// Fungsi: JavaScript yang dipakai SEMUA halaman.
//   1. buka / tutup sidebar dan menu profil
//   2. popup (modal): form tambah / ubah, lihat detail, konfirmasi hapus
//   3. pesan sukses hilang sendiri
// JavaScript khusus tiap halaman ada di file js/<nama-halaman>.js
// Data tidak disimpan di JavaScript. Semua data ada di database (PHP).
// ==========================================================


// ---------- 1. Fungsi kecil yang sering dipakai ----------

// esc() = membuat teks aman ditampilkan di HTML
function esc(teks) {
    if (teks === null || teks === undefined) {
        return '';
    }
    return String(teks)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// 2026-11-17 -> 17/11/2026
function tgl(tanggal) {
    if (!tanggal) {
        return '-';
    }
    var p = String(tanggal).substr(0, 10).split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

// label status berwarna (kelas warnanya ada di css/style.css)
function pill(status) {
    var kelas = String(status).toLowerCase().replace(/ /g, '-');
    return '<span class="pill p-' + kelas + '">' + esc(status) + '</span>';
}

// kalau kosong tampilkan "-"
function isi(teks) {
    if (teks === null || teks === undefined || teks === '') {
        return '-';
    }
    return teks;
}

var IKON_TUTUP = '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
var IKON_UBAH = '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
var IKON_HAPUS = '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>';


// ---------- 2. Sidebar dan menu profil ----------

function ubahSidebar() {
    var app = document.getElementById('app');
    if (window.innerWidth < 900) {
        app.classList.toggle('drawer-open');   // di HP: sidebar geser masuk
    } else {
        app.classList.toggle('collapsed');     // di laptop: sidebar mengecil
    }
}

function ubahMenuProfil() {
    var menu = document.getElementById('menu-profil');
    var tombol = document.getElementById('tombol-profil-menu');
    menu.hidden = !menu.hidden;
    tombol.setAttribute('aria-expanded', menu.hidden ? 'false' : 'true');
}

// klik di luar menu profil = menu tertutup
document.addEventListener('click', function (e) {
    var menu = document.getElementById('menu-profil');
    if (menu && !menu.hidden && !e.target.closest('.pf')) {
        menu.hidden = true;
    }
});


// ---------- 3. Popup (modal) ----------

// Membuat popup. Parameter:
//   judul  = tulisan di bagian atas popup
//   isi    = isi popup (HTML)
//   footer = tombol-tombol di bagian bawah (HTML)
//   lebar  = true kalau popup dibuat lebih lebar
function bukaModal(judul, isi, footer, lebar) {
    var modal = document.getElementById('modal');
    var html = '';
    html += '<div class="mback">';
    html += '<div class="mbox' + (lebar ? ' wide' : '') + '" role="dialog" aria-modal="true" aria-label="' + esc(judul) + '">';
    html += '<div class="mhead"><h3>' + esc(judul) + '</h3>';
    html += '<button type="button" class="icon-btn" onclick="tutupModal()" aria-label="Tutup">' + IKON_TUTUP + '</button></div>';
    html += isi;
    if (footer) {
        html += '<div class="mfoot">' + footer + '</div>';
    }
    html += '</div></div>';
    modal.innerHTML = html;
    modal.hidden = false;

    // kursor langsung ke isian pertama
    var kolom = modal.querySelector('input:not([readonly]):not([type=hidden]), select, textarea');
    if (kolom) {
        kolom.focus();
    }
}

function tutupModal() {
    var modal = document.getElementById('modal');
    modal.hidden = true;
    modal.innerHTML = '';
}

// klik area gelap di luar kotak = popup tertutup
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('mback')) {
        tutupModal();
    }
});

// tombol Esc = popup tertutup
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        tutupModal();
    }
});


// ---------- 4. Popup FORM (tambah / ubah data) ----------
// Contoh pemakaian ada di js/permohonan.js
//
// opsi = {
//   judul  : 'Tambah Permohonan',
//   file   : 'permohonan.php',     (file PHP yang menyimpan data)
//   id     : 0,                    (0 = tambah baru, angka lain = ubah)
//   tombol : 'Simpan',
//   fields : [ {nama, label, tipe, wajib, pilihan, penuh, readonly, ph} ],
//   data   : { ...nilai awal... }
// }
function bukaForm(opsi) {
    var data = opsi.data || {};
    var html = '<form method="post" action="' + opsi.file + '" id="form-modal" class="mbody">';
    html += '<input type="hidden" name="aksi" value="simpan">';
    html += '<input type="hidden" name="id" value="' + (opsi.id || 0) + '">';
    html += '<div class="fgrid">';

    for (var i = 0; i < opsi.fields.length; i++) {
        var f = opsi.fields[i];
        var nilai = (data[f.nama] === undefined || data[f.nama] === null) ? '' : data[f.nama];
        var idKolom = 'f_' + f.nama;

        html += '<div class="fld' + (f.penuh ? ' full' : '') + '">';
        html += '<label for="' + idKolom + '">' + f.label + (f.wajib ? ' <i>*</i>' : '') + '</label>';

        if (f.tipe === 'select') {
            html += '<select id="' + idKolom + '" name="' + f.nama + '">';
            for (var j = 0; j < f.pilihan.length; j++) {
                var terpilih = (f.pilihan[j] === nilai) ? ' selected' : '';
                html += '<option' + terpilih + '>' + esc(f.pilihan[j]) + '</option>';
            }
            html += '</select>';
        } else if (f.tipe === 'textarea') {
            html += '<textarea id="' + idKolom + '" name="' + f.nama + '" rows="3">' + esc(nilai) + '</textarea>';
        } else {
            html += '<input id="' + idKolom + '" name="' + f.nama + '" type="' + (f.tipe || 'text') + '" value="' + esc(nilai) + '" placeholder="' + esc(f.ph || '') + '"';
            if (f.wajib) {
                html += ' required';
            }
            if (f.readonly) {
                html += ' readonly';
            }
            if (f.tipe === 'password') {
                html += ' autocomplete="new-password"';
            }
            html += '>';
        }
        html += '</div>';
    }
    html += '</div></form>';

    var footer = '<button type="button" class="btn ghost" onclick="tutupModal()">Kembali</button>';
    footer += '<button type="submit" class="btn" form="form-modal">' + (opsi.tombol || 'Simpan') + '</button>';

    bukaModal(opsi.judul, html, footer, false);
}


// ---------- 5. Popup LIHAT detail ----------
// baris = [ ['Label', 'isi'], ['Status', '<span>..</span>', true] ]
// Isi otomatis di-escape, kecuali elemen ketiga bernilai true (berarti HTML)
// fungsiUbah = fungsi yang dijalankan tombol "Ubah" (boleh kosong)
var fungsiUbahSekarang = null;

function bukaLihat(judul, baris, fungsiUbah) {
    var html = '<div class="mbody"><dl class="dl">';
    for (var i = 0; i < baris.length; i++) {
        var nilai = baris[i][2] ? baris[i][1] : esc(isi(baris[i][1]));
        html += '<div><dt>' + baris[i][0] + '</dt><dd>' + nilai + '</dd></div>';
    }
    html += '</dl></div>';

    var footer = '<button type="button" class="btn ghost" onclick="tutupModal()">Tutup</button>';
    fungsiUbahSekarang = fungsiUbah || null;
    if (fungsiUbah) {
        footer += '<button type="button" class="btn" onclick="fungsiUbahSekarang()">' + IKON_UBAH + 'Ubah</button>';
    }
    bukaModal(judul, html, footer, false);
}


// ---------- 6. Popup konfirmasi ----------

// Konfirmasi hapus. Dipanggil dari tombol tong sampah di tabel.
function konfirmasiHapus(file, id, nama) {
    var html = '<form method="post" action="' + file + '" id="form-hapus" class="mbody">';
    html += '<input type="hidden" name="aksi" value="hapus">';
    html += '<input type="hidden" name="id" value="' + id + '">';
    html += '<p class="confirm">Anda akan menghapus <b>' + esc(nama) + '</b>. Tindakan ini tidak bisa dibatalkan.</p>';
    html += '</form>';

    var footer = '<button type="button" class="btn ghost" onclick="tutupModal()">Kembali/button>';
    footer += '<button type="submit" class="btn danger" form="form-hapus">' + IKON_HAPUS + 'Ya, Hapus</button>';
    bukaModal('Hapus data?', html, footer, false);
}

// Menampilkan isi dari elemen tersembunyi (dipakai "Lihat Semua" dan laporan)
function bukaIsi(judul, idElemen) {
    var isiHtml = '<div class="mbody">' + document.getElementById(idElemen).innerHTML + '</div>';
    bukaModal(judul, isiHtml, '<button type="button" class="btn" onclick="tutupModal()">Tutup</button>', true);
}


// ---------- 7. Pesan sukses hilang sendiri ----------
var pesanToast = document.getElementById('toast');
if (pesanToast) {
    setTimeout(function () {
        pesanToast.hidden = true;
    }, 2800);
}