-- K6 audit UI/UX: kop struk per cabang. Tambah telepon/WA per cabang di tbcabang.
-- Kosong = kop fallback ke tbsetting (perilaku lama). Reversible:
--   ALTER TABLE tbcabang DROP COLUMN telepon_cabang;
ALTER TABLE tbcabang ADD COLUMN telepon_cabang VARCHAR(30) NULL DEFAULT NULL AFTER alamat_cabang;
