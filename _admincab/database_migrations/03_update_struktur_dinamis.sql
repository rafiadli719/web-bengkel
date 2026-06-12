-- ============================================================================
-- MIGRATION: Update Struktur Database Menjadi Dinamis
-- Menghapus field hardcoded cabang (stok_pacul, max_adw, ktg_cdt, dll)
-- dan menambahkan kolom yang diperlukan untuk kalkulasi MIN/MAX
--
-- BACKUP DATABASE SEBELUM MENJALANKAN FILE INI!
-- ============================================================================

-- ============================================================================
-- 1. UPDATE TABEL tblitem_minmax - Tambah kolom yang hilang
-- ============================================================================

-- Tambah kolom W1-W12 untuk data penjualan mingguan
ALTER TABLE `tblitem_minmax`
ADD COLUMN `w1` INT DEFAULT 0 AFTER `kd_cabang`,
ADD COLUMN `w2` INT DEFAULT 0 AFTER `w1`,
ADD COLUMN `w3` INT DEFAULT 0 AFTER `w2`,
ADD COLUMN `w4` INT DEFAULT 0 AFTER `w3`,
ADD COLUMN `w5` INT DEFAULT 0 AFTER `w4`,
ADD COLUMN `w6` INT DEFAULT 0 AFTER `w5`,
ADD COLUMN `w7` INT DEFAULT 0 AFTER `w6`,
ADD COLUMN `w8` INT DEFAULT 0 AFTER `w7`,
ADD COLUMN `w9` INT DEFAULT 0 AFTER `w8`,
ADD COLUMN `w10` INT DEFAULT 0 AFTER `w9`,
ADD COLUMN `w11` INT DEFAULT 0 AFTER `w10`,
ADD COLUMN `w12` INT DEFAULT 0 AFTER `w11`,
ADD COLUMN `max_1w` INT DEFAULT 0 COMMENT 'Penjualan tertinggi 1 minggu (W1-W12)' AFTER `w12`,
ADD COLUMN `max_2w` INT DEFAULT 0 COMMENT 'Penjualan tertinggi 2 minggu berturut-turut' AFTER `max_1w`,
ADD COLUMN `total_transaksi_84hari` INT DEFAULT 0 COMMENT 'Jumlah transaksi dalam 84 hari' AFTER `kategori`,
ADD COLUMN `avg_interval_hari` DECIMAL(5,2) DEFAULT 0 COMMENT 'Rata-rata interval hari antar transaksi' AFTER `total_transaksi_84hari`,
ADD COLUMN `lead_time_hari` INT DEFAULT 3 COMMENT 'Estimasi waktu tunggu barang datang' AFTER `supplier2`,
ADD COLUMN `stok_saat_ini` INT DEFAULT 0 COMMENT 'Stok terakhir' AFTER `lead_time_hari`;

-- ============================================================================
-- 2. UPDATE TABEL tblrencana_order_detail - Hapus kolom hardcoded cabang
-- ============================================================================

-- Hapus semua kolom hardcoded PACUL, ADW, CDT, TRY
ALTER TABLE `tblrencana_order_detail`
DROP COLUMN `stok_pacul`,
DROP COLUMN `stok_adw`,
DROP COLUMN `stok_cdt`,
DROP COLUMN `stok_try`,
DROP COLUMN `max_pacul`,
DROP COLUMN `max_adw`,
DROP COLUMN `max_cdt`,
DROP COLUMN `max_try`,
DROP COLUMN `ktg_pacul`,
DROP COLUMN `ktg_adw`,
DROP COLUMN `ktg_cdt`,
DROP COLUMN `ktg_try`,
DROP COLUMN `stok_after_pacul`,
DROP COLUMN `stok_after_adw`,
DROP COLUMN `stok_after_cdt`,
DROP COLUMN `stok_after_try`,
DROP COLUMN `order_pacul`,
DROP COLUMN `order_adw`,
DROP COLUMN `order_cdt`,
DROP COLUMN `order_try`;

-- ============================================================================
-- 3. UPDATE TABEL tblrealisasi_order - Hapus kolom hardcoded cabang
-- ============================================================================

ALTER TABLE `tblrealisasi_order`
DROP COLUMN `jatah_pacul`,
DROP COLUMN `jatah_adw`,
DROP COLUMN `jatah_cdt`,
DROP COLUMN `jatah_try`,
DROP COLUMN `real_pacul`,
DROP COLUMN `real_adw`,
DROP COLUMN `real_cdt`,
DROP COLUMN `real_try`;

-- ============================================================================
-- 4. PASTIKAN TABEL PENDUKUNG SUDAH ADA
-- ============================================================================

-- Tabel penjualan harian
CREATE TABLE IF NOT EXISTS `tblpenjualan_harian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_item` VARCHAR(50) NOT NULL,
    `kd_cabang` VARCHAR(10) NOT NULL,
    `tanggal` DATE NOT NULL,
    `qty_jual` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_item_cabang_tanggal` (`no_item`, `kd_cabang`, `tanggal`),
    INDEX `idx_item` (`no_item`),
    INDEX `idx_cabang` (`kd_cabang`),
    INDEX `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Data penjualan harian untuk kalkulasi MIN/MAX (84 hari)';

-- Tabel penjualan mingguan
CREATE TABLE IF NOT EXISTS `tblpenjualan_mingguan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_item` VARCHAR(50) NOT NULL,
    `kd_cabang` VARCHAR(10) NOT NULL,
    `tahun` INT NOT NULL,
    `minggu` INT NOT NULL COMMENT 'Minggu ke-1 sampai 52',
    `qty_jual` INT DEFAULT 0,
    `jml_transaksi` INT DEFAULT 0 COMMENT 'Jumlah transaksi dalam minggu ini',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_item_cabang_week` (`no_item`, `kd_cabang`, `tahun`, `minggu`),
    INDEX `idx_item` (`no_item`),
    INDEX `idx_cabang` (`kd_cabang`),
    INDEX `idx_tahun_minggu` (`tahun`, `minggu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Data penjualan per minggu untuk kalkulasi MIN/MAX';

-- Tabel rencana order detail per cabang (dinamis)
CREATE TABLE IF NOT EXISTS `tblrencana_order_detail_cabang` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_rencana` VARCHAR(30) NOT NULL,
    `no_item` VARCHAR(50) NOT NULL,
    `kd_cabang` VARCHAR(10) NOT NULL,
    `stok_awal` INT DEFAULT 0 COMMENT 'Stok sebelum transfer',
    `min_stok` INT DEFAULT 0,
    `max_stok` INT DEFAULT 0,
    `kategori` CHAR(1) DEFAULT 'E',
    `transfer_masuk` INT DEFAULT 0 COMMENT 'Qty transfer masuk dari cabang lain',
    `transfer_keluar` INT DEFAULT 0 COMMENT 'Qty transfer keluar ke cabang lain',
    `stok_setelah_transfer` INT DEFAULT 0 COMMENT 'Stok setelah transfer',
    `order_qty` INT DEFAULT 0 COMMENT 'Qty order untuk cabang ini',
    `jatah_qty` INT DEFAULT 0 COMMENT 'Jatah dari barang yang datang',
    `real_qty` INT DEFAULT 0 COMMENT 'Realisasi barang yang sudah diterima',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_rencana_item_cabang` (`no_rencana`, `no_item`, `kd_cabang`),
    INDEX `idx_no_rencana` (`no_rencana`),
    INDEX `idx_no_item` (`no_item`),
    INDEX `idx_kd_cabang` (`kd_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Detail order per item per cabang - Dinamis';

-- Tabel realisasi order distribusi (dinamis) - skip jika sudah ada
CREATE TABLE IF NOT EXISTS `tblrealisasi_order_distribusi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `realisasi_id` INT NOT NULL,
    `kd_cabang` VARCHAR(10) NOT NULL,
    `jatah_qty` INT DEFAULT 0 COMMENT 'Jatah untuk cabang ini',
    `real_qty` INT DEFAULT 0 COMMENT 'Qty yang sudah dikirim/diterima',
    `status` ENUM('pending','sent','received') DEFAULT 'pending',
    `sent_at` DATETIME NULL,
    `received_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_realisasi_cabang` (`realisasi_id`, `kd_cabang`),
    INDEX `idx_realisasi` (`realisasi_id`),
    INDEX `idx_cabang` (`kd_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Distribusi realisasi order ke cabang';

-- ============================================================================
-- 5. DROP VIEWS LAMA JIKA ADA (akan dibuat ulang)
-- ============================================================================
DROP VIEW IF EXISTS `view_stok_cabang`;
DROP VIEW IF EXISTS `view_item_order_segera`;
DROP VIEW IF EXISTS `view_item_minmax_summary`;
DROP VIEW IF EXISTS `view_rencana_order_per_supplier`;

-- ============================================================================
-- 6. BUAT VIEWS BARU
-- ============================================================================

-- View stok per cabang
CREATE VIEW `view_stok_cabang` AS
SELECT
    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci AS no_item,
    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci AS kd_cabang,
    SUM(masuk - keluar) AS stok_akhir
FROM tbstok
GROUP BY
    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci;

-- View item perlu order
CREATE VIEW `view_item_order_segera` AS
SELECT
    m.no_item,
    CONVERT(i.namaitem USING utf8mb4) AS nama_item,
    m.kd_cabang,
    c.nama_cabang,
    COALESCE(s.stok_akhir, 0) AS stok_saat_ini,
    m.min_stok,
    m.max_stok,
    m.kategori,
    ROUND(m.min_stok / 2, 0) AS kebutuhan_3_hari,
    COALESCE(m.lead_time_hari, 3) AS lead_time_hari,
    CASE
        WHEN COALESCE(s.stok_akhir, 0) < ROUND(m.min_stok / 2, 0) THEN 'URGENT'
        WHEN COALESCE(s.stok_akhir, 0) < m.min_stok THEN 'WARNING'
        ELSE 'OK'
    END AS status_stok,
    m.supplier1,
    m.supplier2
FROM tblitem_minmax m
LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
LEFT JOIN tbcabang c ON m.kd_cabang = c.kode_cabang
LEFT JOIN view_stok_cabang s ON m.no_item = s.no_item AND m.kd_cabang = s.kd_cabang
WHERE m.kategori IN ('A', 'B', 'C')
  AND COALESCE(s.stok_akhir, 0) < m.min_stok;

-- View summary MIN/MAX per item
CREATE VIEW `view_item_minmax_summary` AS
SELECT
    m.no_item,
    i.namaitem AS nama_item,
    i.jenis,
    i.hargapokok AS harga,
    COUNT(DISTINCT m.kd_cabang) AS jml_cabang,
    SUM(COALESCE(s.stok_akhir, 0)) AS total_stok,
    SUM(m.min_stok) AS total_min,
    SUM(m.max_stok) AS total_max,
    MIN(m.kategori) AS kategori_terbaik,
    MAX(m.kategori) AS kategori_terburuk,
    MAX(m.supplier1) AS supplier1,
    MAX(m.supplier2) AS supplier2
FROM tblitem_minmax m
LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
LEFT JOIN view_stok_cabang s ON m.no_item = s.no_item AND m.kd_cabang = s.kd_cabang
GROUP BY m.no_item, i.namaitem, i.jenis, i.hargapokok;

-- View rencana order per supplier
CREATE VIEW `view_rencana_order_per_supplier` AS
SELECT
    h.no_rencana,
    h.tanggal,
    'ORDER1' AS tipe_order,
    d.order1_supplier_final AS supplier,
    COUNT(DISTINCT d.no_item) AS total_item,
    SUM(d.order1_qty_final) AS total_qty,
    SUM(d.order1_nilai) AS total_nilai
FROM tblrencana_order_header h
JOIN tblrencana_order_detail d ON h.no_rencana = d.no_rencana
WHERE d.order1_qty_final > 0
GROUP BY h.no_rencana, h.tanggal, d.order1_supplier_final

UNION ALL

SELECT
    h.no_rencana,
    h.tanggal,
    'ORDER2' AS tipe_order,
    d.order2_supplier_final AS supplier,
    COUNT(DISTINCT d.no_item) AS total_item,
    SUM(d.order2_qty_final) AS total_qty,
    SUM(d.order2_nilai) AS total_nilai
FROM tblrencana_order_header h
JOIN tblrencana_order_detail d ON h.no_rencana = d.no_rencana
WHERE d.order2_qty_final > 0
GROUP BY h.no_rencana, h.tanggal, d.order2_supplier_final;

-- ============================================================================
-- SELESAI
-- Struktur database sekarang dinamis dan mendukung cabang baru tanpa ALTER TABLE
-- ============================================================================
