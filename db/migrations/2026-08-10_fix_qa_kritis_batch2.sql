-- Migration: 2026-08-10_fix_qa_kritis_batch2.sql
-- Lanjutan fix docs/planning/2026-08-09-qa-temuan-browser.md (A-1 #6/#7/#8,
-- A-3 Master Karyawan) — keputusan bisnis dikonfirmasi via chat 2026-08-10.
--
-- 1) tbcabang.status — dipakai lap_antarcab_terima.php & penjualan_mitra_add.php
--    (AND status='1'), kolom belum ada. Default aktif semua cabang existing.
ALTER TABLE tbcabang
  ADD COLUMN status VARCHAR(1) NOT NULL DEFAULT '1' COMMENT '1=aktif, 0=nonaktif' AFTER tipe_cabang;

-- 2) tbl_setting_antarcabang — dipakai setting_antarcabang.php, tabel belum
--    pernah dibuat. Kolom dipetakan dari query INSERT/UPDATE/SELECT di file itu.
CREATE TABLE IF NOT EXISTS tbl_setting_antarcabang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kd_cabang VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'kosong = setting GLOBAL, lihat setting_antarcabang.php',
  tipe_cabang_tujuan INT NOT NULL COMMENT 'FK ke tbcabang_tipe.id',
  diskon_persen DECIMAL(5,2) NOT NULL DEFAULT 0,
  margin_persen DECIMAL(5,2) NOT NULL DEFAULT 0,
  tempo_hari INT NOT NULL DEFAULT 0,
  cara_bayar VARCHAR(20) NOT NULL DEFAULT 'Tunai',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cabang_tipe (kd_cabang, tipe_cabang_tujuan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) tbuser_karyawan.kode_jabatan — beda konsep dari kode_level (kode_level
--    dipertahankan terpisah). FK ringan ke tbjabatan.kode_jabatan.
ALTER TABLE tbuser_karyawan
  ADD COLUMN kode_jabatan VARCHAR(6) NULL COMMENT 'FK ke tbjabatan.kode_jabatan' AFTER kode_level;

-- 4) tbuser_karyawan.is_active — kolom baru (bukan infer dari tanggal_keluar).
--    Backfill: tanggal_keluar sudah terisi -> nonaktif, kosong -> aktif.
ALTER TABLE tbuser_karyawan
  ADD COLUMN is_active VARCHAR(10) NOT NULL DEFAULT 'aktif' COMMENT 'aktif | nonaktif' AFTER spesialisasi;

UPDATE tbuser_karyawan
  SET is_active = 'nonaktif'
  WHERE tanggal_keluar IS NOT NULL;
