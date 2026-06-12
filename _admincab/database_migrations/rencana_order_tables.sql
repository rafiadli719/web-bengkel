-- ============================================================================
-- MIGRATION: Tabel untuk Sistem Rencana Order
-- Berdasarkan analisis file RENCANA ORDER 21052025.xlsx
-- Tanggal: 19 Desember 2025
-- VERSI 2: Struktur dinamis per cabang (tidak hardcode nama cabang)
-- ============================================================================

-- ============================================================================
-- 1. TABEL PENJUALAN HARIAN (untuk kalkulasi 84 hari / 12 minggu)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblpenjualan_harian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_item VARCHAR(50) NOT NULL,
    kd_cabang VARCHAR(10) NOT NULL,
    tanggal DATE NOT NULL,
    qty_jual INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_item_cabang_tanggal (no_item, kd_cabang, tanggal),
    INDEX idx_item (no_item),
    INDEX idx_cabang (kd_cabang),
    INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Data penjualan harian untuk kalkulasi MIN/MAX (84 hari)';

-- ============================================================================
-- 2. TABEL PENJUALAN MINGGUAN (agregasi dari harian)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblpenjualan_mingguan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_item VARCHAR(50) NOT NULL,
    kd_cabang VARCHAR(10) NOT NULL,
    tahun INT NOT NULL,
    minggu INT NOT NULL COMMENT 'Minggu ke-1 sampai 52',
    qty_jual INT DEFAULT 0,
    jml_transaksi INT DEFAULT 0 COMMENT 'Jumlah transaksi dalam minggu ini',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_item_cabang_week (no_item, kd_cabang, tahun, minggu),
    INDEX idx_item (no_item),
    INDEX idx_cabang (kd_cabang),
    INDEX idx_tahun_minggu (tahun, minggu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Data penjualan per minggu untuk kalkulasi MIN/MAX';

-- ============================================================================
-- 3. TABEL ITEM MIN/MAX (Per Item Per Cabang - Dinamis)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblitem_minmax (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_item VARCHAR(50) NOT NULL,
    kd_cabang VARCHAR(10) NOT NULL,

    -- Perhitungan mingguan (W1-W12)
    w1 INT DEFAULT 0, w2 INT DEFAULT 0, w3 INT DEFAULT 0, w4 INT DEFAULT 0,
    w5 INT DEFAULT 0, w6 INT DEFAULT 0, w7 INT DEFAULT 0, w8 INT DEFAULT 0,
    w9 INT DEFAULT 0, w10 INT DEFAULT 0, w11 INT DEFAULT 0, w12 INT DEFAULT 0,

    -- MAX 1 Minggu dan 2 Minggu
    max_1w INT DEFAULT 0 COMMENT 'Penjualan tertinggi 1 minggu (W1-W12)',
    max_2w INT DEFAULT 0 COMMENT 'Penjualan tertinggi 2 minggu berturut-turut',

    -- MIN/MAX Stok
    min_stok INT DEFAULT 0 COMMENT 'MIN = Penjualan terendah 1 minggu (W1-W12)',
    max_stok INT DEFAULT 0 COMMENT 'MAX = MAX penjualan 2 minggu berturut-turut',

    -- Klasifikasi berdasarkan frekuensi transaksi 84 hari
    total_transaksi_84hari INT DEFAULT 0 COMMENT 'Jumlah transaksi dalam 84 hari',
    avg_interval_hari DECIMAL(5,2) DEFAULT 0 COMMENT 'Rata-rata interval hari antar transaksi',
    kategori CHAR(1) DEFAULT 'E' COMMENT 'A=Fast(1-3hari), B=Medium(4-12hari), C=Slow(>12hari), D=Dead(>30hari), E=Non-Stock',

    -- Pengaturan Order
    kelipatan_order1 INT DEFAULT 1 COMMENT 'Kelipatan qty untuk ORDER 1',
    kelipatan_order2 INT DEFAULT 1 COMMENT 'Kelipatan qty untuk ORDER 2',
    sat_beli_vs_jual DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Rasio satuan beli vs jual',

    -- Supplier
    supplier1 VARCHAR(50) NULL COMMENT 'Supplier tempo <= 14 hari',
    supplier2 VARCHAR(50) NULL COMMENT 'Supplier tempo > 14 hari (NSA)',

    -- Lead time
    lead_time_hari INT DEFAULT 3 COMMENT 'Estimasi waktu tunggu barang datang',

    -- Stok saat ini
    stok_saat_ini INT DEFAULT 0 COMMENT 'Stok terakhir',

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_item_cabang (no_item, kd_cabang),
    INDEX idx_kategori (kategori),
    INDEX idx_supplier1 (supplier1),
    INDEX idx_supplier2 (supplier2),

    CONSTRAINT fk_minmax_cabang FOREIGN KEY (kd_cabang)
        REFERENCES tbcabang(kode_cabang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Master MIN/MAX stok per item per cabang - Dinamis';

-- ============================================================================
-- 4. TABEL RENCANA ORDER HEADER
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblrencana_order_header (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rencana VARCHAR(30) NOT NULL,
    tanggal DATE NOT NULL,
    periode_awal DATE NOT NULL COMMENT 'Tanggal awal periode analisis (84 hari lalu)',
    periode_akhir DATE NOT NULL COMMENT 'Tanggal akhir periode analisis',
    total_item INT DEFAULT 0,
    total_qty INT DEFAULT 0,
    total_nilai DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft','approved','ordered','partial','completed') DEFAULT 'draft',
    note TEXT NULL,
    approved_by VARCHAR(50) NULL,
    approved_at DATETIME NULL,
    created_by VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_no_rencana (no_rencana),
    INDEX idx_tanggal (tanggal),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Header rencana order';

-- ============================================================================
-- 5. TABEL RENCANA ORDER DETAIL (Per Item - Dinamis tanpa hardcode cabang)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblrencana_order_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rencana VARCHAR(30) NOT NULL,
    no_item VARCHAR(50) NOT NULL,
    nama_item VARCHAR(200) NULL,
    jenis VARCHAR(20) NULL COMMENT 'ORISIN/OLIGEN/AKIGEN',
    harga DECIMAL(15,2) DEFAULT 0,

    -- ORDER 1 (Tempo <= 14 hari)
    order1_qty INT DEFAULT 0,
    order1_qty_edit INT NULL,
    order1_qty_final INT DEFAULT 0,
    order1_supplier VARCHAR(50) NULL,
    order1_supplier_edit VARCHAR(50) NULL,
    order1_supplier_final VARCHAR(50) NULL,
    order1_nilai DECIMAL(15,2) DEFAULT 0,

    -- ORDER 2 (Tempo > 14 hari / NSA)
    order2_qty INT DEFAULT 0,
    order2_qty_edit INT NULL,
    order2_qty_final INT DEFAULT 0,
    order2_supplier VARCHAR(50) NULL,
    order2_supplier_edit VARCHAR(50) NULL,
    order2_supplier_final VARCHAR(50) NULL,
    order2_nilai DECIMAL(15,2) DEFAULT 0,

    -- Status
    status_order1 ENUM('pending','ordered','received') DEFAULT 'pending',
    status_order2 ENUM('pending','ordered','received') DEFAULT 'pending',
    no_po_order1 VARCHAR(50) NULL,
    no_po_order2 VARCHAR(50) NULL,

    keterangan TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_no_rencana (no_rencana),
    INDEX idx_no_item (no_item),
    INDEX idx_order1_supplier (order1_supplier_final),
    INDEX idx_order2_supplier (order2_supplier_final),

    CONSTRAINT fk_rencana_detail FOREIGN KEY (no_rencana)
        REFERENCES tblrencana_order_header(no_rencana) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Detail rencana order per item';

-- ============================================================================
-- 6. TABEL RENCANA ORDER DETAIL PER CABANG (Dinamis - relasi ke master cabang)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblrencana_order_detail_cabang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rencana VARCHAR(30) NOT NULL,
    no_item VARCHAR(50) NOT NULL,
    kd_cabang VARCHAR(10) NOT NULL,

    -- Data stok dan MIN/MAX dari cabang ini
    stok_awal INT DEFAULT 0 COMMENT 'Stok sebelum transfer',
    min_stok INT DEFAULT 0,
    max_stok INT DEFAULT 0,
    kategori CHAR(1) DEFAULT 'E',

    -- Transfer antar cabang
    transfer_masuk INT DEFAULT 0 COMMENT 'Qty transfer masuk dari cabang lain',
    transfer_keluar INT DEFAULT 0 COMMENT 'Qty transfer keluar ke cabang lain',
    stok_setelah_transfer INT DEFAULT 0 COMMENT 'Stok setelah transfer',

    -- Order untuk cabang ini
    order_qty INT DEFAULT 0 COMMENT 'Qty order untuk cabang ini',

    -- Jatah dari realisasi order
    jatah_qty INT DEFAULT 0 COMMENT 'Jatah dari barang yang datang',
    real_qty INT DEFAULT 0 COMMENT 'Realisasi barang yang sudah diterima',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_rencana_item_cabang (no_rencana, no_item, kd_cabang),
    INDEX idx_no_rencana (no_rencana),
    INDEX idx_no_item (no_item),
    INDEX idx_kd_cabang (kd_cabang),

    CONSTRAINT fk_detail_cabang_rencana FOREIGN KEY (no_rencana)
        REFERENCES tblrencana_order_header(no_rencana) ON DELETE CASCADE,
    CONSTRAINT fk_detail_cabang_cabang FOREIGN KEY (kd_cabang)
        REFERENCES tbcabang(kode_cabang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Detail order per item per cabang - Dinamis';

-- ============================================================================
-- 7. TABEL RENCANA TRANSFER ANTAR CABANG
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblrencana_transfer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rencana VARCHAR(30) NOT NULL,
    tanggal DATE NOT NULL,
    no_item VARCHAR(50) NOT NULL,
    dari_cabang VARCHAR(10) NOT NULL,
    ke_cabang VARCHAR(10) NOT NULL,
    qty_suggest INT DEFAULT 0 COMMENT 'Qty saran sistem',
    qty_edit INT NULL COMMENT 'Qty override manual',
    qty_final INT DEFAULT 0 COMMENT 'Qty final yang ditransfer',
    status ENUM('draft','approved','executed','cancelled') DEFAULT 'draft',
    approved_by VARCHAR(50) NULL,
    approved_at DATETIME NULL,
    executed_by VARCHAR(50) NULL,
    executed_at DATETIME NULL,
    no_nota_antarcab VARCHAR(50) NULL,
    created_by VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_no_rencana (no_rencana),
    INDEX idx_tanggal (tanggal),
    INDEX idx_dari_cabang (dari_cabang),
    INDEX idx_ke_cabang (ke_cabang),
    INDEX idx_status (status),
    INDEX idx_item (no_item),

    CONSTRAINT fk_transfer_dari FOREIGN KEY (dari_cabang)
        REFERENCES tbcabang(kode_cabang) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_ke FOREIGN KEY (ke_cabang)
        REFERENCES tbcabang(kode_cabang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Rencana transfer antar cabang';

-- ============================================================================
-- 8. TABEL REALISASI ORDER (Dinamis per cabang)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblrealisasi_order (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_rencana VARCHAR(30) NOT NULL,
    no_po VARCHAR(50) NOT NULL,
    no_item VARCHAR(50) NOT NULL,
    qty_order INT DEFAULT 0,
    qty_received INT DEFAULT 0,
    status ENUM('pending','partial','complete') DEFAULT 'pending',
    received_at DATETIME NULL,
    received_by VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_no_rencana (no_rencana),
    INDEX idx_no_po (no_po),
    INDEX idx_no_item (no_item),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Realisasi order header';

-- ============================================================================
-- 9. TABEL REALISASI ORDER DISTRIBUSI (Per Cabang - Dinamis)
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblrealisasi_order_distribusi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    realisasi_id INT NOT NULL,
    kd_cabang VARCHAR(10) NOT NULL,
    jatah_qty INT DEFAULT 0 COMMENT 'Jatah untuk cabang ini',
    real_qty INT DEFAULT 0 COMMENT 'Qty yang sudah dikirim/diterima',
    status ENUM('pending','sent','received') DEFAULT 'pending',
    sent_at DATETIME NULL,
    received_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_realisasi_cabang (realisasi_id, kd_cabang),
    INDEX idx_realisasi (realisasi_id),
    INDEX idx_cabang (kd_cabang),

    CONSTRAINT fk_distribusi_realisasi FOREIGN KEY (realisasi_id)
        REFERENCES tblrealisasi_order(id) ON DELETE CASCADE,
    CONSTRAINT fk_distribusi_cabang FOREIGN KEY (kd_cabang)
        REFERENCES tbcabang(kode_cabang) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Distribusi realisasi order ke cabang';

-- ============================================================================
-- 10. TABEL SUPPLIER TEMPO
-- ============================================================================
CREATE TABLE IF NOT EXISTS tblsupplier_tempo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_supplier VARCHAR(50) NOT NULL,
    nama_supplier VARCHAR(200) NULL,
    tempo_hari INT DEFAULT 0 COMMENT 'Tempo pembayaran dalam hari',
    kategori_tempo ENUM('cepat','lambat') DEFAULT 'cepat' COMMENT 'cepat=<=14 hari, lambat=>14 hari',
    no_wa VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    alamat TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_kode_supplier (kode_supplier),
    INDEX idx_tempo (tempo_hari),
    INDEX idx_kategori (kategori_tempo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Master supplier dengan tempo';

-- ============================================================================
-- VIEW: Stok per cabang dari tbstok
-- ============================================================================
CREATE OR REPLACE VIEW view_stok_cabang AS
SELECT
    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci AS no_item,
    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci AS kd_cabang,
    SUM(masuk - keluar) AS stok_akhir
FROM tbstok
GROUP BY
    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci;

-- ============================================================================
-- VIEW: Item dengan stok rendah (perlu order segera)
-- ============================================================================
CREATE OR REPLACE VIEW view_item_order_segera AS
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
    m.lead_time_hari,
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
  AND COALESCE(s.stok_akhir, 0) < m.min_stok
ORDER BY
    CASE WHEN COALESCE(s.stok_akhir, 0) < ROUND(m.min_stok / 2, 0) THEN 1 ELSE 2 END,
    m.kd_cabang, m.no_item;

-- ============================================================================
-- VIEW: Summary MIN/MAX per item (aggregat semua cabang)
-- ============================================================================
CREATE OR REPLACE VIEW view_item_minmax_summary AS
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

-- ============================================================================
-- VIEW: Summary rencana order per supplier
-- ============================================================================
CREATE OR REPLACE VIEW view_rencana_order_per_supplier AS
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
-- INSERT DATA SUPPLIER TEMPO DEFAULT
-- ============================================================================
INSERT IGNORE INTO tblsupplier_tempo (kode_supplier, nama_supplier, tempo_hari, kategori_tempo) VALUES
('GSM', 'GSM', 7, 'cepat'),
('HKJ', 'HKJ', 14, 'cepat'),
('AHASS', 'AHASS', 14, 'cepat'),
('NJM', 'NJM', 7, 'cepat'),
('NSA', 'NSA', 30, 'lambat'),
('BJP', 'BJP', 21, 'lambat'),
('IMAM', 'IMAM', 14, 'cepat'),
('SAPTA AJI', 'Sapta Aji', 21, 'lambat'),
('CVMMMS', 'CV MMMS', 14, 'cepat');

-- ============================================================================
-- STORED PROCEDURE: Populate penjualan harian dari transaksi (84 hari)
-- ============================================================================
DELIMITER //

DROP PROCEDURE IF EXISTS sp_populate_penjualan_harian//
CREATE PROCEDURE sp_populate_penjualan_harian()
BEGIN
    DECLARE v_start_date DATE;
    SET v_start_date = DATE_SUB(CURDATE(), INTERVAL 84 DAY);

    -- Hapus data lama
    DELETE FROM tblpenjualan_harian WHERE tanggal < v_start_date;

    -- Insert dari tbstok (tipe 3=Penjualan, 4=Penjualan Service)
    INSERT INTO tblpenjualan_harian (no_item, kd_cabang, tanggal, qty_jual)
    SELECT
        CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
        CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci,
        tanggal,
        SUM(keluar) AS qty_jual
    FROM tbstok
    WHERE tipe IN ('3', '4')
      AND tanggal >= v_start_date
      AND keluar > 0
    GROUP BY
        CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
        CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci,
        tanggal
    ON DUPLICATE KEY UPDATE
        qty_jual = VALUES(qty_jual);

END //

-- ============================================================================
-- STORED PROCEDURE: Populate penjualan mingguan dari harian
-- ============================================================================
DROP PROCEDURE IF EXISTS sp_populate_penjualan_mingguan//
CREATE PROCEDURE sp_populate_penjualan_mingguan()
BEGIN
    -- Agregasi dari harian ke mingguan
    INSERT INTO tblpenjualan_mingguan (no_item, kd_cabang, tahun, minggu, qty_jual, jml_transaksi)
    SELECT
        no_item,
        kd_cabang,
        YEAR(tanggal) AS tahun,
        WEEK(tanggal, 1) AS minggu,
        SUM(qty_jual) AS qty_jual,
        COUNT(*) AS jml_transaksi
    FROM tblpenjualan_harian
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 84 DAY)
    GROUP BY no_item, kd_cabang, YEAR(tanggal), WEEK(tanggal, 1)
    ON DUPLICATE KEY UPDATE
        qty_jual = VALUES(qty_jual),
        jml_transaksi = VALUES(jml_transaksi),
        updated_at = NOW();

END //

-- ============================================================================
-- STORED PROCEDURE: Hitung MIN/MAX dan Klasifikasi (84 hari / 12 minggu)
-- ============================================================================
DROP PROCEDURE IF EXISTS sp_hitung_minmax_stok//
CREATE PROCEDURE sp_hitung_minmax_stok(
    IN p_kd_cabang VARCHAR(10)
)
BEGIN
    DECLARE v_start_date DATE;
    SET v_start_date = DATE_SUB(CURDATE(), INTERVAL 84 DAY);

    -- Populate data penjualan dulu
    CALL sp_populate_penjualan_harian();
    CALL sp_populate_penjualan_mingguan();

    -- Update/Insert tblitem_minmax dengan data 12 minggu
    INSERT INTO tblitem_minmax (
        no_item, kd_cabang,
        w1, w2, w3, w4, w5, w6, w7, w8, w9, w10, w11, w12,
        max_1w, max_2w, min_stok, max_stok,
        total_transaksi_84hari, avg_interval_hari, kategori,
        stok_saat_ini
    )
    SELECT
        pm.no_item,
        pm.kd_cabang,
        -- W1-W12 (minggu terbaru ke terlama)
        MAX(CASE WHEN rn = 1 THEN qty_jual ELSE 0 END) AS w1,
        MAX(CASE WHEN rn = 2 THEN qty_jual ELSE 0 END) AS w2,
        MAX(CASE WHEN rn = 3 THEN qty_jual ELSE 0 END) AS w3,
        MAX(CASE WHEN rn = 4 THEN qty_jual ELSE 0 END) AS w4,
        MAX(CASE WHEN rn = 5 THEN qty_jual ELSE 0 END) AS w5,
        MAX(CASE WHEN rn = 6 THEN qty_jual ELSE 0 END) AS w6,
        MAX(CASE WHEN rn = 7 THEN qty_jual ELSE 0 END) AS w7,
        MAX(CASE WHEN rn = 8 THEN qty_jual ELSE 0 END) AS w8,
        MAX(CASE WHEN rn = 9 THEN qty_jual ELSE 0 END) AS w9,
        MAX(CASE WHEN rn = 10 THEN qty_jual ELSE 0 END) AS w10,
        MAX(CASE WHEN rn = 11 THEN qty_jual ELSE 0 END) AS w11,
        MAX(CASE WHEN rn = 12 THEN qty_jual ELSE 0 END) AS w12,
        -- MAX 1W = penjualan tertinggi 1 minggu
        MAX(qty_jual) AS max_1w,
        -- MAX 2W akan dihitung setelah insert
        0 AS max_2w,
        -- MIN Stok = penjualan terendah (non-zero) atau 0
        COALESCE(MIN(CASE WHEN qty_jual > 0 THEN qty_jual END), 0) AS min_stok,
        -- MAX Stok akan dihitung setelah insert
        0 AS max_stok,
        -- Total transaksi 84 hari
        SUM(jml_transaksi) AS total_transaksi_84hari,
        -- Avg interval (84 hari / jumlah transaksi)
        CASE WHEN SUM(jml_transaksi) > 0 THEN 84.0 / SUM(jml_transaksi) ELSE 999 END AS avg_interval_hari,
        -- Kategori berdasarkan interval
        CASE
            WHEN SUM(jml_transaksi) >= 22 THEN 'A'  -- Fast: ~1-3 hari interval (84/22=3.8)
            WHEN SUM(jml_transaksi) >= 7 THEN 'B'   -- Medium: ~4-12 hari interval (84/7=12)
            WHEN SUM(jml_transaksi) >= 4 THEN 'C'   -- Slow: ~12-21 hari interval
            WHEN SUM(jml_transaksi) >= 1 THEN 'D'   -- Dead: > 21 hari interval
            ELSE 'E'                                  -- Non-Stock: 0 transaksi
        END AS kategori,
        -- Stok saat ini
        COALESCE((SELECT SUM(masuk - keluar) FROM tbstok s
                  WHERE CONVERT(s.no_item USING utf8mb4) COLLATE utf8mb4_general_ci = pm.no_item
                  AND CONVERT(s.kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci = pm.kd_cabang), 0) AS stok_saat_ini
    FROM (
        SELECT
            no_item, kd_cabang, tahun, minggu, qty_jual, jml_transaksi,
            ROW_NUMBER() OVER (PARTITION BY no_item, kd_cabang ORDER BY tahun DESC, minggu DESC) AS rn
        FROM tblpenjualan_mingguan
        WHERE (tahun * 100 + minggu) >= (YEAR(v_start_date) * 100 + WEEK(v_start_date, 1))
    ) pm
    WHERE pm.rn <= 12
      AND (p_kd_cabang IS NULL OR pm.kd_cabang = p_kd_cabang)
    GROUP BY pm.no_item, pm.kd_cabang
    ON DUPLICATE KEY UPDATE
        w1 = VALUES(w1), w2 = VALUES(w2), w3 = VALUES(w3), w4 = VALUES(w4),
        w5 = VALUES(w5), w6 = VALUES(w6), w7 = VALUES(w7), w8 = VALUES(w8),
        w9 = VALUES(w9), w10 = VALUES(w10), w11 = VALUES(w11), w12 = VALUES(w12),
        max_1w = VALUES(max_1w),
        min_stok = VALUES(min_stok),
        total_transaksi_84hari = VALUES(total_transaksi_84hari),
        avg_interval_hari = VALUES(avg_interval_hari),
        kategori = VALUES(kategori),
        stok_saat_ini = VALUES(stok_saat_ini),
        updated_at = NOW();

    -- Update MAX 2W (penjualan tertinggi 2 minggu berturut-turut)
    UPDATE tblitem_minmax m
    SET max_2w = GREATEST(
        w1 + w2, w2 + w3, w3 + w4, w4 + w5, w5 + w6,
        w6 + w7, w7 + w8, w8 + w9, w9 + w10, w10 + w11, w11 + w12
    ),
    max_stok = GREATEST(
        w1 + w2, w2 + w3, w3 + w4, w4 + w5, w5 + w6,
        w6 + w7, w7 + w8, w8 + w9, w9 + w10, w10 + w11, w11 + w12
    )
    WHERE p_kd_cabang IS NULL OR m.kd_cabang = p_kd_cabang;

END //

-- ============================================================================
-- STORED PROCEDURE: Generate Rencana Order
-- ============================================================================
DROP PROCEDURE IF EXISTS sp_generate_rencana_order//
CREATE PROCEDURE sp_generate_rencana_order(
    IN p_tanggal DATE,
    IN p_user VARCHAR(50),
    OUT p_no_rencana VARCHAR(30)
)
BEGIN
    DECLARE v_no_rencana VARCHAR(30);

    -- Generate nomor rencana
    SELECT CONCAT('RO', DATE_FORMAT(p_tanggal, '%Y%m%d'),
           LPAD(COALESCE(
               (SELECT COUNT(*) + 1 FROM tblrencana_order_header
                WHERE DATE(tanggal) = p_tanggal), 1
           ), 3, '0')) INTO v_no_rencana;

    -- Hitung MIN/MAX dulu
    CALL sp_hitung_minmax_stok(NULL);

    -- Insert header
    INSERT INTO tblrencana_order_header (
        no_rencana, tanggal, periode_awal, periode_akhir,
        status, created_by, created_at
    ) VALUES (
        v_no_rencana, p_tanggal,
        DATE_SUB(p_tanggal, INTERVAL 84 DAY),
        p_tanggal,
        'draft', p_user, NOW()
    );

    -- Insert detail per item
    INSERT INTO tblrencana_order_detail (
        no_rencana, no_item, nama_item, jenis, harga,
        order1_qty, order1_supplier, order1_nilai,
        order2_qty, order2_supplier, order2_nilai
    )
    SELECT
        v_no_rencana,
        m.no_item,
        i.namaitem,
        i.jenis,
        i.hargapokok,
        -- ORDER 1: kebutuhan sampai 75% MAX
        GREATEST(0, ROUND(SUM(m.max_stok) * 0.75 - SUM(m.stok_saat_ini), 0)) AS order1_qty,
        MAX(m.supplier1) AS order1_supplier,
        GREATEST(0, ROUND(SUM(m.max_stok) * 0.75 - SUM(m.stok_saat_ini), 0)) * i.hargapokok AS order1_nilai,
        0 AS order2_qty,
        MAX(m.supplier2) AS order2_supplier,
        0 AS order2_nilai
    FROM tblitem_minmax m
    LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
    WHERE m.kategori NOT IN ('E')
    GROUP BY m.no_item, i.namaitem, i.jenis, i.hargapokok
    HAVING GREATEST(0, ROUND(SUM(m.max_stok) * 0.75 - SUM(m.stok_saat_ini), 0)) > 0;

    -- Insert detail per cabang
    INSERT INTO tblrencana_order_detail_cabang (
        no_rencana, no_item, kd_cabang,
        stok_awal, min_stok, max_stok, kategori,
        order_qty
    )
    SELECT
        v_no_rencana,
        m.no_item,
        m.kd_cabang,
        m.stok_saat_ini,
        m.min_stok,
        m.max_stok,
        m.kategori,
        GREATEST(0, ROUND(m.max_stok * 0.75 - m.stok_saat_ini, 0)) AS order_qty
    FROM tblitem_minmax m
    WHERE m.kategori NOT IN ('E')
      AND GREATEST(0, ROUND(m.max_stok * 0.75 - m.stok_saat_ini, 0)) > 0;

    -- Update totals
    UPDATE tblrencana_order_header h
    SET
        total_item = (SELECT COUNT(DISTINCT no_item) FROM tblrencana_order_detail WHERE no_rencana = v_no_rencana),
        total_qty = (SELECT COALESCE(SUM(order1_qty + order2_qty), 0) FROM tblrencana_order_detail WHERE no_rencana = v_no_rencana),
        total_nilai = (SELECT COALESCE(SUM(order1_nilai + order2_nilai), 0) FROM tblrencana_order_detail WHERE no_rencana = v_no_rencana)
    WHERE no_rencana = v_no_rencana;

    SET p_no_rencana = v_no_rencana;

END //

DELIMITER ;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
