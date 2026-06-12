-- ================================================================
-- Migration: Antar Cabang Upload Excel Feature
-- Date: 2024
-- Description: Menambahkan kolom yang diperlukan untuk fitur
--              upload penjualan antar cabang dan penerimaan
-- ================================================================

-- Add columns to tblorderjual_header if not exists
ALTER TABLE `tblorderjual_header`
ADD COLUMN IF NOT EXISTS `kd_cabang_tujuan` VARCHAR(10) DEFAULT NULL COMMENT 'Kode cabang tujuan untuk antar cabang',
ADD COLUMN IF NOT EXISTS `tipe_transaksi` VARCHAR(50) DEFAULT 'NORMAL' COMMENT 'NORMAL, ANTAR_CABANG',
ADD COLUMN IF NOT EXISTS `cara_bayar` VARCHAR(20) DEFAULT 'Tunai' COMMENT 'Tunai, Kredit',
ADD COLUMN IF NOT EXISTS `syarat_hari` INT(11) DEFAULT 0 COMMENT 'Syarat pembayaran dalam hari',
ADD COLUMN IF NOT EXISTS `tanggal_terima` DATE DEFAULT NULL COMMENT 'Tanggal penerimaan di cabang tujuan',
ADD COLUMN IF NOT EXISTS `user_terima` VARCHAR(100) DEFAULT NULL COMMENT 'User yang menerima di cabang tujuan';

-- Add index for faster query
ALTER TABLE `tblorderjual_header`
ADD INDEX IF NOT EXISTS `idx_kd_cabang_tujuan` (`kd_cabang_tujuan`),
ADD INDEX IF NOT EXISTS `idx_tipe_transaksi` (`tipe_transaksi`);

-- Create setting table for antar cabang if not exists
CREATE TABLE IF NOT EXISTS `tbl_setting_antarcabang` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tipe_cabang_tujuan` CHAR(1) DEFAULT NULL COMMENT '1=Cabang Sendiri, 2=Cabang Mitra',
    `margin_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Margin persen dari HPP',
    `syarat_hari_default` INT(11) DEFAULT 0 COMMENT 'Syarat pembayaran default',
    `aktif` TINYINT(1) DEFAULT 1,
    `kd_cabang` VARCHAR(10) DEFAULT NULL COMMENT 'Khusus cabang tertentu, null=semua',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings if not exists
INSERT INTO `tbl_setting_antarcabang` (`tipe_cabang_tujuan`, `margin_persen`, `syarat_hari_default`, `aktif`)
SELECT '1', 0.00, 0, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tbl_setting_antarcabang` WHERE `tipe_cabang_tujuan` = '1');

INSERT INTO `tbl_setting_antarcabang` (`tipe_cabang_tujuan`, `margin_persen`, `syarat_hari_default`, `aktif`)
SELECT '2', 5.00, 10, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `tbl_setting_antarcabang` WHERE `tipe_cabang_tujuan` = '2');

-- ================================================================
-- Usage Notes:
-- 1. Jalankan SQL ini di phpMyAdmin atau mysql command line
-- 2. Backup database sebelum menjalankan migration
-- 3. Setting margin dapat diubah di tbl_setting_antarcabang:
--    - Cabang Sendiri (tipe 1): margin 0%, tunai
--    - Cabang Mitra (tipe 2): margin 5%, tempo 10 hari
-- ================================================================
