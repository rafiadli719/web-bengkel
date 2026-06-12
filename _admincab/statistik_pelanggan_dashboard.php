<?php
/**
 * DASHBOARD STATISTIK PELANGGAN
 * File: statistik_pelanggan_dashboard.php
 * Deskripsi: Dashboard untuk menampilkan statistik pelanggan real-time
 * Dibuat: 2 November 2025
 */

session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    
    // Get user info
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user 
                                        FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'] ?? '';
    $pwd = $tm_cari['password'] ?? '';
    $lvl_akses = $tm_cari['user_akses'] ?? '';
    $foto_user = $tm_cari['foto_user'] ?? '';
    if($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

    // Get cabang info
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'] ?? '';
    $tipe_cabang = $tm_cari['tipe_cabang'] ?? '';

// Get statistik summary
$query_summary = "SELECT 
    COUNT(*) as total_pelanggan,
    SUM(total_nominal) as total_pendapatan,
    AVG(rata_rata_transaksi) as avg_transaksi,
    SUM(CASE WHEN status_member = 'Bronze' THEN 1 ELSE 0 END) as bronze_count,
    SUM(CASE WHEN status_member = 'Silver' THEN 1 ELSE 0 END) as silver_count,
    SUM(CASE WHEN status_member = 'Gold' THEN 1 ELSE 0 END) as gold_count,
    SUM(CASE WHEN status_member = 'Platinum' THEN 1 ELSE 0 END) as platinum_count,
    SUM(CASE WHEN lama_tidak_datang > 30 THEN 1 ELSE 0 END) as perlu_followup
FROM statistik_pelanggan";

$result_summary = mysqli_query($koneksi, $query_summary);
$summary = mysqli_fetch_array($result_summary);

// Handle refresh statistik
if(isset($_POST['btn_refresh_statistik'])) {
    // Statistik otomatis di-update oleh trigger, tidak perlu manual refresh
    // Jika ingin force refresh, bisa uncomment baris berikut setelah SP dibuat:
    // mysqli_query($koneksi, "CALL sp_refresh_statistik_pelanggan()");
    echo "<script>alert('Statistik pelanggan selalu up-to-date otomatis via trigger!'); window.location='statistik_pelanggan_dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Dashboard Statistik Pelanggan" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <!-- Bootstrap & FontAwesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    
    <script src="assets/js/ace-extra.min.js"></script>
    
    <style>
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .member-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-bronze { background-color: #CD7F32; }
        .badge-silver { background-color: #C0C0C0; }
        .badge-gold { background-color: #FFD700; color: #000; }
        .badge-platinum { background-color: #E5E4E2; color: #000; }
    </style>
</head>

<body class="no-skin">
    <?php include "lib/header.php"; ?>
    
    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>
        
        <?php include "lib/sidebar.php"; ?>
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li class="active">Dashboard Statistik Pelanggan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <!-- Summary Cards -->
                    <div class="row">
                        <div class="col-xs-12 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-label"><i class="fa fa-users"></i> Total Pelanggan</div>
                                <div class="stat-number text-primary"><?php echo number_format($summary['total_pelanggan']); ?></div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-label"><i class="fa fa-money"></i> Total Pendapatan</div>
                                <div class="stat-number text-success">Rp <?php echo number_format($summary['total_pendapatan'], 0, ',', '.'); ?></div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-label"><i class="fa fa-calculator"></i> Rata-rata Transaksi</div>
                                <div class="stat-number text-info">Rp <?php echo number_format($summary['avg_transaksi'], 0, ',', '.'); ?></div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <div class="stat-card">
                                <div class="stat-label"><i class="fa fa-bell"></i> Perlu Follow Up</div>
                                <div class="stat-number text-warning"><?php echo number_format($summary['perlu_followup']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Member Distribution -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat">
                                    <h4 class="widget-title"><i class="fa fa-trophy"></i> Distribusi Member</h4>
                                    <div class="widget-toolbar">
                                        <form method="post" style="display:inline;">
                                            <button type="submit" name="btn_refresh_statistik" class="btn btn-xs btn-info">
                                                <i class="fa fa-refresh"></i> Refresh Statistik
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main padding-12">
                                        <div class="row">
                                            <div class="col-xs-3 text-center">
                                                <span class="member-badge badge-bronze">BRONZE</span>
                                                <h3><?php echo $summary['bronze_count']; ?></h3>
                                            </div>
                                            <div class="col-xs-3 text-center">
                                                <span class="member-badge badge-silver">SILVER</span>
                                                <h3><?php echo $summary['silver_count']; ?></h3>
                                            </div>
                                            <div class="col-xs-3 text-center">
                                                <span class="member-badge badge-gold">GOLD</span>
                                                <h3><?php echo $summary['gold_count']; ?></h3>
                                            </div>
                                            <div class="col-xs-3 text-center">
                                                <span class="member-badge badge-platinum">PLATINUM</span>
                                                <h3><?php echo $summary['platinum_count']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="tabbable">
                                <ul class="nav nav-tabs" id="myTab">
                                    <li class="active">
                                        <a data-toggle="tab" href="#tab-semua">
                                            <i class="fa fa-list"></i> Semua Pelanggan
                                        </a>
                                    </li>
                                    <li>
                                        <a data-toggle="tab" href="#tab-followup">
                                            <i class="fa fa-bell"></i> Perlu Follow Up
                                        </a>
                                    </li>
                                    <li>
                                        <a data-toggle="tab" href="#tab-top">
                                            <i class="fa fa-star"></i> Top Pelanggan
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Tab Semua Pelanggan -->
                                    <div id="tab-semua" class="tab-pane fade in active">
                                        <?php include "_template/_statistik_semua_pelanggan.php"; ?>
                                    </div>

                                    <!-- Tab Follow Up -->
                                    <div id="tab-followup" class="tab-pane fade">
                                        <?php include "_template/_statistik_followup_pelanggan.php"; ?>
                                    </div>

                                    <!-- Tab Top Pelanggan -->
                                    <div id="tab-top" class="tab-pane fade">
                                        <?php include "_template/_statistik_top_pelanggan.php"; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include "lib/footer.php"; ?>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script type="text/javascript">
        jQuery(function($) {
            // Enable Bootstrap tabs
            $('#myTab a').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
        });
    </script>
</body>
</html>

<?php
}
?>
