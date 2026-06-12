<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];        
    $kd_cabang=$_SESSION['_cabang'];                
    include "../config/koneksi.php";
    include "../config/permission_check.php";

    // Optional debug mode: add ?debug=1
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }

    // Get user data from session
    $_nama = $_SESSION['_nama_lengkap'] ?? $_SESSION['_username'] ?? 'User';
    $lvl_akses = $_SESSION['user_akses'] ?? 0;
    $foto_user = $_SESSION['_foto'] ?? '../file_upload/avatar.png';
    if (empty($foto_user)) {
        $foto_user = '../file_upload/avatar.png';
    } elseif (strpos($foto_user, '../') !== 0 && strpos($foto_user, 'http') !== 0) {
        $foto_user = '../' . ltrim($foto_user, '/');
    }

    // RBAC: only allow users with permission to add users (Admin always allowed)
    if (!isAdmin()) {
        checkPermissionOrDie('users', 'add');
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
    
    // Kode karyawan akan dibuat otomatis saat simpan berdasarkan tanggal masuk (format: yyyy-mm-0001)
    
    // Get posisi list
    $query_posisi = "SELECT * FROM tb_master_posisi ORDER BY kode_posisi ASC";
    $result_posisi = mysqli_query($koneksi, $query_posisi);
    
    // Get level list
    $query_level = "SELECT * FROM tb_master_level ORDER BY kode_level ASC";
    $result_level = mysqli_query($koneksi, $query_level);
    
    // Get cabang list
    $query_cabang = "SELECT * FROM tbcabang ORDER BY nama_cabang ASC";
    $result_cabang = mysqli_query($koneksi, $query_cabang);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Tambah Karyawan - <?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Tambah Karyawan Baru" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- Bootstrap & FontAwesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    
    <!-- ACE Styles -->
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    
    <style>
        .form-section {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
        }
        
        .form-section h4 {
            margin-top: 0;
            color: #0066cc;
            font-weight: bold;
        }
        
        .required {
            color: red;
        }
        
        .form-group label {
            font-weight: 500;
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
                        <?php include "../lib/titel.php"; ?>
                    </small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="<?php echo $foto_user; ?>" alt="<?php echo $_nama; ?>" />
                            <span class="user-info">
                                <small>Welcome,</small>
                                <span class="user-name-text"><?php echo $_nama; ?></span>
                            </span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>

                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i>&nbsp;My Profile</a></li>
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-lock"></i>&nbsp;Change Password</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i>&nbsp;Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_master01a.php"; ?>
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
                            <a href="master_karyawan.php">Master Karyawan</a>
                        </li>
                        <li class="active">Tambah Karyawan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            <i class="fa fa-plus"></i> Tambah Karyawan Baru
                        </h1>
                    </div>

                    <!-- Alert Messages -->
                    <div id="alertContainer"></div>

                    <!-- Form -->
                    <form id="formKaryawan" method="POST" action="master_karyawan_save.php">
                        <!-- Data Pribadi -->
                        <div class="form-section">
                            <h4><i class="fa fa-user"></i> Data Pribadi</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Kode Karyawan</label>
                                        <input type="text" name="kode_karyawan" class="form-control" value="" placeholder="Akan dibuat otomatis saat simpan (yyyymm0001)" readonly>
                                        <small class="form-text text-muted">Otomatis dari tanggal masuk</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NIK <span class="required">*</span></label>
                                        <input type="text" name="nik" class="form-control" placeholder="Nomor Identitas Karyawan" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama Lengkap <span class="required">*</span></label>
                                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap karyawan" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama Panggilan</label>
                                        <input type="text" name="nama_panggilan" class="form-control" placeholder="Nama panggilan">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="Email karyawan">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nomor Telepon</label>
                                        <input type="text" name="telp" class="form-control" placeholder="Nomor telepon">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Pekerjaan -->
                        <div class="form-section">
                            <h4><i class="fa fa-briefcase"></i> Data Pekerjaan</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Posisi <span class="required">*</span></label>
                                        <select name="kode_posisi" class="form-control" required>
                                            <option value="">-- Pilih Posisi --</option>
                                            <?php if ($result_posisi && mysqli_num_rows($result_posisi) > 0) { 
                                                while($row = mysqli_fetch_assoc($result_posisi)) { ?>
                                                <option value="<?php echo $row['kode_posisi']; ?>">
                                                    <?php echo $row['nama_posisi']; ?>
                                                </option>
                                            <?php } } else { ?>
                                                <option value="" disabled>Data posisi belum tersedia</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Level</label>
                                        <select name="kode_level" class="form-control">
                                            <option value="">-- Pilih Level --</option>
                                            <?php if ($result_level && mysqli_num_rows($result_level) > 0) { 
                                                while($row = mysqli_fetch_assoc($result_level)) { ?>
                                                <option value="<?php echo $row['kode_level']; ?>">
                                                    <?php echo $row['nama_level']; ?>
                                                </option>
                                            <?php } } else { ?>
                                                <option value="" disabled>Data level belum tersedia</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Cabang <span class="required">*</span></label>
                                        <select name="kode_cabang" class="form-control" required>
                                            <option value="">-- Pilih Cabang --</option>
                                            <?php 
                                            if ($result_cabang && mysqli_num_rows($result_cabang) > 0) {
                                                mysqli_data_seek($result_cabang, 0);
                                                while($row = mysqli_fetch_assoc($result_cabang)) { 
                                            ?>
                                                <option value="<?php echo $row['kode_cabang']; ?>" 
                                                    <?php echo ($row['kode_cabang'] == $kd_cabang) ? 'selected' : ''; ?>>
                                                    <?php echo $row['nama_cabang']; ?>
                                                </option>
                                            <?php } } else { ?>
                                                <option value="" disabled>Data cabang belum tersedia</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Masuk <span class="required">*</span></label>
                                        <input type="date" name="tanggal_masuk" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Spesialisasi</label>
                                        <textarea name="spesialisasi" class="form-control" rows="2" placeholder="Spesialisasi/Keahlian khusus"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Sertifikat</label>
                                        <textarea name="sertifikat" class="form-control" rows="2" placeholder="Sertifikat yang dimiliki"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Keluar <small>(Kosongkan jika masih aktif)</small></label>
                                        <input type="date" name="tanggal_keluar" class="form-control" placeholder="Tanggal keluar dari perusahaan">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Simpan
                            </button>
                            <a href="master_karyawan.php" class="btn btn-default">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#formKaryawan').submit(function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                
                $.ajax({
                    url: 'master_karyawan_save.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            showAlert('success', response.message);
                            setTimeout(function() {
                                window.location.href = 'master_karyawan.php';
                            }, 2000);
                        } else {
                            showAlert('danger', 'Error: ' + response.message);
                        }
                    },
                    error: function() {
                        showAlert('danger', 'Error saving data');
                    }
                });
            });
        });

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
