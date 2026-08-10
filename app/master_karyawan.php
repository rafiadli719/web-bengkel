<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];                       // Get user data from session
    include "../config/koneksi.php";
    include "../config/permission_check.php";
    require_once "_include_menu_rbac.php";

    // Optional debug mode: add ?debug=1 to URL
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }


    $_nama = $_SESSION['_nama_lengkap'] ?? $_SESSION['_username'] ?? 'User';
    $lvl_akses = $_SESSION['user_akses'] ?? 0;
    $foto_user = $_SESSION['_foto'] ?? '../file_upload/avatar.png';
    if (empty($foto_user)) {
        $foto_user = "../file_upload/avatar.png";
    } elseif (strpos($foto_user, '../') !== 0 && strpos($foto_user, 'http') !== 0) {
        $foto_user = '../' . ltrim($foto_user, '/');
    }


    if (!canAccessPage($koneksi, $id_user, 'master_karyawan_read')) {
        header("location:403.php?permission=master_karyawan_read");
        exit();
    }

    // Get branch data
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");            
    if ($cari_kd && mysqli_num_rows($cari_kd) > 0) {
        $tm_cari=mysqli_fetch_array($cari_kd);
        $nama_cabang=$tm_cari['nama_cabang'];                 
        $tipe_cabang=$tm_cari['tipe_cabang']; 
    } else {
        $nama_cabang = 'Cabang';
        $tipe_cabang = '';
    }
    
    $tgl_skr=date('d');	
    $bulan_skr=date('m');
    $thn_skr=date('Y');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>

    <meta name="description" content="Master Karyawan Management" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- page specific plugin styles -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fullcalendar.min.css" />

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

    <!-- inline styles related to this page -->

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

    <!--[if lte IE 8]>
    <script src="assets/js/html5shiv.min.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->
    <script type="text/javascript" src="chartjs/Chart.js"></script>

    <!-- External scripts removed to avoid tracking prevention issues -->
    <!-- If needed, add them back locally or use different CDN -->
    
    <style>
        .page-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .btn-group-sm .btn {
            padding: 3px 8px;
            font-size: 12px;
        }
        
        .table-responsive {
            margin-top: 20px;
        }
        
        .badge-posisi {
            font-size: 11px;
            padding: 4px 8px;
        }
        
        .badge-level {
            font-size: 11px;
            padding: 4px 8px;
        }
        
        .status-aktif {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-nonaktif {
            color: #dc3545;
            font-weight: bold;
        }
        
        .search-box {
            margin-bottom: 15px;
        }
        
        .filter-section {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        #navbar.navbar {
            min-height: 56px;
            border: 0;
        }

        #navbar .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 56px;
            padding: 0 16px;
        }

        #navbar .navbar-header {
            display: flex;
            align-items: center;
        }

        #navbar .navbar-brand {
            height: auto;
            padding: 0;
            display: flex;
            align-items: center;
            color: #d8e6f3;
        }

        #navbar .navbar-brand small {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 28px;
            line-height: 1;
        }

        #navbar .navbar-brand i {
            font-size: 22px;
            color: #2d7fd3;
        }

        #navbar .navbar-buttons {
            margin: 0;
        }

        #navbar .ace-nav > li {
            height: auto;
            line-height: normal;
            background: transparent;
            border: 0;
        }

        #navbar .ace-nav > li > a {
            height: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            background: transparent;
        }

        #navbar .nav-user-photo {
            width: 38px;
            height: 38px;
            max-width: 38px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.55);
            object-fit: cover;
            margin: 0;
        }

        #navbar .user-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            line-height: 1.15;
            margin: 0;
            color: #f3f7fb;
        }

        #navbar .user-info small {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .04em;
            opacity: .75;
            margin-bottom: 2px;
        }

        #navbar .user-info .user-name-text {
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
        }

        #navbar .ace-nav > li > a .fa-caret-down {
            color: rgba(255,255,255,0.8);
        }

        @media (max-width: 767px) {
            #navbar .navbar-container {
                padding: 0 10px;
            }

            #navbar .navbar-brand small {
                font-size: 22px;
            }

            #navbar .user-info small {
                display: none;
            }
        }
    </style>
</head>

<body class="no-skin">

    <div id="navbar" class="navbar navbar-default          ace-save-state">
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
                        <?php include "../lib/logo.php"; ?>
                        <?php include "../lib/subtitel.php"; ?>
                    </small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                
                <ul class="nav ace-nav">

                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <span class="user-name-text"><?php echo $_nama; ?></span>
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

        <div id="sidebar" class="sidebar                  responsive                    ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_dashboard.php"; ?>

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
                        <li class="active">Master Karyawan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <h4 class="header blue"><i class="fa fa-users"></i> Master Karyawan</h4>
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Kelola data karyawan, user, mekanik, dan kepala mekanik</small>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <div id="alertContainer"></div>

                    <!-- Filter Section -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-filter"></i> Filter Data</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Cari Karyawan:</label>
                                                    <input type="text" id="searchInput" class="form-control" placeholder="Nama atau Kode Karyawan">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Filter Posisi:</label>
                                                    <select id="filterPosisi" class="form-control">
                                                        <option value="">-- Semua Posisi --</option>
                                                        <option value="MK">Mekanik</option>
                                                        <option value="KM">Kepala Mekanik</option>
                                                        <option value="ADM">Admin</option>
                                                        <option value="KSR">Kasir</option>
                                                        <option value="USR">User</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Filter Level:</label>
                                                    <select id="filterLevel" class="form-control">
                                                        <option value="">-- Semua Level --</option>
                                                        <option value="KM">Kepala</option>
                                                        <option value="MS">Senior</option>
                                                        <option value="MJ">Junior</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Filter Status:</label>
                                                    <select id="filterStatus" class="form-control">
                                                        <option value="">-- Semua Status --</option>
                                                        <option value="aktif">Aktif</option>
                                                        <option value="nonaktif">Non-Aktif</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button class="btn btn-sm btn-primary" id="btnFilter">
                                                    <i class="fa fa-search"></i> Filter
                                                </button>
                                                <button class="btn btn-sm btn-default" id="btnReset">
                                                    <i class="fa fa-refresh"></i> Reset
                                                </button>
                                                <a href="master_karyawan_add.php" class="btn btn-sm btn-success pull-right">
                                                    <i class="fa fa-plus"></i> Tambah Karyawan
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Karyawan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table id="karyawanTable" class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 5%;">No</th>
                                                        <th style="width: 12%;">Kode Karyawan</th>
                                                        <th style="width: 20%;">Nama Lengkap</th>
                                                        <th style="width: 12%;">Posisi</th>
                                                        <th style="width: 10%;">Level</th>
                                                        <th style="width: 10%;">Cabang</th>
                                                        <th style="width: 10%;">Status</th>
                                                        <th style="width: 21%;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tableBody">
                                                    <!-- Data akan dimuat via AJAX -->
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

    <script>
        $(document).ready(function() {
            loadKaryawanData();

            // Filter button
            $('#btnFilter').click(function() {
                loadKaryawanData();
            });

            // Reset button
            $('#btnReset').click(function() {
                $('#searchInput').val('');
                $('#filterPosisi').val('');
                $('#filterLevel').val('');
                $('#filterStatus').val('');
                loadKaryawanData();
            });

            // Search on enter
            $('#searchInput').keypress(function(e) {
                if(e.which == 13) {
                    loadKaryawanData();
                    return false;
                }
            });
        });

        function loadKaryawanData() {
            var search = $('#searchInput').val();
            var posisi = $('#filterPosisi').val();
            var level = $('#filterLevel').val();
            var status = $('#filterStatus').val();

            $.ajax({
                url: 'master_karyawan_ajax.php',
                type: 'POST',
                data: {
                    action: 'getList',
                    search: search,
                    posisi: posisi,
                    level: level,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        displayTable(response.data);
                    } else {
                        showAlert('danger', 'Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    var errorMsg = 'Error loading data';
                    if(xhr.status === 500) {
                        errorMsg = 'Server error (500). Check console for details.';
                    } else if(xhr.status === 401) {
                        errorMsg = 'Unauthorized. Please login again.';
                    } else if(xhr.responseText) {
                        try {
                            var json = JSON.parse(xhr.responseText);
                            errorMsg = json.message || errorMsg;
                        } catch(e) {
                            errorMsg = xhr.responseText;
                        }
                    }
                    
                    showAlert('danger', errorMsg);
                }
            });
        }

        function displayTable(data) {
            var html = '';
            var no = 1;

            if(data.length === 0) {
                html = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
            } else {
                $.each(data, function(index, row) {
                    // Determine status based on is_active from tbuser_karyawan (nilai: 'aktif'/'nonaktif')
                    var isAktif = (row.is_active === 'aktif' || row.is_active === null || typeof row.is_active === 'undefined');
                    var statusClass = isAktif ? 'label label-success' : 'label label-danger';
                    var statusText = isAktif ? 'Aktif' : 'Non-Aktif';
                    
                    var posisiText = getPosisiText(row.kode_posisi);
                    var levelText = getLevelText(row.kode_level);

                    html += '<tr>';
                    html += '<td>' + no + '</td>';
                    html += '<td><strong>' + row.kode_karyawan + '</strong></td>';
                    html += '<td>' + row.nama_lengkap + '</td>';
                    html += '<td><span class="badge badge-posisi badge-info">' + posisiText + '</span></td>';
                    html += '<td><span class="badge badge-level badge-warning">' + levelText + '</span></td>';
                    html += '<td>' + row.kode_cabang + '</td>';
                    html += '<td><span class="' + statusClass + '">' + statusText + '</span></td>';
                    html += '<td>';
                    html += '<div class="btn-group btn-group-sm">';
                    html += '<a href="master_karyawan_edit.php?id=' + row.id + '" class="btn btn-primary" title="Edit"><i class="fa fa-edit"></i> Edit</a>';
                    html += '<button class="btn btn-danger" onclick="deleteKaryawan(' + row.id + ')" title="Hapus"><i class="fa fa-trash"></i> Hapus</button>';
                    html += '</div>';
                    html += '</td>';
                    html += '</tr>';
                    no++;
                });
            }

            $('#tableBody').html(html);
        }

        function getPosisiText(kode) {
            var posisi = {
                'MK': 'Mekanik',
                'KM': 'Kepala Mekanik',
                'ADM': 'Admin',
                'KSR': 'Kasir',
                'USR': 'User'
            };
            return posisi[kode] || kode;
        }

        function getLevelText(kode) {
            var level = {
                'KM': 'Kepala',
                'MS': 'Senior',
                'MJ': 'Junior'
            };
            return level[kode] || kode;
        }

        function deleteKaryawan(id) {
            if(confirm('Apakah Anda yakin ingin menghapus karyawan ini?')) {
                $.ajax({
                    url: 'master_karyawan_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            showAlert('success', response.message);
                            loadKaryawanData();
                        } else {
                            showAlert('danger', 'Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Delete Error:', {
                            status: xhr.status,
                            responseText: xhr.responseText
                        });
                        
                        var errorMsg = 'Error deleting data';
                        if(xhr.responseText) {
                            try {
                                var json = JSON.parse(xhr.responseText);
                                errorMsg = json.message || errorMsg;
                            } catch(e) {
                                errorMsg = xhr.responseText;
                            }
                        }
                        
                        showAlert('danger', errorMsg);
                    }
                });
            }
        }

        function showAlert(type, message) {
            var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade in" role="alert">';
            alertHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            alertHtml += '<span aria-hidden="true">&times;</span></button>';
            alertHtml += message + '</div>';
            
            $('#alertContainer').html(alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('#alertContainer').fadeOut(function() {
                    $(this).html('').show();
                });
            }, 5000);
        }
    </script>
</body>
</html>

<?php } ?>
