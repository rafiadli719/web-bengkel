<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
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

    // Function untuk check status koneksi Accurate API
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

function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

function generateApiSignature($timestamp, $secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $secret, true));
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

    <style>
        .field-group {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fafafa;
        }
        .field-group h4 {
            margin-top: 0;
            color: #337ab7;
            border-bottom: 2px solid #337ab7;
            padding-bottom: 5px;
        }
        .form-group label {
            font-weight: 600;
        }
        .help-block {
            color: #666;
            font-size: 11px;
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
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_master01d.php"; ?>
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
                        <li><a href="barang_pabrik.php">Pabrik Barang</a></li>
                        <li class="active">Tambah Data</li>
                    </ul>
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
                                <i class="fa fa-check-circle"></i> ✅ Terhubung - Data akan otomatis sinkronisasi ke Accurate Online sebagai merek barang
                            <?php else: ?>
                                <i class="fa fa-exclamation-triangle"></i> ⚠️ Tidak terhubung - Data hanya disimpan di database lokal
                                <br><small><?php echo $_SESSION['accurate_message']; ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form class="form-horizontal" action="save_barang_pabrik.php" method="post" id="pabrikForm">
                        
                        <!-- Informasi Pabrik Barang -->
                        <div class="field-group">
                            <h4><i class="fa fa-industry"></i> Informasi Pabrik Barang</h4>
                            <div class="row">
                                <div class="col-xs-8">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtnama"> 
                                            <span class="text-danger">*</span> Nama Pabrik Barang
                                        </label>
                                        <div class="col-sm-9">
                                            <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-12" 
                                                   required autocomplete="off" maxlength="100"
                                                   placeholder="Contoh: YAMAHA, HONDA, SUZUKI, KAWASAKI" />
                                            <div class="help-block">
                                                <i class="fa fa-info-circle"></i> 
                                                Masukkan nama pabrik/merek spare part. Nama ini akan digunakan sebagai kategori di Accurate Online.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="txtdeskripsi"> Deskripsi </label>
                                        <div class="col-sm-9">
                                            <textarea id="txtdeskripsi" name="txtdeskripsi" class="col-xs-10 col-sm-12" 
                                                      rows="3" maxlength="255"
                                                      placeholder="Deskripsi tambahan mengenai pabrik (opsional)"></textarea>
                                            <div class="help-block">
                                                <i class="fa fa-info-circle"></i> 
                                                Deskripsi opsional untuk memberikan informasi tambahan tentang pabrik.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xs-4">
                                    <div class="widget-box widget-color-blue2">
                                        <div class="widget-header">
                                            <h5 class="widget-title"><i class="ace-icon fa fa-info"></i> Informasi</h5>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <p><strong>Sinkronisasi Accurate:</strong></p>
                                                <ul class="list-unstyled">
                                                    <li>✅ Data disimpan di database lokal</li>
                                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                        <li>✅ Otomatis dibuat sebagai merek barang di Accurate</li>
                                                        <li>✅ Dapat digunakan untuk pengelompokan item berdasarkan pabrik</li>
                                                    <?php else: ?>
                                                        <li>⚠️ Sinkronisasi Accurate tidak tersedia</li>
                                                    <?php endif; ?>
                                                </ul>
                                                
                                                <p><strong>Contoh Nama Pabrik:</strong></p>
                                                <ul class="list-unstyled small">
                                                    <li>• YAMAHA</li>
                                                    <li>• HONDA</li>
                                                    <li>• SUZUKI</li>
                                                    <li>• KAWASAKI</li>
                                                    <li>• FEDERAL PARTS</li>
                                                    <li>• ASPIRA</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info sinkronisasi -->
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    <strong>Informasi Sinkronisasi:</strong> 
                                    Data pabrik barang akan disimpan ke database lokal. 
                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                        Sistem akan otomatis mencoba membuat merek barang dengan nama pabrik ini di Accurate Online. 
                                        Merek ini nantinya dapat digunakan untuk mengelompokkan item/barang berdasarkan pabrik.
                                    <?php else: ?>
                                        Sinkronisasi ke Accurate Online tidak tersedia karena koneksi bermasalah.
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
                                            Simpan Pabrik Barang
                                            <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                                & Sync ke Accurate
                                            <?php endif; ?>
                                        </button>
                                        <button class="btn btn-warning" type="reset" onclick="resetForm()">
                                            <i class="ace-icon fa fa-undo bigger-110"></i>
                                            Reset Form
                                        </button>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <a href="barang_pabrik.php" class="btn btn-default btn-lg">
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
        </div>

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content"><?php include "../lib/footer.php"; ?></div>
            </div>
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
                                    <li>Periksa permission untuk item category save</li>
                                </ul>
                            </li>
                            <li><strong>Periksa Koneksi Internet:</strong>
                                <ul>
                                    <li>Pastikan server dapat mengakses internet</li>
                                    <li>Cek firewall tidak memblokir koneksi</li>
                                </ul>
                            </li>
                        </ol>
                        
                        <h5>Catatan untuk Pabrik Barang:</h5>
                        <p>Pabrik barang akan disinkronkan sebagai <strong>merek barang</strong> di Accurate Online. Ini memungkinkan pengelompokan item berdasarkan pabrik/merek seperti YAMAHA, HONDA, SUZUKI, dll.</p>
                        
                        <h5>Jika API Merek Tidak Tersedia:</h5>
                        <p>Sistem akan mencoba beberapa kemungkinan endpoint API. Jika tidak berhasil, Anda dapat membuat merek secara manual di:</p>
                        <p><strong>Accurate Online > Master Data > Item > Merek</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"><i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i></a>
    </div>

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");</script>
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
                $(window).off('resize.chosen').on('resize.chosen', function() {
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
            setTimeout(function() { $('.alert-dismissible').fadeOut('slow'); }, 15000);

            // Focus pada field nama pabrik
            $('#txtnama').focus();

            // Form validation
            $('#pabrikForm').on('submit', function(e) {
                var nama = $('#txtnama').val().trim();
                
                if (nama === '') {
                    e.preventDefault();
                    alert('⚠️ Nama Pabrik Barang harus diisi!');
                    $('#txtnama').focus();
                    return false;
                }
                
                if (nama.length < 2) {
                    e.preventDefault();
                    alert('⚠️ Nama Pabrik Barang minimal 2 karakter!');
                    $('#txtnama').focus();
                    return false;
                }
                
                var confirmMessage = 'Apakah Anda yakin ingin menyimpan pabrik barang ini?\n\n' +
                                   '📋 Detail Pabrik:\n' +
                                   'Nama: ' + nama + '\n\n' +
                                   '💾 Data akan disimpan ke database lokal';
                
                <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                    confirmMessage += ' dan akan dicoba sinkronisasi ke Accurate Online sebagai kategori barang.';
                <?php else: ?>
                    confirmMessage += '.\n⚠️ Sinkronisasi ke Accurate tidak tersedia.';
                <?php endif; ?>
                
                return confirm(confirmMessage);
            });

            // Auto-uppercase dan clean nama pabrik
            $('#txtnama').on('input', function() {
                // Convert to uppercase and remove special characters except space and dash
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s\-]/g, '');
            });

            // Auto-capitalize deskripsi
            $('#txtdeskripsi').on('input', function() {
                var words = this.value.split(' ');
                for (var i = 0; i < words.length; i++) {
                    if (words[i].length > 0) {
                        words[i] = words[i][0].toUpperCase() + words[i].substr(1).toLowerCase();
                    }
                }
                this.value = words.join(' ');
            });
        });

        // Function untuk show troubleshooting modal
        function showTroubleshooting() {
            $('#troubleshootingModal').modal('show');
        }

        // Function untuk reset form
        function resetForm() {
            if (confirm('⚠️ Apakah Anda yakin ingin mereset form?\n\nSemua data yang telah diisi akan hilang.')) {
                $('#pabrikForm')[0].reset();
                $('#txtnama').focus();
            }
        }

        // Auto-refresh status setiap 5 menit
        setInterval(function() { 
            console.log('Auto-checking Accurate status...'); 
        }, 300000);
    </script>
</body>
</html>