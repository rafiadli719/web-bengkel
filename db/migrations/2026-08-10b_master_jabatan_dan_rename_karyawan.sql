-- Migration: 2026-08-10b_master_jabatan_dan_rename_karyawan.sql
-- Lanjutan fix Master Karyawan (A-3, docs/planning/2026-08-09-qa-temuan-browser.md)
-- keputusan dikonfirmasi via chat 2026-08-10.
--
-- master_jabatan — dipakai dropdown Jabatan (per Posisi, ada urutan tampil)
-- di master_karyawan_edit.php. Tabel terpisah dari tbjabatan (tbjabatan
-- pakai kode_divisi, konsep beda, dibiarkan apa adanya buat kebutuhan lain).
CREATE TABLE IF NOT EXISTS master_jabatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_jabatan VARCHAR(20) NOT NULL,
  nama_jabatan VARCHAR(100) NOT NULL,
  kode_posisi VARCHAR(20) NOT NULL COMMENT 'FK ke tb_master_posisi.kode_posisi',
  urutan INT NOT NULL DEFAULT 0,
  is_active ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_kode_jabatan (kode_jabatan),
  KEY idx_kode_posisi (kode_posisi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update komentar kolom: tbuser_karyawan.kode_jabatan FK ke master_jabatan
-- (bukan tbjabatan seperti asumsi awal migration 2026-08-10_fix_qa_kritis_batch2.sql)
ALTER TABLE tbuser_karyawan
  MODIFY COLUMN kode_jabatan VARCHAR(20) NULL COMMENT 'FK ke master_jabatan.kode_jabatan';
