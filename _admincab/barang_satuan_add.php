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

    // Function to check Accurate connection using config utilities
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
    <!-- [Head content remains the same as previous version] -->
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
</head>
<body class="no-skin">
    <!-- [Navbar and Sidebar content remains the same as previous version] -->
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
            <?php include "menu_master01c.php"; ?>
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
                        <li><a href="#">Daftar Item</a></li>
                        <li><a href="barang_satuan.php">Satuan Barang</a></li>
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

                    <br>
                    <div class="row">
                        <div class="col-xs-12">
                            <form class="form-horizontal" action="save_barang_satuan.php" method="post">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label no-padding-right" for="txtkd"> Kode </label>
                                    <div class="col-sm-9">
                                        <input type="text" id="txtkd" name="txtkd" class="col-xs-10 col-sm-6" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label no-padding-right" for="txtnama"> Satuan Barang </label>
                                    <div class="col-sm-9">
                                        <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-6" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-2 col-sm-9">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i>
                                            <strong>Informasi:</strong>
                                            Data satuan akan disimpan ke database lokal.
                                            <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                Sistem akan otomatis mencoba sinkronisasi ke Accurate Online.
                                            <?php else: ?>
                                                Sinkronisasi ke Accurate Online tidak tersedia karena koneksi API bermasalah.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="clearfix form-actions">
                                    <div class="col-md-offset-2 col-md-9">
                                        <button class="btn btn-info" type="submit">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            Save
                                            <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                & Sync to Accurate
                                            <?php endif; ?>
                                        </button>
                                        <button class="btn" type="reset">
                                            <i class="ace-icon fa fa-undo bigger-110"></i>
                                            Reset
                                        </button>
                                        <a href="barang_satuan.php" class="btn btn-default">
                                            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
                                            Kembali
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

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
                                    <li>Periksa permission untuk unit_save</li>
                                </ul>
                            </li>
                            <li><strong>Periksa Koneksi Internet:</strong>
                                <ul>
                                    <li>Pastikan server dapat mengakses internet</li>
                                    <li>Cek firewall tidak memblokir koneksi</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- [Scripts remain the same as previous version] -->
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
                if(!ace.vars['touch']) {
                    $('.chosen-select').chosen({allow_single_deselect:true});
                    $(window).off('resize.chosen').on('resize.chosen', function() {
                        $('.chosen-select').each(function() {
                            var $this = $(this);
                            $this.next().css({'width': $this.parent().width()});
                        });
                    }).trigger('resize.chosen');
                    $(document).on('settings.ace.chosen', function(e, event_name, event_val) {
                        if(event_name != 'sidebar_collapsed') return;
                        $('.chosen-select').each(function() {
                            var $this = $(this);
                            $this.next().css({'width': $this.parent().width()});
                        });
                    });
                }
                $('[data-rel=tooltip]').tooltip({container:'body'});
                $('[data-rel=popover]').popover({container:'body'});
                autosize($('textarea[class*=autosize]'));
                $.mask.definitions['~']='[+-]';
                $('.input-mask-date').mask('99/99/9999');
                $('.input-mask-phone').mask('(999) 999-9999');
                setTimeout(function() {$('.alert-dismissible').fadeOut('slow');}, 15000);
                $('#txtkd').focus();
                $('form').on('submit', function(e) {
                    var kode = $('#txtkd').val().trim();
                    var nama = $('#txtnama').val().trim();
                    if (kode === '' || nama === '') {
                        e.preventDefault();
                        alert('Kode dan Nama Satuan harus diisi!');
                        return false;
                    }
                    var confirmMessage = 'Apakah Anda yakin ingin menyimpan satuan ini?\n\n' +
                                        'Kode: ' + kode + '\n' +
                                        'Nama: ' + nama + '\n\n' +
                                        'Data akan disimpan ke database lokal';
                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                        confirmMessage += ' dan akan dicoba sinkronisasi ke Accurate Online.';
                    <?php else: ?>
                        confirmMessage += '.\nSinkronisasi ke Accurate tidak tersedia.';
                    <?php endif; ?>
                    return confirm(confirmMessage);
                });
                $('#txtkd').on('input', function() {this.value = this.value.toUpperCase();});
                $('#txtnama').on('input', function() {
                    var words = this.value.split(' ');
                    for (var i = 0; i < words.length; i++) {
                        if (words[i].length > 0) {
                            words[i] = words[i][0].toUpperCase() + words[i].substr(1).toLowerCase();
                        }
                    }
                    this.value = words.join(' ');
                });
            });
            function showTroubleshooting() {$('#troubleshootingModal').modal('show');}
            setInterval(function() {console.log('Auto-checking Accurate status...');}, 300000);
        </script>
    </body>
</html>