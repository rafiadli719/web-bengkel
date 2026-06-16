<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";
require_once "_include_access_sync.php";

$stmtUser = mysqli_prepare($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmtUser, 's', $id_user);
mysqli_stmt_execute($stmtUser);
$userResult = mysqli_stmt_get_result($stmtUser);
$userRow = mysqli_fetch_assoc($userResult);
mysqli_stmt_close($stmtUser);

$_nama = $userRow ? $userRow['nama_user'] : 'User';
$foto_user = $userRow && !empty($userRow['foto_user']) ? $userRow['foto_user'] : "file_upload/avatar.png";

$selectedCabang = isset($_GET['kd_cabang']) ? strtoupper(trim($_GET['kd_cabang'])) : '';
$selectedDataset = isset($_GET['dataset']) ? trim($_GET['dataset']) : 'all';
$selectedFrom = accessSyncNormalizeDate(isset($_GET['date_from']) ? $_GET['date_from'] : '');
$selectedTo = accessSyncNormalizeDate(isset($_GET['date_to']) ? $_GET['date_to'] : '');

$allowedDatasets = ['all', 'pembelian', 'penjualan', 'service'];
if (!in_array($selectedDataset, $allowedDatasets, true)) {
    $selectedDataset = 'all';
}

$filters = [
    'kd_cabang' => $selectedCabang,
    'date_from' => $selectedFrom,
    'date_to' => $selectedTo
];

$cabangOptions = accessSyncFetchCabangOptions($koneksi);
$metrics = accessSyncFetchConsolidationMetrics($koneksi, $filters);
$cabangSummary = accessSyncFetchCabangConsolidationSummary($koneksi, $filters);

$recentRows = [];
$datasetsToShow = $selectedDataset === 'all' ? ['pembelian', 'penjualan', 'service'] : [$selectedDataset];
$metricsToRender = $selectedDataset === 'all' ? $metrics : [$selectedDataset => $metrics[$selectedDataset]];
foreach ($datasetsToShow as $datasetKey) {
    $recentRows[$datasetKey] = accessSyncFetchRecentConsolidatedRows($koneksi, $datasetKey, $filters, 12);
}

$grandTotal = 0;
$grandRows = 0;
foreach ($metricsToRender as $metric) {
    $grandTotal += $metric['total_amount'];
    $grandRows += $metric['total_rows'];
}

function accessSyncFormatRupiah($value) {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function accessSyncFormatTanggalTampil($value) {
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .metric-card {
            border: 1px solid #e5e5e5;
            border-left: 4px solid #438eb9;
            border-radius: 12px;
            background: #fff;
            padding: 18px;
            margin-bottom: 18px;
            min-height: 128px;
        }
        .metric-card h5 {
            margin: 0 0 8px;
            color: #777;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .metric-card .metric-value {
            font-size: 30px;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.15;
        }
        .metric-card .metric-help {
            margin-top: 8px;
            color: #888;
            font-size: 12px;
        }
        .section-box {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 22px;
        }
        .table > thead > tr > th {
            white-space: nowrap;
        }
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
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo htmlspecialchars($foto_user); ?>" alt="User Profil" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo htmlspecialchars($_nama); ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li><a href="access-sync.php">Access Sync</a></li>
                        <li class="active">Laporan Konsolidasi</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Laporan Konsolidasi Access
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Monitoring hasil merge staging ke tabel konsolidasi
                            </small>
                        </h1>
                    </div>

                    <div class="section-box">
                        <form method="get" class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>Cabang</label>
                                    <select name="kd_cabang" class="form-control">
                                        <option value="">Semua Cabang</option>
                                        <?php foreach ($cabangOptions as $cabang) { ?>
                                        <option value="<?php echo htmlspecialchars($cabang['kode_cabang']); ?>" <?php echo $selectedCabang === $cabang['kode_cabang'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cabang['kode_cabang'] . ' - ' . $cabang['nama_cabang']); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>Dataset</label>
                                    <select name="dataset" class="form-control">
                                        <option value="all" <?php echo $selectedDataset === 'all' ? 'selected' : ''; ?>>Semua Dataset</option>
                                        <option value="pembelian" <?php echo $selectedDataset === 'pembelian' ? 'selected' : ''; ?>>Pembelian</option>
                                        <option value="penjualan" <?php echo $selectedDataset === 'penjualan' ? 'selected' : ''; ?>>Penjualan</option>
                                        <option value="service" <?php echo $selectedDataset === 'service' ? 'selected' : ''; ?>>Service</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Dari Tanggal</label>
                                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($selectedFrom); ?>">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>Sampai Tanggal</label>
                                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($selectedTo); ?>">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-filter"></i> Filter
                                        </button>
                                        <a href="access-sync-report.php" class="btn btn-default">
                                            <i class="fa fa-refresh"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">
                            <div class="metric-card">
                                <h5>Total Nilai Gabungan</h5>
                                <div class="metric-value"><?php echo accessSyncFormatRupiah($grandTotal); ?></div>
                                <div class="metric-help"><?php echo number_format($grandRows, 0, ',', '.'); ?> transaksi/service terkonsolidasi</div>
                            </div>
                        </div>
                        <?php foreach ($metricsToRender as $metric) { ?>
                        <div class="col-sm-3">
                            <div class="metric-card">
                                <h5><?php echo htmlspecialchars($metric['label']); ?></h5>
                                <div class="metric-value"><?php echo accessSyncFormatRupiah($metric['total_amount']); ?></div>
                                <div class="metric-help">
                                    <?php echo number_format($metric['total_rows'], 0, ',', '.'); ?> data
                                    <?php if (!empty($metric['last_date'])) { ?>
                                        , update terakhir <?php echo htmlspecialchars(accessSyncFormatTanggalTampil($metric['last_date'])); ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <div class="section-box">
                        <div class="clearfix">
                            <h4 class="pull-left"><i class="fa fa-sitemap"></i> Ringkasan per Cabang</h4>
                            <a href="access-sync.php" class="btn btn-info btn-sm pull-right"><i class="fa fa-upload"></i> Kembali ke Uploader</a>
                        </div>
                        <div class="table-responsive" style="margin-top: 15px;">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <?php if ($selectedDataset === 'all') { ?>
                                    <tr>
                                        <th>Kode Cabang</th>
                                        <th>Pembelian</th>
                                        <th>Nilai Pembelian</th>
                                        <th>Penjualan</th>
                                        <th>Nilai Penjualan</th>
                                        <th>Service</th>
                                        <th>Nilai Service</th>
                                    </tr>
                                    <?php } else { ?>
                                    <tr>
                                        <th>Kode Cabang</th>
                                        <th>Total Data</th>
                                        <th>Total Nilai</th>
                                    </tr>
                                    <?php } ?>
                                </thead>
                                <tbody>
                                    <?php if (empty($cabangSummary)) { ?>
                                    <tr>
                                        <td colspan="<?php echo $selectedDataset === 'all' ? '7' : '3'; ?>" class="text-center text-muted">Belum ada data konsolidasi untuk filter ini.</td>
                                    </tr>
                                    <?php } else { ?>
                                        <?php foreach ($cabangSummary as $row) { ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['kd_cabang']); ?></strong></td>
                                            <?php if ($selectedDataset === 'all') { ?>
                                            <td><?php echo number_format($row['pembelian_rows'], 0, ',', '.'); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row['pembelian_amount']); ?></td>
                                            <td><?php echo number_format($row['penjualan_rows'], 0, ',', '.'); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row['penjualan_amount']); ?></td>
                                            <td><?php echo number_format($row['service_rows'], 0, ',', '.'); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row['service_amount']); ?></td>
                                            <?php } else { ?>
                                            <td><?php echo number_format($row[$selectedDataset . '_rows'], 0, ',', '.'); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row[$selectedDataset . '_amount']); ?></td>
                                            <?php } ?>
                                        </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php foreach ($datasetsToShow as $datasetKey) { ?>
                    <div class="section-box">
                        <h4>
                            <i class="fa fa-table"></i>
                            Data Terbaru <?php echo htmlspecialchars(ucfirst($datasetKey)); ?>
                        </h4>
                        <div class="table-responsive" style="margin-top: 15px;">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <?php if ($datasetKey === 'pembelian') { ?>
                                    <tr>
                                        <th>No Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Cabang</th>
                                        <th>Supplier</th>
                                        <th>Total Qty</th>
                                        <th>Total Akhir</th>
                                        <th>Status</th>
                                    </tr>
                                    <?php } elseif ($datasetKey === 'penjualan') { ?>
                                    <tr>
                                        <th>No Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Cabang</th>
                                        <th>Pelanggan</th>
                                        <th>Total Qty</th>
                                        <th>Total Akhir</th>
                                        <th>Status</th>
                                    </tr>
                                    <?php } else { ?>
                                    <tr>
                                        <th>No Service</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Cabang</th>
                                        <th>Pelanggan</th>
                                        <th>No Polisi</th>
                                        <th>Total Akhir</th>
                                        <th>Status</th>
                                    </tr>
                                    <?php } ?>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentRows[$datasetKey])) { ?>
                                    <tr>
                                        <td colspan="<?php echo $datasetKey === 'service' ? '8' : '7'; ?>" class="text-center text-muted">Belum ada data.</td>
                                    </tr>
                                    <?php } else { ?>
                                        <?php foreach ($recentRows[$datasetKey] as $row) { ?>
                                        <tr>
                                            <?php if ($datasetKey === 'pembelian') { ?>
                                            <td><?php echo htmlspecialchars($row['no_transaksi']); ?></td>
                                            <td><?php echo htmlspecialchars(accessSyncFormatTanggalTampil($row['tanggal'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['kd_cabang']); ?></td>
                                            <td><?php echo htmlspecialchars($row['no_supplier']); ?></td>
                                            <td><?php echo number_format((float) $row['total_qty'], 0, ',', '.'); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row['total_akhir']); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <?php } elseif ($datasetKey === 'penjualan') { ?>
                                            <td><?php echo htmlspecialchars($row['no_transaksi']); ?></td>
                                            <td><?php echo htmlspecialchars(accessSyncFormatTanggalTampil($row['tanggal'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['kd_cabang']); ?></td>
                                            <td><?php echo htmlspecialchars($row['no_pelanggan']); ?></td>
                                            <td><?php echo number_format((float) $row['total_qty'], 0, ',', '.'); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row['total_akhir']); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <?php } else { ?>
                                            <td><?php echo htmlspecialchars($row['no_service']); ?></td>
                                            <td><?php echo htmlspecialchars(accessSyncFormatTanggalTampil($row['tanggal'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['jam']); ?></td>
                                            <td><?php echo htmlspecialchars($row['kd_cabang']); ?></td>
                                            <td><?php echo htmlspecialchars($row['no_pelanggan']); ?></td>
                                            <td><?php echo htmlspecialchars($row['no_polisi']); ?></td>
                                            <td><?php echo accessSyncFormatRupiah($row['total_akhir']); ?></td>
                                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                                            <?php } ?>
                                        </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
