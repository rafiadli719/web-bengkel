<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    include "../config/accurate_config.php";

    // Data User
    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'];
    $pwd = $tm_cari['password'];
    $lvl_akses = $tm_cari['user_akses'];
    $foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

    // Data Cabang
    $cari_kd = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $nama_cabang = $tm_cari['nama_cabang'];
    $tipe_cabang = $tm_cari['tipe_cabang'];

    $tgl_skr = date('d');
    $bulan_skr = date('m');
    $thn_skr = date('Y');
    $kd_cabang_supplier = "";

    // Function to check Accurate connection
    if (!function_exists('checkAccurateConnection')) {
        function checkAccurateConnection() {
            $validation = validateAccurateConfig();
            if ($validation !== true) {
                return [
                    'status' => 'disconnected',
                    'message' => 'Konfigurasi API tidak lengkap: ' . implode(', ', $validation)
                ];
            }

            $connection_test = testAccurateConnection();
            if ($connection_test['success']) {
                $host = getAccurateHost();
                if ($host) {
                    $_SESSION['accurate_host'] = $host;
                    return [
                        'status' => 'connected',
                        'message' => 'Terhubung dengan Accurate Online'
                    ];
                } else {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Gagal mendapatkan host Accurate'
                    ];
                }
            } else {
                logAccurateDebug("Connection test failed: " . $connection_test['error']);
                return [
                    'status' => 'disconnected',
                    'message' => $connection_test['error']
                ];
            }
        }
    }

    // Check Accurate connection and store in session
    if (defined('ACCURATE_API_TOKEN') && defined('ACCURATE_SIGNATURE_SECRET') && defined('ACCURATE_API_BASE_URL')) {
        $accurate_connection = checkAccurateConnection();
        $_SESSION['accurate_status'] = $accurate_connection['status'];
        $_SESSION['accurate_message'] = $accurate_connection['message'];
    } else {
        $_SESSION['accurate_status'] = 'disconnected';
        $_SESSION['accurate_message'] = 'Konfigurasi API tidak lengkap atau file config tidak ditemukan';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="with draggable and editable events" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fullcalendar.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
    <script type="text/javascript" src="chartjs/Chart.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.css' rel='stylesheet' />
    
    <style>
        .supplier-type-info {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .whatsapp-format {
            font-size: 11px;
            color: #28a745;
            margin-top: 3px;
        }
        .field-group {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fafafa;
        }
        .field-group h4 {
            margin-top: 0;
            color: #337ab7;
            border-bottom: 2px solid #337ab7;
            padding-bottom: 5px;
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
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-header pull-right">
                <?php if (isset($_SESSION['accurate_status'])): ?>
                    <span class="navbar-brand">
                        <small style="color: <?php echo $_SESSION['accurate_status'] == 'connected' ? 'green' : 'orange'; ?>">
                            <i class="fa fa-circle"></i> Accurate: <?php echo $_SESSION['accurate_status']; ?>
                        </small>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_supplier.php"; ?>
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
                        <li><a href="supplier.php">Data Supplier</a></li>
                        <li class="active">Tambah Data</li>
                    </ul>
                </div>

                <div class="page-content">
                    <?php if (isset($_SESSION['accurate_status'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['accurate_status'] == 'connected' ? 'success' : 'warning'; ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Status Accurate API:</strong>
                            <?php if ($_SESSION['accurate_status'] == 'connected'): ?>
                                <i class="fa fa-check-circle"></i> ✅ Terhubung - Data akan otomatis sinkronisasi ke Accurate Online
                            <?php else: ?>
                                <i class="fa fa-exclamation-triangle"></i> ⚠️ Tidak terhubung - Data hanya disimpan di database lokal
                                <br><small><?php echo $_SESSION['accurate_message']; ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form class="form-horizontal" action="save_supplier.php" method="post" id="supplierForm">
                        
                        <!-- Informasi Dasar Supplier -->
                        <div class="field-group">
                            <h4><i class="fa fa-building"></i> Informasi Dasar Supplier</h4>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtkd"> 
                                            <span class="text-danger">*</span> Kode Supplier
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtkd" name="txtkd" class="col-xs-10 col-sm-12" required autocomplete="off" 
                                                   placeholder="Contoh: SUP001" maxlength="20" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtnama"> 
                                            <span class="text-danger">*</span> Nama Supplier
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-12" required autocomplete="off" 
                                                   placeholder="Nama lengkap supplier" maxlength="100" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="cbotipe"> 
                                            <span class="text-danger">*</span> Tipe Pemasok
                                        </label>
                                        <div class="col-sm-8">
                                            <select class="col-xs-10 col-sm-12 chosen-select" name="cbotipe" id="cbotipe" required>
                                                <option value="">- Pilih Tipe Pemasok -</option>
                                                <option value="perusahaan">Perusahaan</option>
                                                <option value="perorangan">Perorangan</option>
                                                <option value="pemerintahan">Pemerintahan</option>
                                            </select>
                                            <div class="supplier-type-info">
                                                <i class="fa fa-info-circle"></i> 
                                                Perusahaan: PT/CV/UD | Perorangan: Individu | Pemerintahan: Instansi Pemerintah
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xs-6">
                                    <!-- Cabang field removed as per requirement -->
                                </div>
                            </div>
                        </div>

                        <!-- Alamat dan Lokasi -->
                        <div class="field-group">
                            <h4><i class="fa fa-map-marker"></i> Alamat dan Lokasi</h4>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtalamat"> 
                                            <span class="text-danger">*</span> Alamat
                                        </label>
                                        <div class="col-sm-8">
                                            <textarea id="txtalamat" name="txtalamat" class="col-xs-10 col-sm-12" required 
                                                      rows="2" placeholder="Alamat lengkap supplier"></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtkota"> 
                                            <span class="text-danger">*</span> Kota
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtkota" name="txtkota" class="col-xs-10 col-sm-12" required 
                                                   autocomplete="off" placeholder="Nama kota" maxlength="50" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtprop"> 
                                            <span class="text-danger">*</span> Provinsi
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtprop" name="txtprop" class="col-xs-10 col-sm-12" required 
                                                   autocomplete="off" placeholder="Nama provinsi" maxlength="50" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtpos"> Kode Pos </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtpos" name="txtpos" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="12345" maxlength="10" pattern="[0-9]{5}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtnegara"> 
                                            <span class="text-danger">*</span> Negara
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtnegara" name="txtnegara" class="col-xs-10 col-sm-12" required 
                                                   autocomplete="off" value="Indonesia" maxlength="50" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kontak dan Komunikasi -->
                        <div class="field-group">
                            <h4><i class="fa fa-phone"></i> Kontak dan Komunikasi</h4>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txttlp"> 
                                            <span class="text-danger">*</span> No. Telepon
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txttlp" name="txttlp" class="col-xs-10 col-sm-12" required 
                                                   autocomplete="off" placeholder="021-1234567" maxlength="20" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtwa"> No. WhatsApp </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtwa" name="txtwa" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="628123456789" maxlength="20" pattern="[0-9+]+" />
                                            <div class="whatsapp-format">
                                                <i class="fa fa-whatsapp"></i> 
                                                Format: 628123456789 (gunakan 62 untuk kode negara Indonesia)
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtfax"> Fax </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtfax" name="txtfax" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="021-1234568" maxlength="20" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtemail"> 
                                            <span class="text-danger">*</span> Email
                                        </label>
                                        <div class="col-sm-8">
                                            <input type="email" id="txtemail" name="txtemail" class="col-xs-10 col-sm-12" required 
                                                   autocomplete="off" placeholder="supplier@email.com" maxlength="100" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtkontak"> Kontak Person </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtkontak" name="txtkontak" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="Nama kontak person" maxlength="100" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Bank -->
                        <div class="field-group">
                            <h4><i class="fa fa-bank"></i> Informasi Bank</h4>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtbank"> Nama Bank </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtbank" name="txtbank" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="Bank Mandiri" maxlength="50" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtnorek"> No. Rekening </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtnorek" name="txtnorek" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="1234567890" maxlength="50" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtnmrek"> Atas Nama </label>
                                        <div class="col-sm-8">
                                            <input type="text" id="txtnmrek" name="txtnmrek" class="col-xs-10 col-sm-12" 
                                                   autocomplete="off" placeholder="Nama pemilik rekening" maxlength="100" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtnote"> Catatan </label>
                                        <div class="col-sm-8">
                                            <textarea class="col-xs-10 col-sm-12" id="txtnote" name="txtnote" rows="3" 
                                                      placeholder="Catatan tambahan supplier"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pabrik Spare Part -->
                        <div class="field-group">
                            <h4><i class="fa fa-cogs"></i> Pabrik Spare Part</h4>
                            <div class="row">
                                <div class="col-xs-12">
                                    <p class="help-block">
                                        <i class="fa fa-info-circle"></i> 
                                        Pilih pabrik spare part yang disediakan oleh supplier ini
                                    </p>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr style="background-color: #f5f5f5;">
                                                <td align="center" width="5%">
                                                    <input type="checkbox" id="checkAll" onclick="toggleAll(this)">
                                                </td>
                                                <td width="95%"><strong>Nama Pabrik Spare Part</strong></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = mysqli_query($koneksi, "SELECT id, pabrik_barang FROM tbpabrik_barang ORDER BY pabrik_barang");
                                            $no = 1;
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                            ?>
                                                <tr>
                                                    <td class="center">
                                                        <input type="checkbox" name="hapus[]" value="<?php echo $tampil['id']; ?>" 
                                                               class="pabrik-checkbox">
                                                    </td>
                                                    <td><?php echo $tampil['pabrik_barang']; ?></td>
                                                </tr>
                                            <?php
                                                $no++;
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                    <?php if ($no == 1): ?>
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> 
                                            Belum ada data pabrik spare part. Silakan tambahkan data pabrik terlebih dahulu.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Info sinkronisasi -->
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    <strong>Informasi Sinkronisasi:</strong> 
                                    Data supplier akan disimpan ke database lokal. 
                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                        Sistem akan otomatis mencoba sinkronisasi ke Accurate Online dengan mapping:
                                        <ul class="list-unstyled" style="margin-top: 10px; margin-left: 20px;">
                                            <li>• <strong>Perusahaan</strong> → Vendor Type: Corporate</li>
                                            <li>• <strong>Perorangan</strong> → Vendor Type: Individual</li>
                                            <li>• <strong>Pemerintahan</strong> → Vendor Type: Government</li>
                                        </ul>
                                    <?php else: ?>
                                        Sinkronisasi ke Accurate Online tidak tersedia karena koneksi API bermasalah.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="clearfix form-actions">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-xs-6">
                                        <button class="btn btn-info btn-lg" type="submit">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            Simpan Supplier
                                            <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                & Sync to Accurate
                                            <?php endif; ?>
                                        </button>
                                        <button class="btn btn-warning" type="reset" onclick="resetForm()">
                                            <i class="ace-icon fa fa-undo bigger-110"></i>
                                            Reset Form
                                        </button>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <a href="supplier.php" class="btn btn-default btn-lg">
                                            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
                                            Kembali ke Daftar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Panel informasi Accurate -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-small">
                                    <h5 class="widget-title"><i class="ace-icon fa fa-cloud"></i> Status Integrasi Accurate Online</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label class="control-label">Status Koneksi:</label>
                                                    <span class="badge badge-<?php echo (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') ? 'success' : 'warning'; ?>">
                                                        <?php echo isset($_SESSION['accurate_status']) ? strtoupper($_SESSION['accurate_status']) : 'UNKNOWN'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label class="control-label">Last Check:</label>
                                                    <span class="text-muted"><?php echo date('d/m/Y H:i:s'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label class="control-label">Message:</label>
                                                    <p class="help-block"><?php echo isset($_SESSION['accurate_message']) ? $_SESSION['accurate_message'] : 'Status tidak tersedia'; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="window.location.reload();"><i class="fa fa-refresh"></i> Refresh Status</button>
                                                <?php if (!isset($_SESSION['accurate_status']) || $_SESSION['accurate_status'] != 'connected'): ?>
                                                    <button type="button" class="btn btn-sm btn-info" onclick="showTroubleshooting();"><i class="fa fa-question-circle"></i> Troubleshooting</button>
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

            <div class="footer">
                <div class="footer-inner">
                    <div class="footer-content"><?php include "../lib/footer.php"; ?></div>
                </div>
            </div>
            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"><i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i></a>
        </div>

        <!-- Modal Troubleshooting -->
        <div class="modal fade" id="troubleshootingModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title"><i class="fa fa-wrench"></i> Troubleshooting Koneksi Accurate</h4>
                    </div>
                    <div class="modal-body">
                        <h5>Langkah-langkah pemecahan masalah:</h5>
                        <ol>
                            <li><strong>Periksa Konfigurasi API:</strong>
                                <ul>
                                    <li>Pastikan file <code>accurate_config.php</code> ada</li>
                                    <li>Periksa <code>ACCURATE_API_TOKEN</code> tidak kosong</li>
                                    <li>Periksa <code>ACCURATE_SIGNATURE_SECRET</code> tidak kosong</li>
                                    <li>Periksa <code>ACCURATE_API_BASE_URL</code> sudah benar</li>
                                </ul>
                            </li>
                            <li><strong>Periksa API Token:</strong>
                                <ul>
                                    <li>Login ke Accurate Online</li>
                                    <li>Buka menu Developer > API Token</li>
                                    <li>Pastikan token masih aktif</li>
                                    <li>Periksa permission untuk vendor_save</li>
                                </ul>
                            </li>
                            <li><strong>Periksa Koneksi Internet:</strong>
                                <ul>
                                    <li>Pastikan server dapat mengakses internet</li>
                                    <li>Cek firewall tidak memblokir koneksi</li>
                                </ul>
                            </li>
                        </ol>
                        
                        <h5>Field Mapping Accurate:</h5>
                        <div class="table-responsive">
                            <table class="table table-condensed table-bordered">
                                <thead>
                                    <tr style="background-color: #f5f5f5;">
                                        <th>Field Lokal</th>
                                        <th>Field Accurate</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Kode Supplier</td><td>vendorNo</td><td>Nomor identitas vendor</td></tr>
                                    <tr><td>Nama Supplier</td><td>name</td><td>Nama entitas (required)</td></tr>
                                    <tr><td>Tipe Pemasok</td><td>-</td><td>Untuk kategori internal</td></tr>
                                    <tr><td>Alamat</td><td>billStreet</td><td>Alamat penagihan</td></tr>
                                    <tr><td>Kota</td><td>billCity</td><td>Kota alamat penagihan</td></tr>
                                    <tr><td>No. WhatsApp</td><td>mobilePhone</td><td>Nomor mobile/WhatsApp</td></tr>
                                    <tr><td>Email</td><td>email</td><td>Alamat email</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script type="text/javascript">if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");</script>
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/jquery-ui.custom.min.js"></script>
        <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
        <script src="assets/js/chosen.jquery.min.js"></script>
        <script src="assets/js/spinbox.min.js"></script>
        <script src="assets/js/bootstrap-datepicker.min.js"></script>
        <script src="assets/js/bootstrap-timepicker.min.js"></script>
        <script src="assets/js/moment.min.js"></script>
        <script src="assets/js/daterangepicker.min.js"></script>
        <script src="assets/js/bootstrap-datetimepicker.min.js"></script>
        <script src="assets/js/bootstrap-colorpicker.min.js"></script>
        <script src="assets/js/jquery.knob.min.js"></script>
        <script src="assets/js/autosize.min.js"></script>
        <script src="assets/js/jquery.inputlimiter.min.js"></script>
        <script src="assets/js/jquery.maskedinput.min.js"></script>
        <script src="assets/js/bootstrap-tag.min.js"></script>
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>
        
        <script type="text/javascript">
            jQuery(function($) {
                // Initialize chosen select
                if (!ace.vars['touch']) {
                    $('.chosen-select').chosen({ allow_single_deselect: true });
                    $(window).off('resize.chosen').on('resize.chosen', function() {
                        $('.chosen-select').each(function() {
                            var $this = $(this);
                            $this.next().css({ 'width': $this.parent().width() });
                        });
                    }).trigger('resize.chosen');
                    $(document).on('settings.ace.chosen', function(e, event_name, event_val) {
                        if (event_name != 'sidebar_collapsed') return;
                        $('.chosen-select').each(function() {
                            var $this = $(this);
                            $this.next().css({ 'width': $this.parent().width() });
                        });
                    });
                }

                // Initialize tooltips and popovers
                $('[data-rel=tooltip]').tooltip({ container: 'body' });
                $('[data-rel=popover]').popover({ container: 'body' });

                // Initialize autosize for textareas
                autosize($('textarea[class*=autosize]'));

                // Input masks
                $.mask.definitions['~'] = '[+-]';
                $('.input-mask-date').mask('99/99/9999');
                $('.input-mask-phone').mask('(999) 999-9999');

                // Auto-hide alert after 15 seconds
                setTimeout(function() { $('.alert-dismissible').fadeOut('slow'); }, 15000);

                // Focus pada field kode supplier
                $('#txtkd').focus();

                // Form validation
                $('#supplierForm').on('submit', function(e) {
                    var kode = $('#txtkd').val().trim();
                    var nama = $('#txtnama').val().trim();
                    var tipe = $('#cbotipe').val();
                    var alamat = $('#txtalamat').val().trim();
                    var kota = $('#txtkota').val().trim();
                    var provinsi = $('#txtprop').val().trim();
                    var negara = $('#txtnegara').val().trim();
                    var telepon = $('#txttlp').val().trim();
                    var email = $('#txtemail').val().trim();
                    
                    // Validasi field wajib
                    if (kode === '' || nama === '' || tipe === '' || alamat === '' || 
                        kota === '' || provinsi === '' || negara === '' || telepon === '' || email === '') {
                        e.preventDefault();
                        alert('⚠️ Field yang bertanda (*) wajib diisi!\n\nPastikan semua field required telah terisi dengan benar.');
                        return false;
                    }
                    
                    // Validasi format email
                    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email)) {
                        e.preventDefault();
                        alert('⚠️ Format email tidak valid!\n\nContoh format yang benar: user@domain.com');
                        $('#txtemail').focus();
                        return false;
                    }
                    
                    // Validasi WhatsApp (jika diisi)
                    var whatsapp = $('#txtwa').val().trim();
                    if (whatsapp !== '') {
                        var waPattern = /^[0-9+]+$/;
                        if (!waPattern.test(whatsapp)) {
                            e.preventDefault();
                            alert('⚠️ Format WhatsApp tidak valid!\n\nGunakan hanya angka dan tanda +\nContoh: 628123456789');
                            $('#txtwa').focus();
                            return false;
                        }
                    }
                    
                    // Validasi kode pos (jika diisi)
                    var kodepos = $('#txtpos').val().trim();
                    if (kodepos !== '' && !/^[0-9]{5}$/.test(kodepos)) {
                        e.preventDefault();
                        alert('⚠️ Kode pos harus 5 digit angka!\n\nContoh: 12345');
                        $('#txtpos').focus();
                        return false;
                    }
                    
                    var confirmMessage = 'Apakah Anda yakin ingin menyimpan supplier ini?\n\n' +
                                       '📋 Detail Supplier:\n' +
                                       'Kode: ' + kode + '\n' +
                                       'Nama: ' + nama + '\n' +
                                       'Tipe: ' + tipe.charAt(0).toUpperCase() + tipe.slice(1) + '\n' +
                                       'Email: ' + email + '\n\n' +
                                       '💾 Data akan disimpan ke database lokal';
                    
                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                        confirmMessage += ' dan akan dicoba sinkronisasi ke Accurate Online.';
                    <?php else: ?>
                        confirmMessage += '.\n⚠️ Sinkronisasi ke Accurate tidak tersedia.';
                    <?php endif; ?>
                    
                    return confirm(confirmMessage);
                });

                // Auto-uppercase untuk kode supplier
                $('#txtkd').on('input', function() { 
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                });

                // Auto-capitalize untuk nama supplier
                $('#txtnama').on('input', function() {
                    var words = this.value.split(' ');
                    for (var i = 0; i < words.length; i++) {
                        if (words[i].length > 0) {
                            words[i] = words[i][0].toUpperCase() + words[i].substr(1).toLowerCase();
                        }
                    }
                    this.value = words.join(' ');
                });

                // Auto-capitalize untuk kota dan provinsi
                $('#txtkota, #txtprop, #txtnegara').on('input', function() {
                    var words = this.value.split(' ');
                    for (var i = 0; i < words.length; i++) {
                        if (words[i].length > 0) {
                            words[i] = words[i][0].toUpperCase() + words[i].substr(1).toLowerCase();
                        }
                    }
                    this.value = words.join(' ');
                });

                // Validasi WhatsApp real-time
                $('#txtwa').on('input', function() {
                    this.value = this.value.replace(/[^0-9+]/g, '');
                });

                // Validasi kode pos real-time
                $('#txtpos').on('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').substr(0, 5);
                });

                // Tipe pemasok change handler
                $('#cbotipe').on('change', function() {
                    var tipe = $(this).val();
                    var info = '';
                    
                    switch(tipe) {
                        case 'perusahaan':
                            info = '🏢 Entitas bisnis seperti PT, CV, UD, dll';
                            break;
                        case 'perorangan':
                            info = '👤 Individu/perseorangan';
                            break;
                        case 'pemerintahan':
                            info = '🏛️ Instansi pemerintah, dinas, BUMN, dll';
                            break;
                        default:
                            info = 'Pilih tipe pemasok sesuai kategori';
                    }
                    
                    $('.supplier-type-info').html('<i class="fa fa-info-circle"></i> ' + info);
                });

                // Copy nama supplier ke atas nama rekening jika kosong
                $('#txtnama').on('blur', function() {
                    if ($('#txtnmrek').val().trim() === '') {
                        $('#txtnmrek').val($(this).val());
                    }
                });

                // Copy telepon ke WhatsApp dengan format Indonesia jika kosong
                $('#txttlp').on('blur', function() {
                    if ($('#txtwa').val().trim() === '') {
                        var telp = $(this).val().replace(/[^0-9]/g, '');
                        if (telp.startsWith('0')) {
                            $('#txtwa').val('62' + telp.substring(1));
                        }
                    }
                });
            });

            // Function untuk toggle all checkboxes
            function toggleAll(source) {
                $('.pabrik-checkbox').prop('checked', source.checked);
            }

            // Function untuk show troubleshooting modal
            function showTroubleshooting() {
                $('#troubleshootingModal').modal('show');
            }

            // Function untuk reset form
            function resetForm() {
                if (confirm('⚠️ Apakah Anda yakin ingin mereset semua data form?\n\nSemua data yang telah diisi akan hilang.')) {
                    $('#supplierForm')[0].reset();
                    $('.chosen-select').trigger('chosen:updated');
                    $('#checkAll').prop('checked', false);
                    $('.pabrik-checkbox').prop('checked', false);
                    $('#txtkd').focus();
                }
            }

            // Auto-refresh status setiap 5 menit
            setInterval(function() { 
                console.log('Auto-checking Accurate status...'); 
            }, 300000);
        </script>
</body>
</html>