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

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_cabang, tipe_cabang
                                    FROM tbcabang
                                    WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];
    $tipe_cabang=$tm_cari['tipe_cabang'];
    // --------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>

    <meta name="description" content="with draggable and editable events" />
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
    
    <style>
        .service-type-btn {
            padding: 15px;
            margin: 5px 0;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .service-type-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .service-type-btn i {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .service-type-btn strong {
            display: block;
            margin: 5px 0;
        }
        .service-type-btn small {
            display: block;
            opacity: 0.8;
        }
        #btnUpdatePelanggan {
            margin: 10px 0;
            border-radius: 4px;
        }
        .alert {
            border-radius: 4px;
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
                                    <?php include "../lib/logo.php"; ?>
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
                        <li>
                            <a href="#">Servis Reguler</a>
                        </li>
                        <li>
                            <a href="servis-carinopol.php">Input Servis</a>
                        </li>
                        <li class="active">Input Data Pelanggan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Input Data Pelanggan
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Langkah 1: Input Nomor WhatsApp
                            </small>
                        </h1>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="ace-icon fa fa-search"></i>
                                        Pencarian Data Pelanggan & Kendaraan
                                    </h4>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form class="form-horizontal" id="formPelangganAwal" method="post">

                                            <!-- Step 1: Input WhatsApp dan Nopol -->
                                            <div id="step1" class="step-container">
                                                <div class="alert alert-info">
                                                    <i class="ace-icon fa fa-info-circle"></i>
                                                    <strong>Petunjuk:</strong> Anda dapat mengisi salah satu atau kedua field di bawah ini untuk mencari data pelanggan dan kendaraan.
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="txtWhatsApp">
                                                        <i class="ace-icon fa fa-whatsapp green"></i>
                                                        Nomor WhatsApp :
                                                    </label>
                                                    <div class="col-sm-6">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">
                                                                <i class="ace-icon fa fa-phone"></i>
                                                            </span>
                                                            <input type="text" class="form-control"
                                                                id="txtWhatsApp" name="txtWhatsApp"
                                                                placeholder="Contoh: 08123456789"
                                                                autocomplete="off" />
                                                        </div>
                                                        <span class="help-block">
                                                            <small>Format: 08xxx atau +628xxx</small>
                                                        </span>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <button type="button" class="btn btn-primary" id="btnCekWhatsApp">
                                                            <i class="ace-icon fa fa-search"></i>
                                                            Cek WA
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="txtNopolSearch">
                                                        <i class="ace-icon fa fa-car blue"></i>
                                                        Nomor Polisi :
                                                    </label>
                                                    <div class="col-sm-6">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">
                                                                <i class="ace-icon fa fa-car"></i>
                                                            </span>
                                                            <input type="text" class="form-control"
                                                                id="txtNopolSearch" name="txtNopolSearch"
                                                                placeholder="Contoh: B 1234 ABC"
                                                                autocomplete="off"
                                                                maxlength="15"
                                                                pattern="[A-Za-z0-9\s\-]+"
                                                                title="Format: B 1234 ABC (kode wilayah + angka + huruf)"
                                                                style="text-transform: uppercase;" />
                                                        </div>
                                                        <span class="help-block">
                                                            <small><strong>Format:</strong> B 1234 ABC (kode wilayah + angka + huruf)</small>
                                                        </span>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <button type="button" class="btn btn-info" id="btnCekNopolSearch">
                                                            <i class="ace-icon fa fa-search"></i>
                                                            Cek Nopol
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="col-sm-12 text-center">
                                                        <button type="button" class="btn btn-success btn-lg" id="btnCekSemua">
                                                            <i class="ace-icon fa fa-search-plus"></i>
                                                            Cek Semua Data
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Step 2: Show Results -->
                                            <div id="step2" class="step-container" style="display: none;">
                                                <div id="resultAlert" class="alert">
                                                    <div id="resultContent"></div>
                                                </div>

                                                <!-- Step 2a: Additional Nopol Input for Existing Customer -->
                                                <div id="step2a" class="additional-step" style="display: none;">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtNopolBaru">
                                                            <i class="ace-icon fa fa-plus-circle orange"></i>
                                                            Nomor Polisi Baru :
                                                        </label>
                                                        <div class="col-sm-6">
                                                            <div class="input-group">
                                                                <span class="input-group-addon">
                                                                    <i class="ace-icon fa fa-car"></i>
                                                                </span>
                                                                <input type="text" class="form-control"
                                                                    id="txtNopolBaru" name="txtNopolBaru"
                                                                    placeholder="Contoh: B 5678 DEF"
                                                                    autocomplete="off"
                                                                    maxlength="15"
                                                                    pattern="[A-Za-z0-9\s\-]+"
                                                                    title="Format: B 5678 DEF (kode wilayah + angka + huruf)"
                                                                    style="text-transform: uppercase;" />
                                                            </div>
                                                            <span class="help-block">
                                                                <small><strong>Format:</strong> B 5678 DEF (kode wilayah + angka + huruf)</small>
                                                            </span>
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <button type="button" class="btn btn-warning" id="btnCekNopolBaru">
                                                                <i class="ace-icon fa fa-search"></i>
                                                                Cek Ketersediaan
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Navigation Buttons -->
                                            <div class="form-group">
                                                <div class="col-sm-12">
                                                    <div class="pull-left">
                                                        <a href="servis-carinopol.php" class="btn btn-default">
                                                            <i class="ace-icon fa fa-arrow-left"></i>
                                                            Kembali
                                                        </a>
                                                    </div>
                                                    <div class="pull-right">
                                                        <button type="button" class="btn btn-warning" id="btnPelangganBaru" style="display: none;">
                                                            <i class="ace-icon fa fa-user-plus"></i>
                                                            Tambah Pelanggan Baru
                                                        </button>
                                                        <button type="button" class="btn btn-info" id="btnTambahKendaraan" style="display: none;">
                                                            <i class="ace-icon fa fa-plus"></i>
                                                            Tambah Kendaraan Baru
                                                        </button>
                                                        <button type="button" class="btn btn-success" id="btnLanjutServis" style="display: none;">
                                                            <i class="ace-icon fa fa-arrow-right"></i>
                                                            Lanjut Input Servis
                                                        </button>
                                                        <button type="button" class="btn btn-primary" id="btnServisReguler" style="display: none;">
                                                            <i class="ace-icon fa fa-wrench"></i>
                                                            Input Servis Reguler
                                                        </button>
                                                        <button type="button" class="btn btn-warning" id="btnServisJemput" style="display: none;">
                                                            <i class="ace-icon fa fa-truck"></i>
                                                            Input Servis Jemput
                                                        </button>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                            </div>

                                        </form>
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

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
    </script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- ace scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <!-- Nopol Validator - Load after jQuery -->
    <script src="assets/js/nopol-validator.js"></script>

    <!-- inline scripts -->
    <script type="text/javascript">
        jQuery(function($) {
            var globalData = {
                waExists: false,
                nopolExists: false,
                customerData: null,
                vehicleData: null,
                customerVehicles: [],
                searchedWA: '',
                searchedNopol: ''
            };

            // Inisialisasi Nopol Validator untuk semua field nopol
            NopolValidator.init('#txtNopolSearch', {
                showError: true,
                autoFormat: true,
                validateOnBlur: true,
                validateOnInput: true  // Validasi real-time saat mengetik
            });
            
            NopolValidator.init('#txtNopolBaru', {
                showError: true,
                autoFormat: true,
                validateOnBlur: true,
                validateOnInput: true  // Validasi real-time saat mengetik
            });
            
            // Tambahan: Block karakter yang tidak valid saat mengetik
            $('#txtNopolSearch, #txtNopolBaru').on('keypress', function(e) {
                var char = String.fromCharCode(e.which);
                // Hanya izinkan: huruf, angka, spasi, dan dash
                if (!/[A-Za-z0-9\s\-]/.test(char)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Limit panjang input maksimal (format terpanjang: "AB 9999 ABC" = 11 karakter)
            $('#txtNopolSearch, #txtNopolBaru').on('input', function() {
                var val = $(this).val();
                if (val.length > 15) {
                    $(this).val(val.substring(0, 15));
                }
            });

            // Reset form
            function resetForm() {
                globalData = {
                    waExists: false,
                    nopolExists: false,
                    customerData: null,
                    vehicleData: null,
                    customerVehicles: [],
                    searchedWA: '',
                    searchedNopol: ''
                };
                $('#step2').hide();
                $('#step2a').hide();
                hideAllButtons();
            }

            // Hide all action buttons
            function hideAllButtons() {
                $('#btnPelangganBaru, #btnTambahKendaraan, #btnLanjutServis, #btnServisReguler, #btnServisJemput').hide();
            }

            // Show loading state
            function showLoading(btn, text) {
                btn.prop('disabled', true).html('<i class="ace-icon fa fa-spinner fa-spin"></i> ' + text);
            }

            // Reset button state
            function resetButton(btn, originalText) {
                btn.prop('disabled', false).html(originalText);
            }

            // Generate vehicle list HTML
            function generateVehicleListHTML(vehicles) {
                if (!vehicles || vehicles.length === 0) {
                    return '<div class="alert alert-warning"><i class="ace-icon fa fa-exclamation-triangle"></i> Tidak ada kendaraan terdaftar untuk pelanggan ini.</div>';
                }

                var html = '<div class="vehicle-list-container" style="margin-top: 15px;">';
                html += '<h5><i class="ace-icon fa fa-car"></i> Kendaraan Terdaftar (' + vehicles.length + '):</h5>';
                html += '<div class="row">';

                vehicles.forEach(function(vehicle, index) {
                    var cardClass = vehicle.source === 'primary' ? 'panel-success' : 'panel-info';
                    var badgeClass = vehicle.source === 'primary' ? 'badge-success' : 'badge-info';
                    var sourceText = vehicle.source === 'primary' ? 'Utama' : 'Tambahan';
                    
                    html += '<div class="col-md-6 col-sm-12" style="margin-bottom: 10px;">';
                    html += '<div class="panel ' + cardClass + '">';
                    html += '<div class="panel-heading" style="padding: 8px 15px;">';
                    html += '<h6 class="panel-title">';
                    html += '<i class="ace-icon fa fa-car"></i> ';
                    html += '<strong>' + (vehicle.nopolisi || 'N/A') + '</strong>';
                    html += '<span class="badge ' + badgeClass + ' pull-right">' + sourceText + '</span>';
                    html += '</h6>';
                    html += '</div>';
                    html += '<div class="panel-body" style="padding: 10px 15px;">';
                    html += '<table class="table table-condensed" style="margin-bottom: 5px;">';
                    html += '<tr><td width="30%"><strong>Pemilik:</strong></td><td>' + (vehicle.pemilik || 'N/A') + '</td></tr>';
                    if (vehicle.merek && vehicle.merek !== 'Unknown') {
                        html += '<tr><td><strong>Merek:</strong></td><td>' + vehicle.merek + '</td></tr>';
                    }
                    if (vehicle.tipe) {
                        html += '<tr><td><strong>Tipe:</strong></td><td>' + vehicle.tipe + '</td></tr>';
                    }
                    if (vehicle.jenis) {
                        html += '<tr><td><strong>Jenis:</strong></td><td>' + vehicle.jenis + '</td></tr>';
                    }
                    if (vehicle.warna) {
                        html += '<tr><td><strong>Warna:</strong></td><td>' + vehicle.warna + '</td></tr>';
                    }
                    if (vehicle.tahun_buat) {
                        html += '<tr><td><strong>Tahun:</strong></td><td>' + vehicle.tahun_buat + '</td></tr>';
                    }
                    html += '</table>';
                    html += '<div class="text-center">';
                    html += '<button type="button" class="btn btn-primary btn-sm btn-select-vehicle" ';
                    html += 'data-nopol="' + vehicle.nopolisi + '" ';
                    html += 'data-pemilik="' + vehicle.pemilik + '" ';
                    html += 'data-tipe="' + (vehicle.tipe || '') + '">';
                    html += '<i class="ace-icon fa fa-check"></i> Pilih Kendaraan';
                    html += '</button>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                });

                html += '</div>';
                html += '</div>';
                return html;
            }

            // Display results
            function displayResults() {
                var alertClass = 'alert-info';
                var content = '';

                if (globalData.waExists && globalData.nopolExists) {
                    // Both WA and Nopol exist
                    alertClass = 'alert-success';
                    content = '<i class="ace-icon fa fa-check-circle"></i> ' +
                              '<strong>Data Lengkap Ditemukan!</strong><br>' +
                              'Pelanggan: <strong>' + globalData.customerData.nama + '</strong> (' + globalData.customerData.phone + ')<br>' +
                              'Kendaraan: <strong>' + globalData.vehicleData.nopolisi + '</strong> - ' + globalData.vehicleData.pemilik + '<br>' +
                              '<small>Tipe: ' + globalData.vehicleData.tipe + ' | Warna: ' + globalData.vehicleData.warna + '</small><br>' +
                              '<span class="text-info"><i class="ace-icon fa fa-info-circle"></i> Silakan pilih jenis layanan servis:</span>';
                    $('#btnServisReguler').show().data('nopol', globalData.vehicleData.nopolisi);
                    $('#btnServisJemput').show().data('nopol', globalData.vehicleData.nopolisi);

                } else if (globalData.waExists && !globalData.nopolExists) {
                    // WA exists but Nopol doesn't exist
                    alertClass = 'alert-success';
                    content = '<i class="ace-icon fa fa-user-check"></i> ' +
                              '<strong>Pelanggan Ditemukan!</strong><br>' +
                              'Nama: <strong>' + globalData.customerData.nama + '</strong> (' + globalData.customerData.phone + ')<br>';

                    // Show vehicle list if customer has vehicles
                    if (globalData.customerVehicles && globalData.customerVehicles.length > 0) {
                        content += generateVehicleListHTML(globalData.customerVehicles);
                        content += '<div style="margin-top: 15px;">';
                        content += '<div class="alert alert-info">';
                        content += '<i class="ace-icon fa fa-info-circle"></i> ';
                        content += '<strong>Pilih salah satu kendaraan di atas untuk melanjutkan servis, atau tambah kendaraan baru.</strong>';
                        content += '</div>';
                        content += '</div>';
                        $('#btnTambahKendaraan').show().data('phone', globalData.customerData.phone);
                    } else {
                        content += '<div class="alert alert-warning">';
                        content += '<i class="ace-icon fa fa-exclamation-triangle"></i> ';
                        content += 'Pelanggan ini belum memiliki kendaraan terdaftar.';
                        content += '</div>';
                        
                        if (globalData.searchedNopol) {
                            content += '<span class="text-info">Kendaraan <strong>' + globalData.searchedNopol + '</strong> akan didaftarkan untuk pelanggan ini.</span><br>';
                            $('#btnTambahKendaraan').show().data('phone', globalData.customerData.phone).data('nopol', globalData.searchedNopol);
                        } else {
                            content += 'Silakan masukkan nomor polisi kendaraan baru di bawah ini.';
                            $('#step2a').show();
                        }
                    }

                } else if (!globalData.waExists && globalData.nopolExists) {
                    // Nopol exists but WA doesn't exist
                    alertClass = 'alert-info';
                    content = '<i class="ace-icon fa fa-car"></i> ' +
                              '<strong>Kendaraan Ditemukan!</strong><br>' +
                              'Nopol: <strong>' + globalData.vehicleData.nopolisi + '</strong><br>' +
                              'Pemilik: <strong>' + (globalData.vehicleData.pemilik || '-') + '</strong>';

                    if (globalData.vehicleData.telephone) {
                        content += ' (' + globalData.vehicleData.telephone + ')';
                    }

                    // Check if customer data is incomplete
                    var isIncompleteData = !globalData.vehicleData.pemilik || 
                                         globalData.vehicleData.pemilik === '-' || 
                                         globalData.vehicleData.pemilik === '' ||
                                         !globalData.vehicleData.telephone;

                    if (isIncompleteData) {
                        content += '<div class="alert alert-warning" style="margin-top: 15px;">';
                        content += '<i class="ace-icon fa fa-exclamation-triangle"></i> ';
                        content += '<strong>Data pelanggan tidak lengkap!</strong><br>';
                        content += 'Anda dapat melengkapi data pelanggan terlebih dahulu atau langsung lanjut ke servis.';
                        content += '</div>';
                        
                        content += '<div style="margin-top: 15px;">';
                        content += '<button type="button" class="btn btn-info btn-sm" id="btnUpdatePelanggan" data-nopol="' + globalData.vehicleData.nopolisi + '">';
                        content += '<i class="ace-icon fa fa-edit"></i> Update Data Pelanggan (Opsional)';
                        content += '</button>';
                        content += '</div>';
                    }

                    // Service type selection
                    content += '<div style="margin-top: 20px;">';
                    content += '<div class="alert alert-success">';
                    content += '<i class="ace-icon fa fa-info-circle"></i> ';
                    content += '<strong>Pilih Jenis Layanan Servis:</strong>';
                    content += '</div>';
                    content += '<div class="row">';
                    content += '<div class="col-xs-6">';
                    content += '<button type="button" class="btn btn-primary btn-block btn-lg service-type-btn" id="btnServisRegulerVehicle" data-nopol="' + globalData.vehicleData.nopolisi + '">';
                    content += '<i class="ace-icon fa fa-wrench"></i><br>';
                    content += '<strong>Servis Reguler</strong><br>';
                    content += '<small>Pelanggan datang ke bengkel</small>';
                    content += '</button>';
                    content += '</div>';
                    content += '<div class="col-xs-6">';
                    content += '<button type="button" class="btn btn-warning btn-block btn-lg service-type-btn" id="btnServisJemputVehicle" data-nopol="' + globalData.vehicleData.nopolisi + '">';
                    content += '<i class="ace-icon fa fa-truck"></i><br>';
                    content += '<strong>Servis Jemput Antar</strong><br>';
                    content += '<small>Kami jemput motor pelanggan</small>';
                    content += '</button>';
                    content += '</div>';
                    content += '</div>';
                    content += '</div>';

                } else if (!globalData.waExists && !globalData.nopolExists) {
                    // Neither WA nor Nopol exist
                    alertClass = 'alert-danger';
                    content = '<i class="ace-icon fa fa-exclamation-triangle"></i> ' +
                              '<strong>Data Tidak Ditemukan!</strong><br>';

                    if (globalData.searchedWA && globalData.searchedNopol) {
                        content += 'Nomor WA <strong>' + globalData.searchedWA + '</strong> dan Nopol <strong>' + globalData.searchedNopol + '</strong> tidak ditemukan di sistem.<br>';
                    } else if (globalData.searchedWA) {
                        content += 'Nomor WA <strong>' + globalData.searchedWA + '</strong> tidak ditemukan di sistem.<br>';
                    } else if (globalData.searchedNopol) {
                        content += 'Nopol <strong>' + globalData.searchedNopol + '</strong> tidak ditemukan di sistem.<br>';
                    }

                    content += 'Silakan daftarkan sebagai pelanggan baru.';
                    $('#btnPelangganBaru').show();
                }

                $('#resultAlert').removeClass().addClass('alert ' + alertClass);
                $('#resultContent').html(content);
                $('#step2').show();
            }

            // Check WhatsApp only
            $('#btnCekWhatsApp').click(function() {
                var whatsapp = $('#txtWhatsApp').val().trim();

                if (!whatsapp) {
                    alert('Mohon masukkan nomor WhatsApp!');
                    $('#txtWhatsApp').focus();
                    return;
                }

                globalData.searchedWA = whatsapp;
                showLoading($(this), 'Memeriksa WA...');

                $.ajax({
                    url: 'check_phone.php',
                    method: 'POST',
                    data: { phone: whatsapp },
                    dataType: 'json',
                    success: function(response) {
                        resetButton($('#btnCekWhatsApp'), '<i class="ace-icon fa fa-search"></i> Cek WA');

                        if (response.error) {
                            alert('Error: ' + response.error);
                            return;
                        }

                        if (response.exists) {
                            globalData.waExists = true;
                            globalData.customerData = response.data;
                            globalData.customerVehicles = response.vehicles || [];
                            $('#btnServisReguler').data('vehicles', globalData.customerVehicles);
                            $('#btnServisJemput').data('vehicles', globalData.customerVehicles);
                        } else {
                            globalData.waExists = false;
                        }

                        displayResults();
                    },
                    error: function(xhr, status, error) {
                        resetButton($('#btnCekWhatsApp'), '<i class="ace-icon fa fa-search"></i> Cek WA');
                        console.log('AJAX Error Status:', status);
                        console.log('AJAX Error:', error);
                        console.log('Response Text:', xhr.responseText);

                        // Try to parse error response
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            alert('Error: ' + (errorResponse.error || error));
                        } catch(e) {
                            alert('Terjadi kesalahan saat memeriksa data WA. Silakan coba lagi.');
                        }
                    }
                });
            });

            // Check Nopol only
            $('#btnCekNopolSearch').click(function() {
                var nopol = $('#txtNopolSearch').val().trim().toUpperCase();

                if (!nopol) {
                    alert('❌ Mohon masukkan nomor polisi!');
                    $('#txtNopolSearch').focus();
                    return false;
                }

                // Validasi format nopol - RIGID!
                var validation = NopolValidator.validate(nopol);
                if (!validation.valid) {
                    // Tampilkan alert dengan detail error
                    var errorMsg = '❌ FORMAT NOMOR POLISI TIDAK VALID!\n\n';
                    errorMsg += '📋 Error: ' + validation.message + '\n\n';
                    errorMsg += '✅ Contoh format yang BENAR:\n';
                    errorMsg += '   • B 1234 ABC (Jakarta)\n';
                    errorMsg += '   • D 5678 XYZ (Bandung)\n';
                    errorMsg += '   • AB 123 CD (Yogyakarta)\n';
                    errorMsg += '   • 1234-56 (TNI/POLRI)\n';
                    errorMsg += '   • 12 CD 34 (Diplomatik)\n\n';
                    errorMsg += '⚠️ Pastikan:\n';
                    errorMsg += '   • Kode wilayah valid (B, D, AB, dll)\n';
                    errorMsg += '   • Nomor 1-4 digit\n';
                    errorMsg += '   • Seri huruf 1-3 karakter\n';
                    errorMsg += '   • Ada spasi di antara komponen';
                    
                    alert(errorMsg);
                    $('#txtNopolSearch').focus().select();
                    return false;
                }

                // Gunakan format yang sudah divalidasi
                nopol = validation.formatted;
                $('#txtNopolSearch').val(nopol);
                
                globalData.searchedNopol = nopol;
                showLoading($(this), 'Memeriksa Nopol...');

                $.ajax({
                    url: 'check_nopol_general.php',
                    method: 'POST',
                    data: { nopol: nopol },
                    dataType: 'json',
                    success: function(response) {
                        resetButton($('#btnCekNopolSearch'), '<i class="ace-icon fa fa-search"></i> Cek Nopol');

                        if (response.error) {
                            alert('Error: ' + response.error);
                            return;
                        }

                        if (response.exists) {
                            globalData.nopolExists = true;
                            globalData.vehicleData = response.data;
                        } else {
                            globalData.nopolExists = false;
                        }

                        displayResults();
                    },
                    error: function(xhr, status, error) {
                        resetButton($('#btnCekNopolSearch'), '<i class="ace-icon fa fa-search"></i> Cek Nopol');
                        console.log('AJAX Error Status:', status);
                        console.log('AJAX Error:', error);
                        console.log('Response Text:', xhr.responseText);

                        // Try to parse error response
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            alert('Error: ' + (errorResponse.error || error));
                        } catch(e) {
                            alert('Terjadi kesalahan saat memeriksa data Nopol. Silakan coba lagi.');
                        }
                    }
                });
            });

            // Check both WA and Nopol
            $('#btnCekSemua').click(function() {
                var whatsapp = $('#txtWhatsApp').val().trim();
                var nopol = $('#txtNopolSearch').val().trim().toUpperCase();

                if (!whatsapp && !nopol) {
                    alert('❌ Mohon masukkan minimal nomor WhatsApp atau nomor polisi!');
                    return false;
                }

                // Validasi format nopol jika diisi - RIGID!
                if (nopol) {
                    var validation = NopolValidator.validate(nopol);
                    if (!validation.valid) {
                        var errorMsg = '❌ FORMAT NOMOR POLISI TIDAK VALID!\n\n';
                        errorMsg += '📋 Error: ' + validation.message + '\n\n';
                        errorMsg += '✅ Contoh format yang BENAR:\n';
                        errorMsg += '   • B 1234 ABC (Jakarta)\n';
                        errorMsg += '   • D 5678 XYZ (Bandung)\n';
                        errorMsg += '   • AB 123 CD (Yogyakarta)\n\n';
                        errorMsg += '⚠️ Silakan perbaiki format nomor polisi terlebih dahulu!';
                        
                        alert(errorMsg);
                        $('#txtNopolSearch').focus().select();
                        return false;
                    }
                    // Gunakan format yang sudah divalidasi
                    nopol = validation.formatted;
                    $('#txtNopolSearch').val(nopol);
                }

                resetForm();
                globalData.searchedWA = whatsapp;
                globalData.searchedNopol = nopol;

                showLoading($(this), 'Memeriksa Data...');

                var ajaxCalls = [];

                // Check WA if provided
                if (whatsapp) {
                    ajaxCalls.push(
                        $.ajax({
                            url: 'check_phone.php',
                            method: 'POST',
                            data: { phone: whatsapp },
                            dataType: 'json'
                        }).then(function(response) {
                            if (response.error) {
                                throw new Error('WA Check: ' + response.error);
                            }
                            if (response.exists) {
                                globalData.waExists = true;
                                globalData.customerData = response.data;
                                globalData.customerVehicles = response.vehicles || [];
                            }
                        })
                    );
                }

                // Check Nopol if provided
                if (nopol) {
                    ajaxCalls.push(
                        $.ajax({
                            url: 'check_nopol_general.php',
                            method: 'POST',
                            data: { nopol: nopol },
                            dataType: 'json'
                        }).then(function(response) {
                            if (response.error) {
                                throw new Error('Nopol Check: ' + response.error);
                            }
                            if (response.exists) {
                                globalData.nopolExists = true;
                                globalData.vehicleData = response.data;
                            }
                        })
                    );
                }

                // Wait for all AJAX calls to complete
                $.when.apply($, ajaxCalls).done(function() {
                    resetButton($('#btnCekSemua'), '<i class="ace-icon fa fa-search-plus"></i> Cek Semua Data');
                    displayResults();
                }).fail(function(xhr, status, error) {
                    resetButton($('#btnCekSemua'), '<i class="ace-icon fa fa-search-plus"></i> Cek Semua Data');
                    console.log('AJAX Error:', xhr.responseText || error);
                    alert('Terjadi kesalahan saat memeriksa data: ' + (error || 'Unknown error'));
                });
            });

            // Check new Nopol for existing customer
            $('#btnCekNopolBaru').click(function() {
                var nopolBaru = $('#txtNopolBaru').val().trim().toUpperCase();

                if (!nopolBaru) {
                    alert('❌ Mohon masukkan nomor polisi baru!');
                    $('#txtNopolBaru').focus();
                    return false;
                }

                // Validasi format nopol - RIGID!
                var validation = NopolValidator.validate(nopolBaru);
                if (!validation.valid) {
                    // Tampilkan alert dengan detail error
                    var errorMsg = '❌ FORMAT NOMOR POLISI TIDAK VALID!\n\n';
                    errorMsg += '📋 Error: ' + validation.message + '\n\n';
                    errorMsg += '✅ Contoh format yang BENAR:\n';
                    errorMsg += '   • B 1234 ABC (Jakarta)\n';
                    errorMsg += '   • D 5678 XYZ (Bandung)\n';
                    errorMsg += '   • AB 123 CD (Yogyakarta)\n';
                    errorMsg += '   • 1234-56 (TNI/POLRI)\n';
                    errorMsg += '   • 12 CD 34 (Diplomatik)\n\n';
                    errorMsg += '⚠️ Pastikan:\n';
                    errorMsg += '   • Kode wilayah valid (B, D, AB, dll)\n';
                    errorMsg += '   • Nomor 1-4 digit\n';
                    errorMsg += '   • Seri huruf 1-3 karakter\n';
                    errorMsg += '   • Ada spasi di antara komponen';
                    
                    alert(errorMsg);
                    $('#txtNopolBaru').focus().select();
                    return false;
                }

                // Gunakan format yang sudah divalidasi
                nopolBaru = validation.formatted;
                $('#txtNopolBaru').val(nopolBaru);

                showLoading($(this), 'Memeriksa...');

                $.ajax({
                    url: 'check_nopol_general.php',
                    method: 'POST',
                    data: { nopol: nopolBaru },
                    dataType: 'json',
                    success: function(response) {
                        resetButton($('#btnCekNopolBaru'), '<i class="ace-icon fa fa-search"></i> Cek Ketersediaan');

                        if (response.exists) {
                            alert('Nomor polisi ' + nopolBaru + ' sudah terdaftar untuk pelanggan lain!');
                            $('#txtNopolBaru').focus();
                        } else {
                            if(confirm('Nomor polisi ' + nopolBaru + ' tersedia. Tambah kendaraan baru untuk pelanggan ' + globalData.customerData.nama + '?')) {
                                window.location.href = 'pelanggan_add_servis.php?phone=' + encodeURIComponent(globalData.customerData.phone) + '&nopol=' + encodeURIComponent(nopolBaru) + '&mode=add_vehicle';
                            }
                        }
                    },
                    error: function() {
                        resetButton($('#btnCekNopolBaru'), '<i class="ace-icon fa fa-search"></i> Cek Ketersediaan');
                        alert('Terjadi kesalahan saat memeriksa nomor polisi.');
                    }
                });
            });

            // Button actions
            $('#btnPelangganBaru').click(function() {
                var whatsapp = globalData.searchedWA || $('#txtWhatsApp').val().trim();
                var nopol = globalData.searchedNopol || $('#txtNopolSearch').val().trim();
                var url = 'pelanggan_add_servis.php';
                var params = [];

                if (whatsapp) params.push('phone=' + encodeURIComponent(whatsapp));
                if (nopol) params.push('nopol=' + encodeURIComponent(nopol));

                if (params.length > 0) {
                    url += '?' + params.join('&');
                }

                window.location.href = url;
            });

            $('#btnTambahKendaraan').click(function() {
                var phone = $(this).data('phone');
                var nopol = $(this).data('nopol');
                window.location.href = 'pelanggan_add_servis.php?phone=' + encodeURIComponent(phone) + '&nopol=' + encodeURIComponent(nopol) + '&mode=add_vehicle';
            });

            $('#btnLanjutServis').click(function() {
                var nopol = $(this).data('nopol');
                window.location.href = 'save-no-servis-reguler.php?snopol=' + encodeURIComponent(nopol);
            });

            // Service type buttons
            $('#btnServisReguler').click(function() {
                var nopol = $(this).data('nopol');
                window.location.href = 'save-no-servis-reguler.php?snopol=' + encodeURIComponent(nopol);
            });

            $('#btnServisJemput').click(function() {
                var nopol = $(this).data('nopol');
                window.location.href = 'save-no-servis-jemput.php?snopol=' + encodeURIComponent(nopol);
            });

            // Enter key handlers
            $('#txtWhatsApp').keypress(function(e) {
                if (e.which == 13) {
                    $('#btnCekWhatsApp').click();
                }
            });

            $('#txtNopolSearch').keypress(function(e) {
                if (e.which == 13) {
                    $('#btnCekNopolSearch').click();
                }
            });

            $('#txtNopolBaru').keypress(function(e) {
                if (e.which == 13) {
                    $('#btnCekNopolBaru').click();
                }
            });

            // Vehicle selection handler (using event delegation)
            $(document).on('click', '.btn-select-vehicle', function() {
                var nopol = $(this).data('nopol');
                var pemilik = $(this).data('pemilik');
                var tipe = $(this).data('tipe');
                
                if (confirm('Pilih kendaraan ' + nopol + ' (' + pemilik + ') untuk servis?')) {
                    // Show service type selection
                    var serviceHtml = '<div class="alert alert-success" style="margin-top: 15px;">';
                    serviceHtml += '<i class="ace-icon fa fa-check-circle"></i> ';
                    serviceHtml += '<strong>Kendaraan Dipilih: ' + nopol + '</strong><br>';
                    serviceHtml += 'Pemilik: ' + pemilik;
                    if (tipe) serviceHtml += ' | Tipe: ' + tipe;
                    serviceHtml += '<br><br>';
                    serviceHtml += '<div class="text-center">';
                    serviceHtml += '<button type="button" class="btn btn-primary btn-lg" onclick="window.location.href=\'save-no-servis-reguler.php?snopol=' + encodeURIComponent(nopol) + '\'">';
                    serviceHtml += '<i class="ace-icon fa fa-wrench"></i> Servis Reguler';
                    serviceHtml += '</button> ';
                    serviceHtml += '<button type="button" class="btn btn-warning btn-lg" onclick="window.location.href=\'save-no-servis-jemput.php?snopol=' + encodeURIComponent(nopol) + '\'">';
                    serviceHtml += '<i class="ace-icon fa fa-truck"></i> Servis Jemput';
                    serviceHtml += '</button>';
                    serviceHtml += '</div>';
                    serviceHtml += '</div>';
                    
                    // Replace the vehicle list with service selection
                    $('.vehicle-list-container').replaceWith(serviceHtml);
                    $('#btnTambahKendaraan').hide();
                }
            });

            // Event handlers for vehicle-based service selection (using event delegation)
            $(document).on('click', '#btnUpdatePelanggan', function() {
                var nopol = $(this).data('nopol');
                // Redirect to pelanggan add page with nopol parameter for updating customer data
                window.location.href = 'pelanggan_add_enhanced.php?nopol=' + encodeURIComponent(nopol) + '&mode=update_customer';
            });

            $(document).on('click', '#btnServisRegulerVehicle', function() {
                var nopol = $(this).data('nopol');
                if (confirm('Lanjut ke servis reguler untuk kendaraan ' + nopol + '?')) {
                    window.location.href = 'save-no-servis-reguler.php?snopol=' + encodeURIComponent(nopol);
                }
            });

            $(document).on('click', '#btnServisJemputVehicle', function() {
                var nopol = $(this).data('nopol');
                if (confirm('Lanjut ke servis jemput antar untuk kendaraan ' + nopol + '?')) {
                    window.location.href = 'save-no-servis-jemput.php?snopol=' + encodeURIComponent(nopol);
                }
            });

            // Auto focus on WhatsApp field
            $('#txtWhatsApp').focus();
        });
    </script>
</body>
</html>

<?php
}
?>