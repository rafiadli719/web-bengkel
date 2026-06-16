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

    // Get brands for dropdown
    $merk_query = "SELECT id, merek, kode_brand FROM tbpabrik_motor WHERE status = '1' ORDER BY merek ASC";
    $merk_result = mysqli_query($koneksi, $merk_query);

    $error_msg = '';
    $success_msg = '';

// Process form submission
if ($_POST) {
    $tipe_header = strtoupper(trim($_POST['tipe_header']));
    $id_brand = (int)$_POST['id_brand'];

    // Validasi input
    if (empty($tipe_header)) {
        $error_msg = "Nama Tipe harus diisi!";
    } elseif ($id_brand <= 0) {
        $error_msg = "Merk harus dipilih!";
    } elseif (strlen($tipe_header) > 50) {
        $error_msg = "Nama Tipe maksimal 50 karakter!";
    } else {
        // Escape input untuk keamanan
        $tipe_header_escaped = mysqli_real_escape_string($koneksi, $tipe_header);

        // Check duplicate
        $check_query = "SELECT id FROM tbmaster_tipe_header WHERE nama_model = '$tipe_header_escaped' AND id_brand = $id_brand AND status = '1'";
        $check_result = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "Tipe header '$tipe_header' untuk merk tersebut sudah ada!";
        } else {
            // Insert data sesuai struktur tabel yang ada
            $insert_query = "INSERT INTO tbmaster_tipe_header (nama_model, id_brand, status)
                           VALUES ('$tipe_header_escaped', $id_brand, '1')";

            if (mysqli_query($koneksi, $insert_query)) {
                $_SESSION['success'] = "Data tipe header '$tipe_header' berhasil tersimpan!";
                header('Location: master_tipe_header.php');
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

        <meta name="description" content="Input Tipe Header Motor Baru" />
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
                                <a href="master_tipe_header.php">Tipe Header</a>
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
                                            <h4 class="widget-title">Input Tipe Header Motor Baru</h4>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main no-padding">

                                <form class="form-horizontal" role="form" method="POST">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="form-field-1"> Merk Motor <span style="color: red;">*</span></label>

                                        <div class="col-sm-9">
                                            <select id="form-field-1" name="id_brand" class="form-control" required>
                                                <option value="">-- Pilih Merk Motor --</option>
                                                <?php
                                                mysqli_data_seek($merk_result, 0);
                                                while ($merk = mysqli_fetch_assoc($merk_result)): ?>
                                                <option value="<?php echo $merk['id']; ?>"
                                                        <?php echo (isset($id_brand) && $id_brand == $merk['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $merk['kode_brand']; ?> - <?php echo $merk['merek']; ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <span class="help-inline col-xs-12 col-sm-7">
                                                <span class="middle">Pilih merk motor terlebih dahulu</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="form-field-2"> Nama Tipe <span style="color: red;">*</span></label>

                                        <div class="col-sm-9">
                                            <input type="text" id="form-field-2" name="tipe_header" placeholder="Contoh: BEAT, VARIO"
                                                   class="form-control" maxlength="50" style="text-transform: uppercase;"
                                                   value="<?php echo isset($tipe_header) ? htmlspecialchars($tipe_header) : ''; ?>" required />
                                            <span class="help-inline col-xs-12 col-sm-7">
                                                <span class="middle">Nama tipe motor (maksimal 50 karakter)</span>
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
                                            <a href="master_tipe_header.php" class="btn btn-success">
                                                <i class="ace-icon fa fa-list bigger-110"></i>
                                                Kembali ke Daftar
                                            </a>

                                            &nbsp; &nbsp; &nbsp;
                                            <a href="index.php" class="btn btn-warning">
                                                <i class="ace-icon fa fa-home bigger-110"></i>
                                                Ke Menu Awal
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
                // Auto focus pada merk dropdown
                $('#form-field-1').focus();

                // Auto uppercase transformation
                $('#form-field-2').on('input', function() {
                    this.value = this.value.toUpperCase();
                });

                // Enter key navigation
                $('#form-field-1').on('keypress', function(e) {
                    if (e.which == 13) {
                        $('#form-field-2').focus();
                    }
                });

                $('#form-field-2').on('keypress', function(e) {
                    if (e.which == 13) {
                        $(this).closest('form').submit();
                    }
                });
            });
        </script>
    </body>
</html>

<?php } ?>