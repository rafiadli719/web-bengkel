-- ============================================
-- UPDATE DATABASE FOR SIMPLIFIED CANCEL SERVIS
-- Hanya menggunakan kategori_alasan dan alasan_cancel
-- ============================================

-- Backup note: Sebaiknya backup database sebelum menjalankan script ini
-- Field yang dihapus: status_barang, status_pembayaran, nominal_dp, nominal_refund, biaya_cancel, catatan

USE fitmotor_dbbengkel;

-- 1. Cek struktur tabel sebelum perubahan
SELECT
    'BEFORE ALTER' as status,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
  AND TABLE_NAME = 'tb_log_cancel_servis'
ORDER BY ORDINAL_POSITION;

-- 2. Drop kolom yang tidak diperlukan dari tb_log_cancel_servis
-- Hanya menyimpan: id, no_service, tanggal_cancel, alasan_cancel, kategori_alasan, user_cancel, created_at

ALTER TABLE `tb_log_cancel_servis`
  DROP COLUMN IF EXISTS `biaya_cancel`,
  DROP COLUMN IF EXISTS `status_barang`,
  DROP COLUMN IF EXISTS `status_pembayaran`,
  DROP COLUMN IF EXISTS `nominal_dp`,
  DROP COLUMN IF EXISTS `nominal_refund`,
  DROP COLUMN IF EXISTS `catatan`;

-- 3. Pastikan kolom yang tersisa sesuai kebutuhan
ALTER TABLE `tb_log_cancel_servis`
  MODIFY COLUMN `alasan_cancel` TEXT NOT NULL COMMENT 'Keterangan detail alasan pembatalan',
  MODIFY COLUMN `kategori_alasan` ENUM('customer_request','no_stock','no_mekanik','customer_no_show','lainnya') NOT NULL DEFAULT 'lainnya' COMMENT 'Kategori alasan pembatalan';

-- 4. Cek struktur tabel setelah perubahan
SELECT
    'AFTER ALTER' as status,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
  AND TABLE_NAME = 'tb_log_cancel_servis'
ORDER BY ORDINAL_POSITION;

-- 5. Optional: Update field di tblservice jika ada field yang tidak diperlukan
-- Cek apakah ada field biaya_cancel atau cancel_note
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
  AND TABLE_NAME = 'tblservice'
  AND COLUMN_NAME IN ('biaya_cancel', 'cancel_note')
ORDER BY ORDINAL_POSITION;

-- Jika ada, drop field tersebut (uncomment jika diperlukan)
-- ALTER TABLE `tblservice`
--   DROP COLUMN IF EXISTS `biaya_cancel`,
--   DROP COLUMN IF EXISTS `cancel_note`;

-- 6. Verifikasi data existing di tb_log_cancel_servis
SELECT
    COUNT(*) as total_records,
    COUNT(DISTINCT kategori_alasan) as unique_categories
FROM tb_log_cancel_servis;

-- 7. Tampilkan sample data setelah update
SELECT
    id,
    no_service,
    DATE_FORMAT(tanggal_cancel, '%Y-%m-%d %H:%i:%s') as tanggal_cancel,
    kategori_alasan,
    LEFT(alasan_cancel, 50) as alasan_preview,
    user_cancel,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at
FROM tb_log_cancel_servis
ORDER BY id DESC
LIMIT 10;

-- ============================================
-- SELESAI - Cancel Servis Database Updated
-- ============================================
-- Struktur tabel tb_log_cancel_servis sekarang hanya berisi:
-- - id (PK)
-- - no_service (nomor service yang dibatalkan)
-- - tanggal_cancel (tanggal & waktu pembatalan)
-- - alasan_cancel (keterangan detail)
-- - kategori_alasan (dropdown pilihan)
-- - user_cancel (user yang membatalkan)
-- - created_at (timestamp)
-- ============================================
