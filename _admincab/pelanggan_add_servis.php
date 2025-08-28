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
                        <li class="active">Tambah Data dan Servis</li>
                    </ul>
                </div>

                <div class="page-content">
                    <br>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></div>
                    <?php endif; ?>
                    <form class="form-horizontal" action="save_pelanggan_servis.php" method="post">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">INPUT PELANGGAN DAN KENDARAAN BARU</h4>
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
                                                        <label class="col-sm-3 control-label no-padding-right">Nama Pelanggan</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-12" required autocomplete="off" />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Gender</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbogender" id="cbogender" required>
                                                                <option value="">- Pilih -</option>
                                                                <option value="Laki-laki">Laki-laki</option>
                                                                <option value="Perempuan">Perempuan</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Tanggal Lahir</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group">
                                                                <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" value="<?php echo $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" required />
                                                                <span class="input-group-addon">
                                                                    <i class="fa fa-calendar bigger-110"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Validitas Tgl Lahir</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbovalid" id="cbovalid" required>
                                                                <option value="">- Pilih -</option>
                                                                <option value="Valid">Valid</option>
                                                                <option value="Non Valid">Non Valid</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Provinsi</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cboprovinsi" id="cboprovinsi" required>
                                                                <option value="">- Pilih Provinsi -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Kota/Kabupaten</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbokota" id="cbokota" required>
                                                                <option value="">- Pilih Kota/Kabupaten -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Kecamatan</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbokecamatan" id="cbokecamatan" required>
                                                                <option value="">- Pilih Kecamatan -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Alamat Detail</label>
                                                        <div class="col-sm-9">
                                                            <textarea class="col-xs-10 col-sm-12" id="txtalamat" name="txtalamat" rows="3" placeholder="Jalan, RT/RW, No. Rumah, dll" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Patokan</label>
                                                        <div class="col-sm-9">
                                                            <textarea class="col-xs-10 col-sm-12" id="txtpatokan" name="txtpatokan" rows="3"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">No WA/HP</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtnowa" name="txtnowa" class="col-xs-10 col-sm-12" autocomplete="off" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">No Polisi</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtnopol" name="txtnopol" class="col-xs-10 col-sm-12" required autocomplete="off" />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Bl/Th Pajak</label>
                                                        <div class="col-sm-9">
                                                            <div class="row">
                                                                <div class="col-xs-6">
                                                                    <select class="col-xs-10 col-sm-12" name="cbobulanpajak" id="cbobulanpajak" required>
                                                                        <option value="">- Pilih Bulan -</option>
                                                                        <?php for ($i = 1; $i <= 12; $i++) echo "<option value='" . sprintf("%02d", $i) . "'>" . sprintf("%02d", $i) . "</option>"; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-xs-6">
                                                                    <input type="text" id="txtthnpajak" name="txtthnpajak" class="col-xs-10 col-sm-12" placeholder="YYYY" required autocomplete="off" pattern="\d{4}" />
                                                                    <small class="help-block">Format: YYYY</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Merek</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbomerek" id="cbomerek" required>
                                                                <option value="">- Pilih Merek -</option>
                                                                <?php while ($row = mysqli_fetch_array($merek_query)) echo "<option value='{$row['id']}'>{$row['merek']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Tipe</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbotipe" id="cbotipe" required>
                                                                <option value="">- Pilih Tipe -</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Jenis</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbojenis" id="cbojenis" required>
                                                                <option value="">- Pilih Jenis -</option>
                                                                <?php mysqli_data_seek($jenis_query, 0); while ($row = mysqli_fetch_array($jenis_query)) echo "<option value='{$row['kd']}'>{$row['jenis']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Kategori</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" id="txtkategori" name="txtkategori" class="col-xs-10 col-sm-12" readonly />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right">Warna</label>
                                                        <div class="col-sm-9">
                                                            <select class="col-xs-10 col-sm-12" name="cbowarna" id="cbowarna" required>
                                                                <option value="">- Pilih Warna -</option>
                                                                <?php mysqli_data_seek($warna_query, 0); while ($row = mysqli_fetch_array($warna_query)) echo "<option value='{$row['id']}'>{$row['warna']}</option>"; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <button type="submit" class="btn btn-primary btn-block">Lanjut ke Input Garapan</button>
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

        <!-- inline scripts -->
        <script type="text/javascript">
            jQuery(function($) {
                $('form').submit(function(e) {
                    var gender = $('#cbogender').val();
                    var thn_pajak = $('#txtthnpajak').val();
                    if (!gender) {
                        e.preventDefault();
                        alert('Harap pilih gender!');
                        $('#cbogender').focus();
                        return false;
                    }
                    if (thn_pajak && !/^\d{4}$/.test(thn_pajak)) {
                        e.preventDefault();
                        alert('Tahun pajak harus 4 digit (YYYY)!');
                        $('#txtthnpajak').focus();
                        return false;
                    }
                });

                $('.date-picker').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    endDate: '0d'
                }).next().on(ace.click_event, function() {
                    $(this).prev().focus();
                });

                $('#cbomerek').change(function() {
                    var merekId = $(this).val();
                    if (merekId) {
                        $.ajax({
                            url: 'get_tipe_motor.php',
                            type: 'POST',
                            data: { merek_id: merekId },
                            success: function(response) {
                                $('#cbotipe').html(response);
                                $('#cbotipe').trigger('change');
                            },
                            error: function() {
                                alert('Gagal memuat tipe motor.');
                            }
                        });
                    } else {
                        $('#cbotipe').html('<option value="">- Pilih Tipe -</option>');
                        $('#txtkategori').val('');
                    }
                });

                $('#cbotipe').change(function() {
                    var tipeId = $(this).val();
                    if (tipeId) {
                        $.ajax({
                            url: 'get_kategori_motor.php',
                            type: 'POST',
                            data: { tipe_id: tipeId },
                            success: function(response) {
                                $('#txtkategori').val(response);
                            },
                            error: function() {
                                alert('Gagal memuat kategori motor.');
                            }
                        });
                    } else {
                        $('#txtkategori').val('');
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
                        alert('Gagal memuat data provinsi.');
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
                                alert('Gagal memuat data kota.');
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
                                alert('Gagal memuat data kecamatan.');
                            }
                        });
                    } else {
                        $('#cbokecamatan').html('<option value="">- Pilih Kecamatan -</option>');
                    }
                });

                // Auto-fill data from URL parameters
                var urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('phone')) {
                    $('#txtnowa').val(urlParams.get('phone'));
                }
                if (urlParams.get('nopol')) {
                    $('#txtnopol').val(urlParams.get('nopol'));
                }
                if (urlParams.get('mode') === 'add_vehicle') {
                    // If adding vehicle for existing customer, auto-fill customer data
                    var phone = urlParams.get('phone');
                    if (phone) {
                        $.ajax({
                            url: 'get_customer_data.php',
                            type: 'POST',
                            data: { phone: phone },
                            dataType: 'json',
                            success: function(customer) {
                                if (customer.exists) {
                                    $('#txtnama').val(customer.data.nama);
                                    $('#cbogender').val(customer.data.gender);
                                    $('#id-date-picker-1').val(customer.data.tgl_lahir);
                                    $('#cbovalid').val(customer.data.valid_tgl_lahir);
                                    $('#txtalamatdetail').val(customer.data.alamat);
                                    $('#txtpatokan').val(customer.data.patokan);
                                    $('#txtnowa').val(customer.data.phone);
                                    
                                    // Set province and city if available
                                    if (customer.data.provinsi) {
                                        $('#cboprovinsi').val(customer.data.provinsi).trigger('change');
                                    }
                                    if (customer.data.kota) {
                                        setTimeout(function() {
                                            $('#cbokota').val(customer.data.kota);
                                        }, 500);
                                    }
                                    
                                    // Disable customer fields since this is existing customer
                                    $('#txtnama, #cbogender, #id-date-picker-1, #cbovalid, #txtalamatdetail, #txtpatokan, #txtnowa, #cboprovinsi, #cbokota, #cbokecamatan').prop('readonly', true).prop('disabled', true);
                                    
                                    // Focus on vehicle fields
                                    $('#txtnopol').focus();
                                }
                            },
                            error: function() {
                                console.log('Gagal memuat data pelanggan.');
                            }
                        });
                    }
                }
            });
        </script>
</body>
</html>
<?php
mysqli_close($koneksi);
?>