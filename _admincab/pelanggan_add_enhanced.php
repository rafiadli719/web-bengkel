<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

// Ambil data user
$stmt = mysqli_prepare($koneksi, "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tm_cari = mysqli_fetch_array($result);
$_nama = $tm_cari['nama_user'];
$pwd = $tm_cari['password'];
$lvl_akses = $tm_cari['user_akses'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";
mysqli_stmt_close($stmt);

// Ambil data cabang
$stmt = mysqli_prepare($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang = ?");
mysqli_stmt_bind_param($stmt, "s", $kd_cabang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tm_cari = mysqli_fetch_array($result);
$nama_cabang = $tm_cari['nama_cabang'];
$tipe_cabang = $tm_cari['tipe_cabang'];
mysqli_stmt_close($stmt);

// Fetch master data
$merek_query = mysqli_query($koneksi, "SELECT id, merek FROM tbpabrik_motor ORDER BY merek");
$warna_query = mysqli_query($koneksi, "SELECT id, warna FROM tbwarna ORDER BY warna");
$jenis_query = mysqli_query($koneksi, "SELECT kd, jenis FROM tbjenis_motor ORDER BY kd");

$tgl_skr = date('d');
$bulan_skr = date('m');
$thn_skr = date('Y');
$tgl_pilih = date('d/m/Y');

// Check if editing existing customer
$edit_mode = isset($_GET['phone']) && !empty($_GET['phone']);
$customer_data = null;
if ($edit_mode) {
    $phone = $_GET['phone'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM tblpelanggan WHERE telephone = ?");
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $customer_data = mysqli_fetch_array($result);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Enhanced customer and vehicle registration" />
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
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

    <!-- ace settings handler -->
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .service-btn {
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .service-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .service-btn i {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .service-btn strong {
            display: block;
            margin: 5px 0;
        }
        .service-btn small {
            display: block;
            opacity: 0.8;
        }
        #step-save-data {
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
        }
        #step-service-selection {
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
        }
        .alert {
            border-radius: 6px;
        }
        .btn-lg {
            font-size: 16px;
            padding: 12px 24px;
        }
        .file-input-container {
            position: relative;
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
                        <?php include "../lib/logo.php"; ?>
                        <?php include "../lib/subtitel.php"; ?>
                    </small>
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
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Data Master</a></li>
                        <li><a href="#">Pelanggan</a></li>
                        <li class="active"><?php echo $edit_mode ? 'Edit Data Pelanggan' : 'Tambah Data Pelanggan'; ?></li>
                    </ul>
                </div>

                <div class="page-content">
                    <br>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></div>
                    <?php endif; ?>
                    
                    <form class="form-horizontal" action="save_pelanggan_servis_enhanced.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="edit_mode" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                        <input type="hidden" name="original_phone" value="<?php echo $customer_data['telephone'] ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-user"></i>
                                            <?php echo $edit_mode ? 'EDIT DATA PELANGGAN DAN KENDARAAN' : 'INPUT PELANGGAN DAN KENDARAAN BARU'; ?>
                                        </h4>
                                        <div class="widget-toolbar">
                                            <a href="#" data-action="collapse">
                                                <i class="ace-icon fa fa-chevron-up"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <!-- Left Column - Customer Data -->
                                                <div class="col-xs-6">
                                                    <h5><i class="ace-icon fa fa-user"></i> Data Pelanggan</h5>
                                                    <hr>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Nama Pelanggan</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtnama" name="txtnama" class="form-control" required autocomplete="off" 
                                                                   value="<?php echo htmlspecialchars($customer_data['namapelanggan'] ?? ''); ?>" />
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Gender</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbogender" id="cbogender" required>
                                                                <option value="">- Pilih -</option>
                                                                <option value="Laki-laki" <?php echo ($customer_data['gender'] ?? '') == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                                                <option value="Perempuan" <?php echo ($customer_data['gender'] ?? '') == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Tanggal Lahir</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
                                                                       value="<?php echo $customer_data ? date('d/m/Y', strtotime($customer_data['tgllahir'])) : $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" required />
                                                                <span class="input-group-addon">
                                                                    <i class="fa fa-calendar bigger-110"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Provinsi</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cboprovinsi" id="cboprovinsi" required>
                                                                <option value="">- Pilih Provinsi -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Kota/Kabupaten</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbokota" id="cbokota" required>
                                                                <option value="">- Pilih Kota/Kabupaten -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Kecamatan</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbokecamatan" id="cbokecamatan" required>
                                                                <option value="">- Pilih Kecamatan -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Validitas Tgl Lahir</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbovalid" id="cbovalid" required>
                                                                <option value="">- Pilih -</option>
                                                                <option value="Valid" <?php echo ($customer_data['valid_tgl_lahir'] ?? '') == 'Valid' ? 'selected' : ''; ?>>Valid</option>
                                                                <option value="Non Valid" <?php echo ($customer_data['valid_tgl_lahir'] ?? '') == 'Non Valid' ? 'selected' : ''; ?>>Non Valid</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Alamat Detail</label>
                                                        <div class="col-sm-9">
                                                            <textarea class="form-control" id="txtalamat" name="txtalamat" rows="3" placeholder="Jalan, RT/RW, No. Rumah, dll" required><?php echo htmlspecialchars($customer_data['alamat'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Patokan</label>
                                                        <div class="col-sm-9">
                                                            <textarea class="form-control" id="txtpatokan" name="txtpatokan" rows="2" placeholder="Dekat dengan..."><?php echo htmlspecialchars($customer_data['patokan'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">No WA/HP</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtnowa" name="txtnowa" class="form-control" autocomplete="off" 
                                                                   value="<?php echo htmlspecialchars($customer_data['telephone'] ?? ($_GET['phone'] ?? '')); ?>" />
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Link Google Maps</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <span class="input-group-addon">
                                                                    <i class="ace-icon fa fa-map-marker"></i>
                                                                </span>
                                                                <input type="url" id="txtgooglemaps" name="txtgooglemaps" class="form-control" 
                                                                       placeholder="https://maps.google.com/..." autocomplete="off" 
                                                                       value="<?php echo htmlspecialchars($customer_data['google_maps_link'] ?? ''); ?>" />
                                                                <span class="input-group-btn">
                                                                    <button type="button" class="btn btn-info" id="btnOpenMaps" title="Buka Maps">
                                                                        <i class="ace-icon fa fa-external-link"></i>
                                                                    </button>
                                                                </span>
                                                            </div>
                                                            <span class="help-block">
                                                                <small><i class="ace-icon fa fa-info-circle"></i> Salin link Google Maps lokasi rumah pelanggan</small>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Foto Tampak Rumah</label>
                                                        <div class="col-sm-9">
                                                            <div class="file-input-container">
                                                                <input type="file" id="txtfotorumah" name="txtfotorumah" accept="image/*" class="form-control" />
                                                                <div class="photo-preview" id="photoPreview" style="margin-top: 10px; display: none;">
                                                                    <img id="previewImage" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;" />
                                                                    <br>
                                                                    <button type="button" class="btn btn-sm btn-danger" id="btnRemovePhoto" style="margin-top: 5px;">
                                                                        <i class="ace-icon fa fa-trash"></i> Hapus Foto
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <span class="help-block">
                                                                <small><i class="ace-icon fa fa-camera"></i> Upload foto tampak depan rumah pelanggan (JPG, PNG, max 2MB)</small>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Right Column - Vehicle Data -->
                                                <div class="col-xs-6">
                                                    <h5><i class="ace-icon fa fa-motorcycle"></i> Data Kendaraan</h5>
                                                    <hr>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">No Polisi</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtnopol" name="txtnopol" class="form-control" required autocomplete="off" 
                                                                   value="<?php echo htmlspecialchars($_GET['nopol'] ?? ''); ?>" style="text-transform: uppercase;" />
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Bl/Th Pajak</label>
                                                        <div class="col-sm-9">
                                                            <div class="row">
                                                                <div class="col-xs-6">
                                                                    <select class="form-control" name="cbobulanpajak" id="cbobulanpajak" required>
                                                                        <option value="">- Pilih Bulan -</option>
                                                                        <?php for ($i = 1; $i <= 12; $i++) echo "<option value='" . sprintf("%02d", $i) . "'>" . sprintf("%02d", $i) . "</option>"; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-xs-6">
                                                                    <input type="text" id="txtthnpajak" name="txtthnpajak" class="form-control" placeholder="YYYY" required autocomplete="off" pattern="\d{4}" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Merek</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbomerek" id="cbomerek" required>
                                                                <option value="">- Pilih Merek -</option>
                                                                <?php while ($row = mysqli_fetch_array($merek_query)) echo "<option value='{$row['id']}'>{$row['merek']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Tipe</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbotipe" id="cbotipe" required>
                                                                <option value="">- Pilih Tipe -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Jenis</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbojenis" id="cbojenis" required>
                                                                <option value="">- Pilih Jenis -</option>
                                                                <?php mysqli_data_seek($jenis_query, 0); while ($row = mysqli_fetch_array($jenis_query)) echo "<option value='{$row['kd']}'>{$row['jenis']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Warna</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cbowarna" id="cbowarna" required>
                                                                <option value="">- Pilih Warna -</option>
                                                                <?php mysqli_data_seek($warna_query, 0); while ($row = mysqli_fetch_array($warna_query)) echo "<option value='{$row['id']}'>{$row['warna']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Informasi Sumber</label>
                                                        <div class="col-sm-9">
                                                            <select class="form-control" name="cboinformasisumber" id="cboinformasisumber" required>
                                                                <option value="">- Pilih Sumber Informasi -</option>
                                                                <option value="Teman/Keluarga" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'Teman/Keluarga' ? 'selected' : ''; ?>>Teman/Keluarga</option>
                                                                <option value="Facebook" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                                                <option value="Instagram" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                                                <option value="TikTok" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'TikTok' ? 'selected' : ''; ?>>TikTok</option>
                                                                <option value="Google/Internet" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'Google/Internet' ? 'selected' : ''; ?>>Google/Internet</option>
                                                                <option value="Lewat Jalan" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'Lewat Jalan' ? 'selected' : ''; ?>>Lewat Jalan</option>
                                                                <option value="Lainnya" <?php echo ($customer_data['informasi_sumber'] ?? '') == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 1: Save Customer & Vehicle Data -->
                        <div class="row" id="step-save-data">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-save"></i>
                                            SIMPAN DATA PELANGGAN & KENDARAAN
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="alert alert-info">
                                                <i class="ace-icon fa fa-info-circle"></i>
                                                <strong>Langkah 1:</strong> Simpan data pelanggan dan kendaraan terlebih dahulu
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-12">
                                                    <div class="pull-left">
                                                        <a href="input_pelanggan_awal.php" class="btn btn-default">
                                                            <i class="ace-icon fa fa-arrow-left"></i>
                                                            Kembali
                                                        </a>
                                                    </div>
                                                    <div class="pull-right">
                                                        <button type="button" id="btnSimpanData" class="btn btn-primary btn-lg">
                                                            <i class="ace-icon fa fa-save"></i> 
                                                            <span id="btnSimpanText">Simpan Data Pelanggan & Kendaraan</span>
                                                        </button>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Service Type Selection (Hidden initially) -->
                        <div class="row" id="step-service-selection" style="display: none;">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-wrench"></i>
                                            PILIH JENIS LAYANAN SERVIS
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="alert alert-success">
                                                <i class="ace-icon fa fa-check-circle"></i>
                                                <strong>Data berhasil disimpan!</strong> Sekarang pilih jenis layanan servis:
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-6">
                                                    <button type="button" id="btnServisReguler" class="btn btn-primary btn-block btn-lg service-btn">
                                                        <i class="ace-icon fa fa-wrench"></i><br>
                                                        <strong>Servis Reguler</strong><br>
                                                        <small>Pelanggan datang ke bengkel</small>
                                                    </button>
                                                </div>
                                                <div class="col-xs-6">
                                                    <button type="button" id="btnServisJemput" class="btn btn-warning btn-block btn-lg service-btn">
                                                        <i class="ace-icon fa fa-truck"></i><br>
                                                        <strong>Servis Jemput Antar</strong><br>
                                                        <small>Kami jemput motor pelanggan</small>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
    </div>

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- page specific plugin scripts -->
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>

    <!-- ace scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <!-- inline scripts -->
    <script type="text/javascript">
        jQuery(function($) {
            // Form validation
            $('form').submit(function(e) {
                var gender = $('#cbogender').val();
                var thn_pajak = $('#txtthnpajak').val();
                var info_sumber = $('#cboinformasisumber').val();

                if (!gender) {
                    e.preventDefault();
                    alert('Harap pilih gender!');
                    $('#cbogender').focus();
                    return false;
                }
                if (!info_sumber) {
                    e.preventDefault();
                    alert('Harap pilih informasi sumber!');
                    $('#cboinformasisumber').focus();
                    return false;
                }
                if (thn_pajak && !/^\d{4}$/.test(thn_pajak)) {
                    e.preventDefault();
                    alert('Tahun pajak harus 4 digit (YYYY)!');
                    $('#txtthnpajak').focus();
                    return false;
                }
            });

            // Date picker
            $('.date-picker').datepicker({
                autoclose: true,
                todayHighlight: true,
                endDate: '0d'
            });

            // Google Maps functionality
            $('#btnOpenMaps').click(function() {
                var mapsUrl = $('#txtgooglemaps').val();
                if (mapsUrl) {
                    window.open(mapsUrl, '_blank');
                } else {
                    alert('Masukkan link Google Maps terlebih dahulu!');
                    $('#txtgooglemaps').focus();
                }
            });

            // Photo preview functionality
            $('#txtfotorumah').change(function() {
                var file = this.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar! Maksimal 2MB.');
                        $(this).val('');
                        return;
                    }
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage').attr('src', e.target.result);
                        $('#photoPreview').show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('#btnRemovePhoto').click(function() {
                $('#txtfotorumah').val('');
                $('#photoPreview').hide();
            });

            // Auto uppercase for nopol
            $('#txtnopol').on('input', function() {
                this.value = this.value.toUpperCase();
            });

            // Load merek change handler
            $('#cbomerek').change(function() {
                var merekId = $(this).val();
                if (merekId) {
                    $.ajax({
                        url: 'get_tipe_motor.php',
                        type: 'POST',
                        data: { merek_id: merekId },
                        success: function(response) {
                            $('#cbotipe').html(response);
                        },
                        error: function() {
                            alert('Gagal memuat tipe motor.');
                        }
                    });
                } else {
                    $('#cbotipe').html('<option value="">- Pilih Tipe -</option>');
                }
            });

            // Load provinces on page load
            $.ajax({
                url: 'get_provinces.php',
                type: 'GET',
                dataType: 'json',
                success: function(provinces) {
                    var options = '<option value="">- Pilih Provinsi -</option>';
                    $.each(provinces, function(index, province) {
                        options += '<option value="' + province + '">' + province + '</option>';
                    });
                    $('#cboprovinsi').html(options);
                },
                error: function() {
                    console.log('Gagal memuat data provinsi.');
                }
            });

            // Handle province change
            $('#cboprovinsi').change(function() {
                var provinsi = $(this).val();
                if (provinsi) {
                    $.ajax({
                        url: 'get_cities.php',
                        type: 'POST',
                        data: { provinsi: provinsi },
                        dataType: 'json',
                        success: function(cities) {
                            var options = '<option value="">- Pilih Kota/Kabupaten -</option>';
                            $.each(cities, function(index, city) {
                                options += '<option value="' + city + '">' + city + '</option>';
                            });
                            $('#cbokota').html(options);
                            $('#cbokecamatan').html('<option value="">- Pilih Kecamatan -</option>');
                        },
                        error: function() {
                            console.log('Gagal memuat data kota.');
                        }
                    });
                } else {
                    $('#cbokota').html('<option value="">- Pilih Kota/Kabupaten -</option>');
                    $('#cbokecamatan').html('<option value="">- Pilih Kecamatan -</option>');
                }
            });

            // Handle city change
            $('#cbokota').change(function() {
                var provinsi = $('#cboprovinsi').val();
                var kota = $(this).val();
                if (provinsi && kota) {
                    $.ajax({
                        url: 'get_districts.php',
                        type: 'POST',
                        data: { provinsi: provinsi, kota: kota },
                        dataType: 'json',
                        success: function(districts) {
                            var options = '<option value="">- Pilih Kecamatan -</option>';
                            $.each(districts, function(index, district) {
                                options += '<option value="' + district + '">' + district + '</option>';
                            });
                            $('#cbokecamatan').html(options);
                        },
                        error: function() {
                            console.log('Gagal memuat data kecamatan.');
                        }
                    });
                } else {
                    $('#cbokecamatan').html('<option value="">- Pilih Kecamatan -</option>');
                }
            });

            // Handle Save Data Button
            $('#btnSimpanData').click(function() {
                // Validate form first
                var isValid = true;
                var errorMsg = '';

                // Check required fields
                if (!$('#txtnama').val().trim()) {
                    isValid = false;
                    errorMsg = 'Nama pelanggan harus diisi!';
                } else if (!$('#cbogender').val()) {
                    isValid = false;
                    errorMsg = 'Gender harus dipilih!';
                } else if (!$('#cboprovinsi').val()) {
                    isValid = false;
                    errorMsg = 'Provinsi harus dipilih!';
                } else if (!$('#cbokota').val()) {
                    isValid = false;
                    errorMsg = 'Kota harus dipilih!';
                } else if (!$('#cbokecamatan').val()) {
                    isValid = false;
                    errorMsg = 'Kecamatan harus dipilih!';
                } else if (!$('#txtnopol').val().trim()) {
                    isValid = false;
                    errorMsg = 'Nomor polisi harus diisi!';
                } else if (!$('#cbomerek').val()) {
                    isValid = false;
                    errorMsg = 'Merek motor harus dipilih!';
                } else if (!$('#cboinformasisumber').val()) {
                    isValid = false;
                    errorMsg = 'Informasi sumber harus dipilih!';
                }

                if (!isValid) {
                    alert(errorMsg);
                    return;
                }

                // Show loading state
                var $btn = $(this);
                var originalText = $('#btnSimpanText').text();
                $btn.prop('disabled', true);
                $('#btnSimpanText').html('<i class="ace-icon fa fa-spinner fa-spin"></i> Menyimpan...');

                // Prepare form data
                var formData = new FormData();
                
                // Customer data
                formData.append('txtnama', $('#txtnama').val());
                formData.append('cbogender', $('#cbogender').val());
                formData.append('id-date-picker-1', $('#id-date-picker-1').val());
                formData.append('cbovalid', $('#cbovalid').val());
                formData.append('cboprovinsi', $('#cboprovinsi').val());
                formData.append('cbokota', $('#cbokota').val());
                formData.append('cbokecamatan', $('#cbokecamatan').val());
                formData.append('txtalamat', $('#txtalamat').val());
                formData.append('txtpatokan', $('#txtpatokan').val());
                formData.append('txtgooglemaps', $('#txtgooglemaps').val());
                formData.append('txtnowa', $('#txtnowa').val());
                formData.append('cboinformasisumber', $('#cboinformasisumber').val());
                
                // Vehicle data
                formData.append('txtnopol', $('#txtnopol').val().toUpperCase());
                formData.append('cbobulanpajak', $('#cbobulanpajak').val());
                formData.append('txtthnpajak', $('#txtthnpajak').val());
                formData.append('cbomerek', $('#cbomerek').val());
                formData.append('cbotipe', $('#cbotipe').val());
                formData.append('cbojenis', $('#cbojenis').val());
                formData.append('cbowarna', $('#cbowarna').val());
                
                // File upload
                var fileInput = document.getElementById('txtfotorumah');
                if (fileInput && fileInput.files.length > 0) {
                    formData.append('txtfotorumah', fileInput.files[0]);
                }

                // AJAX save
                $.ajax({
                    url: 'save_pelanggan_only.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Hide step 1
                            $('#step-save-data').fadeOut(300, function() {
                                // Show step 2
                                $('#step-service-selection').fadeIn(300);
                                // Store nopol for service buttons
                                $('#btnServisReguler').data('nopol', response.nopol || $('#txtnopol').val());
                                $('#btnServisJemput').data('nopol', response.nopol || $('#txtnopol').val());
                                // Scroll to service selection
                                $('html, body').animate({
                                    scrollTop: $('#step-service-selection').offset().top - 100
                                }, 500);
                            });
                        } else {
                            alert('Error: ' + response.message);
                            // Reset button
                            $btn.prop('disabled', false);
                            $('#btnSimpanText').text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.responseText);
                        alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
                        // Reset button
                        $btn.prop('disabled', false);
                        $('#btnSimpanText').text(originalText);
                    }
                });
            });

            // Handle Service Type Selection
            $('#btnServisReguler').click(function() {
                var nopol = $(this).data('nopol') || $('#txtnopol').val();
                if (confirm('Lanjut ke servis reguler untuk kendaraan ' + nopol + '?')) {
                    window.location.href = 'save-no-servis-reguler.php?snopol=' + encodeURIComponent(nopol);
                }
            });

            $('#btnServisJemput').click(function() {
                var nopol = $(this).data('nopol') || $('#txtnopol').val();
                if (confirm('Lanjut ke servis jemput antar untuk kendaraan ' + nopol + '?')) {
                    window.location.href = 'save-no-servis-jemput.php?snopol=' + encodeURIComponent(nopol);
                }
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($koneksi);
?>
