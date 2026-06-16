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
    // Semua user bisa akses DELETE operation
    $is_admin_pengadaan = true;

$page_title = "Hapus Merk Motor";
$error_msg = '';
$success_msg = '';

// Get ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: master_merk_motor.php');
    exit();
}

// Get current data
$query = "SELECT * FROM tbpabrik_motor WHERE id = $id AND status = '1'";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: master_merk_motor.php');
    exit();
}

$data = mysqli_fetch_assoc($result);

// Process deletion
if ($_POST && isset($_POST['confirm_delete'])) {
    // Cek apakah merk sudah digunakan dalam transaksi
    $check_usage = false;
    $usage_details = [];

    // Cek di master tipe header
    $check1 = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbmaster_tipe_header WHERE id_brand = $id");
    $result1 = mysqli_fetch_assoc($check1);
    if ($result1['total'] > 0) {
        $check_usage = true;
        $usage_details[] = "Tipe Header (" . $result1['total'] . " data)";
    }

    // Cek di tipe motor
    $check2 = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbtipe_motor WHERE kode_pabrik = $id");
    if ($check2) {
        $result2 = mysqli_fetch_assoc($check2);
        if ($result2['total'] > 0) {
            $check_usage = true;
            $usage_details[] = "Tipe Motor (" . $result2['total'] . " data)";
        }
    }

    // Cek di kendaraan
    $check3 = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tblkendaraan WHERE kode_merek = $id");
    if ($check3) {
        $result3 = mysqli_fetch_assoc($check3);
        if ($result3['total'] > 0) {
            $check_usage = true;
            $usage_details[] = "Kendaraan (" . $result3['total'] . " data)";
        }
    }

    if ($check_usage) {
        $error_msg = "Data tidak dapat dihapus karena masih digunakan di: " . implode(", ", $usage_details) . ". Merk motor bisa dihapus jika belum ada transaksi.";
    } else {
        // Soft delete - update status menjadi '0' (sesuai requirement Excel)
        $delete_query = "UPDATE tbpabrik_motor SET status = '0' WHERE id = $id AND status = '1'";

        if (mysqli_query($koneksi, $delete_query)) {
            if (mysqli_affected_rows($koneksi) > 0) {
                $_SESSION['success'] = "Data merk motor '{$data['merek']}' ({$data['kode_brand']}) berhasil dihapus!";
                header('Location: master_merk_motor.php');
                exit();
            } else {
                $error_msg = "Data tidak ditemukan atau sudah terhapus!";
            }
        } else {
            $error_msg = "Gagal menghapus data: " . mysqli_error($koneksi);
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

        <meta name="description" content="Hapus Merk Motor" />
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

        <style>
            .data-hapus {
                background-color: #f2dede;
                border-left: 4px solid #d43f3a;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .warning-box {
                background-color: #fcf8e3;
                border: 1px solid #faebcc;
                color: #8a6d3b;
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
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
                            <li class="active">Hapus</li>
                        </ul>
                    </div>

                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-12">
                                <!-- Alert Messages -->
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
                                            <h4 class="widget-title">Hapus Merk Motor</h4>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main no-padding">

                                                <!-- Warning -->
                                                <div class="warning-box" style="margin: 20px;">
                                                    <i class="fa fa-warning"></i>
                                                    <strong>Perhatian!</strong>
                                                    Anda akan menghapus data merk motor berikut.
                                                    Pastikan data ini tidak sedang digunakan dalam transaksi atau master data lainnya.
                                                </div>

                                                <!-- Data yang akan dihapus -->
                                                <div class="data-hapus" style="margin: 20px;">
                                                    <h5><strong>Data Yang Akan Dihapus</strong></h5>
                                                    <div class="row" style="margin-top: 15px;">
                                                        <div class="col-sm-3"><strong>Kode Merk :</strong></div>
                                                        <div class="col-sm-9"><?php echo htmlspecialchars($data['kode_brand']); ?></div>
                                                    </div>
                                                    <div class="row" style="margin-top: 8px;">
                                                        <div class="col-sm-3"><strong>Keterangan :</strong></div>
                                                        <div class="col-sm-9"><?php echo htmlspecialchars($data['merek']); ?></div>
                                                    </div>
                                                </div>

                                                <!-- Konfirmasi -->
                                                <div class="text-center" style="margin: 20px;">
                                                    <h4 style="color: #d43f3a; margin-bottom: 30px;">
                                                        <i class="fa fa-question-circle"></i>
                                                        Yakin ingin menghapus data ini?
                                                    </h4>

                                                    <form method="POST" action="" style="display: inline;">
                                                        <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg">
                                                            <i class="ace-icon fa fa-trash-o bigger-110"></i>
                                                            Ya, Hapus Data
                                                        </button>
                                                    </form>

                                                    &nbsp; &nbsp; &nbsp;
                                                    <a href="master_merk_motor.php" class="btn btn-success btn-lg">
                                                        <i class="ace-icon fa fa-list bigger-110"></i>
                                                        Lihat Daftar Merk Motor
                                                    </a>

                                                    &nbsp; &nbsp; &nbsp;
                                                    <a href="index.php" class="btn btn-warning btn-lg">
                                                        <i class="ace-icon fa fa-home bigger-110"></i>
                                                        Ke Menu Awal
                                                    </a>
                                                </div>

                                                <!-- Info -->
                                                <div style="margin: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #f39c12;">
                                                    <h5><strong>Catatan Penghapusan:</strong></h5>
                                                    <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                                        <li>• Merk bisa dihapus jika belum ada transaksi</li>
                                                        <li>• Sistem akan mengecek penggunaan merk di master tipe header, tipe motor, dan kendaraan</li>
                                                        <li>• Data yang dihapus tidak benar-benar hilang, hanya dinonaktifkan</li>
                                                        <li>• Setelah hapus berhasil otomatis masuk ke halaman daftar merk motor</li>
                                                    </ul>
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
        </div>

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <script type="text/javascript">
            jQuery(function($) {
                // Konfirmasi sebelum hapus
                $('button[name="confirm_delete"]').on('click', function(e) {
                    if (!confirm('PERHATIAN!\n\nApakah Anda benar-benar yakin ingin menghapus merk motor ini?\n\nData yang sudah dihapus tidak dapat dikembalikan!')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        </script>
    </body>
</html>

<?php } ?>