<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];		
    $kd_cabang=$_SESSION['_cabang'];		                
    include "../config/koneksi.php";
    
    // Get user data
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_user, password, user_akses, foto_user 
                                    FROM tbuser WHERE id='$id_user'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];				        
    $pwd=$tm_cari['password'];				        
    $lvl_akses=$tm_cari['user_akses'];				                
    $foto_user = $tm_cari['foto_user'] ?? '../file_upload/avatar.png';
    if (empty($foto_user)) {
        $foto_user = "../file_upload/avatar.png";
    } elseif (strpos($foto_user, '../') !== 0 && strpos($foto_user, 'http') !== 0) {
        $foto_user = '../' . ltrim($foto_user, '/');
    }

    // Get branch data
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    nama_cabang, tipe_cabang 
                                    FROM tbcabang 
                                    WHERE kode_cabang='$kd_cabang'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];				        
    $tipe_cabang=$tm_cari['tipe_cabang'];	
    
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

    <meta name="description" content="Master Posisi Management" />
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

    <style>
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
        
        .filter-section {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
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
                        <li class="active">Master Posisi</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <h4 class="header blue"><i class="fa fa-briefcase"></i> Master Posisi</h4>
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Kelola data posisi karyawan</small>
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
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Cari Posisi:</label>
                                                    <input type="text" id="searchInput" class="form-control" placeholder="Nama atau Kode Posisi">
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
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Posisi</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="table-responsive">
                                            <table id="posisiTable" class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 5%;">No</th>
                                                        <th style="width: 20%;">Kode Posisi</th>
                                                        <th style="width: 50%;">Nama Posisi</th>
                                                        <th style="width: 25%;">Deskripsi</th>
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
            loadPosisiData();

            // Filter button
            $('#btnFilter').click(function() {
                loadPosisiData();
            });

            // Reset button
            $('#btnReset').click(function() {
                $('#searchInput').val('');
                loadPosisiData();
            });

            // Search on enter
            $('#searchInput').keypress(function(e) {
                if(e.which == 13) {
                    loadPosisiData();
                    return false;
                }
            });
        });

        function loadPosisiData() {
            var search = $('#searchInput').val();

            $.ajax({
                url: 'master_posisi_ajax.php',
                type: 'POST',
                data: {
                    action: 'getList',
                    search: search
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
                html = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
            } else {
                $.each(data, function(index, row) {
                    html += '<tr>';
                    html += '<td>' + no + '</td>';
                    html += '<td><strong>' + row.kode_posisi + '</strong></td>';
                    html += '<td>' + row.nama_posisi + '</td>';
                    html += '<td>' + (row.deskripsi || '-') + '</td>';
                    html += '</tr>';
                    no++;
                });
            }

            $('#tableBody').html(html);
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

<?php
}
?>
