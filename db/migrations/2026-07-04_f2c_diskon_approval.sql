-- F2-C: Diskon Supervisor Approval (planning meeting 28 Jun 2026, Q4/Q4b)
-- Kasir tetap bisa diskon langsung sesuai SOP (member/mitra/karyawan via diskon_source per item).
-- Diskon manual level-invoice (txtpotfaktur_persen/txtpotfaktur_nom saat bayar) di luar SOP itu
-- butuh approval Supervisor/Manager sebelum pembayaran bisa diproses.

CREATE TABLE IF NOT EXISTS `tb_approval_diskon` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `no_service` VARCHAR(30) NOT NULL,
  `jenis` ENUM('jasa','barang','invoice') NOT NULL DEFAULT 'invoice',
  `nominal_diskon` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `persen_diskon` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `alasan` TEXT,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `id_user_cs` VARCHAR(30) DEFAULT NULL,
  `id_user_supervisor` VARCHAR(30) DEFAULT NULL,
  `tanggal_request` DATETIME DEFAULT NULL,
  `tanggal_approval` DATETIME DEFAULT NULL,
  `kd_cabang` VARCHAR(10) DEFAULT NULL,
  KEY `idx_no_service` (`no_service`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Grant permission diskon_approval_approve ke Administrator (1) dan Manager (7)
UPDATE `tb_master_posisi`
SET `permissions` = JSON_ARRAY_APPEND(`permissions`, '$', 'diskon_approval_approve')
WHERE `user_akses_level` IN (1, 7)
  AND NOT JSON_CONTAINS(`permissions`, '"diskon_approval_approve"');
