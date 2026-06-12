-- ============================================================================
-- MIGRATION STEP 1: Tabel untuk Sistem Rencana Order
-- JALANKAN FILE INI TERLEBIH DAHULU
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
-- Tanpa FK dulu agar tidak error jika tbcabang belum ada
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
    min_stok INT DEFAULT 0 COMMENT 'MIN = MAX penjualan 1 minggu (W1-W12)',
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
    INDEX idx_kd_cabang (kd_cabang)
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
-- 5. TABEL RENCANA ORDER DETAIL (Per Item)
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
    INDEX idx_order2_supplier (order2_supplier_final)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Detail rencana order per item';

-- ============================================================================
-- 6. TABEL RENCANA ORDER DETAIL PER CABANG (Dinamis)
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
    INDEX idx_kd_cabang (kd_cabang)
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
    INDEX idx_item (no_item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Rencana transfer antar cabang';

-- ============================================================================
-- 8. TABEL REALISASI ORDER
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
    INDEX idx_cabang (kd_cabang)
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
-- SELESAI STEP 1
-- Lanjutkan dengan menjalankan file 02_rencana_order_views.sql
-- ============================================================================
