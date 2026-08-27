<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";

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

    // Include konfigurasi Accurate API
    include "../config/accurate_config.php";

    /**
     * Function untuk check status koneksi Accurate API
     */
    function checkAccurateConnection() {
        if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
            return [
                'status' => 'disconnected',
                'message' => 'Konfigurasi API tidak lengkap'
            ];
        }

        try {
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
            $url = ACCURATE_API_BASE_URL . '/api/api-token.do';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . ACCURATE_API_TOKEN,
                "X-Api-Timestamp: $timestamp",
                "X-Api-Signature: $signature",
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if (!empty($curl_error)) {
                return [
                    'status' => 'disconnected',
                    'message' => 'Connection error: ' . $curl_error
                ];
            }

            if ($http_code == 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['s']) && $result['s'] == true) {
                    return [
                        'status' => 'connected',
                        'message' => 'Terhubung dengan Accurate Online'
                    ];
                } else {
                    return [
                        'status' => 'disconnected',
                        'message' => 'API Token tidak valid atau permission tidak mencukupi'
                    ];
                }
            } else {
                $error_messages = [
                    401 => 'API Token tidak valid atau expired',
                    403 => 'Akses ditolak - periksa permission API token',
                    404 => 'Endpoint tidak ditemukan',
                    500 => 'Server error'
                ];
                
                $error_msg = $error_messages[$http_code] ?? "HTTP Error: $http_code";
                return [
                    'status' => 'disconnected',
                    'message' => $error_msg
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'disconnected',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    // Check Accurate connection dan simpan ke session
    if (defined('ACCURATE_API_TOKEN') && defined('ACCURATE_SIGNATURE_SECRET') && defined('ACCURATE_API_BASE_URL')) {
        $accurate_connection = checkAccurateConnection();
        $_SESSION['accurate_status'] = $accurate_connection['status'];
        $_SESSION['accurate_message'] = $accurate_connection['message'];
    } else {
        $_SESSION['accurate_status'] = 'disconnected';
        $_SESSION['accurate_message'] = 'File konfigurasi Accurate tidak ditemukan atau tidak lengkap';
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
        <script type="text/javascript" src="chartjs/Chart.js"></script>

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
                
                <!-- Status Accurate API Indicator -->
                <div class="navbar-header pull-right">
                    <?php if (isset($_SESSION['accurate_status'])): ?>
                        <span class="navbar-brand">
                            <small style="color: <?php echo $_SESSION['accurate_status'] == 'connected' ? 'green' : 'orange'; ?>">
                                <i class="fa fa-circle"></i> Accurate: <?php echo $_SESSION['accurate_status']; ?>
                            </small>
                        </span>
                    <?php endif; ?>
                </div>
            </div><!-- /.navbar-container -->
        </div>

        <div class="main-container ace-save-state" id="main-container">
            <script type="text/javascript">
                try { ace.settings.loadState('main-container') } catch(e) {}
            </script>

            <div id="sidebar" class="sidebar responsive ace-save-state">
                <script type="text/javascript">
                    try { ace.settings.loadState('sidebar') } catch(e) {}
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
                                <a href="#">Data Master</a>
                            </li>                            
                            <li>
                                <a href="#">Daftar Item</a>
                            </li>                                                        
                            <li>
                                <a href="barang_kategori.php">Master Kategori Item</a>
                            </li>                                                                                    
                            <li class="active">Input Kategori Item Baru</li>
                        </ul><!-- /.breadcrumb -->
                    </div>

                    <div class="page-content">
                        <!-- Alert untuk status Accurate -->
                        <?php if (isset($_SESSION['accurate_status'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['accurate_status'] == 'connected' ? 'success' : 'warning'; ?> alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
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
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-plus"></i>
                                            INPUT KATEGORI ITEM BARU
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <form class="form-horizontal" action="save_barang_kategori_new.php" method="post">
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="txtkategori"> 
                                                        <strong>KATEGORI ITEM</strong> 
                                                    </label>
                                                    <div class="col-sm-9">
                                                        <input type="text" id="txtkategori" name="txtkategori" class="col-xs-10 col-sm-8" 
                                                               placeholder="Masukkan nama kategori item" required autocomplete="off" />
                                                        <div class="help-block">
                                                            <small class="text-muted">Hanya boleh 1 kata, tidak boleh ada spasi</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="txtketerangan"> 
                                                        <strong>KETERANGAN</strong> 
                                                    </label>
                                                    <div class="col-sm-9">
                                                        <textarea id="txtketerangan" name="txtketerangan" class="col-xs-10 col-sm-8" 
                                                                  rows="3" placeholder="Keterangan dari kategori item" required></textarea>
                                                        <div class="help-block">
                                                            <small class="text-muted">Keterangan diisi bebas sesuai arti</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="margin_sesuai_jenis"> 
                                                        <strong>MARGIN SESUAI JENIS</strong> 
                                                    </label>
                                                    <div class="col-sm-9">
                                                        <select id="margin_sesuai_jenis" name="margin_sesuai_jenis" class="col-xs-10 col-sm-4" required>
                                                            <option value="">-- Pilih --</option>
                                                            <option value="TIDAK">TIDAK</option>
                                                            <option value="YA">YA</option>
                                                        </select>
                                                        <div class="help-block">
                                                            <small class="text-muted">Hanya bisa diisi YA/TIDAK</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group" id="margin_kategori_group" style="display: none;">
                                                    <label class="col-sm-3 control-label no-padding-right" for="txtmargin"> 
                                                        <strong>MARGIN KATEGORI</strong> 
                                                    </label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group col-xs-10 col-sm-4">
                                                            <input type="number" id="txtmargin" name="txtmargin" class="form-control" 
                                                                   min="0" max="100" step="0.01" placeholder="0" />
                                                            <span class="input-group-addon">%</span>
                                                        </div>
                                                        <div class="help-block">
                                                            <small class="text-muted">Kolom margin kategori aktif jika jawaban sebelumnya "TIDAK", hanya bisa diisi angka</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Info sinkronisasi -->
                                                <div class="form-group">
                                                    <div class="col-sm-offset-3 col-sm-9">
                                                        <div class="alert alert-info">
                                                            <i class="fa fa-info-circle"></i> 
                                                            <strong>Informasi:</strong> 
                                                            Data kategori akan disimpan ke database lokal. 
                                                            <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                                Sistem akan otomatis mencoba sinkronisasi ke Accurate Online dengan kode sesuai nama kategori.
                                                            <?php else: ?>
                                                                Sinkronisasi ke Accurate Online tidak tersedia karena koneksi API bermasalah.
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="clearfix form-actions">
                                                    <div class="col-md-offset-3 col-md-9">
                                                        <button class="btn btn-info btn-lg" type="submit">
                                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                                            SIMPAN
                                                            <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                                & Sync to Accurate
                                                            <?php endif; ?>
                                                        </button>
                                                             
                                                        <button class="btn btn-default btn-lg" type="button" onclick="window.location.href='barang_kategori.php'">
                                                            <i class="ace-icon fa fa-list bigger-110"></i>
                                                            LIHAT DAFTAR KATEGORI ITEM
                                                        </button>
                                                             
                                                        <button class="btn btn-warning btn-lg" type="button" onclick="window.location.href='index.php'">
                                                            <i class="ace-icon fa fa-home bigger-110"></i>
                                                            KE MENU AWAL
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel informasi Accurate -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-small">
                                        <h5 class="widget-title">
                                            <i class="ace-icon fa fa-cloud"></i>
                                            Status Integrasi Accurate Online
                                        </h5>
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
                                                        <p class="help-block">
                                                            <?php echo isset($_SESSION['accurate_message']) ? $_SESSION['accurate_message'] : 'Status tidak tersedia'; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
                <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
            </a>
        </div><!-- /.main-container -->

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script type="text/javascript">
            if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
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

        <!-- inline scripts related to this page -->
        <script type="text/javascript">
            jQuery(function($) {
                // Initialize chosen select
                if(!ace.vars['touch']) {
                    $('.chosen-select').chosen({allow_single_deselect:true}); 
                    $(window)
                    .off('resize.chosen')
                    .on('resize.chosen', function() {
                        $('.chosen-select').each(function() {
                             var $this = $(this);
                             $this.next().css({'width': $this.parent().width()});
                        })
                    }).trigger('resize.chosen');
                    $(document).on('settings.ace.chosen', function(e, event_name, event_val) {
                        if(event_name != 'sidebar_collapsed') return;
                        $('.chosen-select').each(function() {
                             var $this = $(this);
                             $this.next().css({'width': $this.parent().width()});
                        })
                    });
                }

                // Initialize tooltips and popovers
                $('[data-rel=tooltip]').tooltip({container:'body'});
                $('[data-rel=popover]').popover({container:'body'});

                // Initialize autosize for textareas
                autosize($('textarea[class*=autosize]'));
                
                // Auto-hide alert after 15 seconds
                setTimeout(function() {
                    $('.alert-dismissible').fadeOut('slow');
                }, 15000);

                // Focus pada field kategori
                $('#txtkategori').focus();

                // Handle margin sesuai jenis change
                $('#margin_sesuai_jenis').on('change', function() {
                    var value = $(this).val();
                    if (value === 'TIDAK') {
                        $('#margin_kategori_group').show();
                        $('#txtmargin').prop('required', true);
                    } else {
                        $('#margin_kategori_group').hide();
                        $('#txtmargin').prop('required', false);
                        $('#txtmargin').val('');
                    }
                });

                // Form validation
                $('form').on('submit', function(e) {
                    var kategori = $('#txtkategori').val().trim();
                    var keterangan = $('#txtketerangan').val().trim();
                    var margin_sesuai_jenis = $('#margin_sesuai_jenis').val();
                    var margin_kategori = $('#txtmargin').val();
                    
                    if (kategori === '' || keterangan === '' || margin_sesuai_jenis === '') {
                        e.preventDefault();
                        alert('Semua field wajib harus diisi!');
                        return false;
                    }
                    
                    // Validasi kategori tidak boleh ada spasi
                    if (kategori.indexOf(' ') !== -1) {
                        e.preventDefault();
                        alert('Kategori Item tidak boleh mengandung spasi!');
                        $('#txtkategori').focus();
                        return false;
                    }
                    
                    // Validasi margin jika diperlukan
                    if (margin_sesuai_jenis === 'TIDAK' && (margin_kategori === '' || margin_kategori < 0)) {
                        e.preventDefault();
                        alert('Margin Kategori harus diisi dengan nilai yang valid!');
                        $('#txtmargin').focus();
                        return false;
                    }
                    
                    var confirmMessage = 'Apakah Anda yakin ingin menyimpan kategori ini?\n\n' +
                                       'Kategori: ' + kategori + '\n' +
                                       'Keterangan: ' + keterangan + '\n' +
                                       'Margin Sesuai Jenis: ' + margin_sesuai_jenis + '\n';
                    
                    if (margin_sesuai_jenis === 'TIDAK') {
                        confirmMessage += 'Margin Kategori: ' + margin_kategori + '%\n';
                    }
                    
                    confirmMessage += '\nData akan disimpan ke database lokal';
                    
                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                        confirmMessage += ' dan akan dicoba sinkronisasi ke Accurate Online.';
                    <?php else: ?>
                        confirmMessage += '.\nSinkronisasi ke Accurate tidak tersedia.';
                    <?php endif; ?>
                    
                    return confirm(confirmMessage);
                });

                // Auto-uppercase untuk kategori
                $('#txtkategori').on('input', function() {
                    this.value = this.value.toUpperCase().replace(/\s/g, '');
                });

                // Auto-uppercase untuk keterangan
                $('#txtketerangan').on('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        </script>
    </body>
</html>
