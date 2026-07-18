<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

    $cari_kd=mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];

    $key = isset($_POST['txtkey']) ? mysqli_real_escape_string($koneksi, trim($_POST['txtkey'])) : '';

    $where = "h.kd_cabang='$kd_cabang'";
    if($key !== '') {
        $where .= " AND (h.noretur LIKE '%$key%' OR h.no_service LIKE '%$key%' OR COALESCE(p.namapelanggan,'') LIKE '%$key%')";
    }

    $list = mysqli_query($koneksi, "SELECT h.*, COALESCE(p.namapelanggan, s.no_pelanggan) AS namapelanggan
                                     FROM tblretur_servis_header h
                                     LEFT JOIN tblservice s ON s.no_service=h.no_service AND s.kd_cabang=h.kd_cabang
                                     LEFT JOIN tblpelanggan p ON p.nopelanggan=s.no_pelanggan
                                     WHERE $where
                                     ORDER BY h.noretur DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
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
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script>try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script>try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li class="active">Retur Servis</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-undo"></i> Retur Servis</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form class="form-inline" action="" method="post">
                                            <div class="form-group">
                                                <label>Cari:&nbsp;</label>
                                                <input type="text" name="txtkey" class="form-control" value="<?php echo htmlspecialchars($key); ?>" placeholder="No retur / no service / nama pelanggan..." />
                                            </div>&nbsp;
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Cari
                                            </button>&nbsp;
                                            <a href="retur_servis.php" class="btn btn-default">
                                                <i class="fa fa-list"></i> Tampilkan Semua
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space space-8"></div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <a href="retur_servis_add.php">
                                <button class="btn btn-success btn-block" type="button">
                                    <i class="fa fa-plus"></i> Input Retur Servis
                                </button>
                            </a>
                        </div>
                    </div>
                    <div class="space space-8"></div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-body">
                                    <div class="widget-main" style="overflow-x:auto;">
                                        <table class="table table-bordered table-striped">
                                            <thead style="background:#5a7a35;color:#fff;">
                                                <tr>
                                                    <th class="center" width="4%">No</th>
                                                    <th width="14%">No. Retur</th>
                                                    <th width="14%">No. Service</th>
                                                    <th width="20%">Pelanggan</th>
                                                    <th width="10%">Tanggal</th>
                                                    <th align="right" width="14%">Total Refund</th>
                                                    <th width="10%" class="center">Status</th>
                                                    <th width="14%" class="center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $no=1; while($row = mysqli_fetch_assoc($list)): ?>
                                            <tr>
                                                <td class="center"><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['noretur']); ?></td>
                                                <td><?php echo htmlspecialchars($row['no_service']); ?></td>
                                                <td><?php echo htmlspecialchars($row['namapelanggan']); ?></td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['tanggal']))); ?></td>
                                                <td align="right"><?php echo number_format($row['total_retur'],0,',','.'); ?></td>
                                                <td class="center">
                                                    <?php echo ($row['status_retur']=='1') ? '<span class="label label-success">Selesai</span>' : '<span class="label label-warning">Proses</span>'; ?>
                                                </td>
                                                <td class="center">
                                                    <a href="retur_servis_detail.php?noretur=<?php echo urlencode($row['noretur']); ?>" class="btn btn-xs btn-info">
                                                        <i class="fa fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
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
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
<?php } ?>
