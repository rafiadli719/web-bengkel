<?php
// Simple DB checker/updater for Procurement PR-PO-DO
// Usage:
// - Dry run (only check):   db_update_procurement.php
// - Apply fixes:            db_update_procurement.php?apply=1

error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();

$base = dirname(__FILE__);
require_once dirname(__FILE__)."/../config/koneksi.php"; // provides $koneksi (mysqli)

// koneksi.php may disable error display; re-enable for this updater
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Fail fast if DB not connected
if(!isset($koneksi) || !$koneksi){
  header('Content-Type: text/plain; charset=utf-8');
  http_response_code(500);
  die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function now(){ return date('Y-m-d H:i:s'); }
function db($sql){ global $koneksi; return mysqli_query($koneksi, $sql); }
function db_err(){ global $koneksi; return mysqli_error($koneksi); }
function db_fetch($res){ return mysqli_fetch_assoc($res); }
function db_val($sql){ $r=db($sql); if($r){ $row=db_fetch($r); if($row){ return array_values($row)[0]; } } return null; }

// Detect current DB/schema
$DB_NAME = db_val("SELECT DATABASE()");
$apply = isset($_GET['apply']) && $_GET['apply']=='1';
$mysql_version = db_val("SELECT VERSION()");
$major = 0; if(preg_match('/^(\d+)/',$mysql_version,$m)){ $major = intval($m[1]); }

$log = [];
function report($status,$msg){
  global $log; $log[] = [strtoupper($status), $msg];
}

function table_exists($name){
  $name = addslashes($name);
  return (int)db_val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$name'")>0;
}
function column_exists($table,$col){
  $table = addslashes($table); $col = addslashes($col);
  return (int)db_val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='$table' AND COLUMN_NAME='$col'")>0;
}
function index_exists($table,$index){
  $table = addslashes($table); $index = addslashes($index);
  return (int)db_val("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='$table' AND INDEX_NAME='$index'")>0;
}
function primary_exists($table){
  $table = addslashes($table);
  return (int)db_val("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='$table' AND CONSTRAINT_TYPE='PRIMARY KEY'")>0;
}
function routine_exists($name, $type='PROCEDURE'){
  $name = addslashes($name);
  return (int)db_val("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE='$type' AND ROUTINE_NAME='$name'")>0;
}
function view_exists($name){ return table_exists($name); }

function table_status($table){
  $table = addslashes($table);
  $r = db("SHOW TABLE STATUS WHERE Name='$table'");
  if($r){ return db_fetch($r); }
  return null;
}

function ensure_engine_collation($table,$engine='InnoDB',$collation='utf8mb4_unicode_ci',$apply=false){
  $st = table_status($table);
  if(!$st){ report('ERROR', "Table $table not found"); return; }
  if(strcasecmp($st['Engine'],$engine)!=0){
    if($apply){ if(db("ALTER TABLE `$table` ENGINE=$engine")){ report('OK', "ALTER TABLE $table ENGINE -> $engine"); } else { report('ERROR', "Engine change $table: ".db_err()); } }
    else { report('TODO', "Change ENGINE of $table to $engine"); }
  } else { report('OK', "$table ENGINE already $engine"); }
  if(strcasecmp($st['Collation'],$collation)!=0){
    if($apply){ if(db("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE $collation")){ report('OK', "CONVERT $table to $collation"); } else { report('ERROR', "Collation convert $table: ".db_err()); } }
    else { report('TODO', "Convert $table to collation $collation"); }
  } else { report('OK', "$table collation already $collation"); }
}

function ensure_primary_key($table,$pkcols,$apply=false){
  if(primary_exists($table)) { report('OK', "$table PRIMARY KEY exists"); return; }
  // sanity: duplicates?
  $col = $pkcols[0];
  $dup = db("SELECT `$col`, COUNT(*) c FROM `$table` GROUP BY `$col` HAVING c>1 LIMIT 1");
  if($dup && mysqli_num_rows($dup)>0){ report('ERROR', "$table has duplicate values in $col. Resolve duplicates before adding PRIMARY KEY."); return; }
  if($apply){
    $cols = '`'.implode('`,`',$pkcols).'`';
    if(db("ALTER TABLE `$table` MODIFY `$col` VARCHAR(50) NOT NULL")){
      if(db("ALTER TABLE `$table` ADD PRIMARY KEY ($cols)")) report('OK', "$table PRIMARY KEY ($cols) added"); else report('ERROR', "Add PK on $table: ".db_err());
    } else { report('ERROR', "Modify $table.$col NOT NULL: ".db_err()); }
  } else {
    report('TODO', "Add PRIMARY KEY on $table (".implode(',',$pkcols).")");
  }
}

function ensure_column($table,$col,$def,$apply=false){
  if(column_exists($table,$col)) { report('OK', "$table.$col exists"); return; }
  if($apply){ if(db("ALTER TABLE `$table` ADD COLUMN `$col` $def")) report('OK', "ADD $table.$col"); else report('ERROR', "ADD $table.$col: ".db_err()); }
  else { report('TODO', "ADD $table.$col $def"); }
}

function ensure_index($table,$index,$cols,$apply=false,$unique=false){
  if(index_exists($table,$index)){ report('OK', "$table.$index exists"); return; }
  $cols_sql = '`'.implode('`,`',$cols).'`';
  $sql = "ALTER TABLE `$table` ".($unique?"ADD UNIQUE INDEX":"ADD INDEX")." `$index` ($cols_sql)";
  if($apply){ if(db($sql)) report('OK', "ADD INDEX $index ON $table"); else report('ERROR', "ADD INDEX $index ON $table: ".db_err()); }
  else { report('TODO', $sql); }
}

function ensure_table($name,$createSql,$apply=false){
  if(table_exists($name)) { report('OK', "Table $name exists"); return; }
  if($apply){ if(db($createSql)) report('OK', "CREATE TABLE $name"); else report('ERROR', "CREATE TABLE $name: ".db_err()); }
  else { report('TODO', "CREATE TABLE $name"); }
}

function ensure_procedure($name,$createSql,$apply=false){
  if(routine_exists($name,'PROCEDURE')){ report('OK', "Procedure $name exists"); return; }
  if($apply){ if(db($createSql)) report('OK', "CREATE PROCEDURE $name"); else report('ERROR', "CREATE PROCEDURE $name: ".db_err()); }
  else { report('TODO', "CREATE PROCEDURE $name"); }
}

function ensure_view($name,$createSql,$apply=false){
  if(view_exists($name)){ report('OK', "View $name exists"); return; }
  if($apply){ if(db($createSql)) report('OK', "CREATE VIEW $name"); else report('ERROR', "CREATE VIEW $name: ".db_err()); }
  else { report('TODO', "CREATE VIEW $name"); }
}

// 1) PO header: engine/collation/PK
if(table_exists('tblorder_header')){
  ensure_engine_collation('tblorder_header','InnoDB','utf8mb4_unicode_ci',$apply);
  ensure_primary_key('tblorder_header',['no_order'],$apply);
  // PO header columns
  ensure_column('tblorder_header','no_pr',"VARCHAR(50) DEFAULT NULL COMMENT 'Link ke PR'",$apply);
  ensure_column('tblorder_header','no_rfq',"VARCHAR(50) DEFAULT NULL COMMENT 'Link ke RFQ'",$apply);
  ensure_column('tblorder_header','status_approval',"ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft'",$apply);
  ensure_column('tblorder_header','approved_by',"VARCHAR(50) DEFAULT NULL",$apply);
  ensure_column('tblorder_header','approved_date',"DATETIME DEFAULT NULL",$apply);
  ensure_column('tblorder_header','rejected_by',"VARCHAR(50) DEFAULT NULL",$apply);
  ensure_column('tblorder_header','rejected_date',"DATETIME DEFAULT NULL",$apply);
  ensure_column('tblorder_header','reject_reason',"TEXT DEFAULT NULL",$apply);
  ensure_column('tblorder_header','po_type',"ENUM('regular','urgent','consignment') NOT NULL DEFAULT 'regular'",$apply);
  ensure_column('tblorder_header','payment_term',"VARCHAR(50) DEFAULT NULL COMMENT 'Net 30, Net 60, COD, dll'",$apply);
  ensure_column('tblorder_header','delivery_address',"TEXT DEFAULT NULL",$apply);
  ensure_column('tblorder_header','contact_person',"VARCHAR(100) DEFAULT NULL",$apply);
  ensure_column('tblorder_header','contact_phone',"VARCHAR(20) DEFAULT NULL",$apply);
  ensure_index('tblorder_header','idx_pr',['no_pr'],$apply,false);
  ensure_index('tblorder_header','idx_status_approval',['status_approval'],$apply,false);
} else {
  report('ERROR', 'tblorder_header not found');
}

// 2) Pembelian header additions (link DO/PR + QC)
if(table_exists('tblpembelian_header')){
  ensure_engine_collation('tblpembelian_header','InnoDB','utf8mb4_unicode_ci',$apply);
  ensure_column('tblpembelian_header','no_do',"VARCHAR(50) DEFAULT NULL COMMENT 'Link ke DO'",$apply);
  ensure_column('tblpembelian_header','no_pr',"VARCHAR(50) DEFAULT NULL COMMENT 'Link ke PR'",$apply);
  ensure_column('tblpembelian_header','status_qc',"ENUM('pending','passed','failed','partial') DEFAULT 'pending'",$apply);
  ensure_column('tblpembelian_header','qc_by',"VARCHAR(50) DEFAULT NULL",$apply);
  ensure_column('tblpembelian_header','qc_date',"DATETIME DEFAULT NULL",$apply);
  ensure_column('tblpembelian_header','qc_notes',"TEXT DEFAULT NULL",$apply);
  ensure_index('tblpembelian_header','idx_do',['no_do'],$apply,false);
  ensure_index('tblpembelian_header','idx_pr',['no_pr'],$apply,false);
} else {
  report('ERROR','tblpembelian_header not found');
}

// 3) PR tables
ensure_table('tblpurchase_request_header',
  "CREATE TABLE `tblpurchase_request_header` (
    `no_pr` VARCHAR(50) NOT NULL PRIMARY KEY,
    `tanggal_pr` DATE NOT NULL,
    `tanggal_butuh` DATE NOT NULL,
    `requester` VARCHAR(50) NOT NULL,
    `departemen` VARCHAR(50) NOT NULL,
    `alasan` TEXT NOT NULL,
    `total_qty` INT(11) NOT NULL DEFAULT 0,
    `total_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status_pr` ENUM('draft','submitted','approved','rejected','closed') NOT NULL DEFAULT 'draft',
    `approved_by` VARCHAR(50) DEFAULT NULL,
    `approved_date` DATETIME DEFAULT NULL,
    `rejected_by` VARCHAR(50) DEFAULT NULL,
    `rejected_date` DATETIME DEFAULT NULL,
    `reject_reason` TEXT DEFAULT NULL,
    `kd_cabang` VARCHAR(10) NOT NULL,
    `created_by` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status_pr`),
    INDEX `idx_tanggal` (`tanggal_pr`),
    INDEX `idx_cabang` (`kd_cabang`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);
ensure_table('tblpurchase_request_detail',
  "CREATE TABLE `tblpurchase_request_detail` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_pr` VARCHAR(50) NOT NULL,
    `nobaris` INT(11) NOT NULL,
    `no_item` VARCHAR(50) NOT NULL,
    `nama_item` VARCHAR(100) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `qty_approved` INT(11) NOT NULL DEFAULT 0,
    `qty_po` INT(11) NOT NULL DEFAULT 0,
    `satuan` VARCHAR(20) NOT NULL,
    `harga_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `keterangan` TEXT DEFAULT NULL,
    INDEX `idx_item` (`no_item`),
    CONSTRAINT `fk_prd_header` FOREIGN KEY (`no_pr`) REFERENCES `tblpurchase_request_header`(`no_pr`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);
ensure_table('tblpr_approval_log',
  "CREATE TABLE `tblpr_approval_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_pr` VARCHAR(50) NOT NULL,
    `level_approval` INT(11) NOT NULL,
    `approver` VARCHAR(50) NOT NULL,
    `action` ENUM('approved','rejected') NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `action_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_prlog_header` FOREIGN KEY (`no_pr`) REFERENCES `tblpurchase_request_header`(`no_pr`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);

// 4) DO tables
ensure_table('tbldelivery_order_header',
  "CREATE TABLE `tbldelivery_order_header` (
    `no_do` VARCHAR(50) NOT NULL PRIMARY KEY,
    `no_po` VARCHAR(50) NOT NULL,
    `no_supplier` VARCHAR(50) NOT NULL,
    `tanggal_do` DATE NOT NULL,
    `tanggal_kirim` DATE NOT NULL,
    `tanggal_estimasi_tiba` DATE NOT NULL,
    `tanggal_tiba` DATE DEFAULT NULL,
    `no_surat_jalan` VARCHAR(50) DEFAULT NULL,
    `no_kendaraan` VARCHAR(20) DEFAULT NULL,
    `nama_pengirim` VARCHAR(100) DEFAULT NULL,
    `telp_pengirim` VARCHAR(20) DEFAULT NULL,
    `alamat_kirim` TEXT NOT NULL,
    `total_qty` INT(11) NOT NULL DEFAULT 0,
    `status_do` ENUM('draft','confirmed','in_transit','arrived','received','cancelled') NOT NULL DEFAULT 'draft',
    `keterangan` TEXT DEFAULT NULL,
    `kd_cabang` VARCHAR(10) NOT NULL,
    `created_by` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status_do`),
    INDEX `idx_tanggal_kirim` (`tanggal_kirim`),
    CONSTRAINT `fk_do_po` FOREIGN KEY (`no_po`) REFERENCES `tblorder_header`(`no_order`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);
ensure_table('tbldelivery_order_detail',
  "CREATE TABLE `tbldelivery_order_detail` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_do` VARCHAR(50) NOT NULL,
    `nobaris` INT(11) NOT NULL,
    `no_item` VARCHAR(50) NOT NULL,
    `qty_po` INT(11) NOT NULL,
    `qty_kirim` INT(11) NOT NULL,
    `qty_terima` INT(11) NOT NULL DEFAULT 0,
    `qty_reject` INT(11) NOT NULL DEFAULT 0,
    `keterangan` TEXT DEFAULT NULL,
    INDEX `idx_item` (`no_item`),
    CONSTRAINT `fk_dod_do` FOREIGN KEY (`no_do`) REFERENCES `tbldelivery_order_header`(`no_do`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);
ensure_table('tbldo_tracking',
  "CREATE TABLE `tbldo_tracking` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_do` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `keterangan` TEXT DEFAULT NULL,
    `lokasi` VARCHAR(100) DEFAULT NULL,
    `updated_by` VARCHAR(50) NOT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_dotrk_do` FOREIGN KEY (`no_do`) REFERENCES `tbldelivery_order_header`(`no_do`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);

// 5) PO approval log
ensure_table('tblpo_approval_log',
  "CREATE TABLE `tblpo_approval_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_po` VARCHAR(50) NOT NULL,
    `level_approval` INT(11) NOT NULL,
    `approver` VARCHAR(50) NOT NULL,
    `action` ENUM('approved','rejected') NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `action_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_po_log_po` FOREIGN KEY (`no_po`) REFERENCES `tblorder_header`(`no_order`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  $apply
);

// 6) Stored procedures
$sp_pr = "CREATE PROCEDURE `sp_generate_no_pr`(IN p_kd_cabang VARCHAR(10), OUT p_no_pr VARCHAR(50))
BEGIN
  DECLARE v_tahun VARCHAR(4);
  DECLARE v_bulan VARCHAR(2);
  DECLARE v_urut INT;
  SET v_tahun = YEAR(CURDATE());
  SET v_bulan = LPAD(MONTH(CURDATE()), 2, '0');
  SELECT COALESCE(MAX(CAST(SUBSTRING(no_pr, -5) AS UNSIGNED)), 0) + 1 INTO v_urut
  FROM tblpurchase_request_header
  WHERE no_pr LIKE CONCAT('PR', v_tahun, v_bulan, p_kd_cabang, '%');
  SET p_no_pr = CONCAT('PR', v_tahun, v_bulan, p_kd_cabang, LPAD(v_urut, 5, '0'));
END";
$sp_do = "CREATE PROCEDURE `sp_generate_no_do`(IN p_kd_cabang VARCHAR(10), OUT p_no_do VARCHAR(50))
BEGIN
  DECLARE v_tahun VARCHAR(4);
  DECLARE v_bulan VARCHAR(2);
  DECLARE v_urut INT;
  SET v_tahun = YEAR(CURDATE());
  SET v_bulan = LPAD(MONTH(CURDATE()), 2, '0');
  SELECT COALESCE(MAX(CAST(SUBSTRING(no_do, -5) AS UNSIGNED)), 0) + 1 INTO v_urut
  FROM tbldelivery_order_header
  WHERE no_do LIKE CONCAT('DO', v_tahun, v_bulan, p_kd_cabang, '%');
  SET p_no_do = CONCAT('DO', v_tahun, v_bulan, p_kd_cabang, LPAD(v_urut, 5, '0'));
END";
ensure_procedure('sp_generate_no_pr',$sp_pr,$apply);
ensure_procedure('sp_generate_no_do',$sp_do,$apply);

// 7) Views (use fallback if MySQL < 8)
$view_pr = "CREATE OR REPLACE VIEW `view_pr_complete` AS
SELECT h.no_pr,h.tanggal_pr,h.tanggal_butuh,h.requester,h.departemen,h.alasan,
       h.total_qty,h.total_estimasi,h.status_pr,h.approved_by,h.approved_date,
       h.kd_cabang,h.created_by,h.created_at,
       COUNT(d.id) AS jumlah_item,
       COALESCE(SUM(d.quantity),0) AS total_qty_detail,
       COALESCE(SUM(d.total_estimasi),0) AS total_estimasi_detail,
       COALESCE(SUM(d.qty_po),0) AS total_qty_po,
       CASE WHEN COALESCE(SUM(d.quantity),0) = COALESCE(SUM(d.qty_po),0) THEN 'Completed'
            WHEN COALESCE(SUM(d.qty_po),0) > 0 THEN 'Partial'
            ELSE 'Open' END AS status_fulfillment
FROM tblpurchase_request_header h
LEFT JOIN tblpurchase_request_detail d ON h.no_pr=d.no_pr
GROUP BY h.no_pr";
ensure_view('view_pr_complete',$view_pr,$apply);

$view_po = "CREATE OR REPLACE VIEW `view_po_with_pr` AS
SELECT 
  po.*, 
  pr.requester AS pr_requester, 
  pr.departemen AS pr_departemen, 
  pr.alasan AS pr_alasan
FROM tblorder_header po
LEFT JOIN tblpurchase_request_header pr ON po.no_pr = pr.no_pr";
ensure_view('view_po_with_pr',$view_po,$apply);

if($major>=8){
  $view_do = "CREATE OR REPLACE VIEW `view_do_tracking_latest` AS
  SELECT doh.*, t.status AS latest_status, t.keterangan AS latest_keterangan,
         t.lokasi AS latest_lokasi, t.updated_at AS latest_update
  FROM tbldelivery_order_header doh
  LEFT JOIN (
    SELECT no_do, status, keterangan, lokasi, updated_at,
           ROW_NUMBER() OVER (PARTITION BY no_do ORDER BY updated_at DESC) AS rn
    FROM tbldo_tracking
  ) t ON doh.no_do=t.no_do AND t.rn=1";
} else {
  $view_do = "CREATE OR REPLACE VIEW `view_do_tracking_latest` AS
  SELECT doh.*, t.status AS latest_status, t.keterangan AS latest_keterangan,
         t.lokasi AS latest_lokasi, t.updated_at AS latest_update
  FROM tbldelivery_order_header doh
  LEFT JOIN tbldo_tracking t ON t.no_do=doh.no_do
  LEFT JOIN (
    SELECT no_do, MAX(updated_at) AS max_updated
    FROM tbldo_tracking GROUP BY no_do
  ) tx ON tx.no_do=t.no_do AND tx.max_updated=t.updated_at";
}
ensure_view('view_do_tracking_latest',$view_do,$apply);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>DB Update Procurement</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<style>body{padding:20px}.ok{color:#2e7d32}.err{color:#c62828}.todo{color:#ef6c00} table td{vertical-align:top}</style>
</head>
<body>
<h3>DB Update Procurement (PR-PO-DO)</h3>
<p>Database: <strong><?php echo esc($DB_NAME); ?></strong> | MySQL: <?php echo esc($mysql_version); ?> | Mode: <strong><?php echo $apply?'APPLY':'DRY-RUN'; ?></strong></p>
<p>
  <a class="btn btn-primary" href="db_update_procurement.php">Dry Run (Check Only)</a>
  <a class="btn btn-success" href="db_update_procurement.php?apply=1" onclick="return confirm('Jalankan perubahan schema?')">Apply Fixes</a>
</p>
<table class="table table-bordered">
  <thead><tr><th>Status</th><th>Keterangan</th></tr></thead>
  <tbody>
  <?php foreach($log as $row){ $cls = ($row[0]=='OK'?'ok':($row[0]=='ERROR'?'err':'todo')); ?>
    <tr><td class="<?php echo $cls; ?>"><?php echo esc($row[0]); ?></td><td><?php echo esc($row[1]); ?></td></tr>
  <?php } ?>
  </tbody>
</table>
<p>Note:
<ul>
  <li>Jika gagal add PRIMARY KEY di tblorder_header karena duplikasi no_order, bersihkan duplikat terlebih dahulu.</li>
  <li>Pastikan user DB memiliki hak ALTER/CREATE/INDEX.</li>
</ul>
</p>
</body>
</html>
