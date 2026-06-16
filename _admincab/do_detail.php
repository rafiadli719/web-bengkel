<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

$no_do = isset($_GET['no_do']) ? trim($_GET['no_do']) : '';
if($no_do===''){
    header('Location: do_list.php');
    exit;
}

// User info
$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

// Header DO
$qh = mysqli_query($koneksi, "SELECT doh.*, s.namasupplier, s.alamat as alamat_supp 
                             FROM tbldelivery_order_header doh 
                             LEFT JOIN tblsupplier s ON s.nosupplier=doh.no_supplier 
                             WHERE doh.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");
$h = mysqli_fetch_assoc($qh);
if(!$h){
    echo '<script>alert("DO tidak ditemukan."); window.location="do_list.php";</script>';
    exit;
}

// Detail DO
$qd = mysqli_query($koneksi, "SELECT d.*, i.namaitem 
                             FROM tbldelivery_order_detail d 
                             LEFT JOIN tblitem i ON i.noitem=d.no_item 
                             WHERE d.no_do='".mysqli_real_escape_string($koneksi,$no_do)."' 
                             ORDER BY d.nobaris, d.id ASC");
$rows = [];
while($r = mysqli_fetch_assoc($qd)){ $rows[] = $r; }
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
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-content { margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state no-print">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <div class="navbar-header pull-left">
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i><?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small><?php echo $_nama; ?></span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                        <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i>Change Password</a></li>
                        <li><a href="profile.php"><i class="ace-icon fa fa-user"></i>Profile</a></li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
    <div id="sidebar" class="sidebar responsive ace-save-state no-print">
        <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
        <?php include "menu_dashboard.php"; ?>
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state no-print" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Pembelian</a></li>
                    <li><a href="do_list.php">Daftar Delivery Order</a></li>
                    <li class="active">Detail DO</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <div class="clearfix no-print" style="margin-bottom:10px;">
                        <a class="btn btn-default" href="do_list.php"><i class="fa fa-arrow-left"></i> Kembali</a>
                        <a class="btn btn-success" href="pembelian_add.php?do=<?php echo urlencode($no_do); ?>"><i class="fa fa-credit-card"></i> Buat Invoice</a>
                        <button class="btn btn-info" onclick="window.print()"><i class="fa fa-print"></i> Cetak</button>
                    </div>

                    <div class="widget-box">
                        <div class="widget-header widget-header-blue widget-header-flat">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-truck"></i> Delivery Order #<?php echo htmlspecialchars($h['no_do']); ?></h4>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <table class="table table-bordered">
                                <tr>
                                    <td width="20%" bgcolor="beige">No. DO</td>
                                    <td width="30%"><?php echo htmlspecialchars($h['no_do']); ?></td>
                                    <td width="20%" bgcolor="beige">Status</td>
                                    <td width="30%"><?php echo htmlspecialchars($h['status_do']); ?></td>
                                </tr>
                                <tr>
                                    <td bgcolor="beige">No. PO</td>
                                    <td><?php echo htmlspecialchars($h['no_po']); ?></td>
                                    <td bgcolor="beige">Tanggal DO</td>
                                    <td><?php echo htmlspecialchars($h['tanggal_do']); ?></td>
                                </tr>
                                <tr>
                                    <td bgcolor="beige">Supplier</td>
                                    <td><?php echo htmlspecialchars($h['no_supplier']); ?> - <?php echo htmlspecialchars($h['namasupplier']); ?></td>
                                    <td bgcolor="beige">Total Qty</td>
                                    <td><?php echo (int)$h['total_qty']; ?></td>
                                </tr>
                                <tr>
                                    <td bgcolor="beige">Alamat Kirim</td>
                                    <td colspan="3"><?php echo htmlspecialchars($h['alamat_kirim']); ?></td>
                                </tr>
                            </table>

                            <div class="table-header">Item DO</div>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th style="width:15%">Kode Item</th>
                                        <th>Nama Item</th>
                                        <th class="text-right" style="width:10%">Qty PO</th>
                                        <th class="text-right" style="width:10%">Qty Kirim</th>
                                        <th class="text-right" style="width:10%">Qty Terima</th>
                                        <th class="text-right" style="width:10%">Qty Reject</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach($rows as $r){ $i++; ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td><?php echo htmlspecialchars($r['no_item']); ?></td>
                                        <td><?php echo htmlspecialchars($r['namaitem']); ?></td>
                                        <td class="text-right"><?php echo (int)$r['qty_po']; ?></td>
                                        <td class="text-right"><?php echo (int)$r['qty_kirim']; ?></td>
                                        <td class="text-right"><?php echo (int)$r['qty_terima']; ?></td>
                                        <td class="text-right"><?php echo (int)$r['qty_reject']; ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div></div>
                    </div>
                </div></div>
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
