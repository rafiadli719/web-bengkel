<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];		
    $kd_cabang = $_SESSION['_cabang'];        
    include "../config/koneksi.php";
    
    // User data
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user 
                                        FROM tbuser WHERE id='$id_user'");			
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'] ?? '';					        
    $pwd = $tm_cari['password'] ?? '';					        
    $lvl_akses = $tm_cari['user_akses'] ?? '';					                
    $foto_user = $tm_cari['foto_user'] ?? '';				
    if($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

    // Cabang data
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");			
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'] ?? '';					        
    $tipe_cabang = $tm_cari['tipe_cabang'] ?? '';	

    // ========================================
    // HANDLE CRUD OPERATIONS
    // ========================================
    $message = '';
    $message_type = '';
    
    // Check if table exists, if not show instruction
    $table_check = mysqli_query($koneksi, "SHOW TABLES LIKE 'master_diskon_periode'");
    $table_exists = mysqli_num_rows($table_check) > 0;
    
    // ADD
    if(isset($_POST['btn_simpan']) && $table_exists) {
        $nama_promo = mysqli_real_escape_string($koneksi, $_POST['nama_promo']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
        $tipe_promo = mysqli_real_escape_string($koneksi, $_POST['tipe_promo']);
        $nilai_promo = floatval($_POST['nilai_promo']);
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $target_type = mysqli_real_escape_string($koneksi, $_POST['target_type']);
        $target_id = mysqli_real_escape_string($koneksi, $_POST['target_id']);
        $target_nama = mysqli_real_escape_string($koneksi, $_POST['target_nama']);
        
        // Check if deskripsi column exists
        $col_check = mysqli_query($koneksi, "SHOW COLUMNS FROM master_diskon_periode LIKE 'deskripsi'");
        $has_deskripsi = mysqli_num_rows($col_check) > 0;
        
        if($has_deskripsi) {
            $sql = "INSERT INTO master_diskon_periode 
                    (nama_promo, deskripsi, tipe_promo, nilai_promo, tanggal_mulai, tanggal_selesai, target_type, target_id, target_nama, created_by)
                    VALUES 
                    ('$nama_promo', '$deskripsi', '$tipe_promo', '$nilai_promo', '$tanggal_mulai', '$tanggal_selesai', '$target_type', '$target_id', '$target_nama', '$id_user')";
        } else {
            $sql = "INSERT INTO master_diskon_periode 
                    (nama_promo, tipe_promo, nilai_promo, tanggal_mulai, tanggal_selesai, target_type, target_id, target_nama)
                    VALUES 
                    ('$nama_promo', '$tipe_promo', '$nilai_promo', '$tanggal_mulai', '$tanggal_selesai', '$target_type', '$target_id', '$target_nama')";
        }
        
        if(mysqli_query($koneksi, $sql)) {
            $message = 'Promo berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'danger';
        }
    }
    
    // EDIT
    if(isset($_POST['btn_update']) && $table_exists) {
        $id_promo = intval($_POST['id_promo']);
        $nama_promo = mysqli_real_escape_string($koneksi, $_POST['nama_promo']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
        $tipe_promo = mysqli_real_escape_string($koneksi, $_POST['tipe_promo']);
        $nilai_promo = floatval($_POST['nilai_promo']);
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $target_type = mysqli_real_escape_string($koneksi, $_POST['target_type']);
        $target_id = mysqli_real_escape_string($koneksi, $_POST['target_id']);
        $target_nama = mysqli_real_escape_string($koneksi, $_POST['target_nama']);
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
        
        // Check if deskripsi column exists
        $col_check = mysqli_query($koneksi, "SHOW COLUMNS FROM master_diskon_periode LIKE 'deskripsi'");
        $has_deskripsi = mysqli_num_rows($col_check) > 0;
        
        $deskripsi_sql = $has_deskripsi ? "deskripsi = '$deskripsi'," : "";
        
        $sql = "UPDATE master_diskon_periode SET
                nama_promo = '$nama_promo',
                $deskripsi_sql
                tipe_promo = '$tipe_promo',
                nilai_promo = '$nilai_promo',
                tanggal_mulai = '$tanggal_mulai',
                tanggal_selesai = '$tanggal_selesai',
                target_type = '$target_type',
                target_id = '$target_id',
                target_nama = '$target_nama',
                status_aktif = '$status_aktif'
                WHERE id_promo = '$id_promo'";
        
        if(mysqli_query($koneksi, $sql)) {
            $message = 'Promo berhasil diupdate!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'danger';
        }
    }
    
    // DELETE
    if(isset($_POST['btn_hapus']) && $table_exists) {
        $id_promo = intval($_POST['id_promo']);
        
        $sql = "DELETE FROM master_diskon_periode WHERE id_promo = '$id_promo'";
        
        if(mysqli_query($koneksi, $sql)) {
            $message = 'Promo berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($koneksi);
            $message_type = 'danger';
        }
    }
    
    // TOGGLE STATUS
    if(isset($_GET['toggle']) && $table_exists) {
        $id_promo = intval($_GET['toggle']);
        $sql = "UPDATE master_diskon_periode SET status_aktif = NOT status_aktif WHERE id_promo = '$id_promo'";
        mysqli_query($koneksi, $sql);
        header("Location: master-diskon-periode.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Diskon Periode</title>
    <meta name="description" content="Master Diskon Periode / Promo" />
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
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>
    
    <style>
        /* Promo Card Styles */
        .promo-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .promo-card:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .promo-card.expired { opacity: 0.55; background: #f5f5f5; filter: grayscale(30%); }
        .promo-card.active-now { border-left: 5px solid #27ae60; background: linear-gradient(145deg, #ffffff 0%, #f0fff0 100%); }
        .promo-card.upcoming { border-left: 5px solid #f39c12; background: linear-gradient(145deg, #ffffff 0%, #fffdf0 100%); }
        .promo-badge { font-size: 24px; font-weight: 700; line-height: 1.2; }
        .target-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .target-workorder { background: #3498db; color: #fff; }
        .target-jasa { background: #27ae60; color: #fff; }
        .target-barang { background: #f39c12; color: #fff; }
        .date-range { font-size: 12px; color: #888; margin-top: 10px; }
        .btn-action { padding: 4px 10px; font-size: 12px; border-radius: 4px; margin-left: 2px; }
        
        /* Modal & Form Styles */
        .modal-header-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .modal-header-custom .close { color: #fff; opacity: 0.8; }
        .form-section { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; border: 1px solid #e9ecef; }
        .form-section-title { font-size: 12px; font-weight: 600; color: #495057; margin-bottom: 10px; text-transform: uppercase; }
        .input-group-addon { background: #f8f9fa; border-color: #ced4da; color: #6c757d; }
        .help-text { font-size: 11px; color: #6c757d; margin-top: 4px; }
        
        /* Search Modal */
        .search-result-list { max-height: 350px; overflow-y: auto; }
        .search-result-item { padding: 10px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; }
        .search-result-item:hover { background: #f0f7ff; }
        .search-result-item.selected { background: #d4edda; }
        .selected-item-display { background: #e3f2fd; border: 2px solid #2196f3; border-radius: 8px; padding: 12px; margin-top: 10px; }
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
                                <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
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
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
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
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            
            <?php include "menu_pelanggan01.php"; ?>

            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Data Master</a></li>
                        <li><a href="#">Pelanggan</a></li>
                        <li class="active">Diskon Periode</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            <i class="fa fa-gift"></i> Master Diskon Periode
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Kelola Promo & Diskon Periode</small>
                        </h1>
                    </div>

                    <?php if(!$table_exists): ?>
                    <div class="alert alert-danger">
                        <h4><i class="fa fa-warning"></i> Tabel Belum Dibuat</h4>
                        <p>Silakan jalankan SQL berikut di phpMyAdmin:</p>
                        <ol>
                            <li>Buka file <code>sql/migration_diskon_periode.sql</code></li>
                            <li>Copy semua isinya dan jalankan di phpMyAdmin</li>
                            <li>Refresh halaman ini</li>
                        </ol>
                        <p>Jika tabel sudah dibuat tapi masih error kolom, jalankan juga: <code>sql/fix_kolom_diskon_periode.sql</code></p>
                    </div>
                    <?php else: ?>

                    <?php if($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo $message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-list"></i> Daftar Promo</h4>
                                    <div class="widget-toolbar">
                                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAdd">
                                            <i class="fa fa-plus"></i> Tambah Promo
                                        </button>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        
                                        <!-- Info Box -->
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i>
                                            <strong>Tentang Diskon Periode:</strong> Diskon ini berlaku untuk item/jasa/WO tertentu dalam rentang tanggal tertentu. Jika ada promo aktif, diskon member TIDAK akan berlaku (Promo > Member).
                                        </div>

                                        <!-- List Promo -->
                                        <div class="row">
                                        <?php
                                        $today = date('Y-m-d');
                                        
                                        // Check deskripsi column
                                        $col_check = mysqli_query($koneksi, "SHOW COLUMNS FROM master_diskon_periode LIKE 'deskripsi'");
                                        $has_deskripsi = mysqli_num_rows($col_check) > 0;
                                        
                                        $select_cols = $has_deskripsi ? "*" : "id_promo, nama_promo, tipe_promo, nilai_promo, tanggal_mulai, tanggal_selesai, target_type, target_id, target_nama, status_aktif";
                                        
                                        $query = mysqli_query($koneksi, "SELECT $select_cols FROM master_diskon_periode ORDER BY tanggal_mulai DESC, id_promo DESC");
                                        
                                        if($query && mysqli_num_rows($query) > 0):
                                            while($row = mysqli_fetch_assoc($query)):
                                                $is_active_now = ($row['status_aktif'] == 1 && $today >= $row['tanggal_mulai'] && $today <= $row['tanggal_selesai']);
                                                $is_upcoming = ($row['status_aktif'] == 1 && $today < $row['tanggal_mulai']);
                                                $is_expired = ($today > $row['tanggal_selesai']);
                                                
                                                $card_class = '';
                                                if($is_expired) $card_class = 'expired';
                                                elseif($is_active_now) $card_class = 'active-now';
                                                elseif($is_upcoming) $card_class = 'upcoming';
                                                
                                                // Add deskripsi if missing
                                                if(!isset($row['deskripsi'])) $row['deskripsi'] = '';
                                        ?>
                                            <div class="col-md-6">
                                                <div class="promo-card <?php echo $card_class; ?>">
                                                    <div class="row">
                                                        <div class="col-xs-8">
                                                            <h4 style="margin-top:0;">
                                                                <?php echo htmlspecialchars($row['nama_promo']); ?>
                                                                <?php if(!$row['status_aktif']): ?>
                                                                    <span class="label label-default">Nonaktif</span>
                                                                <?php elseif($is_active_now): ?>
                                                                    <span class="label label-success">Aktif</span>
                                                                <?php elseif($is_upcoming): ?>
                                                                    <span class="label label-warning">Akan Datang</span>
                                                                <?php elseif($is_expired): ?>
                                                                    <span class="label label-default">Berakhir</span>
                                                                <?php endif; ?>
                                                            </h4>
                                                            <p class="text-muted"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                                                            
                                                            <span class="target-badge target-<?php echo $row['target_type']; ?>">
                                                                <?php echo strtoupper($row['target_type']); ?>
                                                            </span>
                                                            <strong><?php echo htmlspecialchars($row['target_nama'] ?: $row['target_id']); ?></strong>
                                                            
                                                            <div class="date-range">
                                                                <i class="fa fa-calendar"></i>
                                                                <?php echo date('d M Y', strtotime($row['tanggal_mulai'])); ?> - 
                                                                <?php echo date('d M Y', strtotime($row['tanggal_selesai'])); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-xs-4 text-right">
                                                            <div class="promo-badge text-<?php echo $row['tipe_promo'] == 'persen' ? 'success' : 'primary'; ?>">
                                                                <?php if($row['tipe_promo'] == 'persen'): ?>
                                                                    <?php echo number_format($row['nilai_promo'], 0); ?>%
                                                                <?php else: ?>
                                                                    Rp <?php echo number_format($row['nilai_promo'], 0, ',', '.'); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                            <small><?php echo $row['tipe_promo'] == 'persen' ? 'Diskon' : 'Potongan'; ?></small>
                                                            
                                                            <div style="margin-top:10px;">
                                                                <button class="btn btn-xs btn-info btn-action" onclick='editPromo(<?php echo json_encode($row); ?>)'>
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                                <a href="?toggle=<?php echo $row['id_promo']; ?>" class="btn btn-xs btn-<?php echo $row['status_aktif'] ? 'warning' : 'success'; ?> btn-action">
                                                                    <i class="fa fa-<?php echo $row['status_aktif'] ? 'pause' : 'play'; ?>"></i>
                                                                </a>
                                                                <button class="btn btn-xs btn-danger btn-action" onclick="hapusPromo(<?php echo $row['id_promo']; ?>, '<?php echo addslashes($row['nama_promo']); ?>')">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <div class="col-xs-12">
                                                <div class="alert alert-warning">
                                                    <i class="fa fa-exclamation-triangle"></i> Belum ada promo. Klik tombol <strong>Tambah Promo</strong> untuk membuat promo baru.
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php endif; ?>

                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
                </div>
            </div>
        </div>
    </div><!-- /.main-container -->

    <!-- Modal Add -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formAdd">
                    <div class="modal-header modal-header-custom">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-plus"></i> Tambah Promo Baru</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Section: Info Promo -->
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-tag"></i> Informasi Promo</div>
                                    <div class="form-group">
                                        <label>Nama Promo *</label>
                                        <input type="text" name="nama_promo" class="form-control" placeholder="Contoh: Promo Tahun Baru 2025" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi singkat (opsional)"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Section: Nilai Diskon -->
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-percent"></i> Nilai Diskon</div>
                                    <div class="row">
                                        <div class="col-xs-5">
                                            <div class="form-group">
                                                <label>Tipe *</label>
                                                <select name="tipe_promo" id="add_tipe_promo" class="form-control" required>
                                                    <option value="persen">Persen (%)</option>
                                                    <option value="nominal">Nominal (Rp)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-7">
                                            <div class="form-group">
                                                <label>Nilai *</label>
                                                <input type="number" name="nilai_promo" class="form-control" step="0.01" placeholder="10" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Section: Periode -->
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-calendar"></i> Periode Promo</div>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label>Mulai *</label>
                                                <input type="date" name="tanggal_mulai" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label>Selesai *</label>
                                                <input type="date" name="tanggal_selesai" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Section: Target -->
                                <div class="form-section" style="height: 100%;">
                                    <div class="form-section-title"><i class="fa fa-crosshairs"></i> Target Promo</div>
                                    <div class="form-group">
                                        <label>Kategori Target *</label>
                                        <select name="target_type" id="add_target_type" class="form-control" required>
                                            <option value="">-- Pilih --</option>
                                            <option value="barang">Barang / Sparepart</option>
                                            <option value="jasa">Jasa Pekerjaan</option>
                                            <option value="workorder">Work Order / Paket</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Cari & Pilih Item *</label>
                                        <button type="button" class="btn btn-primary btn-block" id="btnSearchAdd" disabled onclick="openSearchModal('add')">
                                            <i class="fa fa-search"></i> Cari Item...
                                        </button>
                                        <input type="hidden" name="target_id" id="add_target_id" required>
                                        <input type="hidden" name="target_nama" id="add_target_nama">
                                    </div>
                                    <div id="add_selected_display" style="display:none;">
                                        <div class="selected-item-display">
                                            <strong>Item Terpilih:</strong><br>
                                            <span id="add_selected_text"></span>
                                            <button type="button" class="btn btn-xs btn-danger pull-right" onclick="clearSelection('add')"><i class="fa fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_simpan" class="btn btn-success"><i class="fa fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formEdit">
                    <input type="hidden" name="id_promo" id="edit_id_promo">
                    <div class="modal-header modal-header-custom">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Promo</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-tag"></i> Informasi Promo</div>
                                    <div class="form-group">
                                        <label>Nama Promo *</label>
                                        <input type="text" name="nama_promo" id="edit_nama_promo" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi</label>
                                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label><input type="checkbox" name="status_aktif" id="edit_status_aktif" value="1"> Aktif</label>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-percent"></i> Nilai Diskon</div>
                                    <div class="row">
                                        <div class="col-xs-5">
                                            <div class="form-group">
                                                <label>Tipe *</label>
                                                <select name="tipe_promo" id="edit_tipe_promo" class="form-control" required>
                                                    <option value="persen">Persen (%)</option>
                                                    <option value="nominal">Nominal (Rp)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-7">
                                            <div class="form-group">
                                                <label>Nilai *</label>
                                                <input type="number" name="nilai_promo" id="edit_nilai_promo" class="form-control" step="0.01" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-calendar"></i> Periode</div>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label>Mulai *</label>
                                                <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label>Selesai *</label>
                                                <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-section" style="height: 100%;">
                                    <div class="form-section-title"><i class="fa fa-crosshairs"></i> Target Promo</div>
                                    <div class="form-group">
                                        <label>Kategori Target *</label>
                                        <select name="target_type" id="edit_target_type" class="form-control" required>
                                            <option value="barang">Barang / Sparepart</option>
                                            <option value="jasa">Jasa Pekerjaan</option>
                                            <option value="workorder">Work Order / Paket</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Cari & Pilih Item *</label>
                                        <button type="button" class="btn btn-primary btn-block" id="btnSearchEdit" onclick="openSearchModal('edit')">
                                            <i class="fa fa-search"></i> Cari Item...
                                        </button>
                                        <input type="hidden" name="target_id" id="edit_target_id" required>
                                        <input type="hidden" name="target_nama" id="edit_target_nama">
                                    </div>
                                    <div id="edit_selected_display">
                                        <div class="selected-item-display">
                                            <strong>Item Terpilih:</strong><br>
                                            <span id="edit_selected_text"></span>
                                            <button type="button" class="btn btn-xs btn-danger pull-right" onclick="clearSelection('edit')"><i class="fa fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btn_update" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Search Item -->
    <div class="modal fade" id="modalSearch" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-search"></i> Cari <span id="search_type_label">Item</span></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="search_keyword" class="form-control" placeholder="Ketik nama atau kode...">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary" onclick="doSearch()"><i class="fa fa-search"></i></button>
                            </span>
                        </div>
                    </div>
                    <div id="search_loading" style="display:none; text-align:center; padding:20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...
                    </div>
                    <div id="search_results" class="search-result-list"></div>
                    <div id="search_empty" style="display:none; text-align:center; padding:20px; color:#999;">
                        <i class="fa fa-inbox fa-2x"></i><br>Tidak ada hasil
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted pull-left" style="line-height:34px;"><i class="fa fa-info-circle"></i> Klik item untuk memilih. Bisa pilih lebih dari 1 item.</small>
                    <button type="button" class="btn btn-success" id="btnConfirmSelection" onclick="confirmSelection()" style="display:none;">
                        <i class="fa fa-check"></i> Pilih Item
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Delete Hidden -->
    <form id="formHapus" method="POST" style="display:none;">
        <input type="hidden" name="id_promo" id="hapus_id_promo">
        <input type="hidden" name="btn_hapus" value="1">
    </form>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    
    <script>
    var currentSearchContext = 'add';
    
    // Handle target type change
    $('#add_target_type').on('change', function() {
        if($(this).val()) {
            $('#btnSearchAdd').prop('disabled', false);
        } else {
            $('#btnSearchAdd').prop('disabled', true);
        }
        clearSelection('add');
    });
    
    $('#edit_target_type').on('change', function() {
        clearSelection('edit');
    });
    
    function openSearchModal(context) {
        currentSearchContext = context;
        var type = $('#' + context + '_target_type').val();
        
        var typeLabel = 'Item';
        if(type == 'barang') typeLabel = 'Barang / Sparepart';
        else if(type == 'jasa') typeLabel = 'Jasa';
        else if(type == 'workorder') typeLabel = 'Work Order';
        
        $('#search_type_label').text(typeLabel);
        $('#search_keyword').val('');
        $('#search_results').html('');
        $('#search_empty').hide();
        
        $('#modalSearch').modal('show');
        
        // Auto search on open
        setTimeout(function() {
            doSearch();
            $('#search_keyword').focus();
        }, 300);
    }
    
    // Search on enter key
    $('#search_keyword').on('keypress', function(e) {
        if(e.which == 13) {
            e.preventDefault();
            doSearch();
        }
    });
    
    function doSearch() {
        var type = $('#' + currentSearchContext + '_target_type').val();
        var keyword = $('#search_keyword').val();
        
        $('#search_loading').show();
        $('#search_results').html('');
        $('#search_empty').hide();
        
        var url = '';
        if(type == 'workorder') {
            url = 'ajax-get-workorder-list.php?q=' + encodeURIComponent(keyword);
        } else {
            // Use the new JSON endpoint for barang/jasa
            url = 'ajax-search-item-promo.php?q=' + encodeURIComponent(keyword) + '&tipe=' + type + '&limit=100';
        }
        
        $.ajax({
            url: url,
            dataType: 'json',
            success: function(response) {
                $('#search_loading').hide();
                
                var data = [];
                if(type == 'workorder') {
                    // Direct array response
                    data = response || [];
                } else {
                    // JSON object with data property
                    data = (response && response.data) ? response.data : [];
                }
                
                if(data.length > 0) {
                    var html = '';
                    for(var i = 0; i < data.length; i++) {
                        var itemId = type == 'workorder' ? data[i].kode_wo : data[i].id;
                        var itemNama = type == 'workorder' ? data[i].nama_wo : data[i].nama;
                        var itemHarga = type == 'workorder' ? data[i].harga : data[i].harga;
                        var itemTipe = type == 'workorder' ? 'WO' : (data[i].tipe || type).toUpperCase();
                        
                        html += '<div class="search-result-item" data-id="' + itemId + '" data-nama="' + escapeHtml(itemNama) + '" onclick="selectItemFromSearch(this)">';
                        html += '<input type="checkbox" class="item-checkbox" style="margin-right: 8px;" onclick="event.stopPropagation();">';
                        html += '<strong>' + itemId + '</strong> - ' + itemNama;
                        if(itemHarga) html += '<br><small class="text-success">Harga: Rp ' + formatNumber(itemHarga) + '</small>';
                        html += '<span class="label label-' + (itemTipe == 'JASA' ? 'success' : (itemTipe == 'WO' ? 'info' : 'warning')) + '" style="float:right;">' + itemTipe + '</span>';
                        html += '</div>';
                    }
                    $('#search_results').html(html);
                } else {
                    $('#search_empty').show();
                }
            },
            error: function(xhr) {
                $('#search_loading').hide();
                $('#search_results').html('<div class="alert alert-danger">Gagal memuat data. Error: ' + xhr.statusText + '</div>');
            }
        });
    }
    
    function selectItemFromSearch(elem) {
        var id = $(elem).data('id');
        var nama = $(elem).data('nama');
        
        // Toggle selection visual
        $(elem).toggleClass('selected');
        $(elem).find('.item-checkbox').prop('checked', $(elem).hasClass('selected'));
        
        // Update multi-select list
        updateSelectedItems();
    }
    
    function updateSelectedItems() {
        var selected = [];
        $('#search_results .search-result-item.selected').each(function() {
            selected.push({
                id: $(this).data('id'),
                nama: $(this).data('nama')
            });
        });
        
        // Store for later use when confirming
        window.pendingSelectedItems = selected;
        
        // Update confirm button
        if(selected.length > 0) {
            $('#btnConfirmSelection').show().text('Pilih ' + selected.length + ' Item');
        } else {
            $('#btnConfirmSelection').hide();
        }
    }
    
    function confirmSelection() {
        var items = window.pendingSelectedItems || [];
        if(items.length == 0) return;
        
        // Build combined ID and name
        var ids = items.map(function(i) { return i.id; }).join(',');
        var names = items.map(function(i) { return i.nama; }).join(', ');
        
        $('#' + currentSearchContext + '_target_id').val(ids);
        $('#' + currentSearchContext + '_target_nama').val(names);
        
        // Display selected
        var displayHtml = '';
        for(var i = 0; i < items.length; i++) {
            displayHtml += '<span class="label label-info" style="margin-right:5px;margin-bottom:3px;display:inline-block;">' + items[i].id + '</span> ';
        }
        $('#' + currentSearchContext + '_selected_text').html(displayHtml + '<br><small>' + names.substring(0, 100) + (names.length > 100 ? '...' : '') + '</small>');
        $('#' + currentSearchContext + '_selected_display').show();
        
        $('#modalSearch').modal('hide');
        window.pendingSelectedItems = [];
    };
    
    function selectItem(id, nama) {
        $('#' + currentSearchContext + '_target_id').val(id);
        $('#' + currentSearchContext + '_target_nama').val(nama);
        $('#' + currentSearchContext + '_selected_text').html('<strong>' + id + '</strong> - ' + nama);
        $('#' + currentSearchContext + '_selected_display').show();
        $('#modalSearch').modal('hide');
    }
    
    function clearSelection(context) {
        $('#' + context + '_target_id').val('');
        $('#' + context + '_target_nama').val('');
        $('#' + context + '_selected_display').hide();
    }
    
    function editPromo(data) {
        $('#edit_id_promo').val(data.id_promo);
        $('#edit_nama_promo').val(data.nama_promo);
        $('#edit_deskripsi').val(data.deskripsi || '');
        $('#edit_tipe_promo').val(data.tipe_promo);
        $('#edit_nilai_promo').val(data.nilai_promo);
        $('#edit_tanggal_mulai').val(data.tanggal_mulai);
        $('#edit_tanggal_selesai').val(data.tanggal_selesai);
        $('#edit_target_type').val(data.target_type);
        $('#edit_target_id').val(data.target_id);
        $('#edit_target_nama').val(data.target_nama);
        $('#edit_status_aktif').prop('checked', data.status_aktif == 1);
        
        // Show selected item
        $('#edit_selected_text').html('<strong>' + data.target_id + '</strong> - ' + (data.target_nama || data.target_id));
        $('#edit_selected_display').show();
        
        $('#modalEdit').modal('show');
    }
    
    function hapusPromo(id, nama) {
        if(confirm('Yakin hapus promo "' + nama + '"?\n\nPromo yang dihapus tidak dapat dikembalikan.')) {
            $('#hapus_id_promo').val(id);
            $('#formHapus').submit();
        }
    }
    
    function escapeHtml(text) {
        return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    </script>
</body>
</html>

<?php } ?>
