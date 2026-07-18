-- Fix regresi: migrasi 2026-07-18_promo_engine_multi_target_cabang_syarat.sql
-- drop kolom target_type/target_id/kd_cabang dari master_diskon_periode, tapi
-- banyak query lama di servis-garansi.php / servis-input-reguler.php /
-- servis-input-reguler-jemput.php masih query kolom itu langsung -> promo
-- barang/jasa/workorder gagal silent sejak migrasi itu jalan.
--
-- View kompatibilitas ini rekonstruksi bentuk lama (1 baris per target,
-- kayak sebelum migrasi) supaya query lama tinggal ganti FROM
-- master_diskon_periode -> v_promo_target_legacy tanpa bongkar WHERE clause.

CREATE OR REPLACE VIEW v_promo_target_legacy AS
SELECT
  p.id_promo,
  p.nama_promo,
  p.tipe_promo,
  p.nilai_promo,
  p.keterangan,
  p.status_aktif,
  p.tanggal_mulai,
  p.tanggal_selesai,
  t.target_type,
  t.target_id,
  t.target_nama
FROM master_diskon_periode p
INNER JOIN master_diskon_periode_target t ON t.id_promo = p.id_promo;
