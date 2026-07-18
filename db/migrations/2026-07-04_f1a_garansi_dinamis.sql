-- F1-A: Garansi Auto-Check (Q7/A3). Jawaban klarifikasi A3 (2026-07-04, Pak Novian):
-- masa garansi TIDAK flat 7/14 hari — dibuat dinamis per tier member, editable di
-- tbmaster_kategori_member (bukan tabel tb_master_garansi terpisah seperti rencana awal).
--
-- Bug ditemukan: kode servis-garansi.php sudah menulis kolom is_garansi/
-- ref_no_service_original/tanggal_garansi_expire/mekanik_original/komisi_garansi_mode
-- sejak sebelumnya, tapi kolom ini TIDAK PERNAH dimigrasikan ke tblservice — INSERT
-- akan gagal "Unknown column" kapan saja alur "buat garansi dari service asal" dipakai.

ALTER TABLE `tblservice`
  ADD COLUMN `is_garansi` TINYINT(1) DEFAULT 0 COMMENT 'F1-A: 1 = service ini adalah klaim garansi' AFTER `boleh_dp`,
  ADD COLUMN `ref_no_service_original` VARCHAR(30) NULL COMMENT 'F1-A: no_service asal yang digaransikan',
  ADD COLUMN `tanggal_garansi_expire` DATE NULL COMMENT 'F1-A: batas masa garansi dari service asal',
  ADD COLUMN `mekanik_original` VARCHAR(50) NULL COMMENT 'F1-B: mekanik pengerjaan asal (untuk transfer komisi)',
  ADD COLUMN `komisi_garansi_mode` ENUM('skip','transfer','unknown') DEFAULT 'unknown' COMMENT 'F1-B: skip jika mekanik sama, transfer jika beda (blocked B1)';

-- Masa garansi per tier member, dinamis & bisa diedit lewat master (bukan hardcode).
ALTER TABLE `tbmaster_kategori_member`
  ADD COLUMN `masa_garansi_hari` INT NOT NULL DEFAULT 7 COMMENT 'F1-A: batas standar (hijau) klaim garansi',
  ADD COLUMN `masa_garansi_maks_hari` INT NOT NULL DEFAULT 14 COMMENT 'F1-A: batas maksimal (kuning/warning) sebelum expired (merah)';

UPDATE `tbmaster_kategori_member` SET `masa_garansi_hari` = 0,  `masa_garansi_maks_hari` = 7  WHERE `status_member` = 'Bronze';
UPDATE `tbmaster_kategori_member` SET `masa_garansi_hari` = 7,  `masa_garansi_maks_hari` = 14 WHERE `status_member` = 'Silver';
UPDATE `tbmaster_kategori_member` SET `masa_garansi_hari` = 11, `masa_garansi_maks_hari` = 18 WHERE `status_member` = 'Gold';
UPDATE `tbmaster_kategori_member` SET `masa_garansi_hari` = 14, `masa_garansi_maks_hari` = 21 WHERE `status_member` = 'Platinum';
