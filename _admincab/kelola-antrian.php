<?php
session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";
    include_once "../lib/rbac.php";
    rbac_require_any(array('input_servis_read','servis_reguler_read','servis_menu_read','antrian_read','antrian_manage'));

    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_user, password, user_akses, foto_user
                                    FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
    $pwd=$tm_cari['password'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // Set username session if not exists
    if(!isset($_SESSION['username'])) {
        $_SESSION['username'] = $_nama;
    }

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_cabang, tipe_cabang
                                    FROM tbcabang
                                    WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';
    $tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';
    // --------------------

    // ========================================
    // HANDLER: UPDATE STATUS ANTRIAN
    // ========================================
    if(isset($_POST['btnupdatestatus'])) {
        $id_antrian = $_POST['id_antrian'];
        $new_status = $_POST['new_status'];
        $no_service = $_POST['no_service_update'];

        $update_fields = "status_antrian='$new_status'";

        // Update jam based on status
        if($new_status == 'diproses' && $_POST['current_status'] == 'menunggu') {
            $update_fields .= ", jam_mulai=NOW()";
        } elseif($new_status == 'selesai') {
            $update_fields .= ", jam_selesai=NOW()";
        }

        mysqli_query($koneksi, "UPDATE tb_antrian_servis
                               SET $update_fields
                               WHERE id='$id_antrian'");

        // Also update service status
        if($new_status == 'diproses') {
            mysqli_query($koneksi, "UPDATE tblservice
                                   SET status_servis='diproses'
                                   WHERE no_service='$no_service'");
        } elseif($new_status == 'selesai') {
            mysqli_query($koneksi, "UPDATE tblservice
                                   SET status_servis='selesai'
                                   WHERE no_service='$no_service'");
        }

        echo "<script>
            alert('Status antrian berhasil diupdate!');
            window.location.href='kelola-antrian.php';
        </script>";
        exit;
    }

    // ========================================
    // HANDLER: BATALKAN ANTRIAN
    // ========================================
    if(isset($_POST['btnbatalkan'])) {
        $id_antrian = $_POST['id_antrian'];
        $alasan_batal = mysqli_real_escape_string($koneksi, $_POST['alasan_batal']);
        $no_service = $_POST['no_service_update'];

        mysqli_query($koneksi, "UPDATE tb_antrian_servis
                               SET status_antrian='batal',
                                   alasan_batal='$alasan_batal',
                                   user_batal='$_nama',
                                   waktu_batal=NOW()
                               WHERE id='$id_antrian'");

        // Also update service status
        mysqli_query($koneksi, "UPDATE tblservice
                               SET status_servis='cancel'
                               WHERE no_service='$no_service'");

        echo "<script>
            alert('Antrian berhasil dibatalkan!');
            window.location.href='kelola-antrian.php';
        </script>";
        exit;
    }

    // Filter parameters
    $filter_tanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : date('Y-m-d');
    $filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';
    $filter_prioritas = isset($_GET['filter_prioritas']) ? $_GET['filter_prioritas'] : 'all';
    $search_keyword = isset($_GET['search']) ? $_GET['search'] : '';

    // Build WHERE clause
    $where_clauses = ["a.tanggal='$filter_tanggal'"];

    if($filter_status != 'all') {
        $where_clauses[] = "a.status_antrian='$filter_status'";
    }

    if($filter_prioritas != 'all') {
        $where_clauses[] = "a.prioritas='$filter_prioritas'";
    }

    if(!empty($search_keyword)) {
        $search_keyword = mysqli_real_escape_string($koneksi, $search_keyword);
        $where_clauses[] = "(a.no_antrian LIKE '%$search_keyword%'
                            OR a.no_service LIKE '%$search_keyword%'
                            OR p.namapelanggan LIKE '%$search_keyword%'
                            OR k.nopolisi LIKE '%$search_keyword%')";
    }

    $where_sql = "WHERE " . implode(" AND ", $where_clauses);

    // Query antrian with service details
    $query = "SELECT
                a.*,
                s.tanggal as tgl_service,
                s.jam,
                s.no_pelanggan,
                s.no_polisi,
                s.status_jemput,
                s.status_servis,
                s.total_akhir,
                p.namapelanggan,
                k.jenis as jenis_motor,
                k.tipe as tipe_motor,
                k.warna
              FROM tb_antrian_servis a
              LEFT JOIN tblservice s ON a.no_service = s.no_service
              LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
              LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
              $where_sql
              ORDER BY
                FIELD(a.status_antrian, 'menunggu', 'diproses', 'selesai', 'batal'),
                FIELD(a.prioritas, 'vip', 'urgent', 'normal'),
                a.jam_ambil ASC";

    $result = mysqli_query($koneksi, $query);

    // Count statistics
    $stats = [
        'total' => 0,
        'menunggu' => 0,
        'diproses' => 0,
        'selesai' => 0,
        'batal' => 0,
        'vip' => 0,
        'urgent' => 0,
        'normal' => 0
    ];

    $temp_result = mysqli_query($koneksi, $query);
    while($row = mysqli_fetch_array($temp_result)) {
        $stats['total']++;
        $stats[$row['status_antrian']]++;
        $stats[$row['prioritas']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Kelola Antrian</title>

    <meta name="description" content="Kelola Antrian Service" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- page specific plugin styles -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

    <!-- text fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- ace styles -->
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

    <!--[if lte IE 9]>
        <link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
    <![endif]-->
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <!--[if lte IE 9]>
      <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
    <![endif]-->

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        /* ============ STAT BOXES - Modern Gradient Style ============ */
        .stat-box {
            padding: 20px 15px;
            border-radius: 8px;
            color: white;
            text-align: center;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 0 0 0 60px;
        }
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .stat-box h3 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .stat-box p {
            margin: 8px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
            font-weight: 500;
        }
        .stat-box i {
            font-size: 16px;
            margin-right: 5px;
        }
        .stat-total { background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); }
        .stat-menunggu { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
        .stat-diproses { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .stat-selesai { background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); }
        
        /* ============ QUICK STATS ROW ============ */
        .quick-stats {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .quick-stat-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            gap: 5px;
        }
        .badge-stat-vip { background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); }
        .badge-stat-urgent { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .badge-stat-normal { background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); }
        .badge-stat-batal { background: linear-gradient(135deg, #636e72 0%, #2d3436 100%); }
        
        /* ============ FILTER SECTION ============ */
        .filter-widget {
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-widget .widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .filter-widget .widget-title {
            color: white;
        }
        .filter-widget .widget-main {
            padding: 15px 20px;
        }
        .filter-widget .form-group {
            margin-right: 15px;
            margin-bottom: 10px;
        }
        .filter-widget label {
            font-weight: 600;
            color: #555;
            margin-right: 8px;
        }
        .filter-widget .btn {
            margin-right: 5px;
        }
        
        /* ============ ANTRIAN CARDS - Enhanced Style ============ */
        .antrian-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .antrian-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 5px solid #ccc;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 0;
        }
        .antrian-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            transform: translateX(5px);
        }
        .antrian-card.menunggu {
            border-left-color: #f39c12;
        }
        .antrian-card.diproses {
            border-left-color: #3498db;
        }
        .antrian-card.diproses .antrian-header {
            background: linear-gradient(90deg, #e3f2fd 0%, #ffffff 100%);
        }
        .antrian-card.selesai {
            border-left-color: #27ae60;
        }
        .antrian-card.selesai .antrian-header {
            background: linear-gradient(90deg, #e8f5e9 0%, #ffffff 100%);
        }
        .antrian-card.batal {
            border-left-color: #e74c3c;
            opacity: 0.7;
        }
        .antrian-card.batal .antrian-header {
            background: linear-gradient(90deg, #ffebee 0%, #ffffff 100%);
        }
        
        /* ============ CARD HEADER ============ */
        .antrian-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            flex-wrap: wrap;
            gap: 10px;
        }
        .antrian-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .antrian-number {
            font-size: 20px;
            font-weight: 700;
            padding: 5px 15px;
            border-radius: 5px;
        }
        .antrian-number.menunggu { background: #f39c12; color: white; }
        .antrian-number.diproses { background: #3498db; color: white; }
        .antrian-number.selesai { background: #27ae60; color: white; }
        .antrian-number.batal { background: #e74c3c; color: white; }
        
        /* ============ BADGES ============ */
        .badge-vip {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-urgent {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-normal {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .service-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .type-reguler { background: #3498db; color: white; }
        .type-rst { background: #e74c3c; color: white; }
        .type-jemput { background: #9b59b6; color: white; }
        .type-garansi { background: #27ae60; color: white; }
        
        /* ============ CARD BODY ============ */
        .antrian-body {
            padding: 20px;
        }
        .antrian-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .info-section h6 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #777;
            min-width: 100px;
            font-weight: 500;
        }
        .info-value {
            color: #333;
            font-weight: 600;
        }
        .info-value.highlight {
            color: #27ae60;
            font-size: 16px;
        }
        
        /* ============ TIME DISPLAY ============ */
        .time-display {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .time-item {
            text-align: center;
            padding: 10px 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .time-item small {
            display: block;
            color: #999;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .time-item strong {
            font-size: 18px;
            color: #333;
        }
        .duration-badge {
            background: #3498db;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* ============ ACTION BUTTONS ============ */
        .antrian-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .btn-mulai { background: #3498db; color: white; border: none; }
        .btn-mulai:hover { background: #2980b9; color: white; }
        .btn-selesai { background: #27ae60; color: white; border: none; }
        .btn-selesai:hover { background: #1e8449; color: white; }
        .btn-batal { background: #e74c3c; color: white; border: none; }
        .btn-batal:hover { background: #c0392b; color: white; }
        .btn-print { background: #667eea; color: white; border: none; }
        .btn-print:hover { background: #5a67d8; color: white; }
        .btn-detail { background: #f8f9fa; color: #555; border: 1px solid #ddd; }
        .btn-detail:hover { background: #e9ecef; color: #333; }
        
        /* ============ NOTES AND ALERTS ============ */
        .antrian-notes {
            margin-top: 15px;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13px;
        }
        .note-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            color: #1565c0;
        }
        .note-danger {
            background: #ffebee;
            border-left: 4px solid #f44336;
            color: #c62828;
        }
        
        /* ============ EMPTY STATE ============ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        .empty-state h4 {
            color: #777;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #999;
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 768px) {
            .stat-box h3 { font-size: 28px; }
            .stat-box p { font-size: 11px; }
            .antrian-header { padding: 12px 15px; }
            .antrian-body { padding: 15px; }
            .antrian-number { font-size: 16px; padding: 4px 12px; }
            .antrian-info-grid { grid-template-columns: 1fr; }
        }
        
        /* ============ PROGRESS INDICATOR ============ */
        .status-progress {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        .progress-step {
            flex: 1;
            height: 4px;
            background: #eee;
            border-radius: 2px;
        }
        .progress-step.active {
            background: #27ae60;
        }
        .progress-step.current {
            background: #3498db;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
                <table>
                    <tr>
                        <td width="20%">
                            <a href="index.php" class="navbar-brand">
                                <small>
                                    <i class="fa fa-leaf"></i>
                                    <?php include "../lib/subtitel.php"; ?>
                                </small>
                            </a>
                        </td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>

                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li>
                                <a href="change_pwd.php">
                                    <i class="ace-icon fa fa-cog"></i>
                                    Change Password
                                </a>
                            </li>
                            <li>
                                <a href="profile.php">
                                    <i class="ace-icon fa fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="logout.php">
                                    <i class="ace-icon fa fa-power-off"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right">
                <a href="#" class="navbar-brand"><small></small></a>
            </div>
        </div><!-- /.navbar-container -->
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_servis01.php"; ?>

            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li>
                            <a href="#">Service</a>
                        </li>
                        <li class="active">Kelola Antrian</li>
                    </ul><!-- /.breadcrumb -->

                    <div class="nav-search" id="nav-search">
                        <form class="form-search" method="GET" action="">
                            <input type="hidden" name="filter_tanggal" value="<?php echo $filter_tanggal; ?>">
                            <input type="hidden" name="filter_status" value="<?php echo $filter_status; ?>">
                            <input type="hidden" name="filter_prioritas" value="<?php echo $filter_prioritas; ?>">
                            <span class="input-icon">
                                <input type="text" name="search" placeholder="Search No.Antrian, No.Service, Pelanggan, Nopol..."
                                       class="nav-search-input" id="nav-search-input" autocomplete="off"
                                       value="<?php echo htmlspecialchars($search_keyword); ?>" />
                                <i class="ace-icon fa fa-search nav-search-icon"></i>
                            </span>
                        </form>
                    </div><!-- /.nav-search -->
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            <i class="ace-icon fa fa-list-ol"></i>
                            Kelola Antrian Service
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Monitoring & Management
                            </small>
                        </h1>
                    </div><!-- /.page-header -->

                    <div class="row">
                        <div class="col-xs-12">
                            <!-- Statistics Cards -->
                            <div class="row">
                                <div class="col-sm-3 col-xs-6">
                                    <div class="stat-box stat-total">
                                        <h3><?php echo $stats['total']; ?></h3>
                                        <p><i class="fa fa-list"></i> Total Antrian</p>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-xs-6">
                                    <div class="stat-box stat-menunggu">
                                        <h3><?php echo $stats['menunggu']; ?></h3>
                                        <p><i class="fa fa-clock-o"></i> Menunggu</p>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-xs-6">
                                    <div class="stat-box stat-diproses">
                                        <h3><?php echo $stats['diproses']; ?></h3>
                                        <p><i class="fa fa-cog fa-spin"></i> Diproses</p>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-xs-6">
                                    <div class="stat-box stat-selesai">
                                        <h3><?php echo $stats['selesai']; ?></h3>
                                        <p><i class="fa fa-check"></i> Selesai</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats Badges -->
                            <div class="quick-stats">
                                <span class="quick-stat-badge badge-stat-vip">
                                    <i class="fa fa-star"></i> VIP: <?php echo $stats['vip']; ?>
                                </span>
                                <span class="quick-stat-badge badge-stat-urgent">
                                    <i class="fa fa-bolt"></i> Urgent: <?php echo $stats['urgent']; ?>
                                </span>
                                <span class="quick-stat-badge badge-stat-normal">
                                    <i class="fa fa-user"></i> Normal: <?php echo $stats['normal']; ?>
                                </span>
                                <span class="quick-stat-badge badge-stat-batal">
                                    <i class="fa fa-times"></i> Batal: <?php echo $stats['batal']; ?>
                                </span>
                            </div>

                            <!-- Filter Section -->
                            <div class="widget-box filter-widget">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="ace-icon fa fa-filter"></i>
                                        Filter Antrian
                                    </h5>
                                    <div class="widget-toolbar">
                                        <a href="#" data-action="collapse">
                                            <i class="ace-icon fa fa-chevron-up"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="GET" action="" class="form-inline">
                                            <div class="form-group">
                                                <label>Tanggal:</label>
                                                <input type="date" name="filter_tanggal" class="form-control"
                                                       value="<?php echo $filter_tanggal; ?>" />
                                            </div>

                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select name="filter_status" class="form-control">
                                                    <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>Semua Status</option>
                                                    <option value="menunggu" <?php echo $filter_status=='menunggu'?'selected':''; ?>>Menunggu</option>
                                                    <option value="diproses" <?php echo $filter_status=='diproses'?'selected':''; ?>>Diproses</option>
                                                    <option value="selesai" <?php echo $filter_status=='selesai'?'selected':''; ?>>Selesai</option>
                                                    <option value="batal" <?php echo $filter_status=='batal'?'selected':''; ?>>Batal</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Prioritas:</label>
                                                <select name="filter_prioritas" class="form-control">
                                                    <option value="all" <?php echo $filter_prioritas=='all'?'selected':''; ?>>Semua Prioritas</option>
                                                    <option value="vip" <?php echo $filter_prioritas=='vip'?'selected':''; ?>>VIP</option>
                                                    <option value="urgent" <?php echo $filter_prioritas=='urgent'?'selected':''; ?>>Urgent</option>
                                                    <option value="normal" <?php echo $filter_prioritas=='normal'?'selected':''; ?>>Normal</option>
                                                </select>
                                            </div>

                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fa fa-search"></i> Filter
                                            </button>
                                            <a href="kelola-antrian.php" class="btn btn-sm btn-default">
                                                <i class="fa fa-refresh"></i> Reset
                                            </a>
                                            <button type="button" class="btn btn-sm btn-info" onclick="window.print();">
                                                <i class="fa fa-print"></i> Print
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Antrian List -->
                            <div class="space-10"></div>

                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($antrian = mysqli_fetch_array($result)):
                                    // Determine service type
                                    $service_type = 'Reguler';
                                    $type_class = 'type-reguler';
                                    if($antrian['status_jemput'] == '1') {
                                        $service_type = 'Jemput';
                                        $type_class = 'type-jemput';
                                    }
                                    if($antrian['prioritas'] == 'urgent') {
                                        $service_type .= ' - RST';
                                        $type_class = 'type-rst';
                                    }

                                    // Calculate duration
                                    $duration = '';
                                    if($antrian['jam_mulai'] && $antrian['status_antrian'] != 'menunggu') {
                                        $start = strtotime($antrian['jam_mulai']);
                                        $end = $antrian['jam_selesai'] ? strtotime($antrian['jam_selesai']) : time();
                                        $diff = $end - $start;
                                        $hours = floor($diff / 3600);
                                        $minutes = floor(($diff % 3600) / 60);
                                        $duration = ($hours > 0 ? $hours . 'j ' : '') . $minutes . 'm';
                                    }
                                ?>
                                <div class="antrian-card <?php echo $antrian['status_antrian']; ?>">
                                    <div class="widget-box">
                                        <div class="widget-header widget-header-flat">
                                            <h4 class="widget-title">
                                                <span class="label label-lg label-<?php
                                                    echo $antrian['status_antrian']=='menunggu' ? 'warning' :
                                                         ($antrian['status_antrian']=='diproses' ? 'info' :
                                                         ($antrian['status_antrian']=='selesai' ? 'success' : 'danger'));
                                                ?>">
                                                    <?php echo $antrian['no_antrian']; ?>
                                                </span>
                                                &nbsp;&nbsp;
                                                <span class="service-type-badge <?php echo $type_class; ?>">
                                                    <?php echo $service_type; ?>
                                                </span>
                                                &nbsp;&nbsp;
                                                <span class="badge-<?php echo $antrian['prioritas']; ?>">
                                                    <?php echo strtoupper($antrian['prioritas']); ?>
                                                </span>
                                            </h4>

                                            <div class="widget-toolbar">
                                                <?php if($antrian['status_antrian'] != 'selesai' && $antrian['status_antrian'] != 'batal'): ?>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown">
                                                        <i class="fa fa-cog"></i> Action
                                                        <span class="ace-icon fa fa-caret-down icon-only"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-info pull-right">
                                                        <?php if($antrian['status_antrian'] == 'menunggu'): ?>
                                                        <li>
                                                            <a href="#" onclick="updateStatus('<?php echo $antrian['id']; ?>', '<?php echo $antrian['no_service']; ?>', 'diproses', '<?php echo $antrian['status_antrian']; ?>')">
                                                                <i class="fa fa-play"></i> Mulai Proses
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        <?php if($antrian['status_antrian'] == 'diproses'): ?>
                                                        <li>
                                                            <a href="#" onclick="updateStatus('<?php echo $antrian['id']; ?>', '<?php echo $antrian['no_service']; ?>', 'selesai', '<?php echo $antrian['status_antrian']; ?>')">
                                                                <i class="fa fa-check"></i> Tandai Selesai
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <a href="#" onclick="batalkanAntrian('<?php echo $antrian['id']; ?>', '<?php echo $antrian['no_service']; ?>', '<?php echo $antrian['no_antrian']; ?>')">
                                                                <i class="fa fa-times"></i> Batalkan
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
                                                <a href="servis-print.php?snoserv=<?php echo $antrian['no_service']; ?>" target="_blank" class="btn btn-xs btn-success">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <table class="table table-borderless table-condensed">
                                                            <tr>
                                                                <td width="35%"><strong>No. Service:</strong></td>
                                                                <td><?php echo $antrian['no_service']; ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Pelanggan:</strong></td>
                                                                <td><?php echo $antrian['namapelanggan']; ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Kendaraan:</strong></td>
                                                                <td>
                                                                    <?php echo $antrian['nopolisi']; ?>
                                                                    <br><small class="text-muted">
                                                                        <?php echo $antrian['jenis_motor']; ?> -
                                                                        <?php echo $antrian['tipe_motor']; ?> -
                                                                        <?php echo $antrian['warna']; ?>
                                                                    </small>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <table class="table table-borderless table-condensed">
                                                            <tr>
                                                                <td width="35%"><strong>Jam Masuk:</strong></td>
                                                                <td><?php echo date('H:i', strtotime($antrian['jam_ambil'])); ?></td>
                                                            </tr>
                                                            <?php if($antrian['jam_mulai']): ?>
                                                            <tr>
                                                                <td><strong>Jam Mulai:</strong></td>
                                                                <td><?php echo date('H:i', strtotime($antrian['jam_mulai'])); ?></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <?php if($antrian['jam_selesai']): ?>
                                                            <tr>
                                                                <td><strong>Jam Selesai:</strong></td>
                                                                <td><?php echo date('H:i', strtotime($antrian['jam_selesai'])); ?></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <?php if($duration): ?>
                                                            <tr>
                                                                <td><strong>Durasi:</strong></td>
                                                                <td><span class="label label-info"><?php echo $duration; ?></span></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <?php if($antrian['estimasi_waktu']): ?>
                                                            <tr>
                                                                <td><strong>Estimasi:</strong></td>
                                                                <td><?php echo $antrian['estimasi_waktu']; ?> menit</td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <?php if($antrian['total_akhir']): ?>
                                                            <tr>
                                                                <td><strong>Total Biaya:</strong></td>
                                                                <td><strong>Rp <?php echo number_format($antrian['total_akhir'], 0, ',', '.'); ?></strong></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        </table>
                                                    </div>
                                                </div>

                                                <?php if($antrian['catatan']): ?>
                                                <div class="alert alert-info">
                                                    <i class="fa fa-info-circle"></i>
                                                    <strong>Catatan:</strong> <?php echo $antrian['catatan']; ?>
                                                </div>
                                                <?php endif; ?>

                                                <?php if($antrian['status_antrian'] == 'batal'): ?>
                                                <div class="alert alert-danger">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <strong>Dibatalkan oleh:</strong> <?php echo $antrian['user_batal']; ?>
                                                    <br><strong>Alasan:</strong> <?php echo $antrian['alasan_batal']; ?>
                                                    <br><strong>Waktu:</strong> <?php echo date('d/m/Y H:i', strtotime($antrian['waktu_batal'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fa fa-inbox"></i>
                                    <h4>Tidak Ada Antrian</h4>
                                    <p>Tidak ada antrian untuk tanggal <?php echo date('d/m/Y', strtotime($filter_tanggal)); ?></p>
                                    <a href="servis-input-reguler.php" class="btn btn-primary btn-sm" style="margin-top: 15px;">
                                        <i class="fa fa-plus"></i> Tambah Service Baru
                                    </a>
                                </div>
                            <?php endif; ?>

                        </div><!-- /.col -->
                    </div><!-- /.row -->

                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <span class="bigger-120">
                        <?php include "../lib/subtitel.php"; ?>
                    </span>
                </div>
            </div>
        </div>

        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
    </div><!-- /.main-container -->

    <!-- Hidden forms for actions -->
    <form id="formUpdateStatus" method="POST" style="display:none;">
        <input type="hidden" name="id_antrian" id="update_id_antrian">
        <input type="hidden" name="no_service_update" id="update_no_service">
        <input type="hidden" name="new_status" id="update_new_status">
        <input type="hidden" name="current_status" id="update_current_status">
        <input type="hidden" name="btnupdatestatus" value="1">
    </form>

    <form id="formBatalkan" method="POST" style="display:none;">
        <input type="hidden" name="id_antrian" id="batal_id_antrian">
        <input type="hidden" name="no_service_update" id="batal_no_service">
        <input type="hidden" name="alasan_batal" id="batal_alasan">
        <input type="hidden" name="btnbatalkan" value="1">
    </form>

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
    </script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- page specific plugin scripts -->
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/jquery.ui.touch-punch.min.js"></script>

    <!-- ace scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <!-- inline scripts -->
    <script type="text/javascript">
        function updateStatus(id, noService, newStatus, currentStatus) {
            var statusText = {
                'diproses': 'memulai proses',
                'selesai': 'menandai selesai'
            };

            if(confirm('Apakah Anda yakin akan ' + statusText[newStatus] + ' antrian ini?')) {
                document.getElementById('update_id_antrian').value = id;
                document.getElementById('update_no_service').value = noService;
                document.getElementById('update_new_status').value = newStatus;
                document.getElementById('update_current_status').value = currentStatus;
                document.getElementById('formUpdateStatus').submit();
            }
        }

        function batalkanAntrian(id, noService, noAntrian) {
            var alasan = prompt('Masukkan alasan pembatalan antrian ' + noAntrian + ':');
            if(alasan && alasan.trim() != '') {
                document.getElementById('batal_id_antrian').value = id;
                document.getElementById('batal_no_service').value = noService;
                document.getElementById('batal_alasan').value = alasan;
                document.getElementById('formBatalkan').submit();
            } else if(alasan !== null) {
                alert('Alasan pembatalan harus diisi!');
            }
        }

        // Auto refresh every 30 seconds (optional)
        // setTimeout(function(){ location.reload(); }, 30000);

        jQuery(function($) {
            // Collapse widget
            $('.widget-header [data-action]').click(function(e){
                e.preventDefault();
                var $this = $(this);
                var $action = $this.data('action');
                var $container = $this.closest('.widget-box');

                if($action === 'collapse') {
                    var $body = $container.find('.widget-body');
                    var $icon = $this.find('[class*="fa-chevron"]');

                    $body.slideToggle();
                    $icon.toggleClass('fa-chevron-up fa-chevron-down');
                }
            });
        });
    </script>
</body>
</html>
