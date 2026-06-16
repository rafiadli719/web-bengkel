-- =====================================================
-- SQL Script: Master Mapping Temuan ke Jasa
-- Tabel baru untuk mapping temuan dengan item jasa
-- =====================================================

-- 1. Tabel Master Mapping Temuan ke Jasa
CREATE TABLE IF NOT EXISTS `tbmaster_temuan_jasa_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_temuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link ke tbmaster_temuan.kode_temuan',
  `noitem` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Link ke tblitem (field: noitem) dengan jenis=SERVIS',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT '1=jasa utama/rekomendasi, 0=alternatif',
  `prioritas` int(11) DEFAULT 1 COMMENT 'Urutan prioritas tampil (1=tertinggi)',
  `waktu_estimasi` int(11) DEFAULT 0 COMMENT 'Estimasi waktu pengerjaan dalam menit',
  `keterangan` varchar(255) DEFAULT NULL COMMENT 'Keterangan tambahan',
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_temuan_jasa` (`kode_temuan`, `noitem`),
  KEY `idx_kode_temuan` (`kode_temuan`),
  KEY `idx_noitem` (`noitem`),
  KEY `idx_status_aktif` (`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Mapping temuan ke jasa/servis untuk auto-suggest';

-- 2. Tabel untuk menyimpan part yang ditambahkan bersama temuan (link temuan-part)
CREATE TABLE IF NOT EXISTS `tbservis_temuan_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `temuan_id` int(11) NOT NULL COMMENT 'Link ke tbservis_temuan.id',
  `no_service` varchar(50) NOT NULL,
  `kode_barang` varchar(20) NOT NULL COMMENT 'Kode part dari tblitem',
  `nama_barang` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `harga_satuan` double DEFAULT 0,
  `total_harga` double DEFAULT 0,
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari saran mapping, 0=manual',
  `mapping_id` int(11) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan_barang_mapping jika dari saran',
  `status` enum('pending','approved','rejected','added_to_service') DEFAULT 'pending',
  `added_to_service_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Part yang ditambahkan bersama temuan';

-- 3. Tabel untuk menyimpan jasa yang ditambahkan bersama temuan (link temuan-jasa)
CREATE TABLE IF NOT EXISTS `tbservis_temuan_jasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `temuan_id` int(11) NOT NULL COMMENT 'Link ke tbservis_temuan.id',
  `no_service` varchar(50) NOT NULL,
  `kode_jasa` varchar(20) NOT NULL COMMENT 'Kode jasa dari tblitem (jenis=SERVIS)',
  `nama_jasa` varchar(100) DEFAULT NULL,
  `harga` double DEFAULT 0,
  `waktu_estimasi` int(11) DEFAULT 0 COMMENT 'Estimasi waktu dalam menit',
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari saran mapping, 0=manual',
  `mapping_id` int(11) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan_jasa_mapping jika dari saran',
  `status` enum('pending','approved','rejected','added_to_service') DEFAULT 'pending',
  `added_to_service_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Jasa yang ditambahkan bersama temuan';

-- 4. View untuk mapping temuan-jasa lengkap
CREATE OR REPLACE VIEW `view_mapping_temuan_jasa` AS
SELECT
    m.id,
    m.kode_temuan,
    mt.nama_temuan,
    mt.kategori AS kategori_temuan,
    m.noitem,
    i.namaitem,
    i.hargajual,
    i.satuan,
    m.is_primary,
    m.prioritas,
    m.waktu_estimasi,
    m.keterangan,
    m.status_aktif
FROM tbmaster_temuan_jasa_mapping m
LEFT JOIN tbmaster_temuan mt ON m.kode_temuan = mt.kode_temuan
LEFT JOIN tblitem i ON m.noitem = i.noitem
WHERE i.jenis = 'SERVIS' OR i.jenis IS NULL
ORDER BY m.kode_temuan, m.is_primary DESC, m.prioritas ASC;

-- 5. Insert sample data untuk testing (opsional)
-- Uncomment jika ingin menambahkan data sample
/*
INSERT INTO `tbmaster_temuan_jasa_mapping`
(`kode_temuan`, `noitem`, `is_primary`, `prioritas`, `waktu_estimasi`, `keterangan`, `status_aktif`, `created_by`)
VALUES
('TMN001', 'SRV-GANTI-OLI', 1, 1, 30, 'Jasa ganti oli standar', 1, 'System'),
('TMN001', 'SRV-TUNE-UP', 0, 2, 60, 'Jasa tune up mesin', 1, 'System'),
('TMN002', 'SRV-GANTI-KAMPAS', 1, 1, 45, 'Jasa ganti kampas rem', 1, 'System');
*/
