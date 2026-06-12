-- Backup created at 2025-12-04 15:00:55

DROP TABLE IF EXISTS `tbmaster_temuan`;
CREATE TABLE `tbmaster_temuan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_temuan` varchar(20) DEFAULT NULL,
  `nama_temuan` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL COMMENT 'Kategori temuan: Mesin, Kelistrikan, Body, dll',
  `tingkat_urgensi` enum('rendah','sedang','tinggi') DEFAULT 'sedang',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_temuan` (`kode_temuan`),
  UNIQUE KEY `uk_kode_temuan` (`kode_temuan`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_active` (`is_active`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_nama_temuan` (`nama_temuan`(50))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master data temuan hasil pengecekan';

-- Data for table `tbmaster_temuan` (12 rows)
INSERT INTO `tbmaster_temuan` VALUES ('1','TMN001','Filter Udara Kotor','Filter udara perlu dibersihkan atau diganti','Mesin','sedang','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('2','TMN002','Oli Mesin Hitam','Oli mesin sudah kotor dan perlu diganti','Mesin','tinggi','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('3','TMN003','Kampas Rem Tipis','Kampas rem sudah menipis dan perlu diganti','Rem','tinggi','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('4','TMN004','Rantai Kendor','Rantai perlu disetting atau diganti','Transmisi','sedang','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('5','TMN005','Aki Lemah','Aki sudah lemah dan perlu diganti','Kelistrikan','tinggi','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('6','TMN006','Ban Gundul','Ban sudah gundul dan perlu diganti','Ban','tinggi','1','2025-11-07 14:07:15','2025-11-30 15:30:16');
INSERT INTO `tbmaster_temuan` VALUES ('7','TMN007','Lampu Mati','Lampu tidak menyala, perlu cek bohlam','Kelistrikan','sedang','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('8','TMN008','Busi Aus','Busi sudah aus dan perlu diganti','Mesin','sedang','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('9','TMN009','Shock Bocor','Shock absorber bocor dan perlu diganti','Suspensi','tinggi','1','2025-11-07 14:07:15','2025-11-07 14:07:15');
INSERT INTO `tbmaster_temuan` VALUES ('10','TMN010','CVT Aus','CVT sudah aus dan perlu diganti','Transmisi','tinggi','1','2025-11-07 14:07:15','2025-11-30 15:30:16');
INSERT INTO `tbmaster_temuan` VALUES ('11','TMN011','gas kurang responsif','dmy','Mesin','sedang','1','2025-12-04 15:00:30','2025-12-04 15:00:30');
INSERT INTO `tbmaster_temuan` VALUES ('12','TMN012','tes','Test integrated flow','Lainnya','sedang','1','2025-12-04 15:00:37','2025-12-04 15:00:37');

DROP TABLE IF EXISTS `tbmaster_temuan_barang_mapping`;
CREATE TABLE `tbmaster_temuan_barang_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_temuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `noitem` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Link ke tblitem (field: noitem)',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT '1=barang utama/rekomendasi, 0=alternatif',
  `prioritas` int(11) DEFAULT 1 COMMENT 'Urutan prioritas tampil (1=tertinggi)',
  `qty_default` int(11) DEFAULT 1 COMMENT 'Qty default yang disarankan',
  `keterangan` varchar(255) DEFAULT NULL COMMENT 'Keterangan tambahan (misal: Original, KW, dsb)',
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mapping` (`kode_temuan`,`noitem`),
  UNIQUE KEY `uk_temuan_item` (`kode_temuan`,`noitem`),
  KEY `idx_temuan` (`kode_temuan`),
  KEY `idx_status` (`status_aktif`),
  KEY `idx_item` (`noitem`),
  KEY `idx_kode_temuan` (`kode_temuan`),
  KEY `idx_noitem` (`noitem`),
  KEY `idx_is_primary` (`is_primary`),
  KEY `idx_status_aktif` (`status_aktif`),
  KEY `idx_prioritas` (`prioritas`),
  KEY `idx_composite_temuan_item` (`kode_temuan`,`noitem`),
  CONSTRAINT `fk_mapping_temuan` FOREIGN KEY (`kode_temuan`) REFERENCES `tbmaster_temuan` (`kode_temuan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Mapping temuan ke part (tblitem) untuk auto-suggest';

-- Data for table `tbmaster_temuan_barang_mapping` (12 rows)
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('1','TMN001','FILTER-001','1','1','1','Filter Udara Original (Rekomendasi)','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('2','TMN001','FILTER-002','0','2','1','Filter Udara KW (Alternatif)','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('3','TMN002','OLI-001','1','1','1','Oli Synthetic (Rekomendasi)','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('4','TMN002','OLI-002','0','2','1','Oli Semi-Synthetic (Alternatif)','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('5','TMN003','KAMPAS-001','1','1','1','Kampas Rem Depan Original','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('6','TMN003','KAMPAS-002','0','2','1','Kampas Rem Depan KW','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('7','TMN004','AKI-001','1','1','1','Aki Kering 12V 5Ah','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('8','TMN004','AKI-002','0','2','1','Aki Basah 12V 5Ah','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('9','TMN005','BAN-001','1','1','1','Ban Tubeless 70/90-14','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('10','TMN005','BAN-002','0','2','1','Ban Dalam 70/90-14','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('11','TMN006','BUSI-001','1','1','1','Busi Iridium (Rekomendasi)','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');
INSERT INTO `tbmaster_temuan_barang_mapping` VALUES ('12','TMN006','BUSI-002','0','2','1','Busi Standar (Alternatif)','1',NULL,'2025-11-26 13:38:33',NULL,'2025-11-26 13:38:33');

DROP TABLE IF EXISTS `tbservis_temuan`;
CREATE TABLE `tbservis_temuan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `keluhan_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_keluhan_status',
  `mekanik_id` varchar(10) DEFAULT NULL COMMENT 'Mekanik yang menemukan',
  `mekanik_name` varchar(100) DEFAULT NULL,
  `kode_temuan` varchar(10) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan',
  `temuan_custom` varchar(255) DEFAULT NULL COMMENT 'Temuan manual jika tidak ada di master',
  `deskripsi_temuan` text DEFAULT NULL,
  `jenis_perbaikan` enum('setting','penggantian_part') NOT NULL DEFAULT 'setting',
  `status_temuan` enum('ditemukan','ditawarkan','disetujui','ditolak','selesai') DEFAULT 'ditemukan',
  `keterangan_tidak_selesai` text DEFAULT NULL COMMENT 'Keterangan jika temuan tidak selesai dikerjakan',
  `tingkat_urgensi` enum('rendah','sedang','tinggi') DEFAULT 'sedang',
  `estimasi_biaya` double DEFAULT 0,
  `biaya_actual` double DEFAULT 0,
  `foto_temuan` varchar(255) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_keluhan_id` (`keluhan_id`),
  KEY `idx_kode_temuan` (`kode_temuan`),
  KEY `idx_status` (`status_temuan`),
  KEY `idx_status_temuan` (`status_temuan`),
  KEY `idx_service` (`no_service`),
  KEY `idx_keluhan` (`keluhan_id`),
  KEY `idx_jenis_perbaikan` (`jenis_perbaikan`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_servis_temuan_master` FOREIGN KEY (`kode_temuan`) REFERENCES `tbmaster_temuan` (`kode_temuan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tbservis_temuan_ibfk_1` FOREIGN KEY (`keluhan_id`) REFERENCES `tbservis_keluhan_status` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Temuan hasil pengecekan per servis';

-- Data for table `tbservis_temuan` (9 rows)
INSERT INTO `tbservis_temuan` VALUES ('1','SV25000000155',NULL,NULL,NULL,'TMN007',NULL,'cuma pasang kabel yang copot','setting','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-08 12:25:18',NULL,'2025-11-08 12:25:18');
INSERT INTO `tbservis_temuan` VALUES ('2','SV25000000156','62',NULL,NULL,'TMN001',NULL,'','setting','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-09 08:57:01',NULL,'2025-11-09 08:57:01');
INSERT INTO `tbservis_temuan` VALUES ('3','SV25000000157',NULL,NULL,NULL,'TMN001',NULL,'','setting','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-09 11:13:21',NULL,'2025-11-09 11:13:21');
INSERT INTO `tbservis_temuan` VALUES ('4','SV25000000157','63',NULL,NULL,'TMN010',NULL,'','setting','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-09 11:43:21',NULL,'2025-11-09 11:43:21');
INSERT INTO `tbservis_temuan` VALUES ('5','SV202563177069','67',NULL,NULL,'TMN001',NULL,'','setting','',NULL,'sedang','0','0',NULL,NULL,'2025-11-19 10:24:54',NULL,'2025-11-19 10:26:33');
INSERT INTO `tbservis_temuan` VALUES ('6','SV25000000188',NULL,NULL,NULL,'TMN006',NULL,'','penggantian_part','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-20 16:16:50',NULL,'2025-11-20 16:16:50');
INSERT INTO `tbservis_temuan` VALUES ('7','SV25000000194','73',NULL,NULL,'TMN001',NULL,'','setting','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-22 09:50:06',NULL,'2025-11-22 09:50:06');
INSERT INTO `tbservis_temuan` VALUES ('8','SV25000000198','74',NULL,NULL,'TMN001',NULL,'','setting','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-23 09:08:37',NULL,'2025-11-23 09:08:37');
INSERT INTO `tbservis_temuan` VALUES ('13','SV25000000204','76',NULL,NULL,'TMN001',NULL,'','penggantian_part','ditemukan',NULL,'sedang','0','0',NULL,NULL,'2025-11-26 15:22:24',NULL,'2025-11-26 15:22:24');

DROP TABLE IF EXISTS `tbservis_penawaran_part`;
CREATE TABLE `tbservis_penawaran_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `temuan_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_temuan',
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari auto-suggest, 0=manual',
  `suggestion_priority` int(11) DEFAULT NULL COMMENT 'Prioritas dari suggestion (1=primary)',
  `kode_barang` varchar(20) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` double NOT NULL,
  `total_harga` double NOT NULL,
  `stok_tersedia` int(11) DEFAULT NULL COMMENT 'Stok saat penawaran dibuat',
  `estimasi_ketersediaan` varchar(50) DEFAULT NULL COMMENT 'ready_stock, indent_1_hari, indent_3_hari, dll',
  `status_penawaran` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `alasan_tolak` enum('customer_tidak_mau','stok_bengkel_kosong','stok_supplier_kosong','harga_tidak_cocok','lainnya') DEFAULT NULL,
  `keterangan_tolak` text DEFAULT NULL,
  `catatan_penawaran` text DEFAULT NULL COMMENT 'Catatan tambahan saat menawarkan',
  `discount_persen` decimal(5,2) DEFAULT 0.00 COMMENT 'Diskon dalam persen',
  `discount_nominal` decimal(15,2) DEFAULT 0.00 COMMENT 'Diskon dalam nominal',
  `harga_final` decimal(15,2) DEFAULT NULL COMMENT 'Harga setelah diskon',
  `tanggal_penawaran` datetime DEFAULT current_timestamp(),
  `tanggal_respon` datetime DEFAULT NULL,
  `user_penawaran` varchar(50) DEFAULT NULL,
  `user_respon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_kode_barang` (`kode_barang`),
  KEY `idx_status` (`status_penawaran`),
  KEY `idx_service` (`no_service`),
  KEY `idx_temuan` (`temuan_id`),
  KEY `idx_status_penawaran` (`status_penawaran`),
  KEY `idx_is_from_suggestion` (`is_from_suggestion`),
  KEY `idx_tanggal_penawaran` (`tanggal_penawaran`),
  KEY `idx_composite_service_status` (`no_service`,`status_penawaran`),
  CONSTRAINT `fk_penawaran_temuan` FOREIGN KEY (`temuan_id`) REFERENCES `tbservis_temuan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tbservis_penawaran_part_ibfk_1` FOREIGN KEY (`temuan_id`) REFERENCES `tbservis_temuan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_quantity_positive` CHECK (`quantity` > 0),
  CONSTRAINT `chk_harga_positive` CHECK (`harga_satuan` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Penawaran part ke customer';

-- Data for table `tbservis_penawaran_part` (6 rows)
INSERT INTO `tbservis_penawaran_part` VALUES ('1','SV25000000194',NULL,'0',NULL,'IP-17210-KVY-960','FILTER UDARA BEAT INDOPART','1','31000','31000',NULL,NULL,'disetujui',NULL,NULL,NULL,'0.00','0.00',NULL,'2025-11-22 10:44:36','2025-11-22 10:44:53','System','System','2025-11-22 10:44:36','2025-11-22 10:44:53');
INSERT INTO `tbservis_penawaran_part` VALUES ('2','SV25000000194',NULL,'0',NULL,'CUSTOM-00006','DMY','1','50000','50000',NULL,NULL,'pending',NULL,NULL,NULL,'0.00','0.00',NULL,'2025-11-22 14:41:35',NULL,'System',NULL,'2025-11-22 14:41:35','2025-11-22 14:41:35');
INSERT INTO `tbservis_penawaran_part` VALUES ('3','SV25000000198',NULL,'0',NULL,'IP-17210-KVY-960','FILTER UDARA BEAT INDOPART','1','31000','31000',NULL,NULL,'pending',NULL,NULL,NULL,'0.00','0.00',NULL,'2025-11-23 09:08:59',NULL,'System',NULL,'2025-11-23 09:08:59','2025-11-23 09:08:59');
INSERT INTO `tbservis_penawaran_part` VALUES ('4','SV25000000198',NULL,'0',NULL,'CUSTOM-00007','FILTER UDARA BEAT INDOPART','1','45000','45000',NULL,NULL,'pending',NULL,NULL,NULL,'0.00','0.00',NULL,'2025-11-23 09:09:38',NULL,'System',NULL,'2025-11-23 09:09:38','2025-11-23 09:09:38');
INSERT INTO `tbservis_penawaran_part` VALUES ('5','SV25000000212',NULL,'0',NULL,'CUSTOM-00008','DMY','1','5000','5000',NULL,NULL,'ditolak','customer_tidak_mau','doubel',NULL,'0.00','0.00',NULL,'2025-12-01 13:59:41','2025-12-01 16:07:49','System','System','2025-12-01 13:59:41','2025-12-01 16:07:49');
INSERT INTO `tbservis_penawaran_part` VALUES ('6','SV25000000212',NULL,'0',NULL,'CUSTOM-00009','DMY','1','5000','5000',NULL,NULL,'disetujui',NULL,NULL,NULL,'0.00','0.00',NULL,'2025-12-01 14:00:14','2025-12-01 16:06:56','System','System','2025-12-01 14:00:14','2025-12-01 16:06:56');

