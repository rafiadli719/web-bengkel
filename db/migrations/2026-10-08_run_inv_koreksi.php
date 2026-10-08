<?php
// Moving Average Fase 3-4: tabel koreksi, periode, transit, PPV, klaim supplier.
// Pakai: php 2026-10-08_run_inv_koreksi.php [up|down] [force]
// down hanya bila semua tabel kosong, kecuali argumen 'force'.
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';
$force = ($argv[2] ?? '') === 'force';
$tabel = ['inv_ppv_log', 'inv_claim_receivable', 'inv_correction_request', 'inv_period', 'inv_transit'];

if ($mode === 'down') {
    $isi = 0;
    foreach ($tabel as $t) {
        try { $isi += (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM `$t`"))[0]; } catch (mysqli_sql_exception $e) {}
    }
    if ($isi > 0 && !$force) { echo "Tabel berisi $isi baris. Down dibatalkan. Pakai 'force' bila yakin.\n"; exit(1); }
    foreach ($tabel as $t) mysqli_query($koneksi, "DROP TABLE IF EXISTS `$t`");
    echo "Down selesai.\n";
    exit(0);
}

$ddl = [
"CREATE TABLE IF NOT EXISTS inv_transit (
  id BIGINT NOT NULL AUTO_INCREMENT,
  no_transfer VARCHAR(40) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  kd_asal VARCHAR(10) NOT NULL,
  kd_tujuan VARCHAR(10) NOT NULL,
  qty DECIMAL(14,3) NOT NULL,
  unit_cost DECIMAL(18,2) NOT NULL,
  value DECIMAL(18,2) NOT NULL,
  status ENUM('in_transit','received','returned') NOT NULL DEFAULT 'in_transit',
  shipped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_inv_transit (no_transfer, no_item),
  KEY idx_inv_transit_status (status, shipped_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS inv_ppv_log (
  id BIGINT NOT NULL AUTO_INCREMENT,
  kd_cabang VARCHAR(10) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  correction_ref VARCHAR(40) NOT NULL,
  qty_affected DECIMAL(14,3) NOT NULL,
  price_difference DECIMAL(18,2) NOT NULL,
  total_variance DECIMAL(18,2) NOT NULL,
  posted_to_account VARCHAR(40) NULL,
  period CHAR(7) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inv_ppv_period (period, kd_cabang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS inv_claim_receivable (
  id BIGINT NOT NULL AUTO_INCREMENT,
  no_klaim VARCHAR(40) NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  no_item VARCHAR(30) NOT NULL,
  qty DECIMAL(14,3) NOT NULL,
  value DECIMAL(18,2) NOT NULL,
  supplier_id VARCHAR(20) NOT NULL DEFAULT '',
  status ENUM('pending','settled','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  settled_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_inv_claim (no_klaim, no_item),
  KEY idx_inv_claim_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS inv_correction_request (
  id BIGINT NOT NULL AUTO_INCREMENT,
  type VARCHAR(30) NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  ref_origin VARCHAR(40) NOT NULL DEFAULT '',
  payload_json TEXT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  maker VARCHAR(50) NOT NULL,
  checker VARCHAR(50) NULL,
  status ENUM('pending','approved','rejected','applied','failed') NOT NULL DEFAULT 'pending',
  decision_note VARCHAR(255) NOT NULL DEFAULT '',
  result_json TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_inv_corr_status (status, kd_cabang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS inv_period (
  period CHAR(7) NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  status ENUM('open','closed','locked') NOT NULL DEFAULT 'open',
  updated_by VARCHAR(50) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (period, kd_cabang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
foreach ($ddl as $sql) mysqli_query($koneksi, $sql);
mysqli_query($koneksi, "INSERT IGNORE INTO inv_config (kd_cabang, config_key, config_value, updated_by) VALUES ('*', 'opname_threshold', '0', 'migrasi')");
echo "Up selesai. Tabel koreksi/periode/transit/ppv/klaim siap.\n";
