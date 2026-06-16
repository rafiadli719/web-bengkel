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

accessSyncEnsureRuntimeSchema($koneksi);
$summary = accessSyncFetchMonitorSummary($koneksi);
$datasetRows = accessSyncFetchMonitorDatasetSummary($koneksi, 12);
$cabangRows = accessSyncFetchMonitorCabangSummary($koneksi, 12);
$recentRuns = accessSyncFetchRecentRuns($koneksi, 40);
$recentErrors = accessSyncFetchRecentErrors($koneksi, 20);

$statusCounts = ['online' => 0, 'warning' => 0, 'offline' => 0, 'failed' => 0];
foreach ($cabangRows as $row) {
    $info = accessSyncMonitorStatusInfo($row['last_sync_at'] ?? '', $row['last_status'] ?? '');
    if (isset($statusCounts[$info['code']])) {
        $statusCounts[$info['code']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta http-equiv="refresh" content="120" />

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .monitor-stat {
            background: #fff;
            border: 1px solid #e6ebf1;
            border-left: 4px solid #438eb9;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .monitor-stat.success { border-left-color: #5cb85c; }
        .monitor-stat.warning { border-left-color: #f0ad4e; }
        .monitor-stat.danger { border-left-color: #d9534f; }
        .monitor-stat.info { border-left-color: #5bc0de; }
        .monitor-stat .label-mini {
            color: #7f8c9a;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        .monitor-stat .value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }
        .section-card {
            background: #fff;
            border: 1px solid #e6ebf1;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 18px;
        }
        .grid-cabang {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }
        .cabang-card {
            background: #fbfdff;
            border: 1px solid #dfe8f1;
            border-left: 4px solid #cfd9e3;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .cabang-card.online { border-left-color: #5cb85c; }
        .cabang-card.warning { border-left-color: #f0ad4e; }
        .cabang-card.offline { border-left-color: #999; }
        .cabang-card.failed { border-left-color: #d9534f; }
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-dot.online { background: #5cb85c; }
        .status-dot.warning { background: #f0ad4e; }
        .status-dot.offline { background: #999; }
        .status-dot.failed { background: #d9534f; }
        .mini-table td, .mini-table th {
            font-size: 12px;
            padding: 8px 10px;
        }
        .tag-soft {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 700;
        }
        .tag-success { background: #e7f7ee; color: #2e8b57; }
        .tag-warning { background: #fff3df; color: #ad6e00; }
        .tag-danger { background: #fde8e7; color: #c0392b; }
        .tag-info { background: #e8f4fb; color: #2b7dbd; }
        .muted-small { color: #7f8c9a; font-size: 12px; }
        .table-wrap {
            overflow-x: auto;
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
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
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
                        <li class="active">Monitor Sync Access</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Monitor Sync Otomatis Access
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Auto refresh 120 detik
                            </small>
                        </h1>
                        <div class="pull-right">
                            <a href="access-sync.php" class="btn btn-info"><i class="fa fa-upload"></i> Uploader</a>
                            <a href="access-sync-report.php" class="btn btn-primary"><i class="fa fa-line-chart"></i> Laporan Konsolidasi</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3">
                            <div class="monitor-stat info">
                                <div class="label-mini">Run Otomatis</div>
                                <div class="value"><?php echo number_format((int) ($summary['total_runs'] ?? 0), 0, ',', '.'); ?></div>
                                <div class="muted-small">Total histori auto sync tersimpan</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="monitor-stat success">
                                <div class="label-mini">Berhasil 24 Jam</div>
                                <div class="value"><?php echo number_format((int) ($summary['rows_success_24h'] ?? 0), 0, ',', '.'); ?></div>
                                <div class="muted-small">Row sukses 24 jam terakhir</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="monitor-stat warning">
                                <div class="label-mini">Run 24 Jam</div>
                                <div class="value"><?php echo number_format((int) ($summary['runs_24h'] ?? 0), 0, ',', '.'); ?></div>
                                <div class="muted-small">Eksekusi otomatis 24 jam terakhir</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="monitor-stat danger">
                                <div class="label-mini">Gagal 24 Jam</div>
                                <div class="value"><?php echo number_format((int) ($summary['rows_failed_24h'] ?? 0), 0, ',', '.'); ?></div>
                                <div class="muted-small">Row gagal 24 jam terakhir</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="section-card">
                                <div class="row">
                                    <div class="col-sm-3"><strong>Cabang Online:</strong> <?php echo (int) $statusCounts['online']; ?></div>
                                    <div class="col-sm-3"><strong>Terlambat:</strong> <?php echo (int) $statusCounts['warning']; ?></div>
                                    <div class="col-sm-3"><strong>Offline:</strong> <?php echo (int) $statusCounts['offline']; ?></div>
                                    <div class="col-sm-3"><strong>Run gagal terakhir:</strong> <?php echo (int) $statusCounts['failed']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h4><i class="fa fa-building"></i> Status Per Cabang</h4>
                        <div class="grid-cabang">
                            <?php foreach ($cabangRows as $row) {
                                $info = accessSyncMonitorStatusInfo($row['last_sync_at'] ?? '', $row['last_status'] ?? '');
                            ?>
                            <div class="cabang-card <?php echo htmlspecialchars($info['code']); ?>">
                                <div style="font-weight:700; font-size:15px;"><?php echo htmlspecialchars($row['nama_cabang']); ?></div>
                                <div class="muted-small"><?php echo htmlspecialchars($row['source_cabang']); ?></div>
                                <div style="margin:10px 0 8px;">
                                    <span class="status-dot <?php echo htmlspecialchars($info['code']); ?>"></span>
                                    <strong><?php echo htmlspecialchars($info['label']); ?></strong>
                                </div>
                                <div class="muted-small">Run: <?php echo number_format((int) $row['total_runs'], 0, ',', '.'); ?></div>
                                <div class="muted-small">Row sukses: <?php echo number_format((int) $row['success_rows'], 0, ',', '.'); ?></div>
                                <div class="muted-small">Row gagal: <?php echo number_format((int) $row['failed_rows'], 0, ',', '.'); ?></div>
                                <div class="muted-small">Last sync: <?php echo htmlspecialchars($row['last_sync_at'] ?: '-'); ?></div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-5">
                            <div class="section-card">
                                <h4><i class="fa fa-database"></i> Ringkasan Per Dataset</h4>
                                <div class="table-wrap">
                                    <table class="table table-striped table-bordered mini-table">
                                        <thead>
                                            <tr>
                                                <th>Dataset</th>
                                                <th>Run</th>
                                                <th>Update</th>
                                                <th>Gagal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($datasetRows as $row) { ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['dataset_label']); ?></strong><br>
                                                    <span class="muted-small"><?php echo htmlspecialchars($row['last_sync_at'] ?: '-'); ?></span>
                                                </td>
                                                <td><?php echo number_format((int) $row['total_runs'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php
                                                    $updatedCount = (int) $row['merge_updated'];
                                                    $upsertCount = (int) $row['merge_upserted'];
                                                    echo number_format($updatedCount > 0 ? $updatedCount : $upsertCount, 0, ',', '.');
                                                    ?>
                                                </td>
                                                <td><?php echo number_format((int) $row['failed_rows'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="section-card">
                                <h4><i class="fa fa-warning"></i> Error Terakhir</h4>
                                <div class="table-wrap">
                                    <table class="table table-striped table-bordered mini-table">
                                        <thead>
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Dataset</th>
                                                <th>Cabang</th>
                                                <th>Row Key</th>
                                                <th>Error</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentErrors)) { ?>
                                            <tr><td colspan="5" class="text-center">Belum ada error auto sync.</td></tr>
                                            <?php } ?>
                                            <?php foreach ($recentErrors as $row) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($row['dataset_label']); ?></td>
                                                <td><?php echo htmlspecialchars($row['source_cabang'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($row['row_key'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($row['error_message']); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h4><i class="fa fa-history"></i> Riwayat Run Otomatis</h4>
                        <div class="table-wrap">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Waktu</th>
                                        <th>Dataset</th>
                                        <th>Cabang</th>
                                        <th>Machine</th>
                                        <th>Status</th>
                                        <th>Rows</th>
                                        <th>Merge</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRuns as $row) { ?>
                                    <tr>
                                        <td>#<?php echo (int) $row['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['started_at']); ?><br>
                                            <span class="muted-small"><?php echo htmlspecialchars($row['finished_at'] ?: '-'); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['dataset_label']); ?></strong><br>
                                            <span class="muted-small"><?php echo htmlspecialchars($row['sync_mode']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['source_cabang'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['machine_name'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'tag-info';
                                            if ($row['status'] === 'success') { $statusClass = 'tag-success'; }
                                            elseif ($row['status'] === 'partial') { $statusClass = 'tag-warning'; }
                                            elseif ($row['status'] === 'failed') { $statusClass = 'tag-danger'; }
                                            ?>
                                            <span class="tag-soft <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                        </td>
                                        <td>
                                            total <?php echo number_format((int) $row['total_rows'], 0, ',', '.'); ?><br>
                                            <span class="muted-small">ok <?php echo number_format((int) $row['success_rows'], 0, ',', '.'); ?> | fail <?php echo number_format((int) $row['failed_rows'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['merge_status'] ?: '-'); ?><br>
                                            <span class="muted-small">
                                                proc <?php echo number_format((int) $row['merge_processed'], 0, ',', '.'); ?> |
                                                ins <?php echo number_format((int) $row['merge_inserted'], 0, ',', '.'); ?> |
                                                upd <?php echo number_format((int) $row['merge_updated'], 0, ',', '.'); ?> |
                                                ups <?php echo number_format((int) $row['merge_upserted'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['notes'] ?: '-'); ?></td>
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

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
