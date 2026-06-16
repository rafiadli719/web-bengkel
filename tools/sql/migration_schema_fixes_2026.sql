-- =============================================================================
-- MIGRATION: Schema Fixes & Sync Improvements
-- Tanggal  : 2026-06-16
-- Deskripsi: Perbaikan inkonsistensi tipe data kolom cabang, item, supplier,
--            serta penambahan tabel tblitem_harga_cabang untuk harga per cabang
-- Jalankan : Sekali saja via app/_tools/migrate_schema_fixes.php atau phpMyAdmin
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. CRITICAL: tblitem_stok.kode_cabang varchar(3) → varchar(10)
--    Data 'PESALAKAN' (9 char) tidak bisa masuk jika masih varchar(3)
-- -----------------------------------------------------------------------------
ALTER TABLE `tblitem_stok`
    MODIFY COLUMN `kode_cabang` VARCHAR(10) NOT NULL DEFAULT '';

-- -----------------------------------------------------------------------------
-- 2. tbuser_karyawan.kode_cabang varchar(20) → varchar(10) (oversized)
-- -----------------------------------------------------------------------------
ALTER TABLE `tbuser_karyawan`
    MODIFY COLUMN `kode_cabang` VARCHAR(10) DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 3. no_item varchar(50) → varchar(20) di tabel transaksi
--    Sesuaikan dengan tblitem.noitem varchar(20)
-- -----------------------------------------------------------------------------
ALTER TABLE `tbitem_keluar_detail`
    MODIFY COLUMN `no_item` VARCHAR(20) NOT NULL DEFAULT '';

ALTER TABLE `tbitem_masuk_detail`
    MODIFY COLUMN `no_item` VARCHAR(20) NOT NULL DEFAULT '';

ALTER TABLE `tblorder_detail`
    MODIFY COLUMN `no_item` VARCHAR(20) NOT NULL DEFAULT '';

ALTER TABLE `tblpembelian_detail`
    MODIFY COLUMN `no_item` VARCHAR(20) NOT NULL DEFAULT '';

ALTER TABLE `tblpenjualan_detail`
    MODIFY COLUMN `no_item` VARCHAR(20) NOT NULL DEFAULT '';

ALTER TABLE `tbldelivery_order_detail`
    MODIFY COLUMN `no_item` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `stg_access_gabung_pembelian`
    MODIFY COLUMN `no_item` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `stg_access_gabung_penjualan`
    MODIFY COLUMN `no_item` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `stg_access_gabung_service_barang`
    MODIFY COLUMN `no_item` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `stg_access_gabung_service_jasa`
    MODIFY COLUMN `no_item` VARCHAR(20) DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 4. no_supplier varchar(50) → varchar(20) di tabel transaksi
--    Sesuaikan dengan tblsupplier.nosupplier varchar(20)
-- -----------------------------------------------------------------------------
ALTER TABLE `tblorder_header`
    MODIFY COLUMN `no_supplier` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `tblpembelian_header`
    MODIFY COLUMN `no_supplier` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `tbldelivery_order_header`
    MODIFY COLUMN `no_supplier` VARCHAR(20) DEFAULT NULL;

ALTER TABLE `stg_access_gabung_pembelian`
    MODIFY COLUMN `no_supplier` VARCHAR(20) DEFAULT NULL;

-- -----------------------------------------------------------------------------
-- 5. BARU: tblitem_harga_cabang
--    Menyimpan harga & stok per item per cabang hasil sync dari MS Access.
--    tblitem tetap sebagai master global (tidak diubah strukturnya).
--    Pakai kd_cabang varchar(10) mengikuti konvensi tabel transaksi.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblitem_harga_cabang` (
    `noitem`        VARCHAR(20)    NOT NULL,
    `kd_cabang`     VARCHAR(10)    NOT NULL,
    `hargapokok`    DOUBLE         NOT NULL DEFAULT 0,
    `hargajual`     DOUBLE         NOT NULL DEFAULT 0,
    `hargajual2`    DOUBLE         NOT NULL DEFAULT 0,
    `hargajual3`    DOUBLE         NOT NULL DEFAULT 0,
    `qty_access`    INT(11)        NOT NULL DEFAULT 0,
    `tanggal_sync`  DATETIME       DEFAULT NULL,
    PRIMARY KEY (`noitem`, `kd_cabang`),
    KEY `idx_kd_cabang` (`kd_cabang`),
    KEY `idx_noitem` (`noitem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Harga dan stok per item per cabang dari sync MS Access FITMOTOR GABUNG';

-- -----------------------------------------------------------------------------
-- 6. Index tbstok untuk performa operasi sync stok per cabang
-- -----------------------------------------------------------------------------
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbstok'
      AND INDEX_NAME = 'idx_sync_key'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `tbstok` ADD INDEX `idx_sync_key` (`no_item`, `kd_cabang`, `no_transaksi`)',
    'SELECT "Index idx_sync_key sudah ada" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 7. Harga per cabang: stored functions + update view_cari_item
--    Dijalankan: 2026-06-16 (sesi kedua)
--    tblitem_harga_cabang dikonversi ke latin1 agar cocok dengan tblitem
-- -----------------------------------------------------------------------------

-- Konversi collation tblitem_harga_cabang ke latin1 (konsisten dengan tblitem)
ALTER TABLE `tblitem_harga_cabang`
    CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci;

-- Fungsi lookup harga jual per cabang (baca @kd_cabang_filter dari sesi PHP)
DROP FUNCTION IF EXISTS `fn_hargajual_cabang`;
DROP FUNCTION IF EXISTS `fn_hargapokok_cabang`;

-- Note: DELIMITER tidak didukung di multi-statement PHP mysqli.
-- Jalankan dua block CREATE FUNCTION berikut via MySQL CLI atau phpMyAdmin.

-- Jalankan via MySQL CLI:
-- DELIMITER //
-- CREATE FUNCTION fn_hargajual_cabang(p_noitem VARCHAR(20))
-- RETURNS DOUBLE READS SQL DATA
-- BEGIN
--     DECLARE v_harga DOUBLE DEFAULT NULL;
--     IF @kd_cabang_filter IS NOT NULL AND @kd_cabang_filter != '' THEN
--         SELECT hargajual INTO v_harga FROM tblitem_harga_cabang
--         WHERE noitem = p_noitem AND kd_cabang = @kd_cabang_filter LIMIT 1;
--     END IF;
--     RETURN v_harga;
-- END//
-- (dan fn_hargapokok_cabang serupa)
-- DELIMITER ;

-- Update view_cari_item: COALESCE harga cabang > harga global
CREATE OR REPLACE VIEW `view_cari_item` AS
SELECT
    `i`.`noitem`           AS `noitem`,
    `i`.`namaitem`         AS `namaitem`,
    `i`.`nama_part_resmi`  AS `nama_part_resmi`,
    COALESCE(`i`.`kodebarcode`, '')  AS `kodebarcode`,
    `i`.`merek`            AS `merek`,
    `i`.`tipe_item`        AS `tipe_item`,
    `i`.`status_validasi`  AS `status_validasi`,
    `i`.`kategori_rak`     AS `kategori_rak`,
    COALESCE(`fn_hargapokok_cabang`(`i`.`noitem`), `i`.`hargapokok`) AS `hargapokok`,
    COALESCE(`fn_hargajual_cabang`(`i`.`noitem`),  `i`.`hargajual`)  AS `hargajual`,
    `i`.`hargajual2`       AS `hargajual2`,
    `i`.`hargajual3`       AS `hargajual3`,
    `i`.`statusitem`       AS `statusitem`,
    `i`.`satuan`           AS `satuan`,
    COALESCE(`i`.`stokmin`, 0)       AS `stokmin`,
    COALESCE(`i`.`rakbarang`, '')    AS `rakbarang`,
    COALESCE(`j`.`namajenis`, 'Tidak Ada') AS `namajenis`,
    `i`.`note`             AS `keterangan`,
    `i`.`created_at`       AS `created_at`,
    `i`.`updated_at`       AS `updated_at`,
    IF(`fn_hargajual_cabang`(`i`.`noitem`) IS NOT NULL, 1, 0) AS `harga_dari_cabang`
FROM `tblitem` `i`
LEFT JOIN `tbljenis` `j` ON `i`.`jenis` = `j`.`kodejenis`
WHERE `i`.`statusitem` = '1';

-- -----------------------------------------------------------------------------
-- SELESAI
-- -----------------------------------------------------------------------------
SELECT 'Migration schema_fixes_2026 selesai dijalankan.' AS status;
