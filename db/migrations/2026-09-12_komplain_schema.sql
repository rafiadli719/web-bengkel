-- Modul Penanganan Komplain — schema + seed
-- Plan: docs/superpowers/plans/2026-09-12-modul-komplain-implementation.md (Task 1 & 2)
-- Spec: docs/Modul_Penanganan_Komplain_Fit_Motor.md

-- Task 1 Step 1: posisi Kepala Cabang (KACAB) — approver REWORK & non-REWORK
INSERT INTO tb_master_posisi (kode_posisi, nama_posisi, departemen, deskripsi, user_akses_level, permissions, is_active)
VALUES ('KACAB', 'Kepala Cabang', 'Management',
  'Penanggung jawab operasional 1 cabang, approver komplain REWORK & non-REWORK',
  7,
  JSON_ARRAY('komplain_approve_rework', 'komplain_close_nonrework', 'komplain_view_cabang'),
  'active');

-- Task 2: DDL tabel inti modul komplain
CREATE TABLE tblkomplain_kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_kategori VARCHAR(20) NOT NULL UNIQUE,
  nama_kategori VARCHAR(50) NOT NULL,
  pic_role ENUM('KEPALA_MEKANIK','KEPALA_CABANG') NOT NULL,
  jenis_penyelesaian TEXT NOT NULL,
  is_active ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO tblkomplain_kategori (kode_kategori, nama_kategori, pic_role, jenis_penyelesaian) VALUES
('REWORK', 'Rework', 'KEPALA_MEKANIK', 'Pelanggan diarahkan kembali untuk perbaikan ulang (ada jadwal kedatangan)'),
('HARGA', 'Harga', 'KEPALA_CABANG', 'Tindak lanjut penanganan (tanpa kedatangan wajib)'),
('SIKAP', 'Sikap', 'KEPALA_CABANG', 'Tindak lanjut penanganan'),
('ANTRIAN', 'Antrian', 'KEPALA_CABANG', 'Tindak lanjut penanganan'),
('FASILITAS', 'Fasilitas', 'KEPALA_CABANG', 'Tindak lanjut penanganan'),
('LAINNYA', 'Lainnya', 'KEPALA_CABANG', 'Tindak lanjut penanganan');

CREATE TABLE tblkomplain (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_komplain VARCHAR(30) NOT NULL UNIQUE,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  nopol VARCHAR(20) NOT NULL,
  kode_cabang VARCHAR(20) NOT NULL,
  kode_kategori VARCHAR(20) NOT NULL,
  channel_lapor ENUM('Telepon','WA','Datang langsung','Lainnya') NOT NULL,
  tanggal_lapor DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  detail_keluhan TEXT NOT NULL,
  no_nota_rujukan VARCHAR(50) DEFAULT NULL,
  pic_kode_karyawan VARCHAR(20) DEFAULT NULL,
  status ENUM('Open','Diajukan','Dijadwalkan','Dikerjakan','Selesai','Ditutup - Ditolak','No-show','Eskalasi Manajemen') NOT NULL DEFAULT 'Open',
  mekanik_servis_awal VARCHAR(100) DEFAULT NULL,
  mekanik_pelaksana_kode VARCHAR(20) DEFAULT NULL,
  jenis_usulan ENUM('Terima','Tolak') DEFAULT NULL,
  alasan_usulan TEXT DEFAULT NULL,
  rencana_tanggal_kedatangan DATE DEFAULT NULL,
  tanggal_rework_aktual DATE DEFAULT NULL,
  biaya_rework DECIMAL(12,2) DEFAULT NULL,
  jumlah_revisi INT NOT NULL DEFAULT 0,
  keputusan_kepala_cabang ENUM('Setuju','Minta Revisi') DEFAULT NULL,
  tindak_lanjut_penanganan TEXT DEFAULT NULL,
  tanggal_ditindaklanjuti DATE DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_nopol (nopol),
  KEY idx_cabang_status (kode_cabang, status),
  KEY idx_kategori (kode_kategori),
  CONSTRAINT fk_komplain_kategori FOREIGN KEY (kode_kategori) REFERENCES tblkomplain_kategori(kode_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE tblkomplain_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  komplain_id INT NOT NULL,
  aksi VARCHAR(50) NOT NULL,
  status_sebelum VARCHAR(30) DEFAULT NULL,
  status_sesudah VARCHAR(30) NOT NULL,
  kode_karyawan_pelaku VARCHAR(20) NOT NULL,
  keterangan TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_komplain (komplain_id),
  CONSTRAINT fk_log_komplain FOREIGN KEY (komplain_id) REFERENCES tblkomplain(id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE tblkomplain_export_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_karyawan VARCHAR(20) NOT NULL,
  cakupan_filter TEXT DEFAULT NULL,
  jumlah_baris INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
