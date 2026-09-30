-- view_laporan_cancel_servis lambat (~1-3 detik walau tb_log_cancel_servis
-- cuma 1-2 baris): JOIN lc.no_service (utf8mb4_general_ci) ke
-- tblservice.no_service (latin1_swedish_ci) beda charset, MySQL gak bisa
-- pakai index tblservice -> full scan 100rb baris per query. JOIN yang sama
-- dipakai updateCancelStatistikPelanggan() (_include_statistik_pelanggan.php).
-- Samakan kolom log ke latin1 (isinya kode service ASCII, gak ada data hilang).
-- Tabel & view lain gak disentuh.

ALTER TABLE tb_log_cancel_servis
  MODIFY no_service VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Nomor service yang dibatalkan';

-- Rollback:
-- ALTER TABLE tb_log_cancel_servis
--   MODIFY no_service VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor service yang dibatalkan';
