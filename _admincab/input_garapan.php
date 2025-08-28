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

// Ambil pelanggan_id dari query string (bisa dari ID atau nopelanggan)
$pelanggan_id = $_GET['pelanggan_id'] ?? '';
$nopelanggan = $_GET['nopelanggan'] ?? '';

// Debug logging
error_log("Debug input_garapan.php: pelanggan_id = " . $pelanggan_id . ", nopelanggan = " . $nopelanggan);

// Karena tblpelanggan tidak memiliki kolom id, kita akan selalu menggunakan nopelanggan
if (empty($nopelanggan) && !empty($pelanggan_id)) {
    // Jika ada pelanggan_id tapi tidak ada nopelanggan, 
    // asumsikan pelanggan_id sebenarnya adalah nopelanggan
    $nopelanggan = $pelanggan_id;
    error_log("Debug: Using pelanggan_id as nopelanggan = " . $nopelanggan);
}

if (empty($nopelanggan)) {
    error_log("Debug: nopelanggan is empty");
    header("location:pelanggan.php?error=" . urlencode("Pelanggan tidak dipilih"));
    exit;
}

// Ambil data pelanggan untuk ditampilkan
// Karena tidak ada kolom id, kita gunakan nopelanggan sebagai key
$stmt = mysqli_prepare($koneksi, "SELECT 
    p.namapelanggan, p.nopelanggan, p.alamat, p.patokan,
    k.pemilik, k.jenis, k.tipe, k.warna, k.no_rangka, k.no_mesin
    FROM tblpelanggan p 
    LEFT JOIN tblkendaraan k ON p.nopelanggan = k.nopolisi 
    WHERE p.nopelanggan = ?");
mysqli_stmt_bind_param($stmt, "s", $nopelanggan);
error_log("Debug: Searching by nopelanggan = " . $nopelanggan);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pelanggan = mysqli_fetch_assoc($result);

if (!$pelanggan) {
    mysqli_stmt_close($stmt);
    header("location:pelanggan.php?error=" . urlencode("Data pelanggan tidak ditemukan"));
    exit;
}

$nama_pelanggan = $pelanggan['namapelanggan'];
$no_polisi = $pelanggan['nopelanggan'];
$alamat_pelanggan = $pelanggan['alamat'];
$patokan = $pelanggan['patokan'];
$pemilik = $pelanggan['pemilik'] ?: $nama_pelanggan;
$jenis = $pelanggan['jenis'] ?: '';
$tipe = $pelanggan['tipe'] ?: '';
$warna = $pelanggan['warna'] ?: '';
$no_rangka = $pelanggan['no_rangka'] ?: '';
$no_mesin = $pelanggan['no_mesin'] ?: '';

mysqli_stmt_close($stmt);

// Ambil pesan error jika ada
$error = $_GET['error'] ?? '';

$tgl_skr = date('d');
$bulan_skr = date('m');
$thn_skr = date('Y');
$tgl_pilih = date('d/m/Y');
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

    <!-- HTML5shiv and Respond.js for IE8 -->
    <!--[if lte IE 8]>
    <script src="assets/js/html5shiv.min.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.css' rel='stylesheet' />
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
        <script type="text/javascript">
            try { ace.settings.loadState('main-container') } catch (e) {}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">
                try { ace.settings.loadState('sidebar') } catch (e) {}
            </script>
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
                        <li><a href="pelanggan.php">Master Pelanggan</a></li>
                        <li class="active">Input Garapan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <br>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Info Pelanggan dan Kendaraan -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">INFO PELANGGAN & KENDARAAN</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <table class="table table-striped">
                                                    <tr>
                                                        <td width="30%"><strong>Nama Pelanggan</strong></td>
                                                        <td>: <?php echo htmlspecialchars($nama_pelanggan); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>No. Polisi</strong></td>
                                                        <td>: <?php echo htmlspecialchars($no_polisi); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Alamat</strong></td>
                                                        <td>: <?php echo htmlspecialchars($alamat_pelanggan); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Patokan</strong></td>
                                                        <td>: <?php echo htmlspecialchars($patokan); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-xs-6">
                                                <table class="table table-striped">
                                                    <tr>
                                                        <td width="30%"><strong>Pemilik</strong></td>
                                                        <td>: <?php echo htmlspecialchars($pemilik); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Jenis/Tipe</strong></td>
                                                        <td>: <?php echo htmlspecialchars($jenis . ' / ' . $tipe); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Warna</strong></td>
                                                        <td>: <?php echo htmlspecialchars($warna); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>No. Rangka/Mesin</strong></td>
                                                        <td>: <?php echo htmlspecialchars($no_rangka . ' / ' . $no_mesin); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form class="form-horizontal" action="save_garapan.php?nopelanggan=<?php echo urlencode($nopelanggan); ?>" method="post">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">INPUT GARAPAN</h4>
                                        <div class="widget-toolbar">
                                            <a href="#" data-action="collapse">
                                                <i class="ace-icon fa fa-chevron-up"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtdaftar">Daftar Pengerjaan</label>
                                                        <div class="col-sm-9">
                                                            <textarea class="col-xs-10 col-sm-12" id="txtdaftar" name="txtdaftar" rows="4" required placeholder="Contoh: Ganti oli mesin, Service rutin, Cek rem..."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Jika Jemput Antar</label>
                                                        <div class="col-sm-9">
                                                            <div class="row">
                                                                <div class="col-xs-6">
                                                                    <label class="control-label">Jam Jemput</label>
                                                                    <input type="time" id="txtjamjemput" name="txtjamjemput" class="col-xs-10 col-sm-12" />
                                                                    <small class="help-block">Kosongkan jika tidak jemput</small>
                                                                </div>
                                                                <div class="col-xs-6">
                                                                    <label class="control-label">Keterangan</label>
                                                                    <textarea class="col-xs-10 col-sm-12" id="txtketerangan" name="txtketerangan" rows="2" placeholder="Alamat lengkap untuk jemput..."></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="ace-icon fa fa-save"></i>
                                            Simpan Garapan & Lanjut ke Input Servis
                                        </button>
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

        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
    </div>

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if ('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
    </script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- page specific plugin scripts -->
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

    <!-- ace scripts -->
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script type="text/javascript">
        jQuery(function($) {
            // Auto-expand textarea
            $('textarea').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Validasi form sebelum submit
            $('form').on('submit', function(e) {
                var daftarPengerjaan = $('#txtdaftar').val().trim();
                var jamJemput = $('#txtjamjemput').val();
                var keterangan = $('#txtketerangan').val().trim();
                
                if (!daftarPengerjaan) {
                    e.preventDefault();
                    alert('Daftar pengerjaan harus diisi!');
                    $('#txtdaftar').focus();
                    return false;
                }
                
                // Jika ada jam jemput, keterangan harus diisi
                if (jamJemput && !keterangan) {
                    e.preventDefault();
                    alert('Jika ada jam jemput, keterangan harus diisi!');
                    $('#txtketerangan').focus();
                    return false;
                }
                
                // Konfirmasi sebelum submit
                var jenisServis = jamJemput ? 'Jemput Antar' : 'Reguler';
                var konfirmasi = 'Anda akan membuat servis ' + jenisServis + '.\n\n';
                konfirmasi += 'Daftar Pengerjaan:\n' + daftarPengerjaan + '\n\n';
                
                if (jamJemput) {
                    konfirmasi += 'Jam Jemput: ' + jamJemput + '\n';
                    konfirmasi += 'Keterangan: ' + keterangan + '\n\n';
                }
                
                konfirmasi += 'Lanjutkan?';
                
                if (!confirm(konfirmasi)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Show/hide keterangan berdasarkan jam jemput
            $('#txtjamjemput').on('change', function() {
                var jamJemput = $(this).val();
                if (jamJemput) {
                    $('#txtketerangan').attr('required', true);
                    $('#txtketerangan').closest('.col-xs-6').find('label').html('Keterangan <span style="color:red">*</span>');
                } else {
                    $('#txtketerangan').removeAttr('required');
                    $('#txtketerangan').closest('.col-xs-6').find('label').html('Keterangan');
                }
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($koneksi);
?>