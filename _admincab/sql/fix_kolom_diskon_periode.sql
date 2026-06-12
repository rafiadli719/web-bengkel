-- ============================================================================
-- FIX: Tambah kolom yang mungkin hilang di master_diskon_periode
-- Jalankan script ini jika ada error "Unknown column"
-- ============================================================================

-- Tambah kolom deskripsi jika belum ada
ALTER TABLE `master_diskon_periode` 
ADD COLUMN IF NOT EXISTS `deskripsi` TEXT NULL COMMENT 'Deskripsi promo (opsional)' AFTER `nama_promo`;

-- Tambah kolom target_nama jika belum ada
ALTER TABLE `master_diskon_periode` 
ADD COLUMN IF NOT EXISTS `target_nama` VARCHAR(200) NULL COMMENT 'Nama target (cache untuk tampilan)' AFTER `target_id`;

-- Tambah kolom created_by jika belum ada
ALTER TABLE `master_diskon_periode` 
ADD COLUMN IF NOT EXISTS `created_by` INT(11) NULL COMMENT 'ID user pembuat' AFTER `status_aktif`;

-- Tambah kolom updated_at jika belum ada
ALTER TABLE `master_diskon_periode` 
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
