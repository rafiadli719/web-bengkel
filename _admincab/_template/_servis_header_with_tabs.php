<?php
// Template header untuk input servis dengan sistem tab
// Digunakan untuk servis-input-reguler.php, servis-input-reguler-jemput.php, dan servis-garansi.php

// Generate nomor antrian berdasarkan jenis servis
function generateAntrianNumber($jenis_servis) {
    $prefix = '';
    switch($jenis_servis) {
        case 'garansi':
            $prefix = 'KOMP'; // Komplain DLL untuk servis garansi
            break;
        case 'jemput':
            $prefix = 'JEM'; // Jemput
            break;
        case 'reguler':
        default:
            $prefix = 'REG'; // Reguler
            break;
    }
    
    $tanggal = date('Y-m-d');
    $query = mysqli_query($GLOBALS['koneksi'], "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal' AND no_antrian LIKE '$prefix%'");
    $result = mysqli_fetch_array($query);
    $urutan = $result['total'] + 1;
    
    return $prefix . str_pad($urutan, 3, '0', STR_PAD_LEFT);
}

// Get current antrian number if exists
$current_antrian = '';
if(!empty($no_service)) {
    $query_antrian = mysqli_query($koneksi, "SELECT no_antrian FROM tb_antrian_servis WHERE no_service = '$no_service'");
    if($query_antrian && mysqli_num_rows($query_antrian) > 0) {
        $antrian_data = mysqli_fetch_array($query_antrian);
        $current_antrian = $antrian_data['no_antrian'];
    }
}

// Determine jenis servis for antrian generation
$jenis_servis = 'reguler'; // default
if(strpos($_SERVER['PHP_SELF'], 'garansi') !== false) {
    $jenis_servis = 'garansi';
} elseif(strpos($_SERVER['PHP_SELF'], 'jemput') !== false) {
    $jenis_servis = 'jemput';
}

$suggested_antrian = generateAntrianNumber($jenis_servis);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>

    <meta name="description" content="Input Service dengan Sistem Antrian" />
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

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->
    <!--[if lte IE 8]>
    <script src="assets/js/html5shiv.min.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->

    <!-- Custom CSS for Service Input -->
    <style>
        .antrian-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .antrian-info {
            text-align: center;
            margin-bottom: 5px;
        }
        
        .antrian-number {
            font-size: 2em;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-menunggu { background: #f39c12; color: #fff; }
        .status-diproses { background: #3498db; color: #fff; }
        .status-selesai { background: #27ae60; color: #fff; }
        .status-batal { background: #e74c3c; color: #fff; }
        
        .prioritas-badge {
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .prioritas-normal { background: #95a5a6; color: #fff; }
        .prioritas-urgent { background: #e74c3c; color: #fff; }
        .prioritas-vip { background: #9b59b6; color: #fff; }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .progress-section {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .action-buttons {
            text-align: center;
            padding: 20px 0;
        }
        
        .action-buttons .btn {
            margin: 0 5px;
            min-width: 120px;
        }
        
        /* Tab styling - using Bootstrap classes */
        .nav-tabs > li > a {
            cursor: pointer;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .tab-pane { min-height: 300px; display: none; }
        .tab-pane.active { display: block; }
        
        .nav-tabs > li.active > a {
            background-color: #f8f9fa;
            border-color: #dee2e6 #dee2e6 #fff;
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
                            <a href="#">Service</a>
                        </li>
                        <li class="active">Input Service</li>
                    </ul>
                </div>

                <div class="page-content">
                    <form class="form-horizontal" action="" method="post" role="form" id="formService">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-blue widget-header-flat">
                                        <h4 class="widget-title lighter">
                                            <i class="ace-icon fa fa-wrench orange"></i>
                                            Input Service #<?php echo $no_service ? $no_service : 'NEW'; ?>
                                        </h4>
                                        <div class="widget-toolbar">
                                            <span class="label label-info arrowed-in arrowed-in-right">Service Order</span>
                                        </div>
                                    </div>

                                    <div class="widget-body">
                                        <div class="widget-main padding-12 no-padding-left no-padding-right">
                                            
                                            <!-- Antrian Section -->
                                            <div class="antrian-section">
                                                <div class="row">
                                                    <div class="col-xs-12 col-sm-6">
                                                        <h5><i class="fa fa-list-ol"></i> Sistem Antrian</h5>
                                                        <div class="antrian-info">
                                                            <small class="text-muted">
                                                                <?php 
                                                                $jenis_text = '';
                                                                switch($jenis_servis) {
                                                                    case 'garansi':
                                                                        $jenis_text = 'Komplain DLL';
                                                                        break;
                                                                    case 'jemput':
                                                                        $jenis_text = 'Servis Jemput';
                                                                        break;
                                                                    case 'reguler':
                                                                    default:
                                                                        $jenis_text = 'Servis Reguler';
                                                                        break;
                                                                }
                                                                echo $jenis_text;
                                                                ?>
                                                            </small>
                                                        </div>
                                                        <div class="antrian-number" id="antrianNumber">
                                                            <?php echo $current_antrian ? $current_antrian : $suggested_antrian; ?>
                                                        </div>
                                                        <div class="text-center">
                                                            <button type="button" class="btn btn-sm btn-warning" onclick="generateAntrian()">
                                                                <i class="fa fa-refresh"></i> Generate Antrian
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 col-sm-6">
                                                        <div class="form-group">
                                                            <label class="col-sm-4 control-label">Prioritas:</label>
                                                            <div class="col-sm-8">
                                                                <select class="form-control" name="prioritas_antrian" id="prioritas_antrian">
                                                                    <option value="normal">Normal</option>
                                                                    <option value="urgent">Urgent</option>
                                                                    <option value="vip">VIP</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-sm-4 control-label">Estimasi (menit):</label>
                                                            <div class="col-sm-8">
                                                                <input type="number" class="form-control" name="estimasi_waktu" id="estimasi_waktu" placeholder="60">
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-sm-4 control-label">Catatan:</label>
                                                            <div class="col-sm-8">
                                                                <textarea class="form-control" name="catatan_antrian" id="catatan_antrian" rows="2" placeholder="Catatan khusus..."></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tabbable">
                                                <ul class="nav nav-tabs" id="myTab">
                                                    <li class="active">
                                                        <a data-toggle="tab" href="#service-details" aria-expanded="true">
                                                            <i class="green ace-icon fa fa-info-circle bigger-120"></i>
                                                            Detail Service
                                                        </a>
                                                    </li>
                                                    <li class="">
                                                        <a data-toggle="tab" href="#workorder-details" aria-expanded="false">
                                                            <i class="blue ace-icon fa fa-clipboard bigger-120"></i>
                                                            Work Order
                                                        </a>
                                                    </li>
                                                    <li class="">
                                                        <a data-toggle="tab" href="#service-items" aria-expanded="false">
                                                            <i class="orange ace-icon fa fa-truck bigger-120"></i>
                                                            Item Barang
                                                        </a>
                                                    </li>
                                                    <li class="">
                                                        <a data-toggle="tab" href="#service-jasa" aria-expanded="false">
                                                            <i class="purple ace-icon fa fa-cogs bigger-120"></i>
                                                            Item Jasa
                                                        </a>
                                                    </li>
                                                    <li class="">
                                                        <a data-toggle="tab" href="#mechanic-progress" aria-expanded="false">
                                                            <i class="red ace-icon fa fa-users bigger-120"></i>
                                                            Progress Mekanik
                                                        </a>
                                                    </li>
                                                    <li class="">
                                                        <a data-toggle="tab" href="#service-actions" aria-expanded="false">
                                                            <i class="grey ace-icon fa fa-cogs bigger-120"></i>
                                                            Actions
                                                        </a>
                                                    </li>
                                                </ul>

                                                <div class="tab-content">
                                                    <!-- Tab 1: Detail Service -->
                                                    <div id="service-details" class="tab-pane active">
                                                        <?php include "_servis_detail_tab.php"; ?>
                                                    </div>
                                                    
                                                    <!-- Tab 2: Work Order -->
                                                    <div id="workorder-details" class="tab-pane">
                                                        <?php include "_servis_workorder_tab.php"; ?>
                                                    </div>
                                                    
                                                    <!-- Tab 3: Item Barang -->
                                                    <div id="service-items" class="tab-pane">
                                                        <?php include "_servis_items_tab.php"; ?>
                                                    </div>
                                                    
                                                    <!-- Tab 4: Item Jasa -->
                                                    <div id="service-jasa" class="tab-pane">
                                                        <?php include "_servis_jasa_tab.php"; ?>
                                                    </div>
                                                    
                                                    <!-- Tab 5: Progress Mekanik -->
                                                    <div id="mechanic-progress" class="tab-pane">
                                                        <?php include "_servis_progress_tab.php"; ?>
                                                    </div>
                                                    
                                                    <!-- Tab 6: Actions -->
                                                    <div id="service-actions" class="tab-pane">
                                                        <?php include "_servis_actions_tab.php"; ?>
                                                    </div>
                                                </div>
                                            </div>