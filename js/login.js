// ==========================================================
// File: js/login.js
// Fungsi: JavaScript halaman login.
// ==========================================================

// kursor otomatis ke kolom username (atau kolom password kalau username sudah terisi)
var kolomUser = document.getElementById('l-user');
var kolomPass = document.getElementById('l-pass');

if (kolomUser.value === '') {
    kolomUser.focus();
} else {
    kolomPass.focus();
}
