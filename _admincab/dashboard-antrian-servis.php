<?php
session_start();

if(empty($_SESSION['_iduser'])){
    header("location:index.php");
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
    
    // Ambil data antrian hari ini
    $tanggal_hari_ini = date('Y-m-d');
    $query_antrian_hari_ini = "SELECT COUNT(*) as total_antrian FROM tb_antrian_servis WHERE tanggal = '$tanggal_hari_ini'";
    $result_antrian_hari_ini = mysqli_query($koneksi, $query_antrian_hari_ini);
    $total_antrian_hari_ini = mysqli_fetch_array($result_antrian_hari_ini)['total_antrian'];
    
    // Ambil data antrian yang sedang diproses
    $query_antrian_diproses = "SELECT COUNT(*) as total_diproses FROM tb_antrian_servis WHERE tanggal = '$tanggal_hari_ini' AND status_antrian = 'diproses'";
    $result_antrian_diproses = mysqli_query($koneksi, $query_antrian_diproses);
    $total_antrian_diproses = mysqli_fetch_array($result_antrian_diproses)['total_diproses'];
    
    // Ambil data antrian yang selesai
    $query_antrian_selesai = "SELECT COUNT(*) as total_selesai FROM tb_antrian_servis WHERE tanggal = '$tanggal_hari_ini' AND status_antrian = 'selesai'";
    $result_antrian_selesai = mysqli_query($koneksi, $query_antrian_selesai);
    $total_antrian_selesai = mysqli_fetch_array($result_antrian_selesai)['total_selesai'];
    
    // Ambil data antrian yang menunggu
    $query_antrian_menunggu = "SELECT COUNT(*) as total_menunggu FROM tb_antrian_servis WHERE tanggal = '$tanggal_hari_ini' AND status_antrian = 'menunggu'";
    $result_antrian_menunggu = mysqli_query($koneksi, $query_antrian_menunggu);
    $total_antrian_menunggu = mysqli_fetch_array($result_antrian_menunggu)['total_menunggu'];
    
    // Ambil data antrian terbaru
    $query_antrian_terbaru = "SELECT a.*, s.no_polisi, s.no_pelanggan, p.namapelanggan 
                               FROM tb_antrian_servis a 
                               LEFT JOIN tblservice s ON a.no_service = s.no_service 
                               LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan 
                               WHERE a.tanggal = '$tanggal_hari_ini' 
                               ORDER BY a.created_at DESC 
                               LIMIT 10";
    $result_antrian_terbaru = mysqli_query($koneksi, $query_antrian_terbaru);
    
    // Ambil data mekanik yang sedang bekerja
    $query_mekanik_bekerja = "SELECT pm.*, a.no_antrian, s.no_polisi, p.namapelanggan, pm.nama_mekanik
                              FROM tb_progress_mekanik pm 
                              LEFT JOIN tb_antrian_servis a ON pm.no_service = a.no_service 
                              LEFT JOIN tblservice s ON pm.no_service = s.no_service 
                              LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan 
                              WHERE pm.status_kerja = 'bekerja' 
                              AND a.tanggal = '$tanggal_hari_ini' 
                              ORDER BY pm.updated_at DESC";
    $result_mekanik_bekerja = mysqli_query($koneksi, $query_mekanik_bekerja);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Dashboard Antrian Servis - <?php include "lib/titel.php"; ?></title>
    <meta name="description" content="Dashboard Progress Pengerjaan Antrian Servis" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- page specific plugin styles -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

    <!-- text fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- Custom CSS for Antrian Dashboard -->
    <style>
        .antrian-card, .mekanik-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .antrian-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
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
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .widget-box {
            margin-bottom: 20px;
        }
        
        .widget-title {
            font-size: 16px;
            font-weight: bold;
        }
    </style>

    <!-- ace styles -->
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->
    <!--[if lte IE 8]>
    <script src="assets/js/html5shiv.min.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->

    <style>
        .antrian-card {
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .antrian-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .antrian-body {
            padding: 15px;
        }
        .status-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
        }
        .status-menunggu { background-color: #ffc107; color: #000; }
        .status-diproses { background-color: #17a2b8; color: #fff; }
        .status-selesai { background-color: #28a745; color: #fff; }
        .status-batal { background-color: #dc3545; color: #fff; }
        .prioritas-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .prioritas-normal { background-color: #6c757d; color: #fff; }
        .prioritas-urgent { background-color: #fd7e14; color: #fff; }
        .prioritas-vip { background-color: #e83e8c; color: #fff; }
        .progress-bar-custom {
            height: 20px;
            border-radius: 10px;
        }
        .mekanik-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
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
                    <small>
                        <i class="fa fa-leaf"></i>
                        <?php include "lib/subtitel.php"; ?>
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
                                <?php echo $_nama; ?>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>

                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li>
                                <a href="index.php">
                                    <i class="ace-icon fa fa-home"></i>
                                    Dashboard Utama
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
        </div>
    </div>
    
    <div class="main-container ace-save-state" id="main-container">
        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>                            
                        <li class="active">Dashboard Antrian Servis</li>
                    </ul>
                </div>

                <div class="page-content">
                    <!-- Statistik Antrian -->
                    <div class="row">
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Total Antrian Hari Ini</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-primary"><?php echo $total_antrian_hari_ini; ?></h2>
                                            <small>Antrian</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Sedang Diproses</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-info"><?php echo $total_antrian_diproses; ?></h2>
                                            <small>Sedang Bekerja</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Selesai</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-success"><?php echo $total_antrian_selesai; ?></h2>
                                            <small>Sudah Selesai</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Menunggu</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-warning"><?php echo $total_antrian_menunggu; ?></h2>
                                            <small>Belum Diproses</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Antrian Terbaru -->
                    <div class="row">
                        <div class="col-xs-12 col-md-8">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="fa fa-list"></i> Antrian Terbaru Hari Ini
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php if(mysqli_num_rows($result_antrian_terbaru) > 0): ?>
                                            <?php while($antrian = mysqli_fetch_array($result_antrian_terbaru)): ?>
                                                <div class="antrian-card">
                                                    <div class="antrian-header">
                                                        <div class="row">
                                                            <div class="col-xs-6">
                                                                <h5 class="no-margin">
                                                                    <strong>Antrian <?php echo $antrian['no_antrian']; ?></strong>
                                                                </h5>
                                                                <small class="text-muted">
                                                                    Service: <?php echo $antrian['no_service']; ?>
                                                                </small>
                                                            </div>
                                                            <div class="col-xs-6 text-right">
                                                                <span class="status-badge status-<?php echo $antrian['status_antrian']; ?>">
                                                                    <?php echo ucfirst($antrian['status_antrian']); ?>
                                                                </span>
                                                                <br>
                                                                <span class="prioritas-badge prioritas-<?php echo $antrian['prioritas']; ?>">
                                                                    <?php echo strtoupper($antrian['prioritas']); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="antrian-body">
                                                        <div class="row">
                                                            <div class="col-xs-6">
                                                                <strong>Pelanggan:</strong><br>
                                                                <?php echo $antrian['namapelanggan'] ?? 'N/A'; ?><br>
                                                                <small class="text-muted">
                                                                    <?php echo $antrian['no_polisi'] ?? 'N/A'; ?>
                                                                </small>
                                                            </div>
                                                            <div class="col-xs-6 text-right">
                                                                <strong>Jam Ambil:</strong><br>
                                                                <?php echo $antrian['jam_ambil']; ?><br>
                                                                <?php if($antrian['estimasi_waktu']): ?>
                                                                    <small class="text-info">
                                                                        Estimasi: <?php echo $antrian['estimasi_waktu']; ?> menit
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php if($antrian['catatan']): ?>
                                                            <div class="row" style="margin-top: 10px;">
                                                                <div class="col-xs-12">
                                                                    <strong>Catatan:</strong> <?php echo $antrian['catatan']; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted" style="padding: 40px;">
                                                <i class="fa fa-inbox fa-3x"></i>
                                                <p style="margin-top: 15px;">Belum ada antrian hari ini</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mekanik yang Sedang Bekerja -->
                        <div class="col-xs-12 col-md-4">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="fa fa-users"></i> Mekanik yang Sedang Bekerja
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <?php if(mysqli_num_rows($result_mekanik_bekerja) > 0): ?>
                                            <?php while($mekanik = mysqli_fetch_array($result_mekanik_bekerja)): ?>
                                                <div class="mekanik-card">
                                                    <div class="row">
                                                        <div class="col-xs-8">
                                                            <h6 class="no-margin">
                                                                <i class="fa fa-user"></i> <?php echo $mekanik['nama_mekanik'] ?? 'Mekanik #' . $mekanik['id_mekanik']; ?>
                                                            </h6>
                                                                                                                         <small>
                                                                 <?php echo ucfirst(str_replace('_', ' ', $mekanik['jenis_mekanik'] ?? 'mekanik')); ?>
                                                             </small>
                                                        </div>
                                                        <div class="col-xs-4 text-right">
                                                            <span class="badge"><?php echo $mekanik['persen_kerja'] ?? 0; ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="progress progress-bar-custom" style="margin-top: 10px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo $mekanik['persen_kerja'] ?? 0; ?>%">
                                                        </div>
                                                    </div>
                                                    <div style="margin-top: 10px;">
                                                        <small>
                                                            <strong>Antrian:</strong> <?php echo $mekanik['no_antrian']; ?><br>
                                                            <strong>Motor:</strong> <?php echo $mekanik['no_polisi'] ?? 'N/A'; ?><br>
                                                            <strong>Pelanggan:</strong> <?php echo $mekanik['namapelanggan'] ?? 'N/A'; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted" style="padding: 20px;">
                                                <i class="fa fa-user-times fa-2x"></i>
                                                <p style="margin-top: 10px;">Tidak ada mekanik yang sedang bekerja</p>
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
    </div>

    <!-- Tombol Refresh -->
    <button class="btn btn-primary btn-lg refresh-btn" onclick="location.reload()">
        <i class="fa fa-refresh"></i>
    </button>

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>

    <!-- page specific plugin scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        // Auto refresh setiap 30 detik
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Tambahkan efek loading saat refresh
        $('.refresh-btn').click(function() {
            $(this).find('i').addClass('fa-spin');
            setTimeout(function() {
                $('.refresh-btn i').removeClass('fa-spin');
            }, 1000);
        });
    </script>
</body>
</html>
