-- Task 2: DDL tabel master/independen *_closing_kasir
-- Sumber: fitmotor_maintance-beta (master_akun, master_nama_transaksi, master_rekening_cabang, kas_awal_config)
-- Rename + FK kode_cabang -> tbcabang.cabang_ref_kode + charset latin1 (samakan koneksi fitmotor)
--
-- PREREQUISITE (dijalankan 2026-09-03, di luar spec asli, wajib sebelum 2 CREATE TABLE terakhir):
-- tbcabang.cabang_ref_kode gak punya index apapun -> MySQL nolak bikin FK ke kolom itu.
-- Verified 0 duplikat & 0 null/empty dulu sebelum nambah:
--   ALTER TABLE tbcabang ADD UNIQUE KEY unique_cabang_ref_kode (cabang_ref_kode);
--
-- CATATAN charset: tbcabang.cabang_ref_kode collation asli utf8mb4_general_ci (bukan latin1).
-- FK butuh charset/collation kolom match persis -> kolom kode_cabang di 2 tabel closing_kasir
-- di bawah TERPAKSA override CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci (bukan latin1
-- default tabel), exception dari aturan umum Step 2. Kolom lain di tabel tetap latin1.

CREATE TABLE `master_akun_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_akun` varchar(10) NOT NULL,
  `arti` varchar(100) DEFAULT NULL,
  `jenis_akun` enum('pemasukan','pengeluaran') NOT NULL,
  `kategori` enum('biaya','non_biaya') DEFAULT NULL,
  `require_umur_pakai` tinyint(1) DEFAULT '0',
  `min_umur_pakai` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kode_akun` (`kode_akun`),
  KEY `idx_kode_akun` (`kode_akun`),
  KEY `idx_jenis_akun` (`jenis_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `master_nama_transaksi_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_transaksi` varchar(100) NOT NULL,
  `kode_akun` varchar(10) NOT NULL,
  `keterangan_default` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_akun` (`kode_akun`),
  CONSTRAINT `fk_master_nama_transaksi_closing_kasir` FOREIGN KEY (`kode_akun`) REFERENCES `master_akun_closing_kasir` (`kode_akun`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `master_rekening_cabang_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_cabang` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_bank` varchar(100) NOT NULL,
  `nama_rekening` varchar(100) NOT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `jenis_rekening` enum('Mitra','Milik Sendiri') DEFAULT 'Milik Sendiri',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rekening_per_cabang` (`kode_cabang`,`no_rekening`),
  KEY `idx_kode_cabang` (`kode_cabang`),
  KEY `idx_is_active` (`status`),
  CONSTRAINT `fk_master_rekening_cabang_closing_kasir` FOREIGN KEY (`kode_cabang`) REFERENCES `tbcabang` (`cabang_ref_kode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `kas_awal_config_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_cabang` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `nominal_minimum` decimal(15,2) DEFAULT '500000.00',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` varchar(20) DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kode_cabang` (`kode_cabang`),
  KEY `idx_is_active` (`status`),
  CONSTRAINT `fk_kas_awal_config_closing_kasir` FOREIGN KEY (`kode_cabang`) REFERENCES `tbcabang` (`cabang_ref_kode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
