CREATE TABLE IF NOT EXISTS `tbitem_jenis_motor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `noitem` varchar(20) NOT NULL,
  `kd_jenis_motor` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_jenis` (`noitem`,`kd_jenis_motor`),
  KEY `idx_itemjm_noitem` (`noitem`),
  KEY `idx_itemjm_kd_jenis` (`kd_jenis_motor`),
  CONSTRAINT `fk_itemjm_jenis` FOREIGN KEY (`kd_jenis_motor`) REFERENCES `tbjenis_motor` (`kd`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `tbworkorder_jenis_motor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_wo` varchar(10) NOT NULL,
  `kd_jenis_motor` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wo_jenis` (`kode_wo`,`kd_jenis_motor`),
  KEY `idx_wojm_kode_wo` (`kode_wo`),
  KEY `idx_wojm_kd_jenis` (`kd_jenis_motor`),
  CONSTRAINT `fk_wojm_jenis` FOREIGN KEY (`kd_jenis_motor`) REFERENCES `tbjenis_motor` (`kd`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `tbtipe_jenis_motor_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_tipe` int(11) NOT NULL,
  `kd_jenis_motor` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipe_jenis` (`kode_tipe`,`kd_jenis_motor`),
  KEY `idx_tjm_kode_tipe` (`kode_tipe`),
  KEY `idx_tjm_kd_jenis` (`kd_jenis_motor`),
  CONSTRAINT `fk_tjm_tipe` FOREIGN KEY (`kode_tipe`) REFERENCES `tbtipe_motor` (`kode_tipe`),
  CONSTRAINT `fk_tjm_jenis` FOREIGN KEY (`kd_jenis_motor`) REFERENCES `tbjenis_motor` (`kd`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `tblitem`
  ADD KEY `idx_tblitem_noitem` (`noitem`),
  ADD KEY `idx_tblitem_namaitem` (`namaitem`),
  ADD KEY `idx_tblitem_kodebarcode` (`kodebarcode`),
  ADD KEY `idx_tblitem_rakbarang` (`rakbarang`),
  ADD KEY `idx_tblitem_jenis` (`jenis`);

ALTER TABLE `tbworkorderheader`
  ADD KEY `idx_tbworkorderheader_kode_wo` (`kode_wo`);

ALTER TABLE `tbworkorderdetail`
  ADD KEY `idx_tbworkorderdetail_kode_wo` (`kode_wo`),
  ADD KEY `idx_tbworkorderdetail_kode_barang` (`kode_barang`);
