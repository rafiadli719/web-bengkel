-- SQL Script untuk membuat tabel Kepala Mekanik Harian
-- Database: fitmotor_dbbengkel

-- Tabel 1: Master Kepala Mekanik
CREATE TABLE IF NOT EXISTS `tbl_master_kepala_mekanik` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_cabang` varchar(20) NOT NULL,
  `nama_kepala_mekanik` varchar(100) NOT NULL,
  `status_aktif` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cabang` (`kode_cabang`),
  KEY `idx_status` (`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel 2: Kepala Mekanik Harian
CREATE TABLE IF NOT EXISTS `tbl_kepala_mekanik_harian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_cabang` varchar(20) NOT NULL,
  `tanggal_kerja` date NOT NULL,
  `kepala_mekanik_1` varchar(100) NOT NULL COMMENT 'Kepala Mekanik Utama',
  `kepala_mekanik_2` varchar(100) DEFAULT NULL COMMENT 'Kepala Mekanik Backup',
  `shift_kerja` enum('full','pagi','siang','malam') DEFAULT 'full',
  `keterangan` text,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_cabang_tanggal` (`kode_cabang`,`tanggal_kerja`),
  KEY `idx_tanggal` (`tanggal_kerja`),
  KEY `idx_cabang` (`kode_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert beberapa data contoh untuk Master Kepala Mekanik
-- Sesuaikan dengan nama cabang dan kepala mekanik yang ada
-- INSERT INTO `tbl_master_kepala_mekanik` (`kode_cabang`, `nama_kepala_mekanik`, `status_aktif`) VALUES
-- ('CAB001', 'Budi Santoso', 'aktif'),
-- ('CAB001', 'Agus Wijaya', 'aktif'),
-- ('CAB001', 'Dedi Setiawan', 'aktif');

-- Catatan:
-- 1. Jalankan script ini melalui phpMyAdmin atau command line MySQL
-- 2. Pastikan database fitmotor_dbbengkel sudah dipilih
-- 3. Setelah membuat tabel, isi Master Kepala Mekanik melalui halaman master_kepala_mekanik.php
