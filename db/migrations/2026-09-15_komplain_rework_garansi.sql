-- Modul Komplain: REWORK harus masuk jalur garansi (bukan servis reguler/jemput)
-- Konteks: no_nota_rujukan sebelumnya teks bebas, gak tervalidasi ke no_service
-- asli, jadi gak bisa dipakai sebagai ref_service pas rework disetujui.
-- Tambah no_service_asli (wajib diisi via form baru, tervalidasi ke tblservice)
-- + no_service_rework (no_service GAR-xxxx hasil auto-create pas Kepala Cabang
-- approve usulan Terima). no_nota_rujukan TIDAK dihapus (reversible, no data loss).

ALTER TABLE tblkomplain
  ADD COLUMN no_service_asli VARCHAR(30) DEFAULT NULL AFTER no_nota_rujukan,
  ADD COLUMN no_service_rework VARCHAR(30) DEFAULT NULL AFTER no_service_asli,
  ADD KEY idx_no_service_asli (no_service_asli);

-- Rollback:
-- ALTER TABLE tblkomplain
--   DROP KEY idx_no_service_asli,
--   DROP COLUMN no_service_rework,
--   DROP COLUMN no_service_asli;
