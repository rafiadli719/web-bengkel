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
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
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
            <?php include "menu_master01a.php"; ?>
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
                        <li><a href="#">Data Master</a></li>
                        <li><a href="#">Daftar Item</a></li>
                        <li><a href="barang.php">Master Barang</a></li>
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
                                <i class="fa fa-check-circle"></i> ✅ Terhubung - Data akan otomatis sinkronisasi ke Accurate Online
                            <?php else: ?>
                                <i class="fa fa-exclamation-triangle"></i> ⚠️ Tidak terhubung - Data hanya disimpan di database lokal
                                <br><small><?php echo $_SESSION['accurate_message']; ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <br>
                    <form class="form-horizontal" action="save_barang.php" method="post" name="myform">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtkd"> Kode </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtkd" name="txtkd" class="col-xs-10 col-sm-12" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtbarcode"> Kode Barcode </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtbarcode" name="txtbarcode" class="col-xs-10 col-sm-12" autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtnama"> Nama Item </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-12" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbojenis"> Jenis </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbojenis" id="cbojenis" required>
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $sql = "select jenis, namajenis FROM tblitemjenis";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["jenis"]; ?>"><?php echo $sql_res["namajenis"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbosatuan"> Satuan Utama </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosatuan" id="cbosatuan" required>
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $sql = "select satuan, namasatuan FROM tblitemsatuan";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["satuan"]; ?>"><?php echo $sql_res["namasatuan"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Satuan Tambahan (Opsional untuk Multi-Unit) -->
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbosatuan2"> Satuan Tambahan </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosatuan2" id="cbosatuan2">
                                            <option value="">- Tidak Ada -</option>
                                            <?php
                                            $sql = "select satuan, namasatuan FROM tblitemsatuan";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["satuan"]; ?>"><?php echo $sql_res["namasatuan"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtratio2"> Rasio Satuan Tambahan </label>
                                    <div class="col-sm-8">
                                        <input type="number" id="txtratio2" name="txtratio2" class="col-xs-10 col-sm-12" placeholder="Misal: 12 untuk 1 LUSIN = 12 PCS" min="2" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbopabrik"> Pabrik </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbopabrik" id="cbopabrik" required>
                                            <?php
                                            $sql = "select id, pabrik_barang FROM tbpabrik_barang";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["id"]; ?>"><?php echo $sql_res["pabrik_barang"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbostatus"> Status Produk </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbostatus" id="cbostatus" required>
                                            <?php
                                            $sql = "select id, status FROM tbstatus_produk";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["id"]; ?>"><?php echo $sql_res["status"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbotipe"> Tipe Item </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbotipe" id="cbotipe" required onchange="enabledisabletext()">
                                            <?php
                                            $sql = "select id, tipe FROM tbtipe_item";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["id"]; ?>"><?php echo $sql_res["tipe"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbojasa"> Barang+Jasa </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbojasa" id="cbojasa">
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $sql = "select jasa, nilai, id FROM tbhjual_jasa";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["id"]; ?>">
                                                    <?php echo $sql_res["jasa"]; ?> (<?php echo number_format($sql_res['nilai'], 0); ?>)
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtnote"> Keterangan </label>
                                    <div class="col-sm-8">
                                        <textarea class="col-xs-10 col-sm-12" id="txtnote" name="txtnote" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbosupplier1"> Supplier 1 </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosupplier1" id="cbosupplier1">
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $sql = "select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["nosupplier"]; ?>"><?php echo $sql_res["namasupplier"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbosupplier2"> Supplier 2 </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosupplier2" id="cbosupplier2">
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $sql = "select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["nosupplier"]; ?>"><?php echo $sql_res["namasupplier"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbosupplier3"> Supplier 3 </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosupplier3" id="cbosupplier3">
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $sql = "select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc";
                                            $sql_row = mysqli_query($koneksi, $sql);
                                            while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                            ?>
                                                <option value="<?php echo $sql_res["nosupplier"]; ?>"><?php echo $sql_res["namasupplier"]; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txthpokok"> Harga Pokok </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txthpokok" name="txthpokok" class="col-xs-10 col-sm-12" required autocomplete="off" />
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txthj1"> Harga Jual Utama </label>
                                <div class="col-sm-8">
                                    <input type="text" id="txtqty1a" name="txtqty1a" class="col-xs-2" disabled value="1" />
                                    <label class="col-xs-2 control-label no-padding-center" for="txtqty1b"> s/d </label>
                                    <input type="text" id="txtqty1b" name="txtqty1b" class="col-xs-2" required autocomplete="off" />
                                    <label class="col-xs-2 control-label no-padding-right" for="txthj1"> Harga </label>
                                    <input type="text" id="txthj1" name="txthj1" class="col-xs-4" required autocomplete="off" />
                                </div>
                                </div>
                                <!-- Harga Jual untuk Satuan Tambahan (Opsional) -->
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txthj2"> Harga Jual Tambahan </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty2a" name="txtqty2a" class="col-xs-2" autocomplete="off" />
                                        <label class="col-sm-12 control-label no-padding-center" for="txtqty2b"> s/d </label>
                                        <input type="text" id="txtqty2b" name="txtqty2b" class="col-xs-2" autocomplete="off" />
                                        <label class="col-sm-12 control-label no-padding-right" for="txthj2"> Harga </label>
                                        <input type="text" id="txthj2" name="txthj2" class="col-sm-12" autocomplete="off" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space space-8"></div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-12">
                                <div class="tabbable">
                                    <ul class="nav nav-tabs padding-18 tab-size-bigger" id="myTab">
                                        <li class="active">
                                            <a data-toggle="tab" href="#faq-tab-1">Applicable Part</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content no-border padding-24">
                                        <div id="faq-tab-1" class="tab-pane fade in active">
                                            <div class="row">
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel1.php"; ?>
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel2.php"; ?>
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel3.php"; ?>
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel4.php"; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info sinkronisasi -->
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-9">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    <strong>Informasi:</strong> 
                                    Data barang akan disimpan ke database lokal. 
                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                        Sistem akan otomatis mencoba sinkronisasi ke Accurate Online dengan kode sesuai nama barang.
                                    <?php else: ?>
                                        Sinkronisasi ke Accurate Online tidak tersedia karena koneksi bermasalah.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-4">
                                <button class="btn btn-primary btn-block" type="submit">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    Simpan Barang
                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                        & Sync ke Accurate
                                    <?php endif; ?>
                                </button>
                            </div>
                            <div class="col-xs-4">
                                <a href="barang_add.php" onclick="return confirm('Pengisian Barang dibatalkan. Anda Yakin?')">
                                    <button class="btn btn-warning btn-block" type="button">
                                        Batal
                                    </button>
                                </a>
                            </div>
                            <div class="col-xs-4">
                                <a href="barang.php">
                                    <button class="btn btn-primary btn-block" type="button">
                                        Tutup
                                    </button>
                                </a>
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
                                                        <label class="control-label">Pesan:</label>
                                                        <p class="help-block">
                                                            <?php echo isset($_SESSION['accurate_message']) ? $_SESSION['accurate_message'] : 'Status tidak tersedia'; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="window.location.reload();">
                                                        <i class="fa fa-refresh"></i> Refresh Status
                                                    </button>
                                                    <?php if (!isset($_SESSION['accurate_status']) || $_SESSION['accurate_status'] != 'connected'): ?>
                                                        <button type="button" class="btn btn-sm btn-info" onclick="showTroubleshooting();">
                                                            <i class="fa fa-question-circle"></i> Troubleshooting
                                                        </button>
                                                    <?php endif; ?>
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

        <!-- Modal Voting -->
        <div class="modal fade" id="troubleshootingModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title">
                            <i class="fa fa-wrench"></i> Troubleshooting Koneksi Accurate
                        </h4>
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
                                    <li>Periksa permission untuk item save</li>
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
                
                // Input masks
                $.mask.definitions['~']='[+-]';
                $('.input-mask-date').mask('99/99/9999');
                $('.input-mask-phone').mask('(999) 999-9999');

                // Auto-hide alert after 15 seconds
                setTimeout(function() {
                    $('.alert-dismissible').fadeOut('slow');
                }, 15000);

                // Focus pada field kode barang
                $('#txtkd').focus();

                // Form validation
                $('form').on('submit', function(e) {
                    var kode = $('#txtkd').val().trim();
                    var nama = $('#txtnama').val().trim();
                    var jenis = $('#cbojenis').val();
                    var satuan = $('#cbosatuan').val();
                    var satuan2 = $('#cbosatuan2').val();
                    var ratio2 = $('#txtratio2').val();
                    var pabrik = $('#cbopabrik').val();
                    var status = $('#cbostatus').val();
                    var tipe = $('#cbotipe').val();
                    
                    if (kode === '' || nama === '' || jenis === '' || satuan === '' || pabrik === '' || status === '' || tipe === '') {
                        e.preventDefault();
                        alert('Semua field wajib harus diisi!');
                        return false;
                    }

                    // Validasi satuan tambahan
                    if (satuan2 !== '' && satuan2 === satuan) {
                        e.preventDefault();
                        alert('Satuan tambahan tidak boleh sama dengan satuan utama!');
                        return false;
                    }

                    if (satuan2 !== '' && (ratio2 === '' || ratio2 <= 1)) {
                        e.preventDefault();
                        alert('Rasio satuan tambahan harus diisi dan lebih besar dari 1!');
                        return false;
                    }

                    var confirmMessage = 'Apakah Anda yakin ingin menyimpan barang ini?\n\n' +
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

                // Auto-uppercase untuk kode dan nama
                $('#txtkd, #txtnama').on('input', function() {
                    this.value = this.value.toUpperCase();
                });

                // Tipe item change handler
                $('#cbotipe').change(function(){
                    if($(this).val() === '1'){
                        $('#cbojasa').attr('disabled', 'disabled');
                    } else {
                        $('#cbojasa').attr('disabled', false);
                    }
                });

                // Enable/disable harga satuan tambahan berdasarkan satuan2
                $('#cbosatuan2').change(function() {
                    var satuan2 = $(this).val();
                    if (satuan2 !== '') {
                        $('#txtqty2a, #txtqty2b, #txthj2').prop('disabled', false);
                    } else {
                        $('#txtqty2a, #txtqty2b, #txthj2').prop('disabled', true).val('');
                        $('#txtratio2').val('');
                    }
                });
            });

            // Function untuk show troubleshooting modal
            function showTroubleshooting() {
                $('#troubleshootingModal').modal('show');
            }

            // Auto-refresh status setiap 5 menit
            setInterval(function() {
                console.log('Auto-checking Accurate status...');
            }, 300000);

            function enabledisabletext() {
                if(document.myform.cbotipe.value == '1') {
                    document.myform.cbojasa.disabled = false;
                } else if(document.myform.cbotipe.value == '2') {
                    document.myform.cbojasa.disabled = true;
                }
            }
        </script>
    </body>
</html>