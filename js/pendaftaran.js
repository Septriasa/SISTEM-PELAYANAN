function pdEl(tag, kelas, teks) {
    var el = document.createElement(tag);
    if (kelas) el.className = kelas;
    if (teks !== undefined) el.textContent = teks;
    return el;
}

// Membuat satu kolom form: label + input
function pdField(label, input, wajib, penuh) {
    var wrap = pdEl('div', 'fld' + (penuh ? ' full' : ''));
    var lab = pdEl('label', '', label + ' ');
    if (wajib) lab.appendChild(pdEl('i', '', '*'));
    wrap.appendChild(lab);
    wrap.appendChild(input);
    return wrap;
}

function pdInput(tipe, nama, nilai) {
    var el = document.createElement('input');
    el.type = tipe;
    if (nama) el.name = nama;
    el.value = nilai || '';
    return el;
}

// Menutup popup paling atas
function pdTutup(back) {
    if (back && back.parentNode) back.parentNode.removeChild(back);
}

//Popup konfirmasi: "Apakah anda yakin ingin merubah data tersebut?"
function pdKonfirmasi(pesan, saatIya) {
    var back = pdEl('div', 'mback pd-konfirmasi');
    back.setAttribute('role', 'dialog');
    back.setAttribute('aria-modal', 'true');
    back.style.zIndex = 150;

    var box = pdEl('div', 'mbox pd-kecil');
    var head = pdEl('div', 'mhead');
    head.appendChild(pdEl('h3', '', 'Konfirmasi'));
    var body = pdEl('div', 'mbody');
    body.appendChild(pdEl('p', 'confirm', pesan));

    var foot = pdEl('div', 'mfoot');
    var tidak = pdEl('button', 'btn ghost', 'Tidak');
    tidak.type = 'button';
    var iya = pdEl('button', 'btn', 'Iya');
    iya.type = 'button';
    foot.appendChild(tidak);
    foot.appendChild(iya);

    box.appendChild(head);
    box.appendChild(body);
    box.appendChild(foot);
    back.appendChild(box);
    document.body.appendChild(back);
    iya.focus();

    tidak.onclick = function () { pdTutup(back); };
    iya.onclick = function () {
        iya.disabled = true;
        tidak.disabled = true;
        saatIya();
    };
    back.addEventListener('click', function (ev) {
        if (ev.target === back) pdTutup(back);
    });
}

//POP UP "Ubah Pendaftaran" untuk admin
function ubahPendaftaran(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    var back = pdEl('div', 'mback pd-ubah');
    back.setAttribute('role', 'dialog');
    back.setAttribute('aria-modal', 'true');

    var box = pdEl('div', 'mbox');

    // kepala popup
    var head = pdEl('div', 'mhead');
    head.appendChild(pdEl('h3', '', 'Ubah Pendaftaran'));
    var x = pdEl('button', 'pd-x');
    x.type = 'button';
    x.setAttribute('aria-label', 'Tutup');
    x.textContent = '×';
    head.appendChild(x);

    // formulir
    var form = document.createElement('form');
    form.method = 'post';
    form.action = 'pendaftaran.php';
    form.className = 'pd-form';

    var tersembunyi = [['aksi', 'simpan'], ['id', d.id], ['kembali', window.location.search]];
    tersembunyi.forEach(function (h) {
        form.appendChild(pdInput('hidden', h[0], h[1]));
    });

    var body = pdEl('div', 'mbody');
    var grid = pdEl('div', 'fgrid');

    var nama = pdInput('text', 'nama', d.nama);
    nama.required = true;
    nama.placeholder = 'Contoh: Andi Pratama';
    grid.appendChild(pdField('Nama Lengkap', nama, true));

    // Jenis layanan: diisi oleh pengguna saat mendaftar, admin hanya melihat
    var layanan = pdInput('text', '', d.Jenis_Layanan || '-');
    layanan.readOnly = true;
    layanan.tabIndex = -1;
    grid.appendChild(pdField('Jenis Layanan', layanan, false));

    var email = pdInput('email', 'email', d.email);
    email.placeholder = 'nama@email.co.id';
    grid.appendChild(pdField('Email', email, false));

    var telepon = pdInput('tel', 'telepon', d.telepon);
    telepon.placeholder = '0812-0000-0000';
    grid.appendChild(pdField('Telepon', telepon, false));

    var tanggal = pdInput('date', 'tanggal', d.tanggal);
    tanggal.required = true;
    grid.appendChild(pdField('Tanggal Daftar', tanggal, true));

    // Status: di sinilah admin memproses Disetujui / Ditolak / Menunggu Verifikasi
    var status = document.createElement('select');
    status.name = 'status';
    ['Menunggu Verifikasi', 'Disetujui', 'Ditolak'].forEach(function (s) {
        var o = document.createElement('option');
        o.value = s;
        o.textContent = s;
        if (s === d.status) o.selected = true;
        status.appendChild(o);
    });
    grid.appendChild(pdField('Status', status, false));

    // Nomor antrean: otomatis, dibuat saat status pertama kali diproses
    var nomor = pdInput('text', '', d.Nomor_Antrian ? d.Nomor_Antrian : 'Otomatis setelah diproses');
    nomor.readOnly = true;
    nomor.tabIndex = -1;
    grid.appendChild(pdField('Nomor Antrean', nomor, false));

    body.appendChild(grid);
    form.appendChild(body);

    // POP UP
    var foot = pdEl('div', 'mfoot');
    var kembali = pdEl('button', 'btn ghost', 'Kembali');
    kembali.type = 'button';
    var simpan = pdEl('button', 'btn', 'Simpan Perubahan');
    simpan.type = 'submit';
    foot.appendChild(kembali);
    foot.appendChild(simpan);
    form.appendChild(foot);

    box.appendChild(head);
    box.appendChild(form);
    back.appendChild(box);
    document.body.appendChild(back);
    nama.focus();

    // tutup popup
    x.onclick = kembali.onclick = function () { pdTutup(back); };
    back.addEventListener('mousedown', function (ev) {
        if (ev.target === back) pdTutup(back);
    });
    back.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !document.querySelector('.pd-konfirmasi')) pdTutup(back);
    });

    // Simpan Perubahan-tanya dulu "Apakah anda yakin...?"
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (!form.reportValidity()) return;
        pdKonfirmasi('Apakah anda yakin ingin merubah data tersebut?', function () {
            form.submit(); 
        });
    });
}

// ---------- Popup LIHAT detail ----------
function lihatPendaftaran(tombol) {
    var d = JSON.parse(tombol.dataset.json);

    bukaLihat('Detail Pendaftaran', [
        ['Nama Lengkap',    d.nama],
        ['Jenis Layanan',   d.Jenis_Layanan],
        ['Nomor Antrean',   d.Nomor_Antrian ? d.Nomor_Antrian : '-'],
        ['Email',           d.email],
        ['Telepon',         d.telepon],
        ['Tanggal Daftar',  tgl(d.tanggal)],
        ['Status',          pill(d.status), true]
    ], function () {
        ubahPendaftaran(tombol);  
    });
}