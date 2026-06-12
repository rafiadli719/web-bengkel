<?php
/**
 * Migration Script for Duplicate Tables
 *
 * This script analyzes and migrates data from duplicate tables:
 * - tblpr_* -> tblpurchase_request_*
 * - tbldo_* -> tbldelivery_order_*
 *
 * IMPORTANT: This script is for analysis and migration only.
 * Backup your database before running any migration!
 */

session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Get user info (no admin check for easier access)
$id_user = $_SESSION['_iduser'];

$action = isset($_GET['action']) ? $_GET['action'] : 'analyze';
$msg = '';
$msg_type = 'info';

// Check if duplicate tables exist
function tableExists($koneksi, $tableName){
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Get table row count
function getRowCount($koneksi, $tableName){
    if(!tableExists($koneksi, $tableName)) return 0;
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM $tableName");
    $row = mysqli_fetch_assoc($result);
    return (int)$row['cnt'];
}

// Analysis
$analysis = [];

// PR Tables
$analysis['pr'] = [
    'old_header' => 'tblpr_header',
    'old_detail' => 'tblpr_detail',
    'new_header' => 'tblpurchase_request_header',
    'new_detail' => 'tblpurchase_request_detail',
    'old_header_exists' => tableExists($koneksi, 'tblpr_header'),
    'old_detail_exists' => tableExists($koneksi, 'tblpr_detail'),
    'new_header_exists' => tableExists($koneksi, 'tblpurchase_request_header'),
    'new_detail_exists' => tableExists($koneksi, 'tblpurchase_request_detail'),
    'old_header_count' => getRowCount($koneksi, 'tblpr_header'),
    'old_detail_count' => getRowCount($koneksi, 'tblpr_detail'),
    'new_header_count' => getRowCount($koneksi, 'tblpurchase_request_header'),
    'new_detail_count' => getRowCount($koneksi, 'tblpurchase_request_detail'),
];

// DO Tables
$analysis['do'] = [
    'old_header' => 'tbldo_header',
    'old_detail' => 'tbldo_detail',
    'new_header' => 'tbldelivery_order_header',
    'new_detail' => 'tbldelivery_order_detail',
    'old_header_exists' => tableExists($koneksi, 'tbldo_header'),
    'old_detail_exists' => tableExists($koneksi, 'tbldo_detail'),
    'new_header_exists' => tableExists($koneksi, 'tbldelivery_order_header'),
    'new_detail_exists' => tableExists($koneksi, 'tbldelivery_order_detail'),
    'old_header_count' => getRowCount($koneksi, 'tbldo_header'),
    'old_detail_count' => getRowCount($koneksi, 'tbldo_detail'),
    'new_header_count' => getRowCount($koneksi, 'tbldelivery_order_header'),
    'new_detail_count' => getRowCount($koneksi, 'tbldelivery_order_detail'),
];

// Migration Actions
if($action == 'migrate_pr' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    if($analysis['pr']['old_header_exists'] && $analysis['pr']['old_header_count'] > 0){
        mysqli_begin_transaction($koneksi);
        try {
            // Get structure of both tables to map columns
            $old_cols = mysqli_query($koneksi, "DESCRIBE tblpr_header");
            $new_cols = mysqli_query($koneksi, "DESCRIBE tblpurchase_request_header");

            // Simple migration - assumes compatible structures
            // You should customize this based on actual column differences
            $migrated = 0;

            // Migrate header
            $q = mysqli_query($koneksi, "SELECT * FROM tblpr_header");
            while($row = mysqli_fetch_assoc($q)){
                // Check if already exists
                $check = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblpurchase_request_header WHERE no_pr='".mysqli_real_escape_string($koneksi,$row['no_pr'])."'");
                $c = mysqli_fetch_assoc($check);
                if($c['c'] == 0){
                    // Insert - customize fields as needed
                    mysqli_query($koneksi, "INSERT INTO tblpurchase_request_header
                        (no_pr, tanggal_pr, kd_cabang, status_pr, created_by)
                        VALUES
                        ('".mysqli_real_escape_string($koneksi,$row['no_pr'])."',
                         '".mysqli_real_escape_string($koneksi,$row['tanggal_pr'])."',
                         '".mysqli_real_escape_string($koneksi,$row['kd_cabang'])."',
                         '".mysqli_real_escape_string($koneksi,$row['status_pr'])."',
                         '".mysqli_real_escape_string($koneksi,isset($row['created_by'])?$row['created_by']:'migrated')."')");
                    $migrated++;
                }
            }

            // Migrate detail
            $q = mysqli_query($koneksi, "SELECT * FROM tblpr_detail");
            while($row = mysqli_fetch_assoc($q)){
                mysqli_query($koneksi, "INSERT INTO tblpurchase_request_detail
                    (no_pr, nobaris, no_item, nama_item, quantity, satuan, keterangan)
                    VALUES
                    ('".mysqli_real_escape_string($koneksi,$row['no_pr'])."',
                     ".((int)$row['nobaris']).",
                     '".mysqli_real_escape_string($koneksi,$row['no_item'])."',
                     '".mysqli_real_escape_string($koneksi,$row['nama_item'])."',
                     ".((int)$row['quantity']).",
                     '".mysqli_real_escape_string($koneksi,$row['satuan'])."',
                     '".mysqli_real_escape_string($koneksi,isset($row['keterangan'])?$row['keterangan']:'')."')");
            }

            mysqli_commit($koneksi);
            $msg = "Berhasil migrasi $migrated PR dari tblpr_* ke tblpurchase_request_*";
            $msg_type = 'success';

            // Refresh analysis
            $analysis['pr']['new_header_count'] = getRowCount($koneksi, 'tblpurchase_request_header');
            $analysis['pr']['new_detail_count'] = getRowCount($koneksi, 'tblpurchase_request_detail');

        } catch(Exception $e){
            mysqli_rollback($koneksi);
            $msg = "Error migrasi: ".$e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $msg = "Tidak ada data untuk dimigrasi dari tblpr_*";
        $msg_type = 'warning';
    }
}

if($action == 'migrate_do' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    if($analysis['do']['old_header_exists'] && $analysis['do']['old_header_count'] > 0){
        mysqli_begin_transaction($koneksi);
        try {
            $migrated = 0;

            // Migrate header
            $q = mysqli_query($koneksi, "SELECT * FROM tbldo_header");
            while($row = mysqli_fetch_assoc($q)){
                // Check if already exists
                $check = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tbldelivery_order_header WHERE no_do='".mysqli_real_escape_string($koneksi,$row['no_do'])."'");
                $c = mysqli_fetch_assoc($check);
                if($c['c'] == 0){
                    mysqli_query($koneksi, "INSERT INTO tbldelivery_order_header
                        (no_do, no_po, no_supplier, tanggal_do, status_do, kd_cabang, created_by)
                        VALUES
                        ('".mysqli_real_escape_string($koneksi,$row['no_do'])."',
                         '".mysqli_real_escape_string($koneksi,$row['no_po'])."',
                         '".mysqli_real_escape_string($koneksi,$row['no_supplier'])."',
                         '".mysqli_real_escape_string($koneksi,$row['tanggal_do'])."',
                         '".mysqli_real_escape_string($koneksi,$row['status_do'])."',
                         '".mysqli_real_escape_string($koneksi,$row['kd_cabang'])."',
                         '".mysqli_real_escape_string($koneksi,isset($row['created_by'])?$row['created_by']:'migrated')."')");
                    $migrated++;
                }
            }

            // Migrate detail
            $q = mysqli_query($koneksi, "SELECT * FROM tbldo_detail");
            while($row = mysqli_fetch_assoc($q)){
                mysqli_query($koneksi, "INSERT INTO tbldelivery_order_detail
                    (no_do, nobaris, no_item, qty_po, qty_kirim, qty_terima)
                    VALUES
                    ('".mysqli_real_escape_string($koneksi,$row['no_do'])."',
                     ".((int)$row['nobaris']).",
                     '".mysqli_real_escape_string($koneksi,$row['no_item'])."',
                     ".((int)$row['qty_po']).",
                     ".((int)$row['qty_kirim']).",
                     ".((int)$row['qty_terima']).")");
            }

            mysqli_commit($koneksi);
            $msg = "Berhasil migrasi $migrated DO dari tbldo_* ke tbldelivery_order_*";
            $msg_type = 'success';

            // Refresh analysis
            $analysis['do']['new_header_count'] = getRowCount($koneksi, 'tbldelivery_order_header');
            $analysis['do']['new_detail_count'] = getRowCount($koneksi, 'tbldelivery_order_detail');

        } catch(Exception $e){
            mysqli_rollback($koneksi);
            $msg = "Error migrasi: ".$e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $msg = "Tidak ada data untuk dimigrasi dari tbldo_*";
        $msg_type = 'warning';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Migration - Duplicate Tables</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
<div class="main-container" style="padding:20px;">
    <div class="page-content">
        <div class="page-header">
            <h1><i class="fa fa-database"></i> Analisis &amp; Migrasi Tabel Duplikat</h1>
        </div>

        <?php if($msg){ ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php } ?>

        <div class="alert alert-warning">
            <i class="fa fa-warning"></i> <strong>PERINGATAN:</strong> Pastikan Anda sudah backup database sebelum melakukan migrasi!
        </div>

        <!-- PR Tables Analysis -->
        <div class="widget-box">
            <div class="widget-header widget-header-blue">
                <h4 class="widget-title"><i class="fa fa-table"></i> Purchase Request (PR) Tables</h4>
            </div>
            <div class="widget-body">
                <div class="widget-main">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Exists</th>
                                <th>Row Count</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="<?php echo $analysis['pr']['old_header_exists']?'warning':''; ?>">
                                <td><code><?php echo $analysis['pr']['old_header']; ?></code> (OLD)</td>
                                <td><?php echo $analysis['pr']['old_header_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-default">No</span>'; ?></td>
                                <td><?php echo $analysis['pr']['old_header_count']; ?></td>
                                <td><?php echo $analysis['pr']['old_header_count']>0?'<span class="label label-warning">Has Data - Needs Migration</span>':'<span class="label label-default">Empty</span>'; ?></td>
                            </tr>
                            <tr class="<?php echo $analysis['pr']['old_detail_exists']?'warning':''; ?>">
                                <td><code><?php echo $analysis['pr']['old_detail']; ?></code> (OLD)</td>
                                <td><?php echo $analysis['pr']['old_detail_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-default">No</span>'; ?></td>
                                <td><?php echo $analysis['pr']['old_detail_count']; ?></td>
                                <td><?php echo $analysis['pr']['old_detail_count']>0?'<span class="label label-warning">Has Data - Needs Migration</span>':'<span class="label label-default">Empty</span>'; ?></td>
                            </tr>
                            <tr class="success">
                                <td><code><?php echo $analysis['pr']['new_header']; ?></code> (ACTIVE)</td>
                                <td><?php echo $analysis['pr']['new_header_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-danger">Missing!</span>'; ?></td>
                                <td><?php echo $analysis['pr']['new_header_count']; ?></td>
                                <td><span class="label label-success">Active Table</span></td>
                            </tr>
                            <tr class="success">
                                <td><code><?php echo $analysis['pr']['new_detail']; ?></code> (ACTIVE)</td>
                                <td><?php echo $analysis['pr']['new_detail_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-danger">Missing!</span>'; ?></td>
                                <td><?php echo $analysis['pr']['new_detail_count']; ?></td>
                                <td><span class="label label-success">Active Table</span></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if($analysis['pr']['old_header_count'] > 0 || $analysis['pr']['old_detail_count'] > 0){ ?>
                    <form method="POST" action="?action=migrate_pr" onsubmit="return confirm('Yakin ingin migrasi data PR? Pastikan sudah backup database!');">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-exchange"></i> Migrate PR Data (<?php echo $analysis['pr']['old_header_count']; ?> records)
                        </button>
                    </form>
                    <?php } else { ?>
                    <p class="text-success"><i class="fa fa-check"></i> Tidak ada data yang perlu dimigrasi</p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- DO Tables Analysis -->
        <div class="widget-box">
            <div class="widget-header widget-header-blue">
                <h4 class="widget-title"><i class="fa fa-table"></i> Delivery Order (DO) Tables</h4>
            </div>
            <div class="widget-body">
                <div class="widget-main">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Exists</th>
                                <th>Row Count</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="<?php echo $analysis['do']['old_header_exists']?'warning':''; ?>">
                                <td><code><?php echo $analysis['do']['old_header']; ?></code> (OLD)</td>
                                <td><?php echo $analysis['do']['old_header_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-default">No</span>'; ?></td>
                                <td><?php echo $analysis['do']['old_header_count']; ?></td>
                                <td><?php echo $analysis['do']['old_header_count']>0?'<span class="label label-warning">Has Data - Needs Migration</span>':'<span class="label label-default">Empty</span>'; ?></td>
                            </tr>
                            <tr class="<?php echo $analysis['do']['old_detail_exists']?'warning':''; ?>">
                                <td><code><?php echo $analysis['do']['old_detail']; ?></code> (OLD)</td>
                                <td><?php echo $analysis['do']['old_detail_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-default">No</span>'; ?></td>
                                <td><?php echo $analysis['do']['old_detail_count']; ?></td>
                                <td><?php echo $analysis['do']['old_detail_count']>0?'<span class="label label-warning">Has Data - Needs Migration</span>':'<span class="label label-default">Empty</span>'; ?></td>
                            </tr>
                            <tr class="success">
                                <td><code><?php echo $analysis['do']['new_header']; ?></code> (ACTIVE)</td>
                                <td><?php echo $analysis['do']['new_header_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-danger">Missing!</span>'; ?></td>
                                <td><?php echo $analysis['do']['new_header_count']; ?></td>
                                <td><span class="label label-success">Active Table</span></td>
                            </tr>
                            <tr class="success">
                                <td><code><?php echo $analysis['do']['new_detail']; ?></code> (ACTIVE)</td>
                                <td><?php echo $analysis['do']['new_detail_exists']?'<span class="label label-success">Yes</span>':'<span class="label label-danger">Missing!</span>'; ?></td>
                                <td><?php echo $analysis['do']['new_detail_count']; ?></td>
                                <td><span class="label label-success">Active Table</span></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if($analysis['do']['old_header_count'] > 0 || $analysis['do']['old_detail_count'] > 0){ ?>
                    <form method="POST" action="?action=migrate_do" onsubmit="return confirm('Yakin ingin migrasi data DO? Pastikan sudah backup database!');">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-exchange"></i> Migrate DO Data (<?php echo $analysis['do']['old_header_count']; ?> records)
                        </button>
                    </form>
                    <?php } else { ?>
                    <p class="text-success"><i class="fa fa-check"></i> Tidak ada data yang perlu dimigrasi</p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="widget-box">
            <div class="widget-header widget-header-flat">
                <h4 class="widget-title"><i class="fa fa-lightbulb-o"></i> Rekomendasi</h4>
            </div>
            <div class="widget-body">
                <div class="widget-main">
                    <h4>Tabel yang Aktif Digunakan:</h4>
                    <ul>
                        <li><strong>PR:</strong> <code>tblpurchase_request_header</code>, <code>tblpurchase_request_detail</code></li>
                        <li><strong>PO:</strong> <code>tblorder_header</code>, <code>tblorder_detail</code> (disarankan rename ke tblpo_*)</li>
                        <li><strong>DO:</strong> <code>tbldelivery_order_header</code>, <code>tbldelivery_order_detail</code>, <code>tbldo_tracking</code></li>
                    </ul>

                    <h4>Langkah Selanjutnya:</h4>
                    <ol>
                        <li>Backup database terlebih dahulu</li>
                        <li>Jalankan migrasi untuk tabel yang memiliki data</li>
                        <li>Verifikasi data sudah ter-migrasi dengan benar</li>
                        <li>Setelah verifikasi, tabel lama (tblpr_*, tbldo_*) dapat di-rename/drop jika sudah tidak diperlukan</li>
                        <li>Update referensi PHP jika ada yang masih menggunakan tabel lama</li>
                    </ol>

                    <h4>SQL untuk Drop Old Tables (jalankan setelah migrasi & verifikasi):</h4>
                    <pre style="background:#f5f5f5; padding:10px; border:1px solid #ddd;">
-- PERINGATAN: Hanya jalankan setelah backup dan verifikasi migrasi!
-- DROP TABLE IF EXISTS tblpr_header;
-- DROP TABLE IF EXISTS tblpr_detail;
-- DROP TABLE IF EXISTS tbldo_header;
-- DROP TABLE IF EXISTS tbldo_detail;
                    </pre>
                </div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <a href="index.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </div>
</div>

<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
