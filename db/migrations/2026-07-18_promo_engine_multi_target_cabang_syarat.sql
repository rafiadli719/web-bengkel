-- Promo Engine (Task 4) — extend master_diskon_periode existing.
-- Live table master_diskon_periode kosong (0 baris) saat migrasi ini dibuat,
-- jadi kolom lama (target_type/target_id/target_nama/kd_cabang) langsung
-- di-drop tanpa proses pemindahan data. Lihat docs/fsd/FSD_PROMO.md.

ALTER TABLE master_diskon_periode
  DROP COLUMN target_type,
  DROP COLUMN target_id,
  DROP COLUMN kd_cabang,
  ADD COLUMN stackable TINYINT(1) NOT NULL DEFAULT 0 AFTER status_aktif,
  ADD COLUMN boleh_gabung_diskon_member TINYINT(1) NOT NULL DEFAULT 0 AFTER stackable,
  ADD COLUMN mode_syarat ENUM('AND','OR') NOT NULL DEFAULT 'AND' AFTER boleh_gabung_diskon_member;

CREATE TABLE master_diskon_periode_target (
  id INT NOT NULL AUTO_INCREMENT,
  id_promo INT NOT NULL,
  target_type ENUM('jasa','barang','workorder') NOT NULL,
  target_id VARCHAR(50) NOT NULL,
  target_nama VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_id_promo (id_promo),
  KEY idx_target (target_type, target_id),
  CONSTRAINT fk_mdpt_promo FOREIGN KEY (id_promo) REFERENCES master_diskon_periode(id_promo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_diskon_periode_cabang (
  id INT NOT NULL AUTO_INCREMENT,
  id_promo INT NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_id_promo (id_promo),
  KEY idx_kd_cabang (kd_cabang),
  CONSTRAINT fk_mdpc_promo FOREIGN KEY (id_promo) REFERENCES master_diskon_periode(id_promo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_diskon_periode_syarat (
  id INT NOT NULL AUTO_INCREMENT,
  id_promo INT NOT NULL,
  jenis_syarat ENUM('kategori_member','minimum_total_servis','jumlah_kunjungan','paket_workorder') NOT NULL,
  operator ENUM('=','>=','<=','IN') NOT NULL,
  nilai VARCHAR(200) NOT NULL,
  rolling_hari INT DEFAULT NULL COMMENT 'hanya untuk jenis_syarat=jumlah_kunjungan',
  PRIMARY KEY (id),
  KEY idx_id_promo (id_promo),
  CONSTRAINT fk_mdps_promo FOREIGN KEY (id_promo) REFERENCES master_diskon_periode(id_promo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE promo_usage_log (
  id INT NOT NULL AUTO_INCREMENT,
  id_promo INT NOT NULL,
  no_service VARCHAR(20) NOT NULL,
  target_type ENUM('jasa','barang','workorder') NOT NULL,
  target_id VARCHAR(50) NOT NULL,
  nilai_potongan DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  urutan_stacking TINYINT NOT NULL DEFAULT 1,
  dipakai_oleh INT DEFAULT NULL,
  tanggal_pakai TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_id_promo (id_promo),
  KEY idx_no_service (no_service),
  CONSTRAINT fk_pul_promo FOREIGN KEY (id_promo) REFERENCES master_diskon_periode(id_promo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
