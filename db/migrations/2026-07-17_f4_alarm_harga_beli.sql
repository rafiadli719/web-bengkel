-- FASE 4: Alarm Harga Beli (jawaban Owner 16 Jul 2026, FSD_PENGADAAN_INVENTORY.md §11.1/§11.2)
-- Threshold naik/turun dibedakan, 1 halaman setting 2 field. Alarm dipicu via TRIGGER
-- di tblpembelian_detail (bukan dari PHP) karena ada 4 titik insert berbeda di app/
-- (pembelian_add.php, pembelian_add_next_rst.php, pembelian_add_rst.php, pembelian_cab_add_proses.php)
-- - trigger di level DB menjamin semua titik insert tertangkap, tidak bisa kelewat satu.

CREATE TABLE IF NOT EXISTS tb_master_threshold_harga (
  id INT PRIMARY KEY AUTO_INCREMENT,
  arah ENUM('naik','turun') NOT NULL,
  persen_threshold DOUBLE NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  updated_by VARCHAR(50) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_arah (arah)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tb_master_threshold_harga (arah, persen_threshold, aktif) VALUES
  ('naik', 5.0, 1),
  ('turun', 10.0, 1);

CREATE TABLE IF NOT EXISTS alarm_harga_beli (
  id INT PRIMARY KEY AUTO_INCREMENT,
  no_item VARCHAR(50) NOT NULL,
  no_transaksi_pembelian VARCHAR(50) NOT NULL,
  harga_beli_lama DOUBLE NOT NULL,
  harga_beli_baru DOUBLE NOT NULL,
  persen_selisih DOUBLE NOT NULL,
  arah ENUM('naik','turun') NOT NULL,
  threshold_saat_itu DOUBLE NOT NULL,
  harga_jual_saat_ini DOUBLE NULL,
  status_klasifikasi VARCHAR(50) NULL,
  status_review ENUM('belum_direview','direview','harga_disesuaikan','diabaikan') DEFAULT 'belum_direview',
  direview_oleh VARCHAR(50) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_no_item (no_item),
  KEY idx_status_review (status_review)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger dieksekusi terpisah (single query, bukan multi_query) lewat db/migrations/2026-07-17_run_alarm_harga_beli.php
-- karena body trigger mengandung banyak ';' yang akan kepotong kalau dijalankan via mysqli_multi_query.
