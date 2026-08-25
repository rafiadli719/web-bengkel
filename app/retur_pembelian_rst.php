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

    $txtkey  = isset($_GET['_key'])  ? mysqli_real_escape_string($koneksi, $_GET['_key'])  : '';
    $cbocari = isset($_GET['_cari']) ? mysqli_real_escape_string($koneksi, $_GET['_cari']) : '';

    $allowed_cols = ['noretur','nopembelian','namasupplier'];
    $where_key = "";
    if($txtkey != '') {
        if($cbocari != '' && in_array($cbocari, $allowed_cols)) {
            $where_key = "AND $cbocari LIKE '%$txtkey%'";
        } else {
            $where_key = "AND (noretur LIKE '%$txtkey%' OR nopembelian LIKE '%$txtkey%' OR namasupplier LIKE '%$txtkey%')";
        }
    }

    $sql_query = "SELECT *, DATE_FORMAT(tgl_retur,'%d/%m/%Y') AS tanggal_retur_fmt,
                         DATE_FORMAT(tgl_beli,'%d/%m/%Y') AS tanggal_beli_fmt
                  FROM view_retur_pembelian
                  WHERE kd_cabang='$kd_cabang' $where_key
                  ORDER BY tgl_retur DESC";

    $res_count = mysqli_query($koneksi, "SELECT count(*) as tot FROM view_retur_pembelian WHERE kd_cabang='$kd_cabang' $where_key");
    $row_count = mysqli_fetch_array($res_count);
    $tot = $row_count['tot'];
    $hasil_cari = "Ditemukan <strong>$tot</strong> data retur pembelian";
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
                        <li><a href="pembelian.php">Pembelian</a></li>
                        <li><a href="retur_pembelian.php">Retur Pembelian</a></li>
                        <li class="active">Hasil Pencarian</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <a href="retur_pembelian_add.php">
                                <button class="btn btn-success btn-block"><i class="fa fa-plus"></i> Input Retur Pembelian</button>
                            </a>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <a href="retur_pembelian.php">
                                <button class="btn btn-default btn-block"><i class="fa fa-search"></i> Cari Ulang</button>
                            </a>
                        </div>
                    </div>
                    <div class="space space-8"></div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header"><?php echo $hasil_cari; ?></div>
                            <table class="table table-bordered table-hover table-striped" id="dynamic-table">
                                <thead>
                                    <tr style="background:#f5f5f5;">
                                        <th class="center" width="5%">Aksi</th>
                                        <th width="12%">No. Retur</th>
                                        <th width="9%">Tgl Retur</th>
                                        <th width="12%">No. Pembelian</th>
                                        <th width="9%">Tgl Beli</th>
                                        <th width="22%">Nama Supplier</th>
                                        <th class="center" width="11%">Total Retur</th>
                                        <th class="center" width="7%">Status</th>
                                        <th width="13%">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sql = mysqli_query($koneksi, $sql_query);
                                while ($tampil = mysqli_fetch_array($sql)) {
                                    // Ambil total_akhir dari header
                                    $hdr = mysqli_query($koneksi, "SELECT total_akhir, total_qty_retur FROM tblretur_pembelian_header WHERE noretur='".mysqli_real_escape_string($koneksi,$tampil['noretur'])."'");
                                    $row_hdr = mysqli_fetch_array($hdr);
                                    $total_akhir = $row_hdr ? (float)$row_hdr['total_akhir'] : 0;
                                    $status_retur = $tampil['status_retur'];
                                    $status_label = ($status_retur == '1')
                                        ? '<span class="label label-success">Selesai</span>'
                                        : '<span class="label label-warning">Proses</span>';
                                ?>
                                <tr>
                                    <td class="center">
                                        <div class="btn-group">
                                            <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-yellow">
                                                Aksi <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-default">
                                                <li><a href="retur_pembelian_detail.php?noretur=<?php echo urlencode($tampil['noretur']); ?>">
                                                    <i class="fa fa-eye"></i> Detail</a></li>
                                                <li><a href="retur_pembelian_cetak.php?noretur=<?php echo urlencode($tampil['noretur']); ?>" target="_blank">
                                                    <i class="fa fa-print"></i> Cetak</a></li>
                                                <?php if($status_retur == '0'): ?>
                                                <li class="divider"></li>
                                                <li><a href="retur_pembelian_batal.php?noretur=<?php echo urlencode($tampil['noretur']); ?>"
                                                       onclick="return confirm('Hapus retur <?php echo htmlspecialchars($tampil['noretur']); ?>?')">
                                                    <i class="fa fa-trash-o text-danger"></i> <span class="text-danger">Hapus</span></a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($tampil['noretur']); ?></strong></td>
                                    <td class="center"><?php echo htmlspecialchars($tampil['tanggal_retur_fmt']); ?></td>
                                    <td><?php echo htmlspecialchars($tampil['nopembelian']); ?></td>
                                    <td class="center"><?php echo htmlspecialchars($tampil['tanggal_beli_fmt']); ?></td>
                                    <td><?php echo htmlspecialchars($tampil['namasupplier']); ?></td>
                                    <td align="right"><?php echo number_format($total_akhir, 0, ',', '.'); ?></td>
                                    <td class="center"><?php echo $status_label; ?></td>
                                    <td><?php echo htmlspecialchars($tampil['note']); ?></td>
                                </tr>
                                <?php } ?>
                                </tbody>
                            </table>
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
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
    jQuery(function($){
        $('#dynamic-table').DataTable({
            bAutoWidth: false,
            "aoColumns": [
                {"bSortable":false}, null, null, null, null, null, null, {"bSortable":false}, null
            ],
            "aaSorting": [],
            "iDisplayLength": 25
        });
    });
    </script>
</body>
</html>
<?php } ?>
