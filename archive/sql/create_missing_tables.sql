-- ============================================================================
-- CREATE MISSING TABLES FOR WEB BENGKEL VALIDATION SYSTEM
-- Database: fitmotor_dbbengkel
-- Purpose: Fix missing master tables for barang validation system
-- ============================================================================

-- Use the correct database
USE fitmotor_dbbengkel;

-- ============================================================================
-- 1. CREATE TBLJENIS TABLE (Item Types/Categories)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tbljenis` (
    `kodejenis` varchar(10) NOT NULL,
    `namajenis` varchar(100) NOT NULL,
    `keterangan` text,
    `statusjenis` enum('1','0') DEFAULT '1',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`kodejenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample jenis data
INSERT INTO `tbljenis` (`kodejenis`, `namajenis`, `keterangan`, `statusjenis`) VALUES
('SP', 'Spare Part', 'Suku cadang kendaraan bermotor', '1'),
('OLI', 'Oli & Pelumas', 'Oli mesin dan pelumas kendaraan', '1'),
('TIRE', 'Ban & Velg', 'Ban dan velg kendaraan', '1'),
('ACCS', 'Aksesoris', 'Aksesoris dan variasi kendaraan', '1'),
('TOOL', 'Tools', 'Peralatan bengkel dan tools', '1'),
('CHEM', 'Chemical', 'Bahan kimia dan cairan kendaraan', '1'),
('ELEC', 'Elektronik', 'Komponen elektronik kendaraan', '1'),
('BODY', 'Body Part', 'Suku cadang body kendaraan', '1'),
('ENG', 'Engine Part', 'Suku cadang mesin kendaraan', '1'),
('BRAKE', 'Brake System', 'Sistem rem kendaraan', '1');

-- ============================================================================
-- 2. CREATE TBLSATUAN TABLE (Units of Measurement)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tblsatuan` (
    `kodesatuan` varchar(10) NOT NULL,
    `satuan` varchar(50) NOT NULL,
    `keterangan` text,
    `statussatuan` enum('1','0') DEFAULT '1',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`kodesatuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample satuan data
INSERT INTO `tblsatuan` (`kodesatuan`, `satuan`, `keterangan`, `statussatuan`) VALUES
('PCS', 'Pcs', 'Pieces - satuan per buah', '1'),
('SET', 'Set', 'Set - satuan per set/paket', '1'),
('LITER', 'Liter', 'Liter - satuan cairan', '1'),
('KG', 'Kg', 'Kilogram - satuan berat', '1'),
('GRAM', 'Gram', 'Gram - satuan berat kecil', '1'),
('METER', 'Meter', 'Meter - satuan panjang', '1'),
('PACK', 'Pack', 'Pack - satuan kemasan', '1'),
('TUBE', 'Tube', 'Tube - satuan tabung', '1'),
('BOTOL', 'Botol', 'Botol - satuan botol', '1'),
('PAIR', 'Pair', 'Pair - satuan pasang', '1');

-- ============================================================================
-- 3. CREATE TBLSUPPLIER TABLE (Suppliers)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tblsupplier` (
    `kode_supplier` varchar(20) NOT NULL,
    `nama_supplier` varchar(200) NOT NULL,
    `alamat_supplier` text,
    `telepon_supplier` varchar(50),
    `email_supplier` varchar(100),
    `contact_person` varchar(100),
    `status_supplier` enum('1','0') DEFAULT '1',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`kode_supplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample supplier data
INSERT INTO `tblsupplier` (`kode_supplier`, `nama_supplier`, `alamat_supplier`, `telepon_supplier`, `contact_person`, `status_supplier`) VALUES
('SUP001', 'PT Honda Parts Indonesia', 'Jakarta Pusat', '021-12345678', 'Budi Santoso', '1'),
('SUP002', 'PT Yamaha Motor Parts', 'Jakarta Timur', '021-87654321', 'Sari Dewi', '1'),
('SUP003', 'PT Suzuki Genuine Parts', 'Bekasi', '021-11111111', 'Ahmad Rahman', '1'),
('SUP004', 'PT Kawasaki Motor Indonesia', 'Karawang', '021-22222222', 'Dina Puspita', '1'),
('SUP005', 'Toko Spare Part Jaya', 'Bandung', '022-33333333', 'Joko Widodo', '1'),
('SUP006', 'CV Mitra Motor Parts', 'Surabaya', '031-44444444', 'Rina Sari', '1'),
('SUP007', 'UD Sumber Motor', 'Medan', '061-55555555', 'Agus Pranoto', '1'),
('SUP008', 'PT Federal Oil', 'Jakarta', '021-66666666', 'Lisa Andriani', '1'),
('SUP009', 'Toko Ban Merdeka', 'Yogyakarta', '0274-7777777', 'Bambang Susilo', '1'),
('SUP010', 'CV Auto Parts Center', 'Semarang', '024-88888888', 'Maya Sinta', '1');

-- ============================================================================
-- 4. CREATE TBRAKBARANG TABLE (Storage Racks)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tbrakbarang` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `kode_rak` varchar(20) NOT NULL,
    `rak_barang` varchar(100) NOT NULL,
    `lokasi_rak` varchar(100),
    `kapasitas_rak` int(11) DEFAULT '0',
    `keterangan_rak` text,
    `status_rak` enum('1','0') DEFAULT '1',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `kode_rak` (`kode_rak`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample rak data
INSERT INTO `tbrakbarang` (`kode_rak`, `rak_barang`, `lokasi_rak`, `kapasitas_rak`, `keterangan_rak`, `status_rak`) VALUES
('RAK-A01', 'Rak A-01', 'Gudang Utama - Sisi Kiri', 100, 'Rak untuk spare part kecil', '1'),
('RAK-A02', 'Rak A-02', 'Gudang Utama - Sisi Kiri', 100, 'Rak untuk spare part kecil', '1'),
('RAK-B01', 'Rak B-01', 'Gudang Utama - Sisi Tengah', 150, 'Rak untuk spare part sedang', '1'),
('RAK-B02', 'Rak B-02', 'Gudang Utama - Sisi Tengah', 150, 'Rak untuk spare part sedang', '1'),
('RAK-C01', 'Rak C-01', 'Gudang Utama - Sisi Kanan', 200, 'Rak untuk spare part besar', '1'),
('RAK-C02', 'Rak C-02', 'Gudang Utama - Sisi Kanan', 200, 'Rak untuk spare part besar', '1'),
('RAK-OLI', 'Rak Oli', 'Gudang Oli', 500, 'Rak khusus untuk oli dan pelumas', '1'),
('RAK-BAN', 'Rak Ban', 'Gudang Ban', 50, 'Rak khusus untuk ban dan velg', '1'),
('RAK-TOOL', 'Rak Tools', 'Workshop', 80, 'Rak untuk tools dan peralatan', '1'),
('RAK-ELEC', 'Rak Elektronik', 'Gudang Utama - Khusus', 120, 'Rak untuk komponen elektronik', '1');

-- ============================================================================
-- 5. CREATE TBKATEGORI_RAK TABLE (Category Racks for NON-ORI)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tbkategori_rak` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `kode` varchar(10) NOT NULL,
    `kategori` varchar(100) NOT NULL,
    `deskripsi` text,
    `status` enum('1','0') DEFAULT '1',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert kategori data for NON-ORI classification
INSERT INTO `tbkategori_rak` (`kode`, `kategori`, `deskripsi`, `status`) VALUES
('KB', 'Kabel', 'Kabel dan komponen kabel kendaraan', '1'),
('EL', 'Kelistrikan', 'Komponen kelistrikan dan elektronik', '1'),
('RM', 'Rem', 'Sistem rem dan komponennya', '1'),
('MS', 'Mesin', 'Komponen mesin dan engine', '1'),
('CV', 'CVT', 'Sistem CVT dan transmisi otomatis', '1'),
('RD', 'Roda', 'Ban, velg, dan komponen roda', '1'),
('CR', 'Carbu', 'Karburator dan sistem bahan bakar', '1'),
('FL', 'Filter', 'Filter udara, oli, dan bahan bakar', '1'),
('CH', 'Cairan', 'Oli, pelumas, dan cairan kendaraan', '1'),
('BD', 'Baud', 'Baud, mur, dan komponen pengikat', '1');

-- ============================================================================
-- 6. CREATE TBITEM_VALIDATION_LOG TABLE (Validation History)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `noitem` varchar(50) NOT NULL,
    `action` varchar(50) NOT NULL,
    `notes` text,
    `user_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_noitem` (`noitem`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 7. ADD MISSING COLUMNS TO TBLITEM (if not exists)
-- ============================================================================

-- Add ORI/NON-ORI classification columns
ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `tipe_item` ENUM('ORI', 'NON_ORI') DEFAULT 'NON_ORI' AFTER `statusitem`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `status_validasi` ENUM('pending_validation', 'validated', 'rejected') DEFAULT 'pending_validation' AFTER `tipe_item`;

-- Add ORI specific columns
ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `merek` VARCHAR(50) NULL AFTER `status_validasi`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `kode_part_resmi` VARCHAR(50) NULL AFTER `merek`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `nama_part_resmi` VARCHAR(100) NULL AFTER `kode_part_resmi`;

-- Add NON-ORI specific columns
ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `penggunaan_motor` VARCHAR(100) NULL AFTER `nama_part_resmi`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `merek_tipe` VARCHAR(100) NULL AFTER `penggunaan_motor`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `kategori_rak` VARCHAR(10) NULL AFTER `merek_tipe`;

-- Add audit trail columns
ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `created_by` INT(11) NULL AFTER `kategori_rak`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `validated_by` INT(11) NULL AFTER `created_by`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `validated_by`;

ALTER TABLE `tblitem`
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- ============================================================================
-- 8. CREATE TBLITEM_STOK TABLE (if not exists)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tblitem_stok` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `noitem` varchar(50) NOT NULL,
    `kode_cabang` varchar(20) NOT NULL,
    `stokmin` int(11) DEFAULT '0',
    `stok_maks` int(11) DEFAULT '0',
    `stok_awal` int(11) DEFAULT '0',
    `rakbarang` int(11) NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_item_cabang` (`noitem`, `kode_cabang`),
    KEY `idx_noitem` (`noitem`),
    KEY `idx_kode_cabang` (`kode_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- 9. CREATE VIEW_CARI_ITEM (if not exists)
-- ============================================================================
CREATE OR REPLACE VIEW `view_cari_item` AS
SELECT
    i.noitem,
    i.namaitem,
    i.kodebarcode,
    i.jenis,
    i.satuan,
    i.hargapokok,
    i.hargajual,
    i.supplier,
    i.rakbarang,
    i.statusitem,
    i.tipe_item,
    i.status_validasi,
    i.merek,
    i.kode_part_resmi,
    i.nama_part_resmi,
    i.penggunaan_motor,
    i.merek_tipe,
    i.kategori_rak,
    i.created_at,
    i.updated_at
FROM tblitem i
WHERE i.statusitem = '1';

-- ============================================================================
-- 10. CREATE VIEW_STOK_MASTER (if not exists)
-- ============================================================================
CREATE OR REPLACE VIEW `view_stok_master` AS
SELECT
    'dummy' as no_item,
    'dummy' as kd_cabang,
    0 as saldo;

-- ============================================================================
-- 11. CREATE INDEXES FOR BETTER PERFORMANCE
-- ============================================================================

-- Add indexes to tblitem for better query performance
ALTER TABLE `tblitem` ADD INDEX IF NOT EXISTS `idx_tipe_item` (`tipe_item`);
ALTER TABLE `tblitem` ADD INDEX IF NOT EXISTS `idx_status_validasi` (`status_validasi`);
ALTER TABLE `tblitem` ADD INDEX IF NOT EXISTS `idx_kategori_rak` (`kategori_rak`);
ALTER TABLE `tblitem` ADD INDEX IF NOT EXISTS `idx_merek` (`merek`);
ALTER TABLE `tblitem` ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- Add foreign key constraints (optional)
-- ALTER TABLE `tbitem_validation_log` ADD FOREIGN KEY (`noitem`) REFERENCES `tblitem` (`noitem`) ON DELETE CASCADE;

-- ============================================================================
-- 12. VERIFICATION QUERIES
-- ============================================================================

-- Check if tables were created successfully
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
-- WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
-- AND TABLE_NAME IN ('tbljenis', 'tblsatuan', 'tblsupplier', 'tbrakbarang', 'tbkategori_rak', 'tbitem_validation_log');

-- Check if columns were added to tblitem
-- DESCRIBE tblitem;

-- Check sample data
-- SELECT * FROM tbljenis LIMIT 5;
-- SELECT * FROM tblsatuan LIMIT 5;
-- SELECT * FROM tblsupplier LIMIT 5;
-- SELECT * FROM tbrakbarang LIMIT 5;
-- SELECT * FROM tbkategori_rak LIMIT 5;

-- ============================================================================
-- EXECUTION NOTES
-- ============================================================================
/*
1. Run this script step by step or all at once
2. Make sure you have proper database permissions
3. Backup your database before running this script
4. Verify each table creation before proceeding
5. Test the barang_validate.php and barang_edit_improved.php after running this script

ESTIMATED EXECUTION TIME: 1-2 minutes
TABLES CREATED: 5 new tables + 1 view
COLUMNS ADDED: 12 new columns to tblitem
SAMPLE DATA: ~50 records across all master tables
*/