<?php
// File: report-tracking-keluhan.php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];        
    $kd_cabang=$_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];				        
    $foto_user=$tm_cari['foto_user'];				
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // Get filter parameters
    $tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-01');
    $tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');
    $filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
    $filter_prioritas = isset($_GET['prioritas']) ? $_GET['prioritas'] : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    $filter_cabang = isset($_GET['cabang']) ? $_GET['cabang'] : $kd_cabang;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Laporan Tracking Keluhan - Bengkel System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
    
    <style>
    .priority-rendah { background-color: #5cb85c; color: white; }
    .priority-sedang { background-color: #f0ad4e; color: white; }
    .priority-tinggi { background-color: #d9534f; color: white; }
    .priority-darurat { background-color: #d9534f; color: white; animation: blink 1s infinite; }
    
    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .status-pending { background-color: #6c757d; }
    .status-dikerjakan { background-color: #ffc107; }
    .status-selesai { background-color: #28a745; }
    .status-skip { background-color: #17a2b8; }
    
    @media print {
        .no-print { display: none !important; }
        .widget-header { background-color: #f8f9fa !important; }
        body { font-size: 12px; }
    }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state no-print">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> Bengkel System</small>							
                </a>								
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile" />
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
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state no-print" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Laporan</a></li>							
                        <li class="active">Tracking Keluhan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <!-- Filter Form -->
                            <div class="widget-box no-print">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-filter"></i> Filter Laporan</h4>
                                    <div class="widget-toolbar">
                                        <button type="button" class="btn btn-xs btn-success" onclick="exportExcel()">
                                            <i class="fa fa-file-excel-o"></i> Export Excel
                                        </button>
                                        <button type="button" class="btn btn-xs btn-info" onclick="window.print()">
                                            <i class="fa fa-print"></i> Print
                                        </button>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="GET" class="form-horizontal">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label">Periode:</label>
                                                        <div class="col-sm-4">
                                                            <input type="date" class="form-control input-sm" name="tgl_dari" 
                                                                   value="<?php echo $tgl_dari; ?>">
                                                        </div>
                                                        <div class="col-sm-1 text-center">s/d</div>
                                                        <div class="col-sm-4">
                                                            <input type="date" class="form-control input-sm" name="tgl_sampai" 
                                                                   value="<?php echo $tgl_sampai; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label">Kategori:</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control input-sm" name="kategori">
                                                                <option value="">Semua Kategori</option>
                                                                <option value="Mesin" <?php echo ($filter_kategori=='Mesin')?'selected':''; ?>>Mesin</option>
                                                                <option value="Rem" <?php echo ($filter_kategori=='Rem')?'selected':''; ?>>Rem</option>
                                                                <option value="Kelistrikan" <?php echo ($filter_kategori=='Kelistrikan')?'selected':''; ?>>Kelistrikan</option>
                                                                <option value="Transmisi" <?php echo ($filter_kategori=='Transmisi')?'selected':''; ?>>Transmisi</option>
                                                                <option value="Ban" <?php echo ($filter_kategori=='Ban')?'selected':''; ?>>Ban</option>
                                                                <option value="Body" <?php echo ($filter_kategori=='Body')?'selected':''; ?>>Body</option>
                                                                <option value="Lainnya" <?php echo ($filter_kategori=='Lainnya')?'selected':''; ?>>Lainnya</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label">Prioritas:</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control input-sm" name="prioritas">
                                                                <option value="">Semua Prioritas</option>
                                                                <option value="rendah" <?php echo ($filter_prioritas=='rendah')?'selected':''; ?>>Rendah</option>
                                                                <option value="sedang" <?php echo ($filter_prioritas=='sedang')?'selected':''; ?>>Sedang</option>
                                                                <option value="tinggi" <?php echo ($filter_prioritas=='tinggi')?'selected':''; ?>>Tinggi</option>
                                                                <option value="darurat" <?php echo ($filter_prioritas=='darurat')?'selected':''; ?>>Darurat</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label">Status:</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control input-sm" name="status">
                                                                <option value="">Semua Status</option>
                                                                <option value="datang" <?php echo ($filter_status=='datang')?'selected':''; ?>>Datang</option>
                                                                <option value="diproses" <?php echo ($filter_status=='diproses')?'selected':''; ?>>Diproses</option>
                                                                <option value="selesai" <?php echo ($filter_status=='selesai')?'selected':''; ?>>Selesai</option>
                                                                <option value="tidak_selesai" <?php echo ($filter_status=='tidak_selesai')?'selected':''; ?>>Tidak Selesai</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-offset-3 col-sm-9">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-search"></i> Filter
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-default" onclick="resetFilter()">
                                                        <i class="fa fa-refresh"></i> Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Statistics -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-bar-chart"></i> Ringkasan Tracking Keluhan</h4>
                                    <div class="widget-toolbar">
                                        <span class="label label-info">Periode: <?php echo date('d/m/Y', strtotime($tgl_dari)); ?> - <?php echo date('d/m/Y', strtotime($tgl_sampai)); ?></span>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php
                                        // Build WHERE conditions
                                        $where_conditions = ["DATE(s.tanggal) BETWEEN '$tgl_dari' AND '$tgl_sampai'"];
                                        
                                        if(!empty($filter_kategori)) {
                                            $where_conditions[] = "mk.kategori = '$filter_kategori'";
                                        }
                                        if(!empty($filter_prioritas)) {
                                            $where_conditions[] = "mk.tingkat_prioritas = '$filter_prioritas'";
                                        }
                                        if(!empty($filter_status)) {
                                            $where_conditions[] = "k.status_pengerjaan = '$filter_status'";
                                        }
                                        if(!empty($filter_cabang)) {
                                            $where_conditions[] = "s.kd_cabang = '$filter_cabang'";
                                        }
                                        
                                        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
                                        
                                        // Get summary statistics
                                        $sql_summary = mysqli_query($koneksi,"SELECT 
                                                                             COUNT(DISTINCT k.id) as total_keluhan,
                                                                             COUNT(DISTINCT s.no_service) as total_service,
                                                                             SUM(CASE WHEN k.status_pengerjaan = 'selesai' THEN 1 ELSE 0 END) as keluhan_selesai,
                                                                             SUM(CASE WHEN k.status_pengerjaan = 'diproses' THEN 1 ELSE 0 END) as keluhan_diproses,
                                                                             SUM(CASE WHEN k.status_pengerjaan = 'tidak_selesai' THEN 1 ELSE 0 END) as keluhan_tidak_selesai,
                                                                             AVG(mk.estimasi_waktu) as avg_estimasi
                                                                             FROM tblservice s
                                                                             JOIN tbservis_keluhan_status k ON s.no_service = k.no_service
                                                                             LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                                                                             $where_clause");
                                        
                                        $summary = mysqli_fetch_array($sql_summary);
                                        $total_keluhan = $summary['total_keluhan'] ?? 0;
                                        $keluhan_selesai = $summary['keluhan_selesai'] ?? 0;
                                        $completion_rate = $total_keluhan > 0 ? round(($keluhan_selesai / $total_keluhan) * 100, 1) : 0;
                                        ?>
                                        
                                        <div class="row">
                                            <div class="col-xs-6 col-sm-3">
                                                <div class="widget-box widget-color-blue2 light-border">
                                                    <div class="widget-header">
                                                        <h5 class="widget-title smaller">Total Service</h5>
                                                    </div>
                                                    <div class="widget-body">
                                                        <div class="widget-main">
                                                            <div class="infobox-data">
                                                                <span class="infobox-data-number"><?php echo number_format($summary['total_service'] ?? 0); ?></span>
                                                                <div class="infobox-content">Service dengan keluhan</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-xs-6 col-sm-3">
                                                <div class="widget-box widget-color-orange light-border">
                                                    <div class="widget-header">
                                                        <h5 class="widget-title smaller">Total Keluhan</h5>
                                                    </div>
                                                    <div class="widget-body">
                                                        <div class="widget-main">
                                                            <div class="infobox-data">
                                                                <span class="infobox-data-number"><?php echo number_format($total_keluhan); ?></span>
                                                                <div class="infobox-content">Keluhan tercatat</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-xs-6 col-sm-3">
                                                <div class="widget-box widget-color-green light-border">
                                                    <div class="widget-header">
                                                        <h5 class="widget-title smaller">Tingkat Penyelesaian</h5>
                                                    </div>
                                                    <div class="widget-body">
                                                        <div class="widget-main">
                                                            <div class="infobox-data">
                                                                <span class="infobox-data-number"><?php echo $completion_rate; ?>%</span>
                                                                <div class="infobox-content"><?php echo $keluhan_selesai; ?> dari <?php echo $total_keluhan; ?> keluhan</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-xs-6 col-sm-3">
                                                <div class="widget-box widget-color-purple light-border">
                                                    <div class="widget-header">
                                                        <h5 class="widget-title smaller">Rata-rata Estimasi</h5>
                                                    </div>
                                                    <div class="widget-body">
                                                        <div class="widget-main">
                                                            <div class="infobox-data">
                                                                <span class="infobox-data-number"><?php echo round($summary['avg_estimasi'] ?? 0); ?></span>
                                                                <div class="infobox-content">Menit per keluhan</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Data -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-table"></i> Detail Tracking Keluhan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover" id="tracking-table">
                                                <thead>
                                                    <tr>
                                                        <th width="3%">No</th>
                                                        <th width="10%">No Service</th>
                                                        <th width="8%">Tanggal</th>
                                                        <th width="12%">Pelanggan</th>
                                                        <th width="25%">Keluhan</th>
                                                        <th width="8%">Kategori</th>
                                                        <th width="8%">Prioritas</th>
                                                        <th width="8%">Status</th>
                                                        <th width="8%">Progress</th>
                                                        <th width="10%">Estimasi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $no = 1;
                                                    $sql = mysqli_query($koneksi,"SELECT 
                                                                                 s.no_service, 
                                                                                 DATE(s.tanggal) as tanggal_service,
                                                                                 s.no_pelanggan,
                                                                                 p.namapelanggan,
                                                                                 k.id as keluhan_id,
                                                                                 k.keluhan,
                                                                                 k.status_pengerjaan,
                                                                                 mk.kode_keluhan,
                                                                                 mk.nama_keluhan,
                                                                                 mk.kategori,
                                                                                 mk.tingkat_prioritas,
                                                                                 mk.estimasi_waktu,
                                                                                 (SELECT COUNT(*) FROM tbservis_keluhan_tracking kt WHERE kt.keluhan_id = k.id) as total_proses,
                                                                                 (SELECT COUNT(*) FROM tbservis_keluhan_tracking kt WHERE kt.keluhan_id = k.id AND kt.status_proses = 'selesai') as proses_selesai
                                                                                 FROM tblservice s
                                                                                 JOIN tbservis_keluhan_status k ON s.no_service = k.no_service
                                                                                 LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                                                                                 LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                                                                                 $where_clause
                                                                                 ORDER BY s.tanggal DESC, s.no_service DESC");
                                                    
                                                    while ($data = mysqli_fetch_array($sql)) {
                                                        $progress = 0;
                                                        if($data['total_proses'] > 0) {
                                                            $progress = round(($data['proses_selesai'] / $data['total_proses']) * 100);
                                                        }
                                                        
                                                        $priority_class = 'priority-' . ($data['tingkat_prioritas'] ?? 'sedang');
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td><?php echo $data['no_service']; ?></td>
                                                        <td class="center"><?php echo date('d/m/Y', strtotime($data['tanggal_service'])); ?></td>
                                                        <td>
                                                            <strong><?php echo $data['namapelanggan'] ?? $data['no_pelanggan']; ?></strong>
                                                            <br><small class="text-muted"><?php echo $data['no_pelanggan']; ?></small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($data['keluhan']); ?>
                                                            <?php if($data['kode_keluhan']) { ?>
                                                                <br><small class="text-muted">
                                                                    <i class="fa fa-tag"></i> <?php echo $data['kode_keluhan']; ?>
                                                                </small>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="center">
                                                            <?php if($data['kategori']) { ?>
                                                                <span class="label label-info"><?php echo $data['kategori']; ?></span>
                                                            <?php } else { ?>
                                                                <span class="text-muted">-</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="center">
                                                            <?php if($data['tingkat_prioritas']) { ?>
                                                                <span class="label <?php echo $priority_class; ?>">
                                                                    <?php echo ucfirst($data['tingkat_prioritas']); ?>
                                                                </span>
                                                            <?php } else { ?>
                                                                <span class="text-muted">-</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="center">
                                                            <?php 
                                                            switch($data['status_pengerjaan']) {
                                                                case 'datang': echo '<span class="label label-default">Datang</span>'; break;
                                                                case 'diproses': echo '<span class="label label-warning">Diproses</span>'; break;
                                                                case 'selesai': echo '<span class="label label-success">Selesai</span>'; break;
                                                                case 'tidak_selesai': echo '<span class="label label-danger">Tidak Selesai</span>'; break;
                                                                default: echo '<span class="label label-default">-</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="center">
                                                            <?php if($data['total_proses'] > 0) { ?>
                                                                <div class="progress" style="margin-bottom: 0; height: 15px;">
                                                                    <div class="progress-bar progress-bar-<?php echo $progress == 100 ? 'success' : ($progress > 50 ? 'warning' : 'info'); ?>" 
                                                                         style="width: <?php echo $progress; ?>%">
                                                                        <small><?php echo $progress; ?>%</small>
                                                                    </div>
                                                                </div>
                                                                <small><?php echo $data['proses_selesai']; ?>/<?php echo $data['total_proses']; ?> proses</small>
                                                            <?php } else { ?>
                                                                <span class="text-muted">Manual</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="center">
                                                            <?php if($data['estimasi_waktu']) { ?>
                                                                <i class="fa fa-clock-o"></i> <?php echo $data['estimasi_waktu']; ?> min
                                                            <?php } else { ?>
                                                                <span class="text-muted">-</span>
                                                            <?php } ?>
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

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script>
    function resetFilter() {
        window.location.href = 'report-tracking-keluhan.php';
    }
    
    function exportExcel() {
        var params = new URLSearchParams(window.location.search);
        params.set('export', 'excel');
        window.location.href = 'export-tracking-keluhan.php?' + params.toString();
    }
    
    // Auto refresh setiap 5 menit jika halaman aktif
    var refreshTimer;
    
    function startAutoRefresh() {
        refreshTimer = setInterval(function() {
            if(document.visibilityState === 'visible') {
                location.reload();
            }
        }, 300000); // 5 menit
    }
    
    function stopAutoRefresh() {
        if(refreshTimer) {
            clearInterval(refreshTimer);
        }
    }
    
    // Event listeners untuk visibility change
    document.addEventListener('visibilitychange', function() {
        if(document.visibilityState === 'visible') {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    // Start auto refresh when page loads
    $(document).ready(function() {
        startAutoRefresh();
    });
    </script>
</body>
</html>

<?php } ?>