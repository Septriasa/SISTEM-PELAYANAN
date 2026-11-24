CREATE DATABASE IF NOT EXISTS ksop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ksop_db;

-- Hapus tabel lama (kalau ada) supaya data kembali ke data contoh
DROP TABLE IF EXISTS aktivitas;
DROP TABLE IF EXISTS laporan;
DROP TABLE IF EXISTS antrean;
DROP TABLE IF EXISTS permohonan;
DROP TABLE IF EXISTS pendaftaran;
DROP TABLE IF EXISTS admin;

-- Tabel admin (akun yang boleh login)
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100),
  password VARCHAR(255) NOT NULL,
  peran VARCHAR(20) NOT NULL DEFAULT 'Operator',
  status VARCHAR(20) NOT NULL DEFAULT 'Aktif'
);

-- Tabel pendaftaran pengguna jasa
CREATE TABLE pendaftaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  perusahaan VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  telepon VARCHAR(30),
  tanggal DATE NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Menunggu Verifikasi',
  catatan TEXT
);

-- Tabel permohonan layanan kapal
CREATE TABLE permohonan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor VARCHAR(20) NOT NULL,
  pemohon VARCHAR(100) NOT NULL,
  layanan VARCHAR(100) NOT NULL,
  kapal VARCHAR(100) NOT NULL,
  gt INT,
  tanggal DATE NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Diproses',
  keterangan TEXT
);

-- Tabel nomor antrean
CREATE TABLE antrean (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor VARCHAR(10) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  layanan VARCHAR(100) NOT NULL,
  tanggal DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Menunggu'
);

-- Tabel laporan yang disimpan (arsip)
CREATE TABLE laporan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  dari DATE NOT NULL,
  sampai DATE NOT NULL,
  catatan TEXT,
  dibuat DATETIME NOT NULL,
  oleh VARCHAR(100) NOT NULL,
  jml_pendaftaran INT NOT NULL DEFAULT 0,
  jml_permohonan INT NOT NULL DEFAULT 0,
  jml_disetujui INT NOT NULL DEFAULT 0,
  jml_ditolak INT NOT NULL DEFAULT 0,
  jml_antrean INT NOT NULL DEFAULT 0
);

-- Tabel catatan aktivitas (muncul di dashboard)
CREATE TABLE aktivitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ikon VARCHAR(20) NOT NULL,
  teks VARCHAR(255) NOT NULL,
  waktu DATETIME NOT NULL
);

-- ---------- DATA CONTOH ----------
-- Password admin  : admin123
-- Password operator: operator123 (disimpan dalam bentuk hash, bukan teks asli)
INSERT INTO admin (nama, username, email, password, peran, status) VALUES
('Admin', 'admin', 'admin@ksop.co.id', '$2y$10$k8O.Mx/sN9O32trbGGBSn.pGS5Z8Fi85cZje7x7P622FIoPopaN7e', 'Super Admin', 'Aktif'),
('Rina Kartika', 'operator', 'rina.kartika@ksop.co.id', '$2y$10$HzZlrCIRO/ZFNmEnTpmCaOC5nRo54wcA87mAmcHgLiutXhZFwM23W', 'Operator', 'Aktif');

INSERT INTO pendaftaran (nama, perusahaan, email, telepon, tanggal, status, catatan) VALUES
('Andi Pratama', 'Pengguna Jasa', 'andi.pratama@contoh.co.id', '0812-3100-5200', DATE_SUB(CURDATE(), INTERVAL 0 DAY), 'Disetujui', ''),
('Siti Rahmawati', 'PT Bahari Nusantara', 'siti.rahmawati@contoh.co.id', '0812-3237-5511', DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Disetujui', ''),
('Budi Santoso', 'PT Tirta Mandiri', 'budi.santoso@contoh.co.id', '0812-3374-5822', DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Menunggu Verifikasi', ''),
('Dewi Lestari', 'PT Tanjung Sejahtera', 'dewi.lestari@contoh.co.id', '0812-3511-6133', DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Disetujui', ''),
('Rizky Ramadhan', 'CV Laut Biru', 'rizky.ramadhan@contoh.co.id', '0812-3648-6444', DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'Ditolak', ''),
('Maya Sari', 'Pengguna Jasa', 'maya.sari@contoh.co.id', '0812-3785-6755', DATE_SUB(CURDATE(), INTERVAL 31 DAY), 'Menunggu Verifikasi', ''),
('Hendra Wijaya', 'PT Kepri Logistik', 'hendra.wijaya@contoh.co.id', '0812-3922-7066', DATE_SUB(CURDATE(), INTERVAL 37 DAY), 'Disetujui', ''),
('Fitri Handayani', 'PT Samudra Jaya', 'fitri.handayani@contoh.co.id', '0812-4059-7377', DATE_SUB(CURDATE(), INTERVAL 43 DAY), 'Disetujui', ''),
('Agus Salim', 'PT Karya Pelayaran', 'agus.salim@contoh.co.id', '0812-4196-7688', DATE_SUB(CURDATE(), INTERVAL 49 DAY), 'Disetujui', ''),
('Putri Ayu', 'CV Pesisir Makmur', 'putri.ayu@contoh.co.id', '0812-4333-7999', DATE_SUB(CURDATE(), INTERVAL 55 DAY), 'Menunggu Verifikasi', ''),
('Joko Susilo', 'Pengguna Jasa', 'joko.susilo@contoh.co.id', '0812-4470-8310', DATE_SUB(CURDATE(), INTERVAL 62 DAY), 'Disetujui', ''),
('Nurul Hidayah', 'PT Bahari Nusantara', 'nurul.hidayah@contoh.co.id', '0812-4607-8621', DATE_SUB(CURDATE(), INTERVAL 68 DAY), 'Ditolak', ''),
('Dimas Anggara', 'PT Tirta Mandiri', 'dimas.anggara@contoh.co.id', '0812-4744-8932', DATE_SUB(CURDATE(), INTERVAL 74 DAY), 'Menunggu Verifikasi', ''),
('Lina Marlina', 'PT Tanjung Sejahtera', 'lina.marlina@contoh.co.id', '0812-4881-9243', DATE_SUB(CURDATE(), INTERVAL 80 DAY), 'Disetujui', ''),
('Eko Prasetyo', 'CV Laut Biru', 'eko.prasetyo@contoh.co.id', '0812-5018-9554', DATE_SUB(CURDATE(), INTERVAL 86 DAY), 'Disetujui', ''),
('Yusuf Maulana', 'Pengguna Jasa', 'yusuf.maulana@contoh.co.id', '0812-5155-9865', DATE_SUB(CURDATE(), INTERVAL 93 DAY), 'Disetujui', ''),
('Rina Kartika', 'PT Kepri Logistik', 'rina.kartika@contoh.co.id', '0812-5292-0176', DATE_SUB(CURDATE(), INTERVAL 99 DAY), 'Menunggu Verifikasi', ''),
('Fajar Nugroho', 'PT Samudra Jaya', 'fajar.nugroho@contoh.co.id', '0812-5429-0487', DATE_SUB(CURDATE(), INTERVAL 105 DAY), 'Disetujui', ''),
('Wulan Sari', 'PT Karya Pelayaran', 'wulan.sari@contoh.co.id', '0812-5566-0798', DATE_SUB(CURDATE(), INTERVAL 111 DAY), 'Ditolak', ''),
('Taufik Hidayat', 'CV Pesisir Makmur', 'taufik.hidayat@contoh.co.id', '0812-5703-1109', DATE_SUB(CURDATE(), INTERVAL 117 DAY), 'Menunggu Verifikasi', ''),
('Andi Pratama', 'Pengguna Jasa', 'andi.pratama@contoh.co.id', '0812-5840-1420', DATE_SUB(CURDATE(), INTERVAL 124 DAY), 'Disetujui', ''),
('Siti Rahmawati', 'PT Bahari Nusantara', 'siti.rahmawati@contoh.co.id', '0812-5977-1731', DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'Disetujui', ''),
('Budi Santoso', 'PT Tirta Mandiri', 'budi.santoso@contoh.co.id', '0812-6114-2042', DATE_SUB(CURDATE(), INTERVAL 136 DAY), 'Disetujui', ''),
('Dewi Lestari', 'PT Tanjung Sejahtera', 'dewi.lestari@contoh.co.id', '0812-6251-2353', DATE_SUB(CURDATE(), INTERVAL 142 DAY), 'Menunggu Verifikasi', ''),
('Rizky Ramadhan', 'CV Laut Biru', 'rizky.ramadhan@contoh.co.id', '0812-6388-2664', DATE_SUB(CURDATE(), INTERVAL 148 DAY), 'Disetujui', ''),
('Maya Sari', 'Pengguna Jasa', 'maya.sari@contoh.co.id', '0812-6525-2975', DATE_SUB(CURDATE(), INTERVAL 155 DAY), 'Ditolak', '');

INSERT INTO permohonan (nomor, pemohon, layanan, kapal, gt, tanggal, status, keterangan) VALUES
('PMH-0022', 'Dewi Lestari', 'Pelayanan Kapal Masuk', 'KM Sinar Bahari', 145, DATE_SUB(CURDATE(), INTERVAL 0 DAY), 'Diproses', ''),
('PMH-0021', 'Joko Susilo', 'Surat Izin Berlayar', 'MT Samudra Raya', 520, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Disetujui', ''),
('PMH-0020', 'Fajar Nugroho', 'Sertifikat Keselamatan Kapal', 'KM Kepri Jaya', 98, DATE_SUB(CURDATE(), INTERVAL 11 DAY), 'Disetujui', ''),
('PMH-0019', 'Rizky Ramadhan', 'Pendaftaran Kapal', 'TB Tirta Perkasa', 310, DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'Ditolak', ''),
('PMH-0018', 'Nurul Hidayah', 'Pemanduan Kapal', 'KM Nusantara Indah', 1250, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Diproses', ''),
('PMH-0017', 'Wulan Sari', 'Laporan Kegiatan Kapal', 'KLM Bintan Sejati', 220, DATE_SUB(CURDATE(), INTERVAL 28 DAY), 'Disetujui', ''),
('PMH-0016', 'Maya Sari', 'Pelayanan Kapal Masuk', 'MV Selat Karimun', 760, DATE_SUB(CURDATE(), INTERVAL 33 DAY), 'Diproses', ''),
('PMH-0015', 'Dimas Anggara', 'Surat Izin Berlayar', 'KM Dumai Express', 180, DATE_SUB(CURDATE(), INTERVAL 39 DAY), 'Disetujui', ''),
('PMH-0014', 'Taufik Hidayat', 'Sertifikat Keselamatan Kapal', 'KM Sinar Bahari', 145, DATE_SUB(CURDATE(), INTERVAL 44 DAY), 'Disetujui', ''),
('PMH-0013', 'Hendra Wijaya', 'Pendaftaran Kapal', 'MT Samudra Raya', 520, DATE_SUB(CURDATE(), INTERVAL 50 DAY), 'Ditolak', ''),
('PMH-0012', 'Lina Marlina', 'Pemanduan Kapal', 'KM Kepri Jaya', 98, DATE_SUB(CURDATE(), INTERVAL 56 DAY), 'Diproses', ''),
('PMH-0011', 'Andi Pratama', 'Laporan Kegiatan Kapal', 'TB Tirta Perkasa', 310, DATE_SUB(CURDATE(), INTERVAL 61 DAY), 'Disetujui', ''),
('PMH-0010', 'Fitri Handayani', 'Pelayanan Kapal Masuk', 'KM Nusantara Indah', 1250, DATE_SUB(CURDATE(), INTERVAL 67 DAY), 'Diproses', ''),
('PMH-0009', 'Eko Prasetyo', 'Surat Izin Berlayar', 'KLM Bintan Sejati', 220, DATE_SUB(CURDATE(), INTERVAL 72 DAY), 'Disetujui', ''),
('PMH-0008', 'Siti Rahmawati', 'Sertifikat Keselamatan Kapal', 'MV Selat Karimun', 760, DATE_SUB(CURDATE(), INTERVAL 78 DAY), 'Disetujui', ''),
('PMH-0007', 'Agus Salim', 'Pendaftaran Kapal', 'KM Dumai Express', 180, DATE_SUB(CURDATE(), INTERVAL 84 DAY), 'Ditolak', ''),
('PMH-0006', 'Yusuf Maulana', 'Pemanduan Kapal', 'KM Sinar Bahari', 145, DATE_SUB(CURDATE(), INTERVAL 89 DAY), 'Diproses', ''),
('PMH-0005', 'Budi Santoso', 'Laporan Kegiatan Kapal', 'MT Samudra Raya', 520, DATE_SUB(CURDATE(), INTERVAL 95 DAY), 'Disetujui', ''),
('PMH-0004', 'Putri Ayu', 'Pelayanan Kapal Masuk', 'KM Kepri Jaya', 98, DATE_SUB(CURDATE(), INTERVAL 100 DAY), 'Diproses', ''),
('PMH-0003', 'Rina Kartika', 'Surat Izin Berlayar', 'TB Tirta Perkasa', 310, DATE_SUB(CURDATE(), INTERVAL 106 DAY), 'Disetujui', ''),
('PMH-0002', 'Dewi Lestari', 'Sertifikat Keselamatan Kapal', 'KM Nusantara Indah', 1250, DATE_SUB(CURDATE(), INTERVAL 112 DAY), 'Disetujui', ''),
('PMH-0001', 'Joko Susilo', 'Pendaftaran Kapal', 'KLM Bintan Sejati', 220, DATE_SUB(CURDATE(), INTERVAL 117 DAY), 'Ditolak', '');

INSERT INTO antrean (nomor, nama, layanan, tanggal, status) VALUES
('A015', 'Maya Sari', 'Pendaftaran Kapal', CURDATE(), 'Selesai'),
('A016', 'Agus Salim', 'Pemanduan Kapal', CURDATE(), 'Selesai'),
('A017', 'Nurul Hidayah', 'Laporan Kegiatan Kapal', CURDATE(), 'Selesai'),
('A018', 'Eko Prasetyo', 'Pelayanan Kapal Masuk', CURDATE(), 'Selesai'),
('A019', 'Fajar Nugroho', 'Surat Izin Berlayar', CURDATE(), 'Selesai'),
('A020', 'Andi Pratama', 'Sertifikat Keselamatan Kapal', CURDATE(), 'Selesai'),
('A021', 'Dewi Lestari', 'Pendaftaran Kapal', CURDATE(), 'Selesai'),
('A022', 'Hendra Wijaya', 'Pemanduan Kapal', CURDATE(), 'Selesai'),
('A023', 'Putri Ayu', 'Laporan Kegiatan Kapal', CURDATE(), 'Dipanggil'),
('A024', 'Dimas Anggara', 'Pelayanan Kapal Masuk', CURDATE(), 'Menunggu'),
('A025', 'Yusuf Maulana', 'Surat Izin Berlayar', CURDATE(), 'Menunggu'),
('A026', 'Wulan Sari', 'Sertifikat Keselamatan Kapal', CURDATE(), 'Menunggu');

INSERT INTO aktivitas (ikon, teks, waktu) VALUES
('users', 'Pengguna baru mendaftar sebagai Pengguna Jasa', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('check', 'Permohonan kapal telah disetujui', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('ticket', 'Nomor antrean A023 sedang dipanggil', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('file', 'Permohonan surat menunggu verifikasi', DATE_SUB(NOW(), INTERVAL 26 HOUR));

-- Dua laporan contoh. Angkanya dihitung dari data di atas.
SET @d1 = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01');
SET @d2 = LAST_DAY(@d1);
INSERT INTO laporan (judul, dari, sampai, catatan, dibuat, oleh, jml_pendaftaran, jml_permohonan, jml_disetujui, jml_ditolak, jml_antrean)
SELECT 'Laporan Bulan Lalu', @d1, @d2, 'Rekap bulanan untuk arsip kantor.', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Admin',
  (SELECT COUNT(*) FROM pendaftaran WHERE tanggal BETWEEN @d1 AND @d2),
  (SELECT COUNT(*) FROM permohonan WHERE tanggal BETWEEN @d1 AND @d2),
  (SELECT COUNT(*) FROM permohonan WHERE tanggal BETWEEN @d1 AND @d2 AND status = 'Disetujui'),
  (SELECT COUNT(*) FROM permohonan WHERE tanggal BETWEEN @d1 AND @d2 AND status = 'Ditolak'),
  (SELECT COUNT(*) FROM antrean WHERE tanggal BETWEEN @d1 AND @d2 AND status = 'Selesai');

SET @d3 = MAKEDATE(YEAR(CURDATE()), 1);
SET @d4 = CURDATE();
INSERT INTO laporan (judul, dari, sampai, catatan, dibuat, oleh, jml_pendaftaran, jml_permohonan, jml_disetujui, jml_ditolak, jml_antrean)
SELECT 'Laporan Tahun Berjalan', @d3, @d4, 'Akumulasi sejak awal tahun.', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Admin',
  (SELECT COUNT(*) FROM pendaftaran WHERE tanggal BETWEEN @d3 AND @d4),
  (SELECT COUNT(*) FROM permohonan WHERE tanggal BETWEEN @d3 AND @d4),
  (SELECT COUNT(*) FROM permohonan WHERE tanggal BETWEEN @d3 AND @d4 AND status = 'Disetujui'),
  (SELECT COUNT(*) FROM permohonan WHERE tanggal BETWEEN @d3 AND @d4 AND status = 'Ditolak'),
  (SELECT COUNT(*) FROM antrean WHERE tanggal BETWEEN @d3 AND @d4 AND status = 'Selesai');
