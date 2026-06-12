-- ============================================================================
-- MIGRATION: History Service Pelanggan & Auto Diskon Member
-- ============================================================================
-- Tanggal: 2025-12-26
-- Deskripsi: Menambahkan tabel untuk menyimpan history lengkap service pelanggan
--            termasuk keluhan, temuan, pengerjaan, mekanik, dan diskon member
-- ============================================================================

-- ============================================================================
-- 1. TABEL UTAMA: tb_history_service_pelanggan
-- Menyimpan history lengkap setiap service yang sudah dibayar
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tb_history_service_pelanggan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_pelanggan` VARCHAR(20) NOT NULL COMMENT 'Nomor pelanggan',
    `no_polisi` VARCHAR(20) NOT NULL COMMENT 'Nomor polisi kendaraan',
    `no_service` VARCHAR(30) NOT NULL COMMENT 'Nomor service unik',
    `tanggal_service` DATE NOT NULL COMMENT 'Tanggal service',
    `jam_service` VARCHAR(10) DEFAULT NULL COMMENT 'Jam service',
    `tipe_service` ENUM('reguler','jemput','garansi') DEFAULT 'reguler' COMMENT 'Tipe service',

    -- Data Pembayaran
    `total_jasa` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total biaya jasa',
    `total_barang` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total biaya barang/sparepart',
    `subtotal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Subtotal sebelum diskon',
    `diskon_member_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase diskon member',
    `diskon_member_nominal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal diskon member',
    `diskon_tambahan_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase diskon tambahan',
    `diskon_tambahan_nominal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal diskon tambahan',
    `total_diskon` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total semua diskon',
    `ppn_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase PPN',
    `ppn_nominal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal PPN',
    `total_bayar` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total akhir yang dibayar',
    `jumlah_bayar` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Jumlah uang yang diterima',
    `kembalian` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Kembalian',
    `metode_pembayaran` VARCHAR(50) DEFAULT 'Tunai' COMMENT 'Metode pembayaran',

    -- Data Kendaraan
    `km_service` INT DEFAULT 0 COMMENT 'KM saat service',
    `tipe_motor` VARCHAR(100) DEFAULT NULL COMMENT 'Tipe motor',
    `merek_motor` VARCHAR(50) DEFAULT NULL COMMENT 'Merek motor',
    `tahun_motor` VARCHAR(10) DEFAULT NULL COMMENT 'Tahun motor',

    -- Keluhan (JSON array)
    `keluhan_list` TEXT DEFAULT NULL COMMENT 'Daftar keluhan dalam format JSON',
    `jumlah_keluhan` INT DEFAULT 0 COMMENT 'Jumlah keluhan',

    -- Temuan (JSON array)
    `temuan_list` TEXT DEFAULT NULL COMMENT 'Daftar temuan dalam format JSON',
    `jumlah_temuan` INT DEFAULT 0 COMMENT 'Jumlah temuan',
    `temuan_disetujui` INT DEFAULT 0 COMMENT 'Jumlah temuan yang disetujui',
    `temuan_ditolak` INT DEFAULT 0 COMMENT 'Jumlah temuan yang ditolak',

    -- Workorder/Pengerjaan (JSON array)
    `workorder_list` TEXT DEFAULT NULL COMMENT 'Daftar workorder dalam format JSON',
    `jumlah_workorder` INT DEFAULT 0 COMMENT 'Jumlah workorder',

    -- Barang/Sparepart (JSON array)
    `barang_list` TEXT DEFAULT NULL COMMENT 'Daftar barang dalam format JSON',
    `jumlah_item_barang` INT DEFAULT 0 COMMENT 'Jumlah item barang',

    -- Jasa (JSON array)
    `jasa_list` TEXT DEFAULT NULL COMMENT 'Daftar jasa dalam format JSON',
    `jumlah_item_jasa` INT DEFAULT 0 COMMENT 'Jumlah item jasa',

    -- Data Kepala Mekanik
    `kepala_mekanik1` VARCHAR(100) DEFAULT NULL COMMENT 'Kepala Mekanik 1',
    `kepala_mekanik2` VARCHAR(100) DEFAULT NULL COMMENT 'Kepala Mekanik 2',
    `persen_kepala1` INT DEFAULT 0 COMMENT 'Persentase kerja Kepala Mekanik 1',
    `persen_kepala2` INT DEFAULT 0 COMMENT 'Persentase kerja Kepala Mekanik 2',

    -- Data Admin
    `admin1` VARCHAR(100) DEFAULT NULL COMMENT 'Admin 1',
    `admin2` VARCHAR(100) DEFAULT NULL COMMENT 'Admin 2',
    `persen_admin1` INT DEFAULT 0 COMMENT 'Persentase kerja Admin 1',
    `persen_admin2` INT DEFAULT 0 COMMENT 'Persentase kerja Admin 2',

    -- Data Mekanik
    `mekanik1` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 1',
    `mekanik2` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 2',
    `mekanik3` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 3',
    `mekanik4` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 4',
    `persen_mekanik1` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 1',
    `persen_mekanik2` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 2',
    `persen_mekanik3` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 3',
    `persen_mekanik4` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 4',

    -- Status Member
    `status_member_sebelum` VARCHAR(50) DEFAULT 'Bronze' COMMENT 'Status member sebelum transaksi',
    `status_member_sesudah` VARCHAR(50) DEFAULT 'Bronze' COMMENT 'Status member setelah transaksi',
    `naik_tier` TINYINT(1) DEFAULT 0 COMMENT '1 jika naik tier setelah transaksi',
    `tier_baru` VARCHAR(50) DEFAULT NULL COMMENT 'Tier baru jika naik tier',

    -- Keterangan & Cabang
    `keterangan` TEXT DEFAULT NULL COMMENT 'Keterangan tambahan',
    `kode_cabang` VARCHAR(20) DEFAULT NULL COMMENT 'Kode cabang',
    `nama_cabang` VARCHAR(100) DEFAULT NULL COMMENT 'Nama cabang',

    -- User yang memproses
    `user_bayar` VARCHAR(50) DEFAULT NULL COMMENT 'User yang memproses pembayaran',
    `user_bayar_nama` VARCHAR(100) DEFAULT NULL COMMENT 'Nama user yang memproses',

    -- Timestamp
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu record dibuat',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu record diupdate',

    -- Unique constraint
    UNIQUE KEY `uk_no_service` (`no_service`),

    -- Index untuk pencarian cepat
    INDEX `idx_pelanggan` (`no_pelanggan`),
    INDEX `idx_polisi` (`no_polisi`),
    INDEX `idx_tanggal` (`tanggal_service`),
    INDEX `idx_cabang` (`kode_cabang`),
    INDEX `idx_tipe` (`tipe_service`),
    INDEX `idx_member` (`status_member_sesudah`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='History lengkap service pelanggan termasuk keluhan, temuan, pengerjaan, dan mekanik';


-- ============================================================================
-- 2. TABEL: tb_history_mekanik_servis
-- Menyimpan detail history mekanik per service
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tb_history_mekanik_servis` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_service` VARCHAR(30) NOT NULL COMMENT 'Nomor service',
    `tanggal_service` DATE NOT NULL COMMENT 'Tanggal service',
    `tipe_role` ENUM('kepala_mekanik','mekanik','admin') NOT NULL COMMENT 'Tipe role karyawan',
    `urutan` INT DEFAULT 1 COMMENT 'Urutan (1,2,3,4)',
    `kode_karyawan` VARCHAR(50) DEFAULT NULL COMMENT 'Kode karyawan',
    `nama_karyawan` VARCHAR(100) NOT NULL COMMENT 'Nama karyawan',
    `persen_kerja` INT DEFAULT 0 COMMENT 'Persentase kerja',
    `total_jasa_service` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total jasa service ini',
    `pendapatan_jasa` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Pendapatan dari jasa (persen x total)',
    `kode_cabang` VARCHAR(20) DEFAULT NULL COMMENT 'Kode cabang',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_no_service` (`no_service`),
    INDEX `idx_karyawan` (`nama_karyawan`),
    INDEX `idx_tanggal` (`tanggal_service`),
    INDEX `idx_role` (`tipe_role`),
    INDEX `idx_cabang` (`kode_cabang`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='History detail mekanik per service';


-- ============================================================================
-- 3. TABEL: tb_log_naik_tier_member
-- Menyimpan log ketika pelanggan naik tier member
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tb_log_naik_tier_member` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_pelanggan` VARCHAR(20) NOT NULL COMMENT 'Nomor pelanggan',
    `nama_pelanggan` VARCHAR(100) DEFAULT NULL COMMENT 'Nama pelanggan',
    `no_service` VARCHAR(30) NOT NULL COMMENT 'Nomor service yang menyebabkan naik tier',
    `tier_lama` VARCHAR(50) NOT NULL COMMENT 'Tier sebelumnya',
    `tier_baru` VARCHAR(50) NOT NULL COMMENT 'Tier baru',
    `total_nominal_saat_naik` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total nominal saat naik tier',
    `total_kunjungan_saat_naik` INT DEFAULT 0 COMMENT 'Total kunjungan saat naik tier',
    `diskon_lama` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Diskon tier lama',
    `diskon_baru` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Diskon tier baru',
    `kode_cabang` VARCHAR(20) DEFAULT NULL COMMENT 'Kode cabang',
    `notifikasi_terkirim` TINYINT(1) DEFAULT 0 COMMENT '1 jika notifikasi WA sudah terkirim',
    `tanggal_notifikasi` DATETIME DEFAULT NULL COMMENT 'Tanggal notifikasi dikirim',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_pelanggan` (`no_pelanggan`),
    INDEX `idx_service` (`no_service`),
    INDEX `idx_tier_baru` (`tier_baru`),
    INDEX `idx_tanggal` (`created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log kenaikan tier member pelanggan';


-- ============================================================================
-- 4. UPDATE TABEL: statistik_pelanggan
-- Tambah kolom untuk tracking diskon yang sudah diberikan
-- ============================================================================

-- Cek dan tambah kolom jika belum ada
SET @dbname = DATABASE();
SET @tablename = 'statistik_pelanggan';

-- Tambah kolom total_diskon_diberikan
SET @columnname = 'total_diskon_diberikan';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` DECIMAL(15,2) DEFAULT 0.00 COMMENT "Total diskon yang sudah diberikan" AFTER `total_motor`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tambah kolom jumlah_service_dengan_diskon
SET @columnname = 'jumlah_service_dengan_diskon';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` INT DEFAULT 0 COMMENT "Jumlah service yang dapat diskon" AFTER `total_diskon_diberikan`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tambah kolom tanggal_naik_tier_terakhir
SET @columnname = 'tanggal_naik_tier_terakhir';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` DATE DEFAULT NULL COMMENT "Tanggal terakhir naik tier" AFTER `jumlah_service_dengan_diskon`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tambah kolom tier_history (JSON untuk menyimpan history perubahan tier)
SET @columnname = 'tier_history';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TEXT DEFAULT NULL COMMENT "History perubahan tier dalam JSON" AFTER `tanggal_naik_tier_terakhir`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;


-- ============================================================================
-- 5. UPDATE TABEL: master_kategori_member
-- Pastikan kolom diskon_persen ada
-- ============================================================================

SET @tablename = 'master_kategori_member';

-- Tambah kolom diskon_persen jika belum ada
SET @columnname = 'diskon_persen';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` DECIMAL(5,2) DEFAULT 0.00 COMMENT "Persentase diskon untuk tier ini" AFTER `max_value`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update diskon_persen berdasarkan nama_kategori (jika kosong)
UPDATE `master_kategori_member`
SET `diskon_persen` = CASE
    WHEN `nama_kategori` = 'Bronze' THEN 0
    WHEN `nama_kategori` = 'Silver' THEN 10
    WHEN `nama_kategori` = 'Gold' THEN 15
    WHEN `nama_kategori` = 'Platinum' THEN 20
    ELSE 0
END
WHERE `diskon_persen` = 0 OR `diskon_persen` IS NULL;


-- ============================================================================
-- 6. VIEW: v_history_service_pelanggan_summary
-- View untuk mempermudah akses summary history pelanggan
-- ============================================================================

CREATE OR REPLACE VIEW `v_history_service_pelanggan_summary` AS
SELECT
    h.no_pelanggan,
    p.namapelanggan,
    p.telephone,
    COUNT(h.id) AS total_service_history,
    SUM(h.total_bayar) AS total_nominal_history,
    AVG(h.total_bayar) AS rata_rata_transaksi,
    MAX(h.tanggal_service) AS terakhir_service,
    MIN(h.tanggal_service) AS pertama_service,
    SUM(h.jumlah_keluhan) AS total_keluhan,
    SUM(h.jumlah_temuan) AS total_temuan,
    SUM(h.temuan_disetujui) AS total_temuan_disetujui,
    SUM(h.diskon_member_nominal) AS total_diskon_diterima,
    MAX(h.status_member_sesudah) AS status_member_terakhir,
    SUM(h.naik_tier) AS jumlah_naik_tier
FROM tb_history_service_pelanggan h
LEFT JOIN tblpelanggan p ON h.no_pelanggan = p.nopelanggan
GROUP BY h.no_pelanggan;


-- ============================================================================
-- 7. VIEW: v_history_mekanik_summary
-- View untuk summary performa mekanik
-- ============================================================================

CREATE OR REPLACE VIEW `v_history_mekanik_summary` AS
SELECT
    hm.nama_karyawan,
    hm.tipe_role,
    hm.kode_cabang,
    COUNT(DISTINCT hm.no_service) AS total_service,
    SUM(hm.pendapatan_jasa) AS total_pendapatan,
    AVG(hm.persen_kerja) AS rata_rata_persen_kerja,
    MAX(hm.tanggal_service) AS terakhir_kerja,
    MIN(hm.tanggal_service) AS pertama_kerja
FROM tb_history_mekanik_servis hm
GROUP BY hm.nama_karyawan, hm.tipe_role, hm.kode_cabang;


-- ============================================================================
-- 8. VIEW: v_riwayat_kendaraan_lengkap
-- View untuk riwayat lengkap per kendaraan
-- ============================================================================

CREATE OR REPLACE VIEW `v_riwayat_kendaraan_lengkap` AS
SELECT
    h.no_polisi,
    h.no_pelanggan,
    k.jenis AS jenis_motor,
    k.tipe AS tipe_motor_detail,
    pm.merek AS merek_motor,
    COUNT(h.id) AS total_service,
    SUM(h.total_bayar) AS total_biaya,
    MAX(h.km_service) AS km_terakhir,
    MAX(h.tanggal_service) AS service_terakhir,
    MIN(h.tanggal_service) AS service_pertama,
    GROUP_CONCAT(DISTINCT h.keluhan_list SEPARATOR '||') AS semua_keluhan,
    GROUP_CONCAT(DISTINCT h.temuan_list SEPARATOR '||') AS semua_temuan
FROM tb_history_service_pelanggan h
LEFT JOIN tblkendaraan k ON h.no_polisi = k.nopolisi
LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
GROUP BY h.no_polisi;


-- ============================================================================
-- SELESAI
-- ============================================================================

SELECT 'Migration completed successfully!' AS status;
