<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama     = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

$kd_safe = mysqli_real_escape_string($koneksi, $kd_cabang);
$cari_cab = mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_safe'");
$tm_cab   = mysqli_fetch_array($cari_cab);
$nama_cabang = $tm_cab['nama_cabang'];

// Defensive column check — same pattern as penerimaan_antarcab.php
$has_order_ke = false;
$has_tipe_trx = false;
$col_check = mysqli_query($koneksi,"SHOW COLUMNS FROM tblorderjual_header");
while($col_check && $col = mysqli_fetch_assoc($col_check)){
    if($col['Field']=='order_ke')  $has_order_ke = true;
    if($col['Field']=='tipe_trx')  $has_tipe_trx = true;
}

$where_order_ke = $has_order_ke ? "AND oh.order_ke='$kd_safe'" : "";
$where_tipe_trx = $has_tipe_trx ? "AND oh.tipe_trx='Antar Cabang'" : "";

$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$where_status = ($filter=='semua') ? "" : "AND oh.status='0'";

$sql = "SELECT oh.no_order, oh.tanggal, oh.kd_cabang, oh.total_qty, oh.total_jual, oh.status, oh.note,
               cb.nama_cabang AS nama_pemesan
        FROM tblorderjual_header oh
        LEFT JOIN tbcabang cb ON cb.kode_cabang = oh.kd_cabang
        WHERE 1=1 $where_order_ke $where_tipe_trx $where_status
        ORDER BY oh.tanggal DESC, oh.no_order DESC";

$q_list = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta charset="utf-8"/>
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css"/>
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style"/>
    <link rel="stylesheet" href="assets/css/ace-skins.min.css"/>
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
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="Profil"/>
                        <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
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
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"
               data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Antar Cabang</a></li>
                    <li class="active">Tarik Data (Kirim)</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Tarik Data &amp; Kirim Barang
                        <small><i class="ace-icon fa fa-angle-double-right"></i> Pesanan masuk ke <?php echo htmlspecialchars($nama_cabang); ?></small>
                    </h1>
                </div>

                <div class="row" style="margin-bottom:10px;">
                    <div class="col-xs-12">
                        <a href="?status=pending" class="btn btn-sm <?php echo $filter=='pending'?'btn-primary':'btn-default'; ?>">
                            <i class="fa fa-clock-o"></i> Belum Diproses
                        </a>
                        <a href="?status=semua" class="btn btn-sm <?php echo $filter=='semua'?'btn-primary':'btn-default'; ?>">
                            <i class="fa fa-list"></i> Semua
                        </a>
                    </div>
                </div>

                <div class="widget-box">
                    <div class="widget-header">
                        <h4 class="widget-title"><i class="fa fa-inbox"></i> Daftar Pesanan Masuk</h4>
                        <div class="widget-toolbar">
                            <a href="pesanan_penjualan_cab_add.php" class="btn btn-xs btn-success">
                                <i class="fa fa-plus"></i> Buat Pesanan
                            </a>
                        </div>
                    </div>
                    <div class="widget-body">
                        <div class="widget-main no-padding">
                            <table class="table table-bordered table-hover table-condensed">
                                <thead>
                                    <tr class="active">
                                        <th>No Pesanan</th>
                                        <th>Tanggal</th>
                                        <th>Dari Cabang</th>
                                        <th class="text-right">Total Qty</th>
                                        <th class="text-right">Total Nilai</th>
                                        <th>Catatan</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(!$q_list || mysqli_num_rows($q_list)==0): ?>
                                    <tr><td colspan="8" class="text-center text-muted" style="padding:20px;">
                                        <i class="fa fa-inbox fa-2x"></i><br/>Tidak ada pesanan masuk
                                    </td></tr>
                                <?php else:
                                    while($row = mysqli_fetch_assoc($q_list)):
                                        $tgl_fmt   = date('d/m/Y', strtotime($row['tanggal']));
                                        $nilai_fmt = 'Rp '.number_format((float)$row['total_jual'],0,',','.');
                                        if($row['status']=='0'){
                                            $status_lbl = '<span class="label label-warning">Menunggu Proses</span>';
                                        } elseif($row['status']=='1'){
                                            $status_lbl = '<span class="label label-success">Sudah Diproses</span>';
                                        } else {
                                            $status_lbl = '<span class="label label-default">'.htmlspecialchars($row['status']).'</span>';
                                        }
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['no_order']); ?></strong></td>
                                        <td><?php echo $tgl_fmt; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_pemesan'] ?: $row['kd_cabang']); ?></td>
                                        <td class="text-right"><?php echo (int)$row['total_qty']; ?></td>
                                        <td class="text-right"><?php echo $nilai_fmt; ?></td>
                                        <td><?php echo htmlspecialchars($row['note']); ?></td>
                                        <td><?php echo $status_lbl; ?></td>
                                        <td class="text-center">
                                            <?php if($row['status']=='0'): ?>
                                            <a href="penjualan_cab_add_proses.php?nopesanan=<?php echo urlencode($row['no_order']); ?>"
                                               class="btn btn-xs btn-primary"
                                               onclick="return confirm('Proses pesanan <?php echo htmlspecialchars($row['no_order']); ?>?\nStok akan langsung berkurang.')">
                                                <i class="fa fa-truck"></i> Proses &amp; Kirim
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted"><i class="fa fa-check"></i> Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
