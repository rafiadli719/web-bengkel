-- ============================================================================
-- MIGRATION: Master Diskon Periode (Promo)
-- ============================================================================
-- Tanggal: 30 Desember 2024
-- Deskripsi: Tabel untuk menyimpan aturan diskon periode/promo
-- ============================================================================

-- Tabel utama untuk menyimpan aturan promo
CREATE TABLE IF NOT EXISTS `master_diskon_periode` (
  `id_promo` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_promo` VARCHAR(100) NOT NULL COMMENT 'Nama promo, contoh: Promo Awal Tahun',
  `deskripsi` TEXT NULL COMMENT 'Deskripsi promo (opsional)',
  `tipe_promo` ENUM('nominal', 'persen') NOT NULL DEFAULT 'persen' COMMENT 'Jenis potongan',
  `nilai_promo` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Nilai diskon (nominal atau persen)',
  `tanggal_mulai` DATE NOT NULL COMMENT 'Tanggal mulai promo',
  `tanggal_selesai` DATE NOT NULL COMMENT 'Tanggal selesai promo',
  `target_type` ENUM('workorder', 'jasa', 'barang') NOT NULL COMMENT 'Jenis target diskon',
  `target_id` VARCHAR(50) NOT NULL COMMENT 'ID target: kode_wo atau noitem',
  `target_nama` VARCHAR(200) NULL COMMENT 'Nama target (cache untuk tampilan)',
  `status_aktif` TINYINT(1) DEFAULT 1 COMMENT '1=Aktif, 0=Nonaktif',
  `created_by` INT(11) NULL COMMENT 'ID user pembuat',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_promo`),
  INDEX `idx_periode` (`tanggal_mulai`, `tanggal_selesai`),
  INDEX `idx_target` (`target_type`, `target_id`),
  INDEX `idx_status` (`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Master Diskon Periode/Promo';

-- ============================================================================
-- CONTOH DATA (Opsional, uncomment jika ingin test)
-- ============================================================================
-- INSERT INTO `master_diskon_periode` 
--   (`nama_promo`, `tipe_promo`, `nilai_promo`, `tanggal_mulai`, `tanggal_selesai`, `target_type`, `target_id`, `target_nama`)
-- VALUES
--   ('Promo Oli Shell 10%', 'persen', 10.00, '2025-01-01', '2025-01-15', 'barang', 'OLI001', 'Oli Shell Helix'),
--   ('Servis Lengkap Hemat 50rb', 'nominal', 50000.00, '2025-01-01', '2025-01-31', 'workorder', 'WO-SL', 'Servis Lengkap');
