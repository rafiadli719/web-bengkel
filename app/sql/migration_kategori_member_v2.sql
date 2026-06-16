-- =====================================================
-- Migration: Perbaikan Sistem Kategori Member
-- Date: 2025-12-29
-- Description: 
--   1. Tambah tabel setting_kategori_member untuk toggle on/off sistem
--   2. Tambah kolom diskon_jasa dan diskon_barang untuk diskon terpisah
-- =====================================================

-- =====================================================
-- 1. CREATE TABLE setting_kategori_member
-- =====================================================
CREATE TABLE IF NOT EXISTS `setting_kategori_member` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipe_kategori` ENUM('nominal', 'kunjungan') NOT NULL UNIQUE COMMENT 'Tipe kategori member',
  `is_enabled` TINYINT(1) DEFAULT 1 COMMENT '1=sistem aktif, 0=sistem nonaktif',
  `keterangan` TEXT COMMENT 'Keterangan tambahan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Setting untuk mengaktifkan/menonaktifkan sistem kategori member global';

-- Insert default values
INSERT IGNORE INTO `setting_kategori_member` (`tipe_kategori`, `is_enabled`, `keterangan`) VALUES
('nominal', 1, 'Member berdasarkan total nominal transaksi pelanggan'),
('kunjungan', 1, 'Member berdasarkan jumlah kunjungan/service pelanggan');

-- =====================================================
-- 2. ALTER TABLE master_kategori_member
-- Tambah kolom diskon_jasa dan diskon_barang
-- =====================================================

-- Check if columns exist before adding
SET @dbname = DATABASE();
SET @tablename = 'master_kategori_member';

-- Add diskon_jasa column if not exists
SET @columnname = 'diskon_jasa';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `diskon_jasa` DECIMAL(5,2) DEFAULT 0 COMMENT ''Diskon untuk jasa servis (persen)'' AFTER `diskon_persen`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add diskon_barang column if not exists
SET @columnname = 'diskon_barang';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `diskon_barang` DECIMAL(5,2) DEFAULT 0 COMMENT ''Diskon untuk barang/sparepart (persen)'' AFTER `diskon_jasa`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 3. MIGRATE existing data
-- Copy diskon_persen to diskon_jasa for existing records
-- =====================================================
UPDATE `master_kategori_member` 
SET `diskon_jasa` = `diskon_persen` 
WHERE `diskon_jasa` = 0 AND `diskon_persen` > 0;

-- =====================================================
-- VERIFICATION QUERIES (Run manually to verify)
-- =====================================================
-- SELECT * FROM setting_kategori_member;
-- DESCRIBE master_kategori_member;
-- SELECT id_kategori, nama_kategori, diskon_persen, diskon_jasa, diskon_barang FROM master_kategori_member;
