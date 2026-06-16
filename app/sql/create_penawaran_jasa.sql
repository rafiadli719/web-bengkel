-- =====================================================
-- SQL Script: Tabel Penawaran Jasa
-- Mirip dengan tbservis_penawaran_part tapi untuk jasa
-- =====================================================

-- 1. Tabel Penawaran Jasa (untuk approval flow)
CREATE TABLE IF NOT EXISTS `tbservis_penawaran_jasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `temuan_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_temuan.id',
  `kode_jasa` varchar(50) NOT NULL COMMENT 'Kode jasa dari tblitem (jenis=SERVIS)',
  `nama_jasa` varchar(255) DEFAULT NULL,
  `harga` double DEFAULT 0,
  `waktu_estimasi` int(11) DEFAULT 0 COMMENT 'Estimasi waktu dalam menit',
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari mapping, 0=manual',
  `mapping_id` int(11) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan_jasa_mapping jika dari saran',
  `status_penawaran` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `alasan_tolak` varchar(100) DEFAULT NULL,
  `keterangan_tolak` text DEFAULT NULL,
  `user_penawaran` varchar(100) DEFAULT NULL COMMENT 'User yang menambahkan penawaran',
  `user_respon` varchar(100) DEFAULT NULL COMMENT 'User yang approve/reject',
  `tanggal_respon` datetime DEFAULT NULL,
  `catatan_penawaran` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_status` (`status_penawaran`),
  KEY `idx_kode_jasa` (`kode_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Penawaran jasa yang menunggu approval';

-- 2. Index tambahan untuk performa
ALTER TABLE `tbservis_penawaran_jasa`
ADD INDEX `idx_service_status` (`no_service`, `status_penawaran`);
