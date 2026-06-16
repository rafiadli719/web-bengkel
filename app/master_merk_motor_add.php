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

    $tgl_skr=date('d');
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // Hilangkan pembatasan hak akses - fokus pada CRUD functionality
    // Semua user bisa akses ADD operation
    $is_admin_pengadaan = true;

    $error_msg = '';
    $success_msg = '';

// Process form submission
if ($_POST) {
    $kode_brand = strtoupper(trim($_POST['kode_brand']));
    $keterangan = strtoupper(trim($_POST['keterangan']));

    // Validasi input
    if (empty($kode_brand)) {
        $error_msg = "Kode merk harus diisi!";
    } elseif (strlen($kode_brand) != 2 || !preg_match('/^[A-Z]\.$/', $kode_brand)) {
        $error_msg = "Kode merk harus 2 karakter: 1 huruf + 1 titik (contoh: H., Y., S.)";
    } elseif (empty($keterangan)) {
        $error_msg = "Keterangan harus diisi!";
    } elseif (strlen($keterangan) > 30) {
        $error_msg = "Keterangan maksimal 30 karakter!";
    } else {
        // Escape input untuk keamanan
        $kode_brand_escaped = mysqli_real_escape_string($koneksi, $kode_brand);
        $keterangan_escaped = mysqli_real_escape_string($koneksi, $keterangan);

        // Cek duplikat kode
        $check_query = "SELECT id FROM tbpabrik_motor WHERE kode_brand = '$kode_brand_escaped' AND status = '1'";
        $check_result = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "Kode merk '$kode_brand' sudah ada! Silakan gunakan kode yang berbeda.";
        } else {
            // Insert data dengan prepared statement concept
            $insert_query = "INSERT INTO tbpabrik_motor (merek, kode_brand, status) VALUES ('$keterangan_escaped', '$kode_brand_escaped', '1')";

            if (mysqli_query($koneksi, $insert_query)) {
                $_SESSION['success'] = "Data merk motor '$keterangan' ($kode_brand) berhasil tersimpan!";
                header('Location: master_merk_motor.php');
                exit();
            } else {
                $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Input Merk Motor Baru" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
        <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <!-- ace settings handler -->
        <script src="assets/js/ace-extra.min.js"></script>
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
                                <a href="#">Data Master</a>
                            </li>
                            <li>
                                <a href="#">Master Motor</a>
                            </li>
                            <li>
                                <a href="master_merk_motor.php">Merk Motor</a>
                            </li>
                            <li class="active">Input Baru</li>
                        </ul>
                    </div>

                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-12">
                                <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger">
                                    <button type="button" class="close" data-dismiss="alert">
                                        <i class="ace-icon fa fa-times"></i>
                                    </button>
                                    <strong><i class="ace-icon fa fa-times"></i> Error!</strong>
                                    <?php echo $error_msg; ?>
                                    <br />
                                </div>
                                <?php endif; ?>

                                <div class="col-xs-12 col-sm-10 col-sm-offset-1">
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">Input Merk Motor Baru</h4>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main no-padding">

                                <form class="form-horizontal" role="form" method="POST">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="form-field-2"> KETERANGAN </label>

                                        <div class="col-sm-9">
                                            <input type="text" id="form-field-2" name="keterangan" placeholder="Masukkan nama merk motor..."
                                                   class="form-control" maxlength="30" style="text-transform: uppercase;"
                                                   value="<?php echo isset($keterangan) ? strtoupper(htmlspecialchars($keterangan)) : ''; ?>" required />
                                            <span class="help-inline col-xs-12 col-sm-7">
                                                <span class="middle">Masukkan keterangan terlebih dahulu, kode merk akan otomatis dibuat</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="form-field-1"> Kode Merk </label>

                                        <div class="col-sm-9">
                                            <input type="text" id="form-field-1" name="kode_brand" placeholder="Otomatis dibuat dari keterangan"
                                                   class="form-control" maxlength="2" style="text-transform: uppercase;"
                                                   value="<?php echo isset($kode_brand) ? htmlspecialchars($kode_brand) : ''; ?>" required />
                                            <span class="help-inline col-xs-12 col-sm-7">
                                                <span class="middle"><strong>Otomatis dibuat dari keterangan</strong> - dapat diedit (1 huruf + 1 titik)</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="clearfix form-actions">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button class="btn btn-info" type="submit">
                                                <i class="ace-icon fa fa-check bigger-110"></i>
                                                Simpan
                                            </button>

                                            &nbsp; &nbsp; &nbsp;
                                            <button class="btn" type="reset">
                                                <i class="ace-icon fa fa-undo bigger-110"></i>
                                                Reset
                                            </button>

                                            &nbsp; &nbsp; &nbsp;
                                            <a href="master_merk_motor.php" class="btn btn-success">
                                                <i class="ace-icon fa fa-list bigger-110"></i>
                                                Kembali ke Daftar
                                            </a>
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
            </div>
        </div>

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <script type="text/javascript">
            jQuery(function($) {
                // Auto focus pada keterangan (field pertama)
                $('#form-field-2').focus();

                // Auto-generate kode merk dari keterangan
                function generateKodeMerk(keterangan) {
                    if (!keterangan) return '';
                    
                    // Ambil huruf pertama dari keterangan
                    var firstLetter = keterangan.trim().toUpperCase().charAt(0);
                    
                    // Format: huruf + titik
                    return firstLetter ? firstLetter + '.' : '';
                }

                // Auto-generate kode merk saat keterangan diubah
                $('#form-field-2').on('input', function() {
                    // Convert keterangan ke uppercase
                    var keterangan = this.value.toUpperCase();
                    this.value = keterangan;
                    
                    // Generate kode merk otomatis
                    var generatedKode = generateKodeMerk(keterangan);
                    $('#form-field-1').val(generatedKode);
                });

                // Format input kode brand (tetap bisa diedit manual)
                $('#form-field-1').on('input', function() {
                    var val = this.value.toUpperCase();
                    val = val.replace(/[^A-Z.]/g, '');
                    if (val.length == 1 && /[A-Z]/.test(val)) {
                        val = val + '.';
                    }
                    this.value = val;
                });
            });
        </script>
    </body>
</html>

<?php } ?>