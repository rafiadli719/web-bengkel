-- Dieksekusi 2026-09-14 ke fitmotor_dbbengkel (live). Nullable dari awal —
-- 47 baris tbuser existing belum punya nilai ini.
ALTER TABLE tbuser
  ADD COLUMN id_karyawan INT NULL DEFAULT NULL COMMENT 'FK opsional ke tbuser_karyawan.id — akun login yang gak punya record HR (mis. akun sistem lama) boleh NULL',
  ADD KEY idx_tbuser_id_karyawan (id_karyawan);

ALTER TABLE tbuser
  ADD CONSTRAINT fk_tbuser_id_karyawan
  FOREIGN KEY (id_karyawan) REFERENCES tbuser_karyawan(id)
  ON DELETE SET NULL ON UPDATE CASCADE;
