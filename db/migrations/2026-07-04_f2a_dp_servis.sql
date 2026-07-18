-- F2-A: Mekanisme DP / Down Payment (planning meeting 28 Jun 2026, Q9)
-- Jawaban klarifikasi #3 (2026-07-04, Mba Dian): DP masuk dan DP offset tampil di laporan
-- sebagai 2 baris terpisah (bukan net = 0). Penanda "servis mesin besar" pada tblservice
-- (kolom boleh_dp) yang membuka opsi Catat DP. Servis dengan DP tetap berjalan/diproses
-- sampai pelunasan sisa, tidak langsung dianggap lunas saat DP masuk.

ALTER TABLE `tblservice`
  ADD COLUMN `boleh_dp` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ditandai servis mesin besar / part inden, membuka opsi Catat DP' AFTER `status_servis`;

CREATE TABLE IF NOT EXISTS `tb_dp_servis` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `no_service` VARCHAR(30) NOT NULL,
  `no_dp` VARCHAR(30) NOT NULL UNIQUE,
  `tanggal_dp` DATE NOT NULL,
  `jumlah_dp` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status` ENUM('pending','offset','batal') NOT NULL DEFAULT 'pending',
  `tanggal_offset` DATETIME DEFAULT NULL,
  `keterangan` TEXT,
  `id_user` VARCHAR(30) DEFAULT NULL,
  `kd_cabang` VARCHAR(10) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_no_service` (`no_service`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
