-- ====================================================
-- SQL Helper: Create VIEWs for Item Search Page
-- File: create_views_if_not_exists.sql
-- ====================================================

-- 1. VIEW view_cari_item (untuk pencarian item barang)
DROP VIEW IF EXISTS `view_cari_item`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_cari_item` AS
SELECT
    `i`.`noitem` AS `noitem`,
    `i`.`namaitem` AS `namaitem`,
    `i`.`nama_part_resmi` AS `nama_part_resmi`,
    COALESCE(`i`.`kodebarcode`,'') AS `kodebarcode`,
    `i`.`merek` AS `merek`,
    `i`.`tipe_item` AS `tipe_item`,
    `i`.`status_validasi` AS `status_validasi`,
    `i`.`kategori_rak` AS `kategori_rak`,
    `i`.`hargapokok` AS `hargapokok`,
    `i`.`hargajual` AS `hargajual`,
    `i`.`statusitem` AS `statusitem`,
    `i`.`satuan` AS `satuan`,
    COALESCE(`i`.`stokmin`,0) AS `stokmin`,
    COALESCE(`i`.`rakbarang`,'') AS `rakbarang`,
    COALESCE(`j`.`namajenis`,'Tidak Ada') AS `namajenis`,
    `i`.`note` AS `keterangan`,
    `i`.`created_at` AS `created_at`,
    `i`.`updated_at` AS `updated_at`
FROM (`tblitem` `i`
LEFT JOIN `tbljenis` `j` ON `i`.`jenis` = `j`.`kodejenis`)
WHERE `i`.`statusitem` = '1';

-- 2. VIEW view_stok_master (untuk informasi stok)
DROP VIEW IF EXISTS `view_stok_master`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_stok_master` AS
SELECT
    `tbstok`.`kd_cabang` AS `kd_cabang`,
    `tbstok`.`no_item` AS `no_item`,
    SUM(`tbstok`.`masuk` - `tbstok`.`keluar`) AS `saldo`
FROM `tbstok`
GROUP BY `tbstok`.`kd_cabang`, `tbstok`.`no_item`;

-- ====================================================
-- Verification Query
-- ====================================================
-- Jalankan query ini untuk verifikasi VIEW sudah dibuat:

SELECT
    TABLE_NAME as 'View Name',
    TABLE_ROWS as 'Estimated Rows'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('view_cari_item', 'view_stok_master')
AND TABLE_TYPE = 'VIEW';

-- ====================================================
-- Test Query
-- ====================================================
-- Test view_cari_item
SELECT COUNT(*) as total_items FROM view_cari_item;

-- Test view_stok_master
SELECT COUNT(*) as total_stok_records FROM view_stok_master;

-- Test pencarian item
SELECT noitem, namaitem, namajenis, hargajual
FROM view_cari_item
LIMIT 10;
