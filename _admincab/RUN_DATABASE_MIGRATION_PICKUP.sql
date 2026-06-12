-- =====================================================
-- QUICK RUN: Create tb_pickup_details Table
-- =====================================================
-- Instruksi: Copy semua isi file ini dan paste di phpMyAdmin tab SQL
-- Atau jalankan via MySQL CLI:
-- mysql -u root -p fitmotor_dbbengkel < RUN_DATABASE_MIGRATION_PICKUP.sql
-- =====================================================

USE `fitmotor_dbbengkel`;

-- Drop table jika sudah ada (HATI-HATI: akan menghapus data!)
-- Uncomment baris di bawah jika ingin re-create table
-- DROP TABLE IF EXISTS `tb_pickup_details`;

-- Create table tb_pickup_details
CREATE TABLE IF NOT EXISTS `tb_pickup_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_service` VARCHAR(50) NOT NULL,
    `alamat_jemput` TEXT COMMENT 'Alamat lengkap penjemputan',
    `kelurahan_jemput` VARCHAR(100) COMMENT 'Kelurahan lokasi jemput',
    `kecamatan_jemput` VARCHAR(100) COMMENT 'Kecamatan lokasi jemput',
    `patokan_jemput` VARCHAR(200) COMMENT 'Patokan alamat (landmark)',
    `tanggal_jemput` DATE COMMENT 'Tanggal jadwal penjemputan',
    `jam_jemput` TIME COMMENT 'Jam jadwal penjemputan',
    `prioritas_jemput` ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal' COMMENT 'Tingkat prioritas penjemputan',
    `status_jemput` ENUM('dijadwalkan', 'dalam_perjalanan', 'dijemput', 'dibatalkan') DEFAULT 'dijadwalkan' COMMENT 'Status penjemputan',
    `nama_kontak_jemput` VARCHAR(100) COMMENT 'Nama kontak person di lokasi jemput',
    `telp_kontak_jemput` VARCHAR(20) COMMENT 'Nomor telepon kontak',
    `hubungan_kontak` ENUM('pemilik', 'keluarga', 'teman', 'karyawan', 'lainnya') COMMENT 'Hubungan kontak dengan pemilik motor',
    `driver_jemput` VARCHAR(50) COMMENT 'Nama driver yang menjemput',
    `kendaraan_derek` VARCHAR(20) COMMENT 'Nomor polisi kendaraan derek',
    `biaya_jemput` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Biaya jemput antar',
    `catatan_jemput` TEXT COMMENT 'Catatan tambahan untuk penjemputan',
    `foto_patokan` VARCHAR(255) COMMENT 'Path foto patokan lokasi',
    `lat_jemput` VARCHAR(20) COMMENT 'Latitude lokasi jemput',
    `long_jemput` VARCHAR(20) COMMENT 'Longitude lokasi jemput',
    `jarak_jemput` DECIMAL(5,1) DEFAULT 0.0 COMMENT 'Jarak penjemputan dalam KM',
    `kondisi_motor` ENUM('jalan', 'mogok') DEFAULT 'jalan' COMMENT 'Kondisi motor saat dijemput',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu dibuat',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu diupdate',

    -- Indexes
    UNIQUE KEY `unique_service` (`no_service`),
    INDEX `idx_tanggal_jemput` (`tanggal_jemput`),
    INDEX `idx_status_jemput` (`status_jemput`),

    -- Foreign Key (disable dulu jika tblservice belum punya primary key)
    CONSTRAINT `fk_pickup_service` FOREIGN KEY (`no_service`)
        REFERENCES `tblservice`(`no_service`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Tabel detail jadwal dan status penjemputan motor untuk service';

-- Verify table created
SELECT
    CONCAT('✓ Table tb_pickup_details created successfully!') AS Status,
    COUNT(*) AS 'Row Count'
FROM tb_pickup_details;

-- Show table structure
DESCRIBE tb_pickup_details;
