-- F1-01 Moving Average: tabel inti inv_* (reversible, lihat 2026-10-08_run_inv_core.php [up|down])
-- Semua tabel baru, tidak mengubah tabel lama. Qty DECIMAL(14,3), nilai DECIMAL(18,2).

CREATE TABLE IF NOT EXISTS inv_location (
  kode VARCHAR(10) NOT NULL,
  alias VARCHAR(10) NOT NULL,
  is_pusat TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (alias),
  KEY idx_inv_location_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inv_balance (
  kd_cabang VARCHAR(10) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  qty_on_hand DECIMAL(14,3) NOT NULL DEFAULT 0,
  average_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
  total_value DECIMAL(18,2) NOT NULL DEFAULT 0,
  version INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (kd_cabang, no_item),
  CONSTRAINT chk_inv_balance_qty CHECK (qty_on_hand >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inv_ledger (
  id BIGINT NOT NULL AUTO_INCREMENT,
  kd_cabang VARCHAR(10) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  trx_type VARCHAR(20) NOT NULL,
  ref_type VARCHAR(30) NOT NULL,
  ref_no VARCHAR(40) NOT NULL,
  ref_line INT NOT NULL DEFAULT 0,
  qty_in DECIMAL(14,3) NOT NULL DEFAULT 0,
  qty_out DECIMAL(14,3) NOT NULL DEFAULT 0,
  unit_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
  value_delta DECIMAL(18,2) NOT NULL DEFAULT 0,
  avg_before DECIMAL(18,2) NOT NULL DEFAULT 0,
  avg_after DECIMAL(18,2) NOT NULL DEFAULT 0,
  qty_after DECIMAL(14,3) NOT NULL DEFAULT 0,
  value_after DECIMAL(18,2) NOT NULL DEFAULT 0,
  transaction_date DATE NOT NULL,
  correction_date DATE NULL,
  is_correction TINYINT(1) NOT NULL DEFAULT 0,
  keterangan VARCHAR(100) NOT NULL DEFAULT '',
  created_by VARCHAR(50) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_inv_ledger_ref (ref_type, ref_no, ref_line, trx_type, kd_cabang, no_item),
  KEY idx_inv_ledger_item (kd_cabang, no_item, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inv_avg_history (
  id BIGINT NOT NULL AUTO_INCREMENT,
  kd_cabang VARCHAR(10) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  avg_before DECIMAL(18,2) NOT NULL,
  avg_after DECIMAL(18,2) NOT NULL,
  trigger_type VARCHAR(20) NOT NULL,
  trigger_ref VARCHAR(40) NOT NULL,
  is_correction TINYINT(1) NOT NULL DEFAULT 0,
  changed_by VARCHAR(50) NOT NULL DEFAULT '',
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inv_avg_item (kd_cabang, no_item, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inv_config (
  kd_cabang VARCHAR(10) NOT NULL,
  config_key VARCHAR(40) NOT NULL,
  config_value VARCHAR(100) NOT NULL,
  updated_by VARCHAR(50) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (kd_cabang, config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inv_audit_log (
  id BIGINT NOT NULL AUTO_INCREMENT,
  actor VARCHAR(50) NOT NULL,
  action VARCHAR(40) NOT NULL,
  ref VARCHAR(60) NOT NULL DEFAULT '',
  reason VARCHAR(255) NOT NULL DEFAULT '',
  before_json TEXT NULL,
  after_json TEXT NULL,
  approval_id BIGINT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inv_audit_ref (ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
