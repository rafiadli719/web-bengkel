<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    // Data User
    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
    $pwd=$tm_cari['password'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // Data Cabang
    $cari_kd=mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];
    $tipe_cabang=$tm_cari['tipe_cabang'];

    $tgl_skr=date('d');
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // Get ID
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: master_tipe_detail.php');
        exit();
    }

    // Get current data with related info
    $query = "SELECT td.*,
                     th.nama_model as tipe_header,
                     pm.merek, pm.kode_brand as brand_code,
                     jm.jenis as nama_jenis,
                     km.kategori as nama_kategori
              FROM tbmaster_tipe_detail td
              LEFT JOIN tbmaster_tipe_header th ON td.id_tipe_header = th.id
              LEFT JOIN tbpabrik_motor pm ON th.id_brand = pm.id
              LEFT JOIN tbjenis_motor jm ON td.id_jenis_motor = jm.kd
              LEFT JOIN tbkategori_motor km ON td.id_kategori_motor = km.id
              WHERE td.id = $id AND td.status = '1'";
    $result = mysqli_query($koneksi, $query);
    if (mysqli_num_rows($result) == 0) {
        header('Location: master_tipe_detail.php');
        exit();
    }

    $data = mysqli_fetch_assoc($result);
    $error_msg = '';

    // Process deletion
    if ($_POST && isset($_POST['confirm_delete'])) {
        // Soft delete
        $delete_query = "UPDATE tbmaster_tipe_detail SET status = '0' WHERE id = $id";
        if (mysqli_query($koneksi, $delete_query)) {
            $_SESSION['delete_success'] = "Data tipe detail '{$data['kode_tipe']}' berhasil dihapus!";
            header('Location: master_tipe_detail.php');
            exit();
        } else {
            $error_msg = "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Hapus Tipe Detail Motor" />
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

                <?php include "menu_master01b.php"; ?>

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
                                <a href="master_tipe_detail.php">Tipe Detail</a>
                            </li>                                                                                    
                            <li class="active">Hapus Tipe Detail</li>
                        </ul><!-- /.breadcrumb -->
                    </div>

                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-container-col ui-sortable">
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">
                                                <i class="ace-icon fa fa-trash-o red"></i>
                                                Hapus Tipe Detail Motor
                                            </h4>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <?php if ($error_msg): ?>
                                                <div class="alert alert-danger">
                                                    <i class="ace-icon fa fa-exclamation-triangle"></i>
                                                    <?php echo $error_msg; ?>
                                                </div>
                                                <?php endif; ?>

                                                <div class="alert alert-warning">
                                                    <h4>
                                                        <i class="ace-icon fa fa-warning bigger-130"></i>
                                                        Konfirmasi Penghapusan
                                                    </h4>
                                                    <p>Anda yakin ingin menghapus data tipe detail berikut? Data yang dihapus tidak dapat dikembalikan.</p>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered">
                                                                <tbody>
                                                                    <tr>
                                                                        <th width="25%" class="info">Kode Tipe</th>
                                                                        <td><strong class="blue"><?php echo htmlspecialchars($data['kode_tipe']); ?></strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="info">Brand & Tipe Header</th>
                                                                        <td><?php echo htmlspecialchars($data['brand_code'] . ' - ' . $data['tipe_header']); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="info">Nama Detail</th>
                                                                        <td><?php echo htmlspecialchars($data['nama_detail']); ?></td>
                                                                    </tr>
                                                                    <?php if ($data['cc']): ?>
                                                                    <tr>
                                                                        <th class="info">CC</th>
                                                                        <td><?php echo $data['cc']; ?>cc</td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                    <?php if ($data['nama_jenis']): ?>
                                                                    <tr>
                                                                        <th class="info">Jenis Motor</th>
                                                                        <td><?php echo htmlspecialchars($data['nama_jenis']); ?></td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                    <?php if ($data['fitur_pembeda'] && $data['fitur_pembeda'] != '-'): ?>
                                                                    <tr>
                                                                        <th class="info">Fitur Pembeda</th>
                                                                        <td><?php echo htmlspecialchars($data['fitur_pembeda']); ?></td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                    <?php if ($data['tahun_awal']): ?>
                                                                    <tr>
                                                                        <th class="info">Tahun Produksi</th>
                                                                        <td>
                                                                            <?php echo $data['tahun_awal']; ?>
                                                                            <?php if ($data['tahun_akhir']): ?>
                                                                            s/d <?php echo htmlspecialchars($data['tahun_akhir']); ?>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                    <?php if ($data['no_seri_mesin']): ?>
                                                                    <tr>
                                                                        <th class="info">Seri Mesin</th>
                                                                        <td><code><?php echo htmlspecialchars($data['no_seri_mesin']); ?></code></td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                    <?php if ($data['nama_kategori']): ?>
                                                                    <tr>
                                                                        <th class="info">Kategori Motor</th>
                                                                        <td><?php echo htmlspecialchars($data['nama_kategori']); ?></td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="clearfix form-actions">
                                                    <div class="col-md-12">
                                                        <form method="POST" style="display: inline;">
                                                            <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg">
                                                                <i class="ace-icon fa fa-trash-o bigger-110"></i>
                                                                Ya, Hapus Data Ini
                                                            </button>
                                                        </form>
                                                        <a href="master_tipe_detail.php" class="btn btn-grey btn-lg">
                                                            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
                                                            Batal
                                                        </a>
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
                        <span class="bigger-120">
                            <span class="blue bolder"><?php include "../lib/subtitel.php"; ?></span>
                            &copy; 2023-<?php echo date('Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
                <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
            </a>
        </div><!-- /.main-container -->

        <!-- basic scripts -->
        <script type="text/javascript">
            if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
        </script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- page specific plugin scripts -->
        <script src="assets/js/jquery-ui.custom.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>
    </body>
</html>

<?php } ?>
