-- =====================================================
-- Migration: Exclude Item dari Diskon Member
-- Date: 2025-12-29
-- Description: 
--   Menambah fitur untuk mengecualikan item/jenis item tertentu
--   dari mendapatkan diskon member pelanggan
-- =====================================================

-- =====================================================
-- 1. ALTER TABLE tbhargajual (Tabel Jenis Item - Harga)
-- Tambah kolom untuk exclude diskon member per jenis
-- =====================================================

SET @dbname = DATABASE();

-- Check and add to tbhargajual
SET @tablename = 'tbhargajual';
SET @columnname = 'exclude_diskon_member';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `exclude_diskon_member` TINYINT(1) DEFAULT 0 COMMENT ''1=Tidak dapat diskon member, 0=Dapat diskon member''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 2. ALTER TABLE tblitem
-- Tambah kolom untuk override exclude per item individual
-- =====================================================

SET @tablename = 'tblitem';
SET @columnname = 'exclude_diskon_member';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `exclude_diskon_member` TINYINT(1) DEFAULT NULL COMMENT ''NULL=ikut jenis, 0=dapat diskon, 1=tidak dapat diskon''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 3. SET DEFAULT untuk kategori dengan margin tipis
-- Contoh: OLIGEN (Oli) di-exclude dari diskon member
-- =====================================================

-- List jenis yang direkomendasikan untuk di-exclude (margin tipis):
-- OLIGEN = Oli Generik
-- Anda bisa menyesuaikan sesuai kebutuhan

UPDATE `tbhargajual` 
SET `exclude_diskon_member` = 1 
WHERE `jenis` IN ('OLIGEN')
AND (`exclude_diskon_member` IS NULL OR `exclude_diskon_member` = 0);

-- =====================================================
-- 4. CREATE TABLE setting_exclude_mode
-- Toggle untuk memilih mode: per jenis ATAU per item
-- =====================================================

CREATE TABLE IF NOT EXISTS `setting_exclude_mode` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mode_aktif` ENUM('per_jenis', 'per_item') NOT NULL DEFAULT 'per_jenis' COMMENT 'Mode yang aktif saat ini',
  `keterangan_per_jenis` TEXT COMMENT 'Keterangan mode per jenis',
  `keterangan_per_item` TEXT COMMENT 'Keterangan mode per item',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Setting untuk memilih mode exclude diskon member';

-- Insert default setting
INSERT IGNORE INTO `setting_exclude_mode` (`id`, `mode_aktif`, `keterangan_per_jenis`, `keterangan_per_item`) VALUES
(1, 'per_jenis', 
 'Exclude diskon berdasarkan JENIS ITEM (kategori). Semua item dalam jenis yang di-exclude tidak akan mendapat diskon member.',
 'Exclude diskon per ITEM INDIVIDUAL. Hanya item yang ditandai secara spesifik yang tidak mendapat diskon member.');

-- =====================================================
-- VERIFICATION QUERIES (Run manually to verify)
-- =====================================================
-- DESCRIBE tbhargajual;
-- DESCRIBE tblitem;
-- SELECT * FROM setting_exclude_mode;
-- SELECT jenis, exclude_diskon_member FROM tbhargajual;
-- SELECT noitem, namaitem, jenis, exclude_diskon_member FROM tblitem WHERE exclude_diskon_member IS NOT NULL LIMIT 10;
