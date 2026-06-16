<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";

// Get user info
$cari_kd = mysqli_query($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari = mysqli_fetch_array($cari_kd);
$_nama = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'];
if($foto_user=='') {
    $foto_user="file_upload/avatar.png";
}

// Get tipe_cabang untuk isolasi data per cabang
$cari_cab = mysqli_query($koneksi, "SELECT tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$tm_cab = mysqli_fetch_array($cari_cab);
$tipe_cabang = $tm_cab['tipe_cabang'] ?? '';

// Get filter parameters
$tanggal_dari    = $_GET['tgl_dari'] ?? date('Y-m-01');
$tanggal_sampai  = $_GET['tgl_sampai'] ?? date('Y-m-d');
$kategori_filter = $_GET['kategori'] ?? '';
$kd_cabang_safe  = mysqli_real_escape_string($koneksi, $kd_cabang);
$tgl_dari_safe   = mysqli_real_escape_string($koneksi, $tanggal_dari);
$tgl_sampai_safe = mysqli_real_escape_string($koneksi, $tanggal_sampai);

// Build WHERE untuk view_laporan_cancel_servis (kolom unqualified, view sudah punya kd_cabang)
$view_conds = ["DATE(tanggal_cancel) BETWEEN '$tgl_dari_safe' AND '$tgl_sampai_safe'"];
// Subquery cabang untuk query langsung ke tb_log_cancel_servis (tabel itu tidak punya kd_cabang)
$cabang_inline = $tipe_cabang !== 'Pusat'
    ? " AND no_service IN (SELECT no_service FROM tblservice WHERE kd_cabang = '$kd_cabang_safe')"
    : '';

if (!empty($kategori_filter)) {
    $kat_safe = mysqli_real_escape_string($koneksi, $kategori_filter);
    $view_conds[] = "kategori_alasan = '$kat_safe'";
}
if ($tipe_cabang !== 'Pusat') {
    $view_conds[] = "kd_cabang = '$kd_cabang_safe'";
}

$query_where_view = "WHERE " . implode(" AND ", $view_conds);

// Get data cancel
$query_cancel = mysqli_query($koneksi, "
    SELECT * FROM view_laporan_cancel_servis
    $query_where_view
    ORDER BY tanggal_cancel DESC
");

// Get statistik dari view agar konsisten dengan filter kd_cabang
$query_stats = mysqli_query($koneksi, "
    SELECT
        COUNT(*) as total_cancel,
        SUM(biaya_cancel) as total_biaya_cancel,
        SUM(nominal_dp) as total_dp,
        SUM(nominal_refund) as total_refund,
        AVG(biaya_cancel) as avg_biaya_cancel
    FROM view_laporan_cancel_servis
    $query_where_view
");
$stats = mysqli_fetch_array($query_stats);

// Get top alasan (tb_log_cancel_servis tidak punya kd_cabang, filter cabang via subquery)
$query_top_alasan = mysqli_query($koneksi, "
    SELECT
        kategori_alasan,
        COUNT(*) as jumlah,
        ROUND((COUNT(*) * 100.0 / (
            SELECT COUNT(*) FROM tb_log_cancel_servis
            WHERE DATE(tanggal_cancel) BETWEEN '$tgl_dari_safe' AND '$tgl_sampai_safe'
            $cabang_inline
        )), 2) as persentase
    FROM tb_log_cancel_servis
    WHERE DATE(tanggal_cancel) BETWEEN '$tgl_dari_safe' AND '$tgl_sampai_safe'
    $cabang_inline
    GROUP BY kategori_alasan
    ORDER BY jumlah DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Laporan Cancel Servis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />

    <style>
        .stat-box {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
        }
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        .print-only { display: none; }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small>
                        <i class="fa fa-leaf"></i>
                        <?php include "../lib/subtitel.php"; ?>
                    </small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_dashboard.php"; ?>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li><a href="#">Laporan</a></li>
                        <li class="active">Cancel Servis</li>
                    </ul>
                </div>

                <div class="page-content">
                    <!-- Header -->
                    <div class="page-header no-print">
                        <h1>
                            <i class="fa fa-ban red"></i>
                            Laporan Service yang Dibatalkan
                            <small class="text-muted">
                                Periode: <?php echo date('d/m/Y', strtotime($tanggal_dari)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_sampai)); ?>
                            </small>
                        </h1>
                    </div>

                    <!-- Filter Form -->
                    <div class="row no-print">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-filter"></i> Filter Laporan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="GET" class="form-inline">
                                            <div class="form-group">
                                                <label>Tanggal Dari:</label>
                                                <input type="date" name="tgl_dari" class="form-control" value="<?php echo $tanggal_dari; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Sampai:</label>
                                                <input type="date" name="tgl_sampai" class="form-control" value="<?php echo $tanggal_sampai; ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Kategori:</label>
                                                <select name="kategori" class="form-control">
                                                    <option value="">-- Semua Kategori --</option>
                                                    <option value="customer_request" <?php echo $kategori_filter == 'customer_request' ? 'selected' : ''; ?>>Customer Request</option>
                                                    <option value="no_stock" <?php echo $kategori_filter == 'no_stock' ? 'selected' : ''; ?>>No Stock</option>
                                                    <option value="no_mekanik" <?php echo $kategori_filter == 'no_mekanik' ? 'selected' : ''; ?>>No Mekanik</option>
                                                    <option value="customer_no_show" <?php echo $kategori_filter == 'customer_no_show' ? 'selected' : ''; ?>>Customer No Show</option>
                                                    <option value="lainnya" <?php echo $kategori_filter == 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Filter
                                            </button>
                                            <button type="button" class="btn btn-success" onclick="window.print()">
                                                <i class="fa fa-print"></i> Print
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistik -->
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $stats['total_cancel'] ?? 0; ?></div>
                                <div class="stat-label">Total Cancel</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="stat-box">
                                <div class="stat-number">Rp <?php echo number_format($stats['total_biaya_cancel'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="stat-label">Total Biaya Cancel</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="stat-box">
                                <div class="stat-number">Rp <?php echo number_format($stats['total_refund'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="stat-label">Total Refund</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="stat-box">
                                <div class="stat-number">Rp <?php echo number_format($stats['avg_biaya_cancel'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="stat-label">Rata-rata Biaya</div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Alasan -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-pie-chart"></i> Top Alasan Pembatalan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Kategori</th>
                                                    <th class="text-right">Jumlah</th>
                                                    <th class="text-right">Persentase</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($row = mysqli_fetch_array($query_top_alasan)): ?>
                                                <tr>
                                                    <td><?php echo ucwords(str_replace('_', ' ', $row['kategori_alasan'])); ?></td>
                                                    <td class="text-right"><?php echo $row['jumlah']; ?></td>
                                                    <td class="text-right">
                                                        <strong><?php echo $row['persentase']; ?>%</strong>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Data -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-table"></i> Detail Pembatalan Service</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>No Service</th>
                                                        <th>Tanggal Service</th>
                                                        <th>Tanggal Cancel</th>
                                                        <th>Customer</th>
                                                        <th>No Polisi</th>
                                                        <th>Kategori</th>
                                                        <th>Alasan</th>
                                                        <th class="text-right">Biaya Cancel</th>
                                                        <th class="text-right">Refund</th>
                                                        <th>User</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    if(mysqli_num_rows($query_cancel) > 0) {
                                                        while($row = mysqli_fetch_array($query_cancel)):
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td><strong><?php echo $row['no_service']; ?></strong></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_service'] . ' ' . $row['jam_service'])); ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_cancel'])); ?></td>
                                                        <td>
                                                            <?php echo $row['namapelanggan']; ?><br>
                                                            <small class="text-muted"><?php echo $row['nopelanggan']; ?></small>
                                                        </td>
                                                        <td>
                                                            <?php echo $row['no_polisi']; ?><br>
                                                            <small class="text-muted"><?php echo $row['jenis_kendaraan'] . ' ' . $row['merek_kendaraan']; ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="label label-warning">
                                                                <?php echo ucwords(str_replace('_', ' ', $row['kategori_alasan'])); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo substr($row['alasan_cancel'], 0, 50) . (strlen($row['alasan_cancel']) > 50 ? '...' : ''); ?></td>
                                                        <td class="text-right">Rp <?php echo number_format($row['biaya_cancel'], 0, ',', '.'); ?></td>
                                                        <td class="text-right">
                                                            <strong class="text-success">Rp <?php echo number_format($row['nominal_refund'], 0, ',', '.'); ?></strong>
                                                        </td>
                                                        <td><?php echo $row['user_cancel']; ?></td>
                                                    </tr>
                                                    <?php
                                                        endwhile;
                                                    } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="11" class="text-center text-muted">
                                                            <i class="fa fa-info-circle"></i> Tidak ada data pembatalan pada periode ini
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
