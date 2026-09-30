-- servis-garansi.php dan servis-input-reguler-jemput.php meng-INSERT
-- tblservis_jasa.keterangan (input "keterangan jasa" di form), tapi kolomnya
-- tidak ada di skema (lokal maupun dump produksi) -> tambah jasa di garansi &
-- jemput selalu ditolak "Unknown column 'keterangan'". Kolom nullable, ADD
-- COLUMN NULL = instan (tanpa rebuild 92rb baris).
ALTER TABLE tblservis_jasa ADD COLUMN keterangan VARCHAR(255) NULL DEFAULT NULL;

-- Rollback:
-- ALTER TABLE tblservis_jasa DROP COLUMN keterangan;
