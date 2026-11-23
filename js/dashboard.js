// ==========================================================
// File: js/dashboard.js
// Fungsi: JavaScript khusus halaman Dashboard.
// ==========================================================

// Popup konfirmasi sebelum data dikembalikan ke data contoh.
// Kalau ditekan "Ya", form dikirim ke reset.php
function konfirmasiReset() {
    var html = '<form method="post" action="reset.php" id="form-reset" class="mbody">';
    html += '<p class="confirm">Semua data pendaftaran, permohonan, antrean, laporan, dan admin akan diganti dengan data contoh awal. Perubahan Anda akan hilang.</p>';
    html += '</form>';

    var footer = '<button type="button" class="btn ghost" onclick="tutupModal()">Batal</button>';
    footer += '<button type="submit" class="btn danger" form="form-reset">Ya, Atur Ulang</button>';

    bukaModal('Atur ulang data contoh?', html, footer, false);
}
