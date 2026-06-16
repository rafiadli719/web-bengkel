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

    // Cek hak akses - hanya admin pengadaan yang bisa full access, CS read only
    $is_admin_pengadaan = ($lvl_akses == 'admin' || $lvl_akses == 'pengadaan');
    $is_read_only = !$is_admin_pengadaan;

    // Handle search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_condition = '';
    if (!empty($search)) {
        $search_condition = "WHERE (merek LIKE '%$search%' OR kode_brand LIKE '%$search%') AND status = '1'";
    } else {
        $search_condition = "WHERE status = '1'";
    }

    // Get data merk motor
    $query = "SELECT * FROM tbpabrik_motor $search_condition ORDER BY merek ASC";
    $result = mysqli_query($koneksi, $query);

    // Handle success message from other pages
    $success_msg = '';
    if (isset($_SESSION['delete_success'])) {
        $success_msg = $_SESSION['delete_success'];
        unset($_SESSION['delete_success']);
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Master Merk Motor" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->

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

        <!-- inline styles related to this page -->

        <!-- ace settings handler -->
        <script src="assets/js/ace-extra.min.js"></script>

        <!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

        <!--[if lte IE 8]>
        <script src="assets/js/html5shiv.min.js"></script>
        <script src="assets/js/respond.min.js"></script>
        <![endif]-->
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
                            <td style="text-align:left; vertical-align:middle;">
                                <span style="color:red; font-weight:bold; font-size:12px;"><?php echo $nama_cabang; ?></span>
                            </td>
                        </tr>
                    </table>

                </div>

                <div class="navbar-buttons navbar-header pull-right" role="navigation">
                    <ul class="nav ace-nav">

                        <li class="grey dropdown-modal">
                            <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                <i class="ace-icon fa fa-tasks"></i>
                                <span class="badge badge-grey">4</span>
                            </a>

                            <ul class="dropdown-menu-right dropdown-navbar dropdown-menu dropdown-caret dropdown-close">
                                <li class="dropdown-header">
                                    <i class="ace-icon fa fa-check"></i>
                                    4 Tasks to complete
                                </li>

                                <li class="dropdown-content">
                                    <ul class="dropdown-menu dropdown-navbar">
                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">Software Update</span>
                                                    <span class="pull-right">65%</span>
                                                </div>

                                                <div class="progress progress-mini">
                                                    <div style="width:65%" class="progress-bar"></div>
                                                </div>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">Hardware Upgrade</span>
                                                    <span class="pull-right">35%</span>
                                                </div>

                                                <div class="progress progress-mini">
                                                    <div style="width:35%" class="progress-bar progress-bar-danger"></div>
                                                </div>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">Unit Testing</span>
                                                    <span class="pull-right">15%</span>
                                                </div>

                                                <div class="progress progress-mini">
                                                    <div style="width:15%" class="progress-bar progress-bar-warning"></div>
                                                </div>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">Bug Fixes</span>
                                                    <span class="pull-right">90%</span>
                                                </div>

                                                <div class="progress progress-mini progress-striped active">
                                                    <div style="width:90%" class="progress-bar progress-bar-success"></div>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <li class="dropdown-footer">
                                    <a href="#">
                                        See tasks with details
                                        <i class="ace-icon fa fa-arrow-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="purple dropdown-modal">
                            <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                <i class="ace-icon fa fa-bell icon-animated-bell"></i>
                                <span class="badge badge-important">8</span>
                            </a>

                            <ul class="dropdown-menu-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
                                <li class="dropdown-header">
                                    <i class="ace-icon fa fa-exclamation-triangle"></i>
                                    8 Notifications
                                </li>

                                <li class="dropdown-content">
                                    <ul class="dropdown-menu dropdown-navbar navbar-pink">
                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">
                                                        <i class="btn btn-xs no-hover btn-pink fa fa-comment"></i>
                                                        New Comments
                                                    </span>
                                                    <span class="pull-right badge badge-info">+12</span>
                                                </div>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#">
                                                <i class="btn btn-xs btn-primary fa fa-user"></i>
                                                Bob just signed up as an editor ...
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">
                                                        <i class="btn btn-xs no-hover btn-success fa fa-shopping-cart"></i>
                                                        New Orders
                                                    </span>
                                                    <span class="pull-right badge badge-success">+8</span>
                                                </div>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#">
                                                <div class="clearfix">
                                                    <span class="pull-left">
                                                        <i class="btn btn-xs no-hover btn-info fa fa-twitter"></i>
                                                        Followers
                                                    </span>
                                                    <span class="pull-right badge badge-info">+11</span>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <li class="dropdown-footer">
                                    <a href="#">
                                        See all notifications
                                        <i class="ace-icon fa fa-arrow-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="green dropdown-modal">
                            <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                <i class="ace-icon fa fa-envelope icon-animated-vertical"></i>
                                <span class="badge badge-success">5</span>
                            </a>

                            <ul class="dropdown-menu-right dropdown-navbar dropdown-menu dropdown-caret dropdown-close">
                                <li class="dropdown-header">
                                    <i class="ace-icon fa fa-envelope-o"></i>
                                    5 Messages
                                </li>

                                <li class="dropdown-content">
                                    <ul class="dropdown-menu dropdown-navbar">
                                        <li>
                                            <a href="#" class="clearfix">
                                                <img src="assets/images/avatars/avatar.png" class="msg-photo" alt="Alex's Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue">Alex:</span>
                                                        Ciao sociis natoque penatibus et auctor ...
                                                    </span>

                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span>a moment ago</span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#" class="clearfix">
                                                <img src="assets/images/avatars/avatar3.png" class="msg-photo" alt="Susan's Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue">Susan:</span>
                                                        Vestibulum id ligula porta felis euismod ...
                                                    </span>

                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span>20 minutes ago</span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#" class="clearfix">
                                                <img src="assets/images/avatars/avatar4.png" class="msg-photo" alt="Bob's Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue">Bob:</span>
                                                        Nullam quis risus eget urna mollis ornare ...
                                                    </span>

                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span>3:15 pm</span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#" class="clearfix">
                                                <img src="assets/images/avatars/avatar2.png" class="msg-photo" alt="Kate's Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue">Kate:</span>
                                                        Ciao sociis natoque penatibus et auctor ...
                                                    </span>

                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span>1:33 pm</span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="#" class="clearfix">
                                                <img src="assets/images/avatars/avatar5.png" class="msg-photo" alt="Fred's Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue">Fred:</span>
                                                        Vestibulum id ligula porta felis euismod ...
                                                    </span>

                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span>10:09 am</span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <li class="dropdown-footer">
                                    <a href="inbox.html">
                                        See all messages
                                        <i class="ace-icon fa fa-arrow-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="light-blue dropdown-modal">
                            <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                                <img class="nav-user-photo" src="<?php echo $foto_user; ?>" alt="<?php echo $_nama; ?>'s Photo" />
                                <span class="user-info">
                                    <small>Welcome,</small>
                                    <?php echo $_nama; ?>
                                </span>

                                <i class="ace-icon fa fa-caret-down"></i>
                            </a>

                            <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                                <li>
                                    <a href="profile.php">
                                        <i class="ace-icon fa fa-cog"></i>
                                        Settings
                                    </a>
                                </li>

                                <li>
                                    <a href="profile.html">
                                        <i class="ace-icon fa fa-user"></i>
                                        Profile
                                    </a>
                                </li>

                                <li class="divider"></li>

                                <li>
                                    <a href="../index.php">
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
                            <li class="active">Merk Motor</li>
                        </ul><!-- /.breadcrumb -->
                    </div>

                    <div class="page-content">

                        <div class="space space-8"></div>

                        <?php if ($success_msg): ?>
                        <div class="alert alert-success">
                            <button type="button" class="close" data-dismiss="alert">
                                <i class="ace-icon fa fa-times"></i>
                            </button>
                            <i class="fa fa-check"></i> <?php echo $success_msg; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_read_only): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Info:</strong> Anda hanya memiliki akses read-only untuk halaman ini.
                            Master Merk Motor hanya bisa diakses penuh oleh Admin Pengadaan.
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-xs-12 col-sm-12">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-table"></i>
                                            Master Merk Motor
                                        </h4>

                                        <span class="widget-toolbar">
                                            <a href="#" data-action="collapse">
                                                <i class="ace-icon fa fa-chevron-up"></i>
                                            </a>

                                            <a href="#" data-action="close">
                                                <i class="ace-icon fa fa-times"></i>
                                            </a>
                                        </span>
                                    </div>

                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <form class="form-horizontal" action="" method="get" role="form">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="col-sm-3 control-label no-padding-right">Cari:</label>
                                                            <div class="col-sm-9">
                                                                <div class="input-group">
                                                                    <input type="text" name="search" class="form-control"
                                                                           placeholder="Cari merk atau kode brand..."
                                                                           value="<?php echo htmlspecialchars($search); ?>">
                                                                    <span class="input-group-btn">
                                                                        <button class="btn btn-sm btn-primary" type="submit">
                                                                            <i class="fa fa-search"></i> Cari
                                                                        </button>
                                                                    </span>
                                                                </div>
                                                                <small class="help-inline">
                                                                    Sistem akan mencari yang memuat kata tersebut
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <div class="col-sm-12 text-right">
                                                                <?php if (!$is_read_only): ?>
                                                                <a href="master_merk_motor_add.php" class="btn btn-sm btn-success">
                                                                    <i class="fa fa-plus"></i> Input Baru
                                                                </a>
                                                                <?php endif; ?>
                                                                <?php if (!empty($search)): ?>
                                                                <a href="master_merk_motor.php" class="btn btn-sm btn-default">
                                                                    <i class="fa fa-list"></i> Lihat Semua
                                                                </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space space-8"></div>
                        <div class="row">
                            <div class="col-xs-12">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th class="center" width="8%">No</th>
                                            <th width="20%">Kode Merk</th>
                                            <th width="45%">Keterangan</th>
                                            <?php if (!$is_read_only): ?>
                                            <th class="center" width="27%">Aksi</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no++; ?></td>
                                            <td><span class="label label-info"><?php echo htmlspecialchars($row['kode_brand']); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['merek']); ?></td>
                                            <?php if (!$is_read_only): ?>
                                            <td class="center">
                                                <a href="master_merk_motor_edit.php?id=<?php echo $row['id']; ?>"
                                                   class="btn btn-xs btn-info" title="Edit">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                                <a href="master_merk_motor_del.php?id=<?php echo $row['id']; ?>"
                                                   class="btn btn-xs btn-danger" title="Hapus"
                                                   onclick="return confirm('Yakin ingin menghapus merk motor ini?\\n\\nMerk bisa dihapus jika belum ada transaksi.')">
                                                    <i class="fa fa-trash-o"></i> Hapus
                                                </a>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="<?php echo $is_read_only ? '3' : '4'; ?>" class="center">
                                                <em>
                                                    <?php if (!empty($search)): ?>
                                                        Tidak ada data merk motor yang sesuai dengan pencarian "<?php echo htmlspecialchars($search); ?>"
                                                    <?php else: ?>
                                                        Belum ada data merk motor
                                                    <?php endif; ?>
                                                </em>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Info Footer -->
                        <div class="space space-8"></div>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="alert alert-info">
                                    <h5><strong>Ketentuan Master Merk Motor:</strong></h5>
                                    <ul style="margin-left: 20px; margin-bottom: 0;">
                                        <li>Kode Merk berisi 2 digit (1 huruf + 1 karakter titik), contoh: H., Y., S., K.</li>
                                        <li>Keterangan diisi bebas sesuai arti kode merk</li>
                                        <li>Tidak boleh ada kode yang sama persis</li>
                                        <li>Merk bisa dihapus jika belum ada transaksi</li>
                                        <li>Master ini hanya bisa diakses penuh oleh Admin Pengadaan</li>
                                    </ul>
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
                            <span class="blue bolder">Ace</span>
                            Application &copy; 2013-2014
                        </span>

                        &nbsp; &nbsp;
                        <span class="action-buttons">
                            <a href="#">
                                <i class="ace-icon fa fa-twitter-square light-blue bigger-150"></i>
                            </a>

                            <a href="#">
                                <i class="ace-icon fa fa-facebook-square text-primary bigger-150"></i>
                            </a>

                            <a href="#">
                                <i class="ace-icon fa fa-rss-square orange bigger-150"></i>
                            </a>
                        </span>
                    </div>
                </div>
            </div>

            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
                <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
            </a>
        </div><!-- /.main-container -->

        <!-- basic scripts -->

        <!--[if !IE]> -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>

        <!-- <![endif]-->

        <!--[if IE]>
        <script src="assets/js/jquery-1.11.3.min.js"></script>
        <![endif]-->
        <script type="text/javascript">
            if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
        </script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- page specific plugin scripts -->

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <!-- inline scripts related to this page -->
        <script type="text/javascript">
            $(document).ready(function() {
                // Auto focus pada search box jika kosong
                <?php if (empty($search)): ?>
                $('input[name="search"]').focus();
                <?php endif; ?>

                // Highlight search text
                <?php if (!empty($search)): ?>
                var searchText = '<?php echo addslashes($search); ?>';
                $('tbody td').each(function() {
                    var text = $(this).html();
                    var regex = new RegExp('(' + searchText + ')', 'gi');
                    var newText = text.replace(regex, '<mark>$1</mark>');
                    $(this).html(newText);
                });
                <?php endif; ?>
            });
        </script>
    </body>
</html>
<?php } ?>