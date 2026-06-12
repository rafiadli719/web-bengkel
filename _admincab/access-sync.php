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

$stmtCabang = mysqli_prepare($koneksi, "SELECT nama_cabang FROM tbcabang WHERE kode_cabang = ? LIMIT 1");
mysqli_stmt_bind_param($stmtCabang, 's', $kd_cabang);
mysqli_stmt_execute($stmtCabang);
$cabangResult = mysqli_stmt_get_result($stmtCabang);
$cabangRow = mysqli_fetch_assoc($cabangResult);
mysqli_stmt_close($stmtCabang);
$nama_cabang = $cabangRow ? $cabangRow['nama_cabang'] : $kd_cabang;

$datasets = accessSyncDatasetConfigs();
$masterDatasets = accessSyncMasterDatasets();
$transactionDatasets = accessSyncTransactionDatasets();
$consolidationMetrics = accessSyncFetchConsolidationMetrics($koneksi);
$totalConsolidationAmount = 0;
$totalConsolidationRows = 0;
foreach ($consolidationMetrics as $metricRow) {
    $totalConsolidationAmount += $metricRow['total_amount'];
    $totalConsolidationRows += $metricRow['total_rows'];
}
$historyRows = [];
$qHistory = mysqli_query($koneksi, "SELECT id, source_name, source_file, sync_mode, started_at, finished_at, status, total_rows, success_rows, failed_rows, notes FROM sync_access_runs ORDER BY id DESC LIMIT 20");
if ($qHistory) {
    while ($row = mysqli_fetch_assoc($qHistory)) {
        $historyRows[] = $row;
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

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .upload-shell {
            border: 2px dashed #7aa6c2;
            border-radius: 14px;
            background: #f8fbff;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all .2s ease;
        }
        .upload-shell:hover, .upload-shell.dragover {
            border-color: #3f7fb2;
            background: #eef6fd;
        }
        .upload-shell i {
            font-size: 52px;
            color: #438eb9;
        }
        .summary-stat {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 15px;
        }
        .summary-stat .big {
            font-size: 24px;
            font-weight: 700;
        }
        .preview-box, .history-box {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 16px;
            margin-top: 20px;
        }
        .table-preview-wrap {
            max-height: 420px;
            overflow: auto;
        }
        .label-status-success { background: #5cb85c; }
        .label-status-partial { background: #f0ad4e; }
        .label-status-failed { background: #d9534f; }
        .label-status-running { background: #5bc0de; }
        .text-truncate-soft {
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: bottom;
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
                        <li class="active">Access Sync</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Access Sync & Uploader
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Cabang aktif: <?php echo htmlspecialchars($nama_cabang); ?> (<?php echo htmlspecialchars($kd_cabang); ?>)
                            </small>
                        </h1>
                        <div class="pull-right">
                            <a href="access-sync-monitor.php" class="btn btn-info">
                                <i class="fa fa-dashboard"></i> Monitor Sync Otomatis
                            </a>
                            <a href="access-sync-report.php" class="btn btn-primary">
                                <i class="fa fa-line-chart"></i> Buka Laporan Konsolidasi
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-4">
                            <div class="summary-stat">
                                <div class="text-muted">Dataset siap upload</div>
                                <div class="big"><?php echo count($datasets); ?></div>
                                <div><?php echo count($masterDatasets); ?> master, <?php echo count($transactionDatasets); ?> transaksi</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="summary-stat">
                                <div class="text-muted">Run tersimpan</div>
                                <div class="big"><?php echo count($historyRows); ?></div>
                                <div>Histori 20 proses terakhir</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="summary-stat">
                                <div class="text-muted">Nilai konsolidasi</div>
                                <div class="big">Rp <?php echo number_format($totalConsolidationAmount, 0, ',', '.'); ?></div>
                                <div><?php echo number_format($totalConsolidationRows, 0, ',', '.'); ?> data sudah masuk laporan</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12 col-lg-5">
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue">
                                    <h4 class="widget-title"><i class="fa fa-upload"></i> Uploader Data Access</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div id="actionMessage" class="alert" style="display:none;"></div>
                                        <div class="form-group">
                                            <label>Dataset</label>
                                            <select id="dataset" class="form-control">
                                                <?php foreach ($datasets as $key => $config) { ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($config['label']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Kode Cabang Sumber</label>
                                            <input type="text" class="form-control" id="sourceCabang" value="<?php echo htmlspecialchars($kd_cabang); ?>" maxlength="20">
                                        </div>

                                        <div class="form-group">
                                            <label>Mode Simpan</label>
                                            <select id="mode" class="form-control">
                                                <option value="preview">Preview Only</option>
                                                <option value="append">Append to Staging</option>
                                                <option value="replace">Replace Staging per Cabang</option>
                                            </select>
                                        </div>

                                        <div class="upload-shell" id="dropZone">
                                            <input type="file" id="sourceFile" accept=".csv,.xls,.xlsx" style="display:none;">
                                            <i class="fa fa-file-excel-o"></i>
                                            <h4>Pilih file export Access</h4>
                                            <p>Klik atau drag file `.csv`, `.xls`, atau `.xlsx` ke sini</p>
                                            <p class="text-muted"><small>Preview diproses di browser, simpan dikirim ke tabel staging.</small></p>
                                        </div>

                                        <div class="alert alert-info" id="fileInfo" style="display:none; margin-top:15px;">
                                            <strong>File:</strong> <span id="fileName"></span>
                                        </div>

                                        <div class="clearfix">
                                            <button class="btn btn-info" id="btnPreview" style="margin-top:15px;">
                                                <i class="fa fa-search"></i> Preview
                                            </button>
                                            <button class="btn btn-success pull-right" id="btnSave" style="margin-top:15px;" disabled>
                                                <i class="fa fa-save"></i> Simpan ke Staging
                                            </button>
                                        </div>

                                        <div class="alert alert-warning" style="margin-top:15px; margin-bottom:0;">
                                            <strong>Catatan:</strong> untuk tahap ini data masuk dulu ke tabel staging, belum merge ke tabel operasional.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 col-lg-7">
                            <div class="preview-box">
                                <h4><i class="fa fa-table"></i> Preview & Validasi</h4>
                                <div id="previewMessage" class="alert alert-info">
                                    Belum ada file yang dipreview.
                                </div>
                                <div class="row" id="summaryRow" style="display:none;">
                                    <div class="col-sm-4"><div class="summary-stat"><div class="text-muted">Total</div><div class="big" id="sumTotal">0</div></div></div>
                                    <div class="col-sm-4"><div class="summary-stat"><div class="text-muted">Valid</div><div class="big text-success" id="sumValid">0</div></div></div>
                                    <div class="col-sm-4"><div class="summary-stat"><div class="text-muted">Invalid</div><div class="big text-danger" id="sumInvalid">0</div></div></div>
                                </div>
                                <div class="table-preview-wrap">
                                    <table class="table table-striped table-bordered" id="previewTable" style="display:none;">
                                        <thead>
                                            <tr>
                                                <th>Baris</th>
                                                <th>Data Kunci</th>
                                                <th>Status</th>
                                                <th>Error</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="history-box">
                        <h4><i class="fa fa-history"></i> Histori Sync Terakhir</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Dataset</th>
                                        <th>File</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Success</th>
                                        <th>Failed</th>
                                        <th>Mulai</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($historyRows)) { ?>
                                    <tr><td colspan="9" class="text-center text-muted">Belum ada histori sync.</td></tr>
                                    <?php } else { foreach ($historyRows as $history) { ?>
                                    <tr>
                                        <td><?php echo (int) $history['id']; ?></td>
                                        <td><?php echo htmlspecialchars($history['source_name']); ?></td>
                                        <td><span class="text-truncate-soft"><?php echo htmlspecialchars($history['source_file']); ?></span></td>
                                        <td><span class="label label-status-<?php echo htmlspecialchars($history['status']); ?>"><?php echo htmlspecialchars($history['status']); ?></span></td>
                                        <td><?php echo (int) $history['total_rows']; ?></td>
                                        <td><?php echo (int) $history['success_rows']; ?></td>
                                        <td><?php echo (int) $history['failed_rows']; ?></td>
                                        <td><?php echo htmlspecialchars($history['started_at']); ?></td>
                                        <td>
                                            <button class="btn btn-xs btn-warning btn-view-errors" data-run-id="<?php echo (int) $history['id']; ?>">
                                                <i class="fa fa-exclamation-triangle"></i> Errors
                                            </button>
                                            <?php if (in_array($history['source_name'], $masterDatasets, true)) { ?>
                                            <button class="btn btn-xs btn-success btn-merge-master" data-run-id="<?php echo (int) $history['id']; ?>" data-dataset="<?php echo htmlspecialchars($history['source_name']); ?>">
                                                <i class="fa fa-random"></i> Merge
                                            </button>
                                            <?php } ?>
                                            <?php if (in_array($history['source_name'], $transactionDatasets, true)) { ?>
                                            <button class="btn btn-xs btn-info btn-merge-transaction" data-run-id="<?php echo (int) $history['id']; ?>" data-dataset="<?php echo htmlspecialchars($history['source_name']); ?>">
                                                <i class="fa fa-exchange"></i> Konsolidasi
                                            </button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php }} ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="errorsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Detail Error Sync</h4>
                </div>
                <div class="modal-body">
                    <div id="errorsContent" class="text-muted">Memuat data...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"
        onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';}else if(this.dataset.fallback==='1'){this.dataset.fallback='2';this.src='https://unpkg.com/xlsx@0.18.5/dist/xlsx.full.min.js';}"
    ></script>
    <script>
        var parsedRows = [];
        var selectedFile = null;

        function setFile(file) {
            selectedFile = file;
            if (!file) {
                $('#fileInfo').hide();
                $('#btnSave').prop('disabled', true);
                parsedRows = [];
                return;
            }
            $('#fileName').text(file.name + ' (' + Math.round(file.size / 1024) + ' KB)');
            $('#fileInfo').show();
        }

        function parseFile(file) {
            return new Promise(function(resolve, reject) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var data = e.target.result;
                        var workbook = XLSX.read(data, { type: 'array' });
                        var sheetName = workbook.SheetNames[0];
                        var rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { defval: '' });
                        resolve(rows);
                    } catch (err) {
                        reject(err);
                    }
                };
                reader.onerror = reject;
                reader.readAsArrayBuffer(file);
            });
        }

        function renderPreview(previewRows, summary) {
            $('#previewTable tbody').empty();
            $('#sumTotal').text(summary.total || 0);
            $('#sumValid').text(summary.valid || 0);
            $('#sumInvalid').text(summary.invalid || 0);
            $('#summaryRow').show();
            $('#previewTable').show();

            if (!previewRows.length) {
                $('#previewMessage').removeClass('alert-info').addClass('alert-warning').text('Tidak ada data preview.');
                return;
            }

            $('#previewMessage').removeClass('alert-warning').addClass('alert-info').text('Preview berhasil dibuat. Periksa baris invalid sebelum simpan.');

            previewRows.forEach(function(item) {
                var mapped = item.mapped || {};
                var keyValue = mapped.no_transaksi || mapped.no_service || mapped.no_item || mapped.no_supplier || mapped.no_mekanik || mapped.no_pelanggan || mapped.no_polisi || '-';
                var isInvalid = (item.errors || []).length > 0;
                var status = isInvalid ? '<span class="label label-danger">Invalid</span>' : '<span class="label label-success">Valid</span>';
                var errors = isInvalid ? item.errors.join('; ') : '-';
                $('#previewTable tbody').append(
                    '<tr>' +
                    '<td>' + item.row_number + '</td>' +
                    '<td>' + $('<div>').text(keyValue).html() + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' + $('<div>').text(errors).html() + '</td>' +
                    '</tr>'
                );
            });
        }

        function showMessage(type, text) {
            var css = 'alert-info';
            if (type === 'success') css = 'alert-success';
            if (type === 'warning') css = 'alert-warning';
            if (type === 'error') css = 'alert-danger';
            $('#actionMessage').removeClass('alert-info alert-success alert-warning alert-danger').addClass(css).html(text).show();
        }

        function postAction(action, extraData) {
            var formData = new FormData();
            formData.append('action', action);
            formData.append('dataset', $('#dataset').val());
            formData.append('mode', $('#mode').val());
            formData.append('source_cabang', $('#sourceCabang').val());
            formData.append('rows', JSON.stringify(parsedRows));
            if (selectedFile) {
                formData.append('source_file', selectedFile);
            }
            if (extraData) {
                Object.keys(extraData).forEach(function(key) {
                    formData.append(key, extraData[key]);
                });
            }

            return $.ajax({
                url: '_ajax/ajax-access-sync.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            });
        }

        $('#dropZone').on('click', function(e) {
            if (e.target && e.target.id === 'sourceFile') {
                return;
            }
            $('#sourceFile').trigger('click');
        }).on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        }).on('dragleave drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        }).on('drop', function(e) {
            var file = e.originalEvent.dataTransfer.files[0];
            if (file) {
                setFile(file);
            }
        });

        $('#sourceFile').on('click', function(e) {
            e.stopPropagation();
        }).on('change', function() {
            if (this.files && this.files[0]) {
                setFile(this.files[0]);
            }
        });

        $('#btnPreview').on('click', function() {
            if (!selectedFile) {
                showMessage('warning', 'Pilih file dulu.');
                return;
            }

            parseFile(selectedFile).then(function(rows) {
                parsedRows = rows;
                return postAction('preview');
            }).then(function(response) {
                if (!response.success) {
                    showMessage('error', response.error || 'Preview gagal');
                    return;
                }
                renderPreview(response.preview || [], response.summary || {});
                showMessage('success', 'Preview berhasil diproses untuk dataset ' + $('#dataset').val() + '.');
                $('#btnSave').prop('disabled', (response.summary && response.summary.valid > 0) ? false : true);
            }).catch(function() {
                showMessage('error', 'Preview gagal diproses.');
            });
        });

        $('#btnSave').on('click', function() {
            if (!parsedRows.length) {
                showMessage('warning', 'Preview dulu sebelum simpan.');
                return;
            }

            if ($('#mode').val() === 'preview') {
                showMessage('warning', 'Ubah mode ke Append atau Replace untuk menyimpan.');
                return;
            }

            postAction('save').done(function(response) {
                if (!response.success) {
                    showMessage('error', response.error || 'Simpan gagal');
                    return;
                }
                showMessage('success', 'Sync tersimpan. Run ID: ' + response.run_id + '. Success: ' + response.summary.success + ', Failed: ' + response.summary.failed);
                setTimeout(function() {
                    window.location.reload();
                }, 900);
            }).fail(function() {
                showMessage('error', 'Simpan gagal diproses.');
            });
        });

        $('.btn-view-errors').on('click', function() {
            var runId = $(this).data('run-id');
            $('#errorsContent').text('Memuat data...');
            $('#errorsModal').modal('show');

            postAction('errors', { run_id: runId }).done(function(response) {
                if (!response.success) {
                    $('#errorsContent').html('<div class="alert alert-danger">' + (response.error || 'Gagal memuat error') + '</div>');
                    return;
                }
                if (!response.errors || !response.errors.length) {
                    $('#errorsContent').html('<div class="alert alert-success">Tidak ada error untuk run ini.</div>');
                    return;
                }
                var html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Dataset</th><th>Key</th><th>Error</th><th>Waktu</th></tr></thead><tbody>';
                response.errors.forEach(function(item) {
                    html += '<tr><td>' + $('<div>').text(item.dataset_name || '').html() + '</td><td>' + $('<div>').text(item.row_key || '-').html() + '</td><td>' + $('<div>').text(item.error_message || '').html() + '</td><td>' + $('<div>').text(item.created_at || '').html() + '</td></tr>';
                });
                html += '</tbody></table></div>';
                $('#errorsContent').html(html);
            }).fail(function() {
                $('#errorsContent').html('<div class="alert alert-danger">Request error.</div>');
            });
        });

        $('.btn-merge-master').on('click', function() {
            var runId = $(this).data('run-id');
            var dataset = $(this).data('dataset');
            showMessage('info', 'Memproses merge master untuk dataset ' + dataset + '...');
            postAction('merge_master', { run_id: runId, dataset: dataset, rows: JSON.stringify([]) }).done(function(response) {
                if (!response.success) {
                    showMessage('error', response.error || 'Merge master gagal.');
                    return;
                }
                var summary = response.summary || {};
                showMessage('success', 'Merge master selesai. Diproses: ' + (summary.processed || 0) + ', insert: ' + (summary.inserted || 0) + ', update: ' + (summary.updated || 0));
            }).fail(function() {
                showMessage('error', 'Request merge master gagal diproses.');
            });
        });

        $('.btn-merge-transaction').on('click', function() {
            var runId = $(this).data('run-id');
            var dataset = $(this).data('dataset');
            showMessage('info', 'Memproses konsolidasi transaksi untuk dataset ' + dataset + '...');
            postAction('merge_transaction', { run_id: runId, dataset: dataset }).done(function(response) {
                if (!response.success) {
                    showMessage('error', response.error || 'Konsolidasi transaksi gagal.');
                    return;
                }
                var summary = response.summary || {};
                showMessage('success', 'Konsolidasi transaksi selesai. Diproses: ' + (summary.processed || 0) + ', upsert: ' + (summary.upserted || 0));
            }).fail(function() {
                showMessage('error', 'Request konsolidasi transaksi gagal diproses.');
            });
        });
    </script>
</body>
</html>
