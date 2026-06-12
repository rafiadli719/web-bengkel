<?php
session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];		
    $kd_cabang=$_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
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
    
    // Set username session if not exists to prevent login redirect
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

    // Handle CRUD Operations
    if(isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if($action == 'add') {
            $kode_keluhan = $_POST['kode_keluhan'];
            $kode_workorder = $_POST['kode_workorder'];
            $prioritas = $_POST['prioritas'];
            
            // Check if mapping already exists
            $check_query = "SELECT COUNT(*) as count FROM tbmaster_keluhan_workorder 
                           WHERE kode_keluhan='$kode_keluhan' AND kode_workorder='$kode_workorder'";
            $check_result = mysqli_query($koneksi, $check_query);
            $check_data = mysqli_fetch_array($check_result);
            
            if($check_data['count'] > 0) {
                echo "<script>alert('Mapping keluhan dan workorder ini sudah ada!');</script>";
            } else {
                $query = "INSERT INTO tbmaster_keluhan_workorder 
                          (kode_keluhan, kode_workorder, prioritas, status_aktif) 
                          VALUES 
                          ('$kode_keluhan', '$kode_workorder', '$prioritas', '1')";
                
                if(mysqli_query($koneksi, $query)) {
                    echo "<script>alert('Mapping berhasil ditambahkan!'); window.location='master-workorder-mapping.php';</script>";
                } else {
                    echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
                }
            }
        }
        
        if($action == 'edit') {
            $id = $_POST['id'];
            $kode_keluhan = $_POST['kode_keluhan'];
            $kode_workorder = $_POST['kode_workorder'];
            $prioritas = $_POST['prioritas'];
            
            $query = "UPDATE tbmaster_keluhan_workorder SET 
                      kode_keluhan='$kode_keluhan', 
                      kode_workorder='$kode_workorder', 
                      prioritas='$prioritas',
                      updated_at=CURRENT_TIMESTAMP
                      WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Mapping berhasil diupdate!'); window.location='master-workorder-mapping.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'delete') {
            $id = $_POST['id'];
            
            // Soft delete
            $query = "UPDATE tbmaster_keluhan_workorder SET status_aktif='0' WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Mapping berhasil dihapus!'); window.location='master-workorder-mapping.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'activate') {
            $id = $_POST['id'];
            
            $query = "UPDATE tbmaster_keluhan_workorder SET status_aktif='1' WHERE id='$id'";
            
            if(mysqli_query($koneksi, $query)) {
                echo "<script>alert('Mapping berhasil diaktifkan!'); window.location='master-workorder-mapping.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
        
        if($action == 'bulk_sync') {
            // Sync workorder_default from mapping table to master keluhan
            $sync_query = "UPDATE tbmaster_keluhan mk
                          INNER JOIN (
                              SELECT kode_keluhan, kode_workorder, prioritas,
                                     ROW_NUMBER() OVER (PARTITION BY kode_keluhan ORDER BY prioritas DESC, id ASC) as rn
                              FROM tbmaster_keluhan_workorder 
                              WHERE status_aktif = '1'
                          ) mwo ON mk.kode_keluhan = mwo.kode_keluhan AND mwo.rn = 1
                          SET mk.workorder_default = mwo.kode_workorder";
            
            if(mysqli_query($koneksi, $sync_query)) {
                $affected = mysqli_affected_rows($koneksi);
                echo "<script>alert('Sync berhasil! $affected keluhan diupdate.'); window.location='master-workorder-mapping.php';</script>";
            } else {
                echo "<script>alert('Error sync: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Pagination
    $limit = 20;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    // Search filter
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $prioritas_filter = isset($_GET['prioritas']) ? $_GET['prioritas'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
    $where_conditions = ["mwo.status_aktif IS NOT NULL"]; // Ensure we get data
    if($search) {
        $where_conditions[] = "(mk.kode_keluhan LIKE '%$search%' OR mk.nama_keluhan LIKE '%$search%' OR wo.kode_wo LIKE '%$search%' OR wo.nama_wo LIKE '%$search%')";
    }
    if($prioritas_filter) {
        $where_conditions[] = "mwo.prioritas = '$prioritas_filter'";
    }
    if($status_filter !== '') {
        $where_conditions[] = "mwo.status_aktif = '$status_filter'";
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Count total records
    $count_query = "SELECT COUNT(*) as total 
                    FROM tbmaster_keluhan_workorder mwo
                    LEFT JOIN tbmaster_keluhan mk ON mwo.kode_keluhan = mk.kode_keluhan
                    LEFT JOIN tbworkorderheader wo ON mwo.kode_workorder = wo.kode_wo
                    $where_clause";
    $count_result = mysqli_query($koneksi, $count_query);
    $total_records = mysqli_fetch_array($count_result)['total'];
    $total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Master WorkOrder Mapping</title>

    <meta name="description" content="Master WorkOrder Mapping Management" />
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

    <!--[if lte IE 8]>
    <script src="assets/js/html5shiv.min.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->
    
    <style>
        /* Fixed Modal Styling */
        #modal-mapping .modal-dialog {
            width: 85%;
            max-width: 900px;
            margin: 30px auto;
        }
        
        #modal-mapping .modal-content {
            border-radius: 6px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        #modal-mapping .modal-header {
            background-color: #f5f5f5;
            border-bottom: 1px solid #e5e5e5;
            padding: 15px 20px;
        }
        
        #modal-mapping .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        
        #modal-mapping .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e5e5e5;
            background-color: #f5f5f5;
        }
        
        /* Fixed Chosen Dropdown Styling */
        #modal-mapping .chosen-container {
            font-size: 13px;
            width: 100% !important;
            position: relative;
            z-index: 1050;
        }
        
        #modal-mapping .chosen-container-single .chosen-single {
            height: 34px;
            line-height: 32px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        
        #modal-mapping .chosen-container-single .chosen-single div b {
            margin-top: 8px;
        }
        
        #modal-mapping .chosen-container .chosen-drop {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1051;
            background: #fff;
            border: 1px solid #ccc;
            border-top: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        #modal-mapping .chosen-container .chosen-results {
            max-height: 200px;
            overflow-y: auto;
            margin: 0;
            padding: 0;
        }
        
        #modal-mapping .chosen-container .chosen-results li {
            padding: 8px 12px;
            line-height: 1.4;
            list-style: none;
            border-bottom: 1px solid #f0f0f0;
        }
        
        #modal-mapping .chosen-container .chosen-results li:last-child {
            border-bottom: none;
        }
        
        #modal-mapping .chosen-container .chosen-results li.highlighted {
            background-color: #337ab7;
            background-image: none;
            color: white;
        }
        
        #modal-mapping .chosen-container .chosen-search input[type="text"] {
            height: 30px;
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Fixed Alert Styling */
        #modal-mapping .alert-sm {
            padding: 6px 12px;
            margin-top: 5px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        #modal-mapping .alert-sm .close {
            font-size: 16px;
            line-height: 1;
            right: 8px;
            top: 4px;
        }
        
        /* Form Group Spacing */
        #modal-mapping .form-group {
            margin-bottom: 15px;
        }
        
        #modal-mapping .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        #modal-mapping .text-muted {
            font-size: 11px;
            margin-top: 3px;
        }
        
        /* Preview Box */
        #workorder-preview {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background-color: #f9f9f9;
            margin-top: 10px;
        }
        
        #workorder-preview strong {
            color: #337ab7;
        }
        
        /* Simplified Loading and Indicators */
        .loading-indicator {
            text-align: center;
            padding: 10px;
            color: #666;
        }
        
        /* Search highlighting */
        .search-highlight {
            background: #ffeb3b;
            padding: 1px 2px;
            border-radius: 2px;
            font-weight: bold;
        }
        
        /* Suggestion styling */
        .suggested-option {
            background-color: #fff3cd !important;
            border-left: 3px solid #ffc107;
        }
        
        .suggested-option.highlighted {
            background-color: #337ab7 !important;
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #modal-mapping .modal-dialog {
                width: 95%;
                margin: 10px auto;
            }
            
            #modal-mapping .chosen-container {
                width: 100% !important;
            }
            
            #modal-mapping .modal-body {
                padding: 15px;
            }
        }
        
        /* Clean up conflicting styles */
        #modal-mapping .row {
            margin-left: -15px;
            margin-right: -15px;
            position: relative;
            z-index: 1;
        }
        
        #modal-mapping .col-sm-6 {
            padding-left: 15px;
            padding-right: 15px;
            position: relative;
            z-index: 1;
        }
        
        /* Prevent text overlap */
        #modal-mapping .form-group {
            position: relative;
            z-index: 1;
            clear: both;
        }
        
        #modal-mapping .alert {
            position: relative;
            z-index: 2;
            clear: both;
            margin-top: 10px;
        }
        
        /* Fix dropdown container positioning */
        #modal-mapping .chosen-container {
            position: relative;
            display: block;
            clear: both;
        }
        
        /* Ensure proper stacking */
        .modal-backdrop {
            z-index: 1040;
        }
        
        .modal {
            z-index: 1050;
        }
        
        .chosen-drop {
            z-index: 1060 !important;
        }
        
        /* Additional fixes for content separation */
        #modal-mapping .modal-body > .row {
            margin-bottom: 15px;
        }
        
        #modal-mapping .modal-body > .row:last-child {
            margin-bottom: 0;
        }
        
        /* Ensure alerts don't overlap */
        #modal-mapping .alert {
            position: relative;
            display: block;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        
        /* Fix for chosen dropdown in modal */
        .modal-open .chosen-container .chosen-drop {
            z-index: 1060;
        }
        
        /* Prevent content bleeding */
        #modal-mapping * {
            box-sizing: border-box;
        }
        
        /* Clear floats properly */
        #modal-mapping .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* Fix chosen dropdown height and positioning */
        #modal-mapping .chosen-container {
            position: relative;
            display: block;
            width: 100% !important;
            margin-bottom: 10px;
        }
        
        #modal-mapping .chosen-container-single .chosen-single {
            height: 34px;
            line-height: 32px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
        }
        
        #modal-mapping .chosen-container .chosen-drop {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1060;
            background: #fff;
            border: 1px solid #ccc;
            border-top: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
        }
        
        #modal-mapping .chosen-container .chosen-results {
            max-height: 180px;
            overflow-y: auto;
            margin: 0;
            padding: 0;
        }
        
        #modal-mapping .chosen-container .chosen-results li {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        #modal-mapping .chosen-container .chosen-results li:hover {
            background-color: #f5f5f5;
        }
        
        #modal-mapping .chosen-container .chosen-results li.highlighted {
            background-color: #337ab7;
            color: #fff;
        }
        
        /* Ensure form groups don't overlap */
        #modal-mapping .form-group {
            margin-bottom: 20px;
            position: relative;
            clear: both;
        }
        
        #modal-mapping .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        /* Fix small text positioning */
        #modal-mapping .form-group small {
            display: block;
            margin-top: 5px;
            clear: both;
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
        </div>
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
                            <a href="#">Master Data</a>
                        </li>                            
                        <li class="active">WorkOrder Mapping</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Master WorkOrder Mapping
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Mapping Keluhan ke WorkOrder
                            </small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-link"></i>
                                        Data Mapping Keluhan - WorkOrder
                                    </h4>
                                    <div class="widget-toolbar">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success btn-sm" onclick="showAddModal()">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Mapping
                                            </button>
                                            <button type="button" class="btn btn-warning btn-sm" onclick="bulkSync()">
                                                <i class="ace-icon fa fa-refresh"></i> Sync to Master
                                            </button>
                                            <a href="master-keluhan-crud.php" class="btn btn-info btn-sm">
                                                <i class="ace-icon fa fa-list"></i> Master Keluhan
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main">
                                        <!-- Info Section -->
                                        <div class="alert alert-info">
                                            <i class="ace-icon fa fa-info-circle"></i>
                                            <strong>Info:</strong> Mapping ini menentukan WorkOrder mana yang akan otomatis dipilih saat keluhan tertentu diinput. 
                                            Prioritas tinggi akan diprioritaskan jika ada multiple mapping untuk keluhan yang sama.
                                        </div>

                                        <!-- Filter Section -->
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <form method="GET" action="">
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <div class="form-group">
                                                                <label>Search:</label>
                                                                <input type="text" class="form-control" name="search" 
                                                                       value="<?php echo htmlspecialchars($search); ?>"
                                                                       placeholder="Kode keluhan, nama keluhan, atau workorder...">
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <div class="form-group">
                                                                <label>Prioritas:</label>
                                                                <select class="form-control" name="prioritas">
                                                                    <option value="">Semua</option>
                                                                    <option value="darurat" <?php echo ($prioritas_filter == 'darurat') ? 'selected' : ''; ?>>Darurat</option>
                                                                    <option value="tinggi" <?php echo ($prioritas_filter == 'tinggi') ? 'selected' : ''; ?>>Tinggi</option>
                                                                    <option value="sedang" <?php echo ($prioritas_filter == 'sedang') ? 'selected' : ''; ?>>Sedang</option>
                                                                    <option value="rendah" <?php echo ($prioritas_filter == 'rendah') ? 'selected' : ''; ?>>Rendah</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <div class="form-group">
                                                                <label>Status:</label>
                                                                <select class="form-control" name="status">
                                                                    <option value="">Semua</option>
                                                                    <option value="1" <?php echo ($status_filter == '1') ? 'selected' : ''; ?>>Aktif</option>
                                                                    <option value="0" <?php echo ($status_filter == '0') ? 'selected' : ''; ?>>Nonaktif</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <div class="form-group">
                                                                <label>&nbsp;</label><br>
                                                                <button type="submit" class="btn btn-info btn-sm">
                                                                    <i class="ace-icon fa fa-search"></i> Filter
                                                                </button>
                                                                <a href="master-workorder-mapping.php" class="btn btn-default btn-sm">
                                                                    <i class="ace-icon fa fa-refresh"></i> Reset
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Data Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%" class="center">No</th>
                                                        <th width="12%">Kode Keluhan</th>
                                                        <th width="25%">Nama Keluhan</th>
                                                        <th width="12%">Kode WorkOrder</th>
                                                        <th width="25%">Nama WorkOrder</th>
                                                        <th width="8%" class="center">Prioritas</th>
                                                        <th width="8%" class="center">Status</th>
                                                        <th width="5%" class="center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $query = "SELECT mwo.*, 
                                                                     mk.nama_keluhan, mk.kategori as keluhan_kategori,
                                                                     wo.nama_wo, wo.harga as wo_harga, wo.waktu as wo_waktu
                                                             FROM tbmaster_keluhan_workorder mwo
                                                             LEFT JOIN tbmaster_keluhan mk ON mwo.kode_keluhan = mk.kode_keluhan
                                                             LEFT JOIN tbworkorderheader wo ON mwo.kode_workorder = wo.kode_wo
                                                             $where_clause
                                                             ORDER BY mwo.prioritas DESC, mwo.kode_keluhan ASC, mwo.kode_workorder ASC
                                                             LIMIT $limit OFFSET $offset";
                                                    
                                                    $result = mysqli_query($koneksi, $query);
                                                    $no = $offset + 1;
                                                    
                                                    if($result && mysqli_num_rows($result) > 0) {
                                                        while($row = mysqli_fetch_array($result)) {
                                                            $prioritas_class = '';
                                                            switch($row['prioritas']) {
                                                                case 'darurat': $prioritas_class = 'label-danger'; break;
                                                                case 'tinggi': $prioritas_class = 'label-warning'; break;
                                                                case 'sedang': $prioritas_class = 'label-info'; break;
                                                                case 'rendah': $prioritas_class = 'label-success'; break;
                                                                default: $prioritas_class = 'label-default';
                                                            }
                                                            
                                                            echo "<tr>";
                                                            echo "<td class='center'>" . $no . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['kode_keluhan']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['nama_keluhan'] ?? 'Keluhan tidak ditemukan') . "<br>";
                                                            echo "<small class='text-muted'>" . htmlspecialchars($row['keluhan_kategori'] ?? '') . "</small></td>";
                                                            echo "<td>" . htmlspecialchars($row['kode_workorder']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['nama_wo'] ?? 'WorkOrder tidak ditemukan') . "<br>";
                                                            if($row['wo_harga']) {
                                                                echo "<small class='text-success'>Rp " . number_format($row['wo_harga'], 0, ',', '.') . " (" . $row['wo_waktu'] . " min)</small>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td class='center'>";
                                                            echo "<span class='label $prioritas_class'>" . ucfirst($row['prioritas']) . "</span>";
                                                            echo "</td>";
                                                            echo "<td class='center'>";
                                                            if($row['status_aktif'] == '1') {
                                                                echo "<span class='label label-success'>Aktif</span>";
                                                            } else {
                                                                echo "<span class='label label-default'>Nonaktif</span>";
                                                            }
                                                            echo "</td>";
                                                            echo "<td class='center'>";
                                                            echo "<div class='btn-group'>";
                                                            echo "<button type='button' class='btn btn-xs btn-info' onclick='showEditModal(" . $row['id'] . ")' title='Edit'>";
                                                            echo "<i class='ace-icon fa fa-edit'></i>";
                                                            echo "</button>";
                                                            
                                                            if($row['status_aktif'] == '1') {
                                                                echo "<button type='button' class='btn btn-xs btn-danger' onclick='deleteMapping(" . $row['id'] . ")' title='Nonaktifkan'>";
                                                                echo "<i class='ace-icon fa fa-trash'></i>";
                                                                echo "</button>";
                                                            } else {
                                                                echo "<button type='button' class='btn btn-xs btn-success' onclick='activateMapping(" . $row['id'] . ")' title='Aktifkan'>";
                                                                echo "<i class='ace-icon fa fa-check'></i>";
                                                                echo "</button>";
                                                            }
                                                            echo "</div>";
                                                            echo "</td>";
                                                            echo "</tr>";
                                                            $no++;
                                                        }
                                                    } else {
                                                        echo "<tr>";
                                                        echo "<td colspan='8' class='center'>Tidak ada data mapping</td>";
                                                        echo "</tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <?php if($total_pages > 1): ?>
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <div class="dataTables_info">
                                                    Menampilkan <?php echo $offset + 1; ?> sampai <?php echo min($offset + $limit, $total_records); ?> dari <?php echo $total_records; ?> entries
                                                </div>
                                            </div>
                                            <div class="col-xs-6">
                                                <div class="dataTables_paginate paging_simple_numbers pull-right">
                                                    <ul class="pagination">
                                                        <?php if($page > 1): ?>
                                                        <li class="paginate_button previous">
                                                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&prioritas=<?php echo urlencode($prioritas_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                                                        </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                                        <li class="paginate_button <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&prioritas=<?php echo urlencode($prioritas_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                                        </li>
                                                        <?php endfor; ?>
                                                        
                                                        <?php if($page < $total_pages): ?>
                                                        <li class="paginate_button next">
                                                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&prioritas=<?php echo urlencode($prioritas_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
                </div>
            </div>
        </div>
        
        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
    </div>

    <!-- Modal Add/Edit Mapping -->
    <div class="modal fade" id="modal-mapping" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="modal-title">Tambah Mapping</h4>
                </div>
                <form id="form-mapping" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="mapping_id">
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Keluhan <span class="text-danger">*</span>:</label>
                                    <select class="form-control chosen-select-keluhan" name="kode_keluhan" id="kode_keluhan" required 
                                            data-placeholder="Ketik untuk mencari keluhan...">
                                        <option value=""></option>
                                        <?php
                                        $keluhan_query = "SELECT kode_keluhan, nama_keluhan, kategori, tingkat_prioritas, estimasi_waktu 
                                                         FROM tbmaster_keluhan 
                                                         WHERE status_aktif='1' 
                                                         ORDER BY kategori, tingkat_prioritas DESC, nama_keluhan";
                                        $keluhan_result = mysqli_query($koneksi, $keluhan_query);
                                        while($keluhan_row = mysqli_fetch_array($keluhan_result)) {
                                            $prioritas_badge = '';
                                            switch($keluhan_row['tingkat_prioritas']) {
                                                case 'darurat': $prioritas_badge = '[DARURAT]'; break;
                                                case 'tinggi': $prioritas_badge = '[TINGGI]'; break;
                                                case 'sedang': $prioritas_badge = '[SEDANG]'; break;
                                                case 'rendah': $prioritas_badge = '[RENDAH]'; break;
                                            }
                                            
                                            echo "<option value='" . $keluhan_row['kode_keluhan'] . "' 
                                                    data-kategori='" . $keluhan_row['kategori'] . "'
                                                    data-prioritas='" . $keluhan_row['tingkat_prioritas'] . "'
                                                    data-waktu='" . $keluhan_row['estimasi_waktu'] . "'>";
                                            echo $keluhan_row['kode_keluhan'] . " - " . $keluhan_row['nama_keluhan'] . " " . $prioritas_badge . " (" . $keluhan_row['kategori'] . ")";
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                    <div style="margin-top: 8px; clear: both;">
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i> 
                                            Ketik kode keluhan, nama, atau kategori untuk mencari
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>WorkOrder <span class="text-danger">*</span>:</label>
                                    <select class="form-control chosen-select-workorder" name="kode_workorder" id="kode_workorder" required
                                            data-placeholder="Ketik untuk mencari workorder...">
                                        <option value=""></option>
                                        <?php
                                        $wo_query = "SELECT kode_wo, nama_wo, harga, waktu, keterangan 
                                                    FROM tbworkorderheader 
                                                    WHERE status = '0'
                                                    ORDER BY nama_wo";
                                        $wo_result = mysqli_query($koneksi, $wo_query);
                                        while($wo_row = mysqli_fetch_array($wo_result)) {
                                            echo "<option value='" . $wo_row['kode_wo'] . "' 
                                                    data-harga='" . $wo_row['harga'] . "'
                                                    data-waktu='" . $wo_row['waktu'] . "'
                                                    data-keterangan='" . htmlspecialchars($wo_row['keterangan']) . "'>";
                                            echo $wo_row['kode_wo'] . " - " . $wo_row['nama_wo'] . " | Rp " . number_format($wo_row['harga'], 0, ',', '.') . " | " . $wo_row['waktu'] . " min";
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                    <div style="margin-top: 8px; clear: both;">
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i> 
                                            Ketik kode WO, nama, atau harga untuk mencari
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Prioritas Mapping:</label>
                                    <select class="form-control" name="prioritas" id="prioritas">
                                        <option value="rendah">Rendah</option>
                                        <option value="sedang" selected>Sedang</option>
                                        <option value="tinggi">Tinggi</option>
                                        <option value="darurat">Darurat</option>
                                    </select>
                                    <small class="text-muted">Prioritas tinggi akan dipilih jika ada multiple mapping untuk keluhan yang sama</small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Preview WorkOrder:</label>
                                    <div id="workorder-preview" class="well well-sm" style="display: none;">
                                        <strong id="preview-nama-wo"></strong><br>
                                        <span class="text-success" id="preview-harga"></span> | 
                                        <span class="text-info" id="preview-waktu"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="alert alert-warning" style="margin-top: 15px; clear: both;">
                                    <i class="ace-icon fa fa-warning"></i>
                                    <strong>Perhatian:</strong> Jika sudah ada mapping dengan keluhan yang sama, prioritas yang lebih tinggi akan digunakan sebagai default.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Forms for Actions -->
    <form id="form-delete" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>
    
    <form id="form-activate" method="POST" style="display: none;">
        <input type="hidden" name="action" value="activate">
        <input type="hidden" name="id" id="activate_id">
    </form>
    
    <form id="form-bulk-sync" method="POST" style="display: none;">
        <input type="hidden" name="action" value="bulk_sync">
    </form>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        // WorkOrder data for preview
        var workorderData = {};
        
        <?php
        // Output workorder data to JavaScript
        $wo_js_query = "SELECT kode_wo, nama_wo, harga, waktu FROM tbworkorderheader";
        $wo_js_result = mysqli_query($koneksi, $wo_js_query);
        echo "var workorderData = {";
        while($wo_js_row = mysqli_fetch_array($wo_js_result)) {
            echo "'" . $wo_js_row['kode_wo'] . "': {";
            echo "'nama': '" . addslashes($wo_js_row['nama_wo']) . "',";
            echo "'harga': " . $wo_js_row['harga'] . ",";
            echo "'waktu': " . $wo_js_row['waktu'];
            echo "},";
        }
        echo "};";
        ?>

        function showAddModal() {
            $('#modal-title').text('Tambah Mapping');
            $('#action').val('add');
            $('#mapping_id').val('');
            $('#form-mapping')[0].reset();
            $('.chosen-select').trigger('chosen:updated');
            $('#workorder-preview').hide();
            $('#modal-mapping').modal('show');
        }

        function showEditModal(id) {
            $('#modal-title').text('Edit Mapping');
            $('#action').val('edit');
            $('#mapping_id').val(id);
            
            // AJAX to get mapping data
            $.ajax({
                url: 'ajax-get-mapping-detail.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        var data = response.data;
                        $('#kode_keluhan').val(data.kode_keluhan);
                        $('#kode_workorder').val(data.kode_workorder);
                        $('#prioritas').val(data.prioritas);
                        $('.chosen-select').trigger('chosen:updated');
                        updateWorkorderPreview();
                        $('#modal-mapping').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading data');
                }
            });
        }

        function deleteMapping(id) {
            if(confirm('Yakin ingin menonaktifkan mapping ini?')) {
                $('#delete_id').val(id);
                $('#form-delete').submit();
            }
        }

        function activateMapping(id) {
            if(confirm('Yakin ingin mengaktifkan mapping ini?')) {
                $('#activate_id').val(id);
                $('#form-activate').submit();
            }
        }

        function bulkSync() {
            if(confirm('Yakin ingin sync semua mapping ke master keluhan?\nIni akan mengupdate workorder_default di master keluhan berdasarkan mapping dengan prioritas tertinggi.')) {
                $('#form-bulk-sync').submit();
            }
        }

        function updateWorkorderPreview() {
            var kodeWo = $('#kode_workorder').val();
            if(kodeWo && workorderData[kodeWo]) {
                var wo = workorderData[kodeWo];
                $('#preview-nama-wo').text(wo.nama);
                $('#preview-harga').text('Rp ' + number_format(wo.harga, 0, ',', '.'));
                $('#preview-waktu').text(wo.waktu + ' menit');
                $('#workorder-preview').show();
            } else {
                $('#workorder-preview').hide();
            }
        }

        function number_format(number, decimals, dec_point, thousands_sep) {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function(n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }

        // Event handlers
        $('#kode_workorder').on('change', updateWorkorderPreview);

        // Form validation
        $('#form-mapping').on('submit', function(e) {
            var kodeKeluhan = $('#kode_keluhan').val();
            var kodeWorkorder = $('#kode_workorder').val();
            
            if(!kodeKeluhan || !kodeWorkorder) {
                e.preventDefault();
                alert('Keluhan dan WorkOrder harus dipilih!');
                return false;
            }
            
            return true;
        });

        jQuery(function($) {
            if(!ace.vars['touch']) {
                // Simple Chosen for Keluhan dropdown
                $('.chosen-select-keluhan').chosen({
                    allow_single_deselect: true,
                    search_contains: true,
                    placeholder_text_single: "Ketik untuk mencari keluhan...",
                    no_results_text: "Tidak ditemukan keluhan:",
                    width: "100%"
                }); 
                
                // Simple Chosen for WorkOrder dropdown
                $('.chosen-select-workorder').chosen({
                    allow_single_deselect: true,
                    search_contains: true,
                    placeholder_text_single: "Ketik untuk mencari workorder...",
                    no_results_text: "Tidak ditemukan workorder:",
                    width: "100%"
                });
                
                // Regular chosen for other dropdowns
                $('.chosen-select').chosen({
                    allow_single_deselect: true,
                    search_contains: true,
                    width: "100%"
                }); 
                
                // Window resize handler
                $(window)
                .off('resize.chosen')
                .on('resize.chosen', function() {
                    $('.chosen-select, .chosen-select-keluhan, .chosen-select-workorder').each(function() {
                         var $this = $(this);
                         $this.next().css({'width': $this.parent().width()});
                    })
                }).trigger('resize.chosen');
            }
            
            // Simple keluhan selection
            $('#kode_keluhan').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var kategori = selectedOption.data('kategori');
                var prioritas = selectedOption.data('prioritas');
                var waktu = selectedOption.data('waktu');
                
                if (kategori) {
                    showKeluhanInfo(kategori, prioritas, waktu);
                    suggestWorkorderBasedOnKeluhan(kategori, prioritas);
                }
            });
            
            // Simple workorder selection
            $('#kode_workorder').on('change', function() {
                updateWorkorderPreview();
                var selectedOption = $(this).find('option:selected');
                var harga = selectedOption.data('harga');
                var waktu = selectedOption.data('waktu');
                var keterangan = selectedOption.data('keterangan');
                
                if (harga) {
                    showWorkorderInfo(harga, waktu, keterangan);
                }
            });
        });
        
        // Show keluhan information
        function showKeluhanInfo(kategori, prioritas, waktu) {
            var info = '<div class="alert alert-info alert-sm">';
            info += '<i class="fa fa-info-circle"></i> ';
            info += '<strong>Info Keluhan:</strong> ';
            info += 'Kategori: ' + kategori + ' | ';
            info += 'Prioritas: ' + prioritas.toUpperCase() + ' | ';
            info += 'Est. Waktu: ' + waktu + ' menit';
            info += '</div>';
            
            $('#keluhan-info').remove(); // Remove existing info
            $('#kode_keluhan').closest('.form-group').append('<div id="keluhan-info">' + info + '</div>');
        }
        
        // Show workorder information  
        function showWorkorderInfo(harga, waktu, keterangan) {
            var info = '<div class="alert alert-success alert-sm">';
            info += '<i class="fa fa-cogs"></i> ';
            info += '<strong>Info WorkOrder:</strong> ';
            info += 'Harga: Rp ' + number_format(harga, 0, ',', '.') + ' | ';
            info += 'Waktu: ' + waktu + ' menit';
            if (keterangan) {
                info += ' | ' + keterangan;
            }
            info += '</div>';
            
            $('#workorder-info').remove(); // Remove existing info
            $('#kode_workorder').closest('.form-group').append('<div id="workorder-info">' + info + '</div>');
        }
        
        // Simple workorder suggestion system
        function suggestWorkorderBasedOnKeluhan(kategori, prioritas) {
            var suggestions = {
                'Mesin': {
                    'darurat': 'WO0005',
                    'tinggi': 'WO0005', 
                    'sedang': 'WO0001',
                    'rendah': 'WO0001'
                },
                'Rem': {
                    'darurat': 'WO0002',
                    'tinggi': 'WO0002',
                    'sedang': 'WO0002', 
                    'rendah': 'WO0001'
                },
                'Elektrik': {
                    'darurat': 'WO0005',
                    'tinggi': 'WO0005',
                    'sedang': 'WO0001',
                    'rendah': 'WO0001'
                }
            };
            
            var suggestedWO = suggestions[kategori] && suggestions[kategori][prioritas] ? suggestions[kategori][prioritas] : 'WO0001';
            
            // Auto-select suggested workorder if available
            if ($('#kode_workorder option[value="' + suggestedWO + '"]').length > 0) {
                $('#kode_workorder').val(suggestedWO);
                $('#kode_workorder').trigger('chosen:updated');
                $('#kode_workorder').trigger('change');
                
                // Show simple suggestion notification
                var suggestion = '<div class="alert alert-info alert-sm" id="suggestion-alert">';
                suggestion += '<i class="fa fa-lightbulb-o"></i> ';
                suggestion += '<strong>Saran:</strong> WorkOrder <strong>' + suggestedWO + '</strong> dipilih otomatis berdasarkan kategori dan prioritas keluhan.';
                suggestion += '<button type="button" class="close" onclick="$(this).parent().remove();">&times;</button>';
                suggestion += '</div>';
                
                $('#suggestion-alert').remove();
                $('#kode_workorder').closest('.form-group').append(suggestion);
                
                // Auto remove suggestion after 5 seconds
                setTimeout(function() {
                    $('#suggestion-alert').fadeOut();
                }, 5000);
            }
        }
        
        // Enhanced search functionality with type-to-find indicators
        function initEnhancedSearch() {
            // Custom search for keluhan with enhanced placeholder
            $('#kode_keluhan_chosen .chosen-search input').attr({
                'placeholder': '🔍 Ketik kode, nama, kategori, atau prioritas...',
                'autocomplete': 'off',
                'spellcheck': 'false'
            });
            
            // Custom search for workorder with enhanced placeholder
            $('#kode_workorder_chosen .chosen-search input').attr({
                'placeholder': '🔧 Ketik kode, nama, harga, atau waktu...',
                'autocomplete': 'off',
                'spellcheck': 'false'
            });
            
            // Add search tips
            addSearchTips();
        }
        
        // Add type-to-find indicators
        function addTypeToFindIndicators() {
            $('.chosen-container').each(function() {
                if (!$(this).find('.type-indicator').length) {
                    $(this).append('<div class="type-indicator">Type to find</div>');
                }
            });
        }
        
        // Show type indicator
        function showTypeIndicator(container, text) {
            container.find('.type-indicator').text(text).addClass('show');
        }
        
        // Hide type indicator
        function hideTypeIndicator(container) {
            container.find('.type-indicator').removeClass('show');
        }
        
        // Highlight search results
        function highlightSearchResults(container, searchTerm) {
            container.find('.chosen-results li').each(function() {
                var text = $(this).text();
                var highlightedText = text.replace(new RegExp('(' + searchTerm + ')', 'gi'), '<span class="search-highlight">$1</span>');
                $(this).html(highlightedText);
            });
        }
        
        // Highlight suggested options
        function highlightSuggestedOptions(suggestedWOs) {
            $('#kode_workorder_chosen .chosen-results li').removeClass('suggested-option');
            suggestedWOs.forEach(function(woCode) {
                $('#kode_workorder_chosen .chosen-results li[data-option-array-index]').each(function() {
                    if ($(this).text().indexOf(woCode) === 0) {
                        $(this).addClass('suggested-option').prepend('⭐ ');
                    }
                });
            });
        }
        
        // Update mapping priority based on keluhan priority
        function updateMappingPriority(keluhanPrioritas) {
            var mappingPrioritas = $('#prioritas');
            var suggestedPriority = keluhanPrioritas;
            
            // Auto-adjust mapping priority to match keluhan priority
            if (mappingPrioritas.val() === 'sedang' || mappingPrioritas.val() === '') {
                mappingPrioritas.val(suggestedPriority);
                
                // Show priority adjustment notification
                var notification = '<div class="alert alert-info alert-sm" id="priority-notification">';
                notification += '<i class="fa fa-level-up"></i> ';
                notification += '<strong>Auto-Adjust:</strong> Prioritas mapping disesuaikan ke <strong>' + suggestedPriority.toUpperCase() + '</strong> mengikuti prioritas keluhan.';
                notification += '</div>';
                
                $('#priority-notification').remove();
                mappingPrioritas.closest('.form-group').append(notification);
                
                setTimeout(function() {
                    $('#priority-notification').fadeOut();
                }, 4000);
            }
        }
        
        // Validate mapping combination
        function validateMappingCombination() {
            var keluhanCode = $('#kode_keluhan').val();
            var workorderCode = $('#kode_workorder').val();
            
            if (keluhanCode && workorderCode) {
                // Check if combination already exists via AJAX
                $.ajax({
                    url: 'ajax-check-mapping-exists.php',
                    type: 'GET',
                    data: { 
                        kode_keluhan: keluhanCode, 
                        kode_workorder: workorderCode 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            showValidationWarning('⚠️ Mapping ini sudah ada dengan prioritas: ' + response.prioritas.toUpperCase());
                        } else {
                            hideValidationWarning();
                        }
                    }
                });
            }
        }
        
        // Show validation warning
        function showValidationWarning(message) {
            $('#validation-warning').remove();
            var warning = '<div class="alert alert-warning alert-sm" id="validation-warning">';
            warning += '<i class="fa fa-exclamation-triangle"></i> ' + message;
            warning += '</div>';
            $('.modal-body').prepend(warning);
        }
        
        // Hide validation warning
        function hideValidationWarning() {
            $('#validation-warning').fadeOut(300, function() {
                $(this).remove();
            });
        }
        
        // Add search tips
        function addSearchTips() {
            var tips = '<div class="alert alert-info alert-sm" style="margin-top: 10px;">';
            tips += '<i class="fa fa-lightbulb-o"></i> ';
            tips += '<strong>Tips Pencarian:</strong> ';
            tips += 'Gunakan spasi untuk mencari multiple kata, contoh: "mesin darurat" atau "rem tinggi"';
            tips += '</div>';
            
            if (!$('.search-tips').length) {
                $('.modal-body .row:first').after('<div class="search-tips">' + tips + '</div>');
            }
        }
        
        // Initialize enhanced search after modal is shown
        $('#modal-mapping').on('shown.bs.modal', function() {
            setTimeout(function() {
                initEnhancedSearch();
                addTypeToFindIndicators();
            }, 150);
        });
        
        // Add CSS for suggested options
        $('<style>').text(`
            .chosen-results li.suggested-option {
                background-color: #fff3cd !important;
                border-left: 3px solid #ffc107;
                font-weight: 500;
            }
            .chosen-results li.suggested-option.highlighted {
                background-color: #337ab7 !important;
                color: white;
            }
            .search-tips {
                margin: 15px 0;
            }
        `).appendTo('head');
    </script>
</body>
</html>

<?php 
}
?>