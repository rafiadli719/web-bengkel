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
    $foto_user = $tm_cari['foto_user'];
    if ($foto_user == '') {
        $foto_user = "file_upload/avatar.png";
    }

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
                }
            }

            return [
                'status' => 'disconnected',
                'message' => 'API Token tidak valid atau permission tidak mencukupi'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'disconnected',
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    // Check Accurate connection
    if (defined('ACCURATE_API_TOKEN') && defined('ACCURATE_SIGNATURE_SECRET') && defined('ACCURATE_API_BASE_URL')) {
        $accurate_connection = checkAccurateConnection();
        $_SESSION['accurate_status'] = $accurate_connection['status'];
        $_SESSION['accurate_message'] = $accurate_connection['message'];
    } else {
        $_SESSION['accurate_status'] = 'disconnected';
        $_SESSION['accurate_message'] = 'File konfigurasi Accurate tidak ditemukan atau tidak lengkap';
    }

    // Get item data
    $kdbrg = $_GET['kd'];
    $cari_kd = mysqli_query($koneksi, "SELECT * FROM tblitem WHERE noitem='$kdbrg'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $kodebarcode = $tm_cari['kodebarcode'];
    $nama = $tm_cari['namaitem'];
    $jenis = $tm_cari['jenis'];
    $satuan = $tm_cari['satuan'];
    $txtqty2 = $tm_cari['hjqtys1'];
    $txtqty3 = $tm_cari['hjqtyd2'];
    $txtqty4 = $tm_cari['hjqtyd3'];
    $txtqty5 = $tm_cari['hjqtys2'];
    $hargajual = $tm_cari['hargajual'];
    $hargajual2 = $tm_cari['hargajual2'];
    $hargajual3 = $tm_cari['hargajual3'];
    $note = $tm_cari['note'];
    $supplier1 = $tm_cari['supplier'];
    $supplier2 = $tm_cari['supplier2'];
    $supplier3 = $tm_cari['supplier3'];
    $hargapokok = $tm_cari['hargapokok'];
    $cbostatus = $tm_cari['statusproduk'];
    $cbotipe = $tm_cari['statusitem'];
    $cbopabrik = $tm_cari['kd_pabrik'];
    $jasa = $tm_cari['jenis_jasa'];

    $cari_kd = mysqli_query($koneksi, "SELECT stokmin, stok_maks, stok_awal, rakbarang FROM tblitem_stok WHERE noitem='$kdbrg' AND kode_cabang='$kd_cabang'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $stokmin = $tm_cari['stokmin'];
    $stok_maks = $tm_cari['stok_maks'];
    $txtstokawal = $tm_cari['stok_awal'];
    $rakbarang = $tm_cari['rakbarang'];
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
                        <li class="active">Edit Data</li>
                    </ul>
                </div>
                <div class="page-content">
                    <?php if (isset($_SESSION['accurate_status'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['accurate_status'] == 'connected' ? 'success' : 'warning'; ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                            <strong>Status Accurate API:</strong>
                            <?php if ($_SESSION['accurate_status'] == 'connected'): ?>
                                <i class="fa fa-check-circle"></i> ✅ Terhubung - Perubahan akan otomatis sinkronisasi ke Accurate Online
                            <?php else: ?>
                                <i class="fa fa-exclamation-triangle"></i> ⚠️ Tidak terhubung - Perubahan hanya disimpan di database lokal
                                <br><small><?php echo $_SESSION['accurate_message']; ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <br>
                    <form class="form-horizontal" action="barang_edit_proses.php" method="post" name="myform">
                        <input type="hidden" name="txtkd" class="form-control" value="<?php echo $kdbrg; ?>"/>
                        <input type="hidden" name="txtkdcab" class="form-control" value="<?php echo $kd_cabang; ?>"/>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Kode </label>
                                    <div class="col-sm-8">
                                        <input type="text" class="col-xs-10 col-sm-12" disabled value="<?php echo $kdbrg; ?>" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtbarcode"> Kode Barcode </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtbarcode" name="txtbarcode" class="col-xs-10 col-sm-12" value="<?php echo $kodebarcode; ?>" autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtnama"> Nama Item </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtnama" name="txtnama" class="col-xs-10 col-sm-12" value="<?php echo $nama; ?>" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbojenis"> Jenis </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbojenis" id="cbojenis" required>
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $q = mysqli_query($koneksi, "select jenis, namajenis FROM tblitemjenis");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['jenis'];
                                                $k_opis = $row1['namajenis'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $jenis) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbosatuan"> Satuan </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbosatuan" id="cbosatuan" required>
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $q = mysqli_query($koneksi, "select satuan, namasatuan FROM tblitemsatuan");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['satuan'];
                                                $k_opis = $row1['namasatuan'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $satuan) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cbopabrik"> Pabrik </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cbopabrik" id="cbopabrik" required>
                                            <?php
                                            $q = mysqli_query($koneksi, "select id, pabrik_barang FROM tbpabrik_barang");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['id'];
                                                $k_opis = $row1['pabrik_barang'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbopabrik) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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
                                            $q = mysqli_query($koneksi, "select id, status FROM tbstatus_produk");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['id'];
                                                $k_opis = $row1['status'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbostatus) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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
                                            $q = mysqli_query($koneksi, "select id, tipe FROM tbtipe_item");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['id'];
                                                $k_opis = $row1['tipe'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbotipe) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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
                                            $q = mysqli_query($koneksi, "select jasa, nilai, id FROM tbhjual_jasa");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['id'];
                                                $k_opis = $row1['jasa'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $jasa) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtnote"> Keterangan </label>
                                    <div class="col-sm-8">
                                        <textarea class="col-xs-10 col-sm-12" id="txtnote" name="txtnote" rows="2"><?php echo $note; ?></textarea>
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
                                            $q = mysqli_query($koneksi, "select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['nosupplier'];
                                                $k_opis = $row1['namasupplier'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $supplier1) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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
                                            $q = mysqli_query($koneksi, "select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['nosupplier'];
                                                $k_opis = $row1['namasupplier'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $supplier2) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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
                                            $q = mysqli_query($koneksi, "select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['nosupplier'];
                                                $k_opis = $row1['namasupplier'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $supplier3) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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
                                        <input type="text" id="txthpokok" name="txthpokok" class="col-xs-10 col-sm-12" value="<?php echo $hargapokok; ?>" required autocomplete="off" />
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txthj1"> Harga Jual </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty1a" name="txtqty1a" class="col-xs-2" disabled value="1" />
                                        <label class="col-xs-2 control-label no-padding-center" for="txtqty1b"> s/d </label>
                                        <input type="text" id="txtqty1b" name="txtqty1b" class="col-xs-2" value="<?php echo $txtqty2; ?>" required autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-right" for="txthj1"> Harga </label>
                                        <input type="text" id="txthj1" name="txthj1" class="col-xs-4" value="<?php echo $hargajual; ?>" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txthj2"> Berdasarkan Jumlah </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty2a" name="txtqty2a" class="col-xs-2" value="<?php echo $txtqty3; ?>" autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-center" for="txtqty2b"> s/d </label>
                                        <input type="text" id="txtqty2b" name="txtqty2b" class="col-xs-2" value="<?php echo $txtqty4; ?>" autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-right" for="txthj2"> Harga </label>
                                        <input type="text" id="txthj2" name="txthj2" class="col-xs-4" value="<?php echo $hargajual2; ?>" autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txthj3">   </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtqty3a" name="txtqty3a" class="col-xs-2" value="<?php echo $txtqty5; ?>" autocomplete="off" />
                                        <label class="col-xs-2 control-label no-padding-center" for="txtqty4b"> s/d </label>
                                        <input type="text" id="txtqty4b" name="txtqty4b" class="col-xs-2" disabled value="Mak" />
                                        <label class="col-xs-2 control-label no-padding-right" for="txthj3"> Harga </label>
                                        <input type="text" id="txthj3" name="txthj3" class="col-xs-4" value="<?php echo $hargajual3; ?>" autocomplete="off" />
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtstokmin"> Stok Minimum </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtstokmin" name="txtstokmin" class="col-xs-10 col-sm-12" value="<?php echo $stokmin; ?>" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="txtstokmaks"> Stok Maksimal </label>
                                    <div class="col-sm-8">
                                        <input type="text" id="txtstokmaks" name="txtstokmaks" class="col-xs-10 col-sm-12" value="<?php echo $stok_maks; ?>" required autocomplete="off" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label no-padding-right" for="cborak"> Rak </label>
                                    <div class="col-sm-8">
                                        <select class="col-xs-10 col-sm-12" name="cborak" id="cborak" required>
                                            <option value="">- Pilih -</option>
                                            <?php
                                            $q = mysqli_query($koneksi, "select id, rak_barang FROM tbrakbarang");
                                            while ($row1 = mysqli_fetch_array($q)) {
                                                $k_id = $row1['id'];
                                                $k_opis = $row1['rak_barang'];
                                            ?>
                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $rakbarang) { echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                                    <?php include "_template/_item_add_tabel1_edit.php"; ?>
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel2_edit.php"; ?>
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel3_edit.php"; ?>
                                                </div>
                                                <div class="col-xs-3">
                                                    <?php include "_template/_item_add_tabel4_edit.php"; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-9">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    <strong>Informasi:</strong>
                                    Perubahan data akan disimpan ke database lokal.
                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                        Sistem akan otomatis mencoba sinkronisasi perubahan ke Accurate Online.
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
                                    Update Barang
                                    <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                                        & Sync ke Accurate
                                    <?php endif; ?>
                                </button>
                            </div>
                            <div class="col-xs-4">
                                <a href="barang.php" onclick="return confirm('Update Barang dibatalkan. Anda Yakin?')">
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
        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
    </div>
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script type="text/javascript">
        if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
    </script>
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
            $('[data-rel=tooltip]').tooltip({container:'body'});
            $('[data-rel=popover]').popover({container:'body'});
            autosize($('textarea[class*=autosize]'));
            $.mask.definitions['~']='[+-]';
            $('.input-mask-date').mask('99/99/9999');
            $('.input-mask-phone').mask('(999) 999-9999');
            setTimeout(function() {
                $('.alert-dismissible').fadeOut('slow');
            }, 15000);
            $('form').on('submit', function(e) {
                var nama = $('#txtnama').val().trim();
                var jenis = $('#cbojenis').val();
                var satuan = $('#cbosatuan').val();
                var pabrik = $('#cbopabrik').val();
                var status = $('#cbostatus').val();
                var tipe = $('#cbotipe').val();
                if (nama === '' || jenis === '' || satuan === '' || pabrik === '' || status === '' || tipe === '') {
                    e.preventDefault();
                    alert('Semua field wajib harus diisi!');
                    return false;
                }
                var confirmMessage = 'Apakah Anda yakin ingin mengupdate barang ini?\n\n' +
                                   'Kode: <?php echo $kdbrg; ?>\n' +
                                   'Nama: ' + nama + '\n\n' +
                                   'Perubahan akan disimpan ke database lokal';
                <?php if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected'): ?>
                    confirmMessage += ' dan akan dicoba sinkronisasi ke Accurate Online.';
                <?php else: ?>
                    confirmMessage += '.\nSinkronisasi ke Accurate tidak tersedia.';
                <?php endif; ?>
                return confirm(confirmMessage);
            });
            $('#txtnama').on('input', function() {
                this.value = this.value.toUpperCase();
            });
            $('#cbotipe').change(function(){
                if($(this).val() === '1'){
                    $('#cbojasa').attr('disabled', 'disabled');
                } else {
                    $('#cbojasa').attr('disabled', false);
                }
            });
        });
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
<?php
}
?>