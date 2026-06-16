<?php
/**
 * RENCANA ORDER DETAIL - Halaman Detail Rencana Order
 * Menampilkan detail item dalam rencana order
 */
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user=$_SESSION['_iduser'];
$kd_cabang=$_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_user'];
$foto_user=$tm_cari['foto_user'];
if($foto_user=='') {
    $foto_user="file_upload/avatar.png";
}

// Get no_rencana from URL
$no_rencana = isset($_GET['no']) ? mysqli_real_escape_string($koneksi, $_GET['no']) : '';
$created = isset($_GET['created']) ? true : false;

if (empty($no_rencana)) {
    header("location:rencana_order.php");
    exit;
}

// Get header data
$sql_header = "SELECT * FROM tblrencana_order_header WHERE no_rencana = '$no_rencana'";
$result_header = mysqli_query($koneksi, $sql_header);

if (!$result_header || mysqli_num_rows($result_header) == 0) {
    header("location:rencana_order.php?error=notfound");
    exit;
}

$header = mysqli_fetch_assoc($result_header);

// Get detail data
$sql_detail = "SELECT d.*,
                      COALESCE(i.namaitem, d.nama_item) as nama_item_display,
                      i.jenis as jenis_item
               FROM tblrencana_order_detail d
               LEFT JOIN tblitem i ON d.no_item = (i.noitem COLLATE utf8mb4_general_ci)
               WHERE d.no_rencana = '$no_rencana'
               ORDER BY d.no_item";
$result_detail = mysqli_query($koneksi, $sql_detail);

// Get summary by supplier
$sql_supplier1 = "SELECT order1_supplier_final as supplier,
                         COUNT(*) as jml_item,
                         SUM(order1_qty_final) as total_qty,
                         SUM(order1_nilai) as total_nilai
                  FROM tblrencana_order_detail
                  WHERE no_rencana = '$no_rencana' AND order1_qty_final > 0
                  GROUP BY order1_supplier_final";
$result_supplier1 = mysqli_query($koneksi, $sql_supplier1);

$sql_supplier2 = "SELECT order2_supplier_final as supplier,
                         COUNT(*) as jml_item,
                         SUM(order2_qty_final) as total_qty,
                         SUM(order2_nilai) as total_nilai
                  FROM tblrencana_order_detail
                  WHERE no_rencana = '$no_rencana' AND order2_qty_final > 0
                  GROUP BY order2_supplier_final";
$result_supplier2 = mysqli_query($koneksi, $sql_supplier2);

// Get cabang distribution
$sql_cabang = "SELECT dc.kd_cabang, c.nama_cabang,
                      SUM(dc.stok_awal) as total_stok,
                      SUM(dc.order_qty) as total_order,
                      COUNT(DISTINCT dc.no_item) as jml_item
               FROM tblrencana_order_detail_cabang dc
               LEFT JOIN tbcabang c ON dc.kd_cabang = c.kode_cabang
               WHERE dc.no_rencana = '$no_rencana'
               GROUP BY dc.kd_cabang, c.nama_cabang";
$result_cabang = mysqli_query($koneksi, $sql_cabang);

// Badge class based on status
$badge_class = 'label-info';
switch ($header['status']) {
    case 'approved': $badge_class = 'label-success'; break;
    case 'ordered': $badge_class = 'label-primary'; break;
    case 'partial': $badge_class = 'label-warning'; break;
    case 'completed': $badge_class = 'label-default'; break;
}
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

    <style>
        .info-box { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .info-box h4 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .summary-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
        }
        .summary-card h3 { margin: 0; font-size: 24px; }
        .summary-card p { margin: 5px 0 0 0; color: #666; }
        .order1-col { background-color: #dff0d8; }
        .order2-col { background-color: #d9edf7; }
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
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
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
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li><a href="rencana_order.php">Rencana Order</a></li>
                        <li class="active">Detail <?php echo htmlspecialchars($no_rencana); ?></li>
                    </ul>
                </div>

                <div class="page-content">

                    <?php if ($created) { ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i>
                        <strong>Berhasil!</strong> Rencana Order <strong><?php echo htmlspecialchars($no_rencana); ?></strong> telah berhasil dibuat.
                    </div>
                    <?php } ?>

                    <div class="page-header">
                        <h1>
                            Detail Rencana Order
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                <?php echo htmlspecialchars($no_rencana); ?>
                            </small>
                        </h1>
                    </div>

                    <!-- Header Info -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="info-box">
                                <h4><i class="fa fa-info-circle"></i> Informasi Rencana Order</h4>
                                <table class="table table-condensed">
                                    <tr>
                                        <td width="40%"><strong>No. Rencana</strong></td>
                                        <td><?php echo htmlspecialchars($header['no_rencana']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal</strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($header['tanggal'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Periode Analisis</strong></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($header['periode_awal'])); ?> -
                                            <?php echo date('d/m/Y', strtotime($header['periode_akhir'])); ?>
                                            <small>(84 hari)</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td><span class="label <?php echo $badge_class; ?>"><?php echo strtoupper($header['status']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat Oleh</strong></td>
                                        <td><?php echo htmlspecialchars($header['created_by']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Dibuat</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($header['created_at'])); ?></td>
                                    </tr>
                                    <?php if ($header['approved_by']) { ?>
                                    <tr>
                                        <td><strong>Diapprove Oleh</strong></td>
                                        <td><?php echo htmlspecialchars($header['approved_by']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Approve</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($header['approved_at'])); ?></td>
                                    </tr>
                                    <?php } ?>
                                </table>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-xs-4">
                                    <div class="summary-card">
                                        <h3 class="text-primary"><?php echo number_format($header['total_item']); ?></h3>
                                        <p>Total Item</p>
                                    </div>
                                </div>
                                <div class="col-xs-4">
                                    <div class="summary-card">
                                        <h3 class="text-info"><?php echo number_format($header['total_qty']); ?></h3>
                                        <p>Total Qty</p>
                                    </div>
                                </div>
                                <div class="col-xs-4">
                                    <div class="summary-card">
                                        <h3 class="text-success"><?php echo number_format($header['total_nilai'], 0, ',', '.'); ?></h3>
                                        <p>Total Nilai (Rp)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="info-box">
                                <h4><i class="fa fa-cogs"></i> Aksi</h4>
                                <div class="btn-group-vertical btn-group-sm" style="width: 100%;">
                                    <?php if ($header['status'] == 'draft') { ?>
                                    <a href="rencana_order_edit.php?no=<?php echo urlencode($no_rencana); ?>" class="btn btn-warning">
                                        <i class="fa fa-edit"></i> Edit Rencana
                                    </a>
                                    <a href="rencana_order_approve.php?no=<?php echo urlencode($no_rencana); ?>" class="btn btn-success">
                                        <i class="fa fa-check"></i> Approve Rencana
                                    </a>
                                    <a href="rencana_order_delete.php?no=<?php echo urlencode($no_rencana); ?>" class="btn btn-danger"
                                       onclick="return confirm('Yakin ingin menghapus rencana order ini?');">
                                        <i class="fa fa-trash"></i> Hapus Rencana
                                    </a>
                                    <?php } ?>
                                    <?php if ($header['status'] == 'approved') { ?>
                                    <a href="po_from_rencana.php?no=<?php echo urlencode($no_rencana); ?>" class="btn btn-primary">
                                        <i class="fa fa-shopping-cart"></i> Buat PO
                                    </a>
                                    <?php } ?>
                                    <a href="rencana_order_print.php?no=<?php echo urlencode($no_rencana); ?>" class="btn btn-default" target="_blank">
                                        <i class="fa fa-print"></i> Print
                                    </a>
                                    <a href="rencana_order.php" class="btn btn-default">
                                        <i class="fa fa-arrow-left"></i> Kembali ke List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space space-8"></div>

                    <!-- Summary per Supplier -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="fa fa-truck"></i> ORDER 1 (Tempo &le; 14 hari)
                                    </h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-striped table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th class="center">Jml Item</th>
                                                    <th class="center">Total Qty</th>
                                                    <th class="right">Total Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $total_order1 = 0;
                                                while ($row = mysqli_fetch_assoc($result_supplier1)) {
                                                    $total_order1 += $row['total_nilai'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                                                    <td class="center"><?php echo number_format($row['jml_item']); ?></td>
                                                    <td class="center"><?php echo number_format($row['total_qty']); ?></td>
                                                    <td class="right">Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?></td>
                                                </tr>
                                                <?php } ?>
                                                <tr class="info">
                                                    <td colspan="3"><strong>Total ORDER 1</strong></td>
                                                    <td class="right"><strong>Rp <?php echo number_format($total_order1, 0, ',', '.'); ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5 class="widget-title">
                                        <i class="fa fa-truck"></i> ORDER 2 (Tempo &gt; 14 hari)
                                    </h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-striped table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th class="center">Jml Item</th>
                                                    <th class="center">Total Qty</th>
                                                    <th class="right">Total Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $total_order2 = 0;
                                                while ($row = mysqli_fetch_assoc($result_supplier2)) {
                                                    $total_order2 += $row['total_nilai'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                                                    <td class="center"><?php echo number_format($row['jml_item']); ?></td>
                                                    <td class="center"><?php echo number_format($row['total_qty']); ?></td>
                                                    <td class="right">Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?></td>
                                                </tr>
                                                <?php } ?>
                                                <tr class="info">
                                                    <td colspan="3"><strong>Total ORDER 2</strong></td>
                                                    <td class="right"><strong>Rp <?php echo number_format($total_order2, 0, ',', '.'); ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space space-8"></div>

                    <!-- Detail Items -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="fa fa-list"></i> Detail Item
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover">
                                                <thead>
                                                    <tr class="info">
                                                        <th class="center" width="3%">No</th>
                                                        <th width="10%">No Item</th>
                                                        <th width="20%">Nama Item</th>
                                                        <th width="6%">Jenis</th>
                                                        <th class="right" width="10%">Harga</th>
                                                        <th class="center order1-col" width="6%">Qty 1</th>
                                                        <th class="order1-col" width="10%">Supplier 1</th>
                                                        <th class="right order1-col" width="10%">Nilai 1</th>
                                                        <th class="center order2-col" width="6%">Qty 2</th>
                                                        <th class="order2-col" width="10%">Supplier 2</th>
                                                        <th class="right order2-col" width="10%">Nilai 2</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    $grand_total = 0;
                                                    while ($row = mysqli_fetch_assoc($result_detail)) {
                                                        $subtotal = $row['order1_nilai'] + $row['order2_nilai'];
                                                        $grand_total += $subtotal;
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no++; ?></td>
                                                        <td><strong><?php echo htmlspecialchars($row['no_item']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($row['nama_item_display']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['jenis'] ?? $row['jenis_item']); ?></td>
                                                        <td class="right"><?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                                        <td class="center order1-col"><?php echo number_format($row['order1_qty_final']); ?></td>
                                                        <td class="order1-col"><?php echo htmlspecialchars($row['order1_supplier_final']); ?></td>
                                                        <td class="right order1-col"><?php echo number_format($row['order1_nilai'], 0, ',', '.'); ?></td>
                                                        <td class="center order2-col"><?php echo number_format($row['order2_qty_final']); ?></td>
                                                        <td class="order2-col"><?php echo htmlspecialchars($row['order2_supplier_final']); ?></td>
                                                        <td class="right order2-col"><?php echo number_format($row['order2_nilai'], 0, ',', '.'); ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="warning">
                                                        <td colspan="7" class="right"><strong>GRAND TOTAL</strong></td>
                                                        <td class="right"><strong>Rp <?php echo number_format($total_order1, 0, ',', '.'); ?></strong></td>
                                                        <td colspan="2"></td>
                                                        <td class="right"><strong>Rp <?php echo number_format($total_order2, 0, ',', '.'); ?></strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($result_cabang && mysqli_num_rows($result_cabang) > 0) { ?>
                    <div class="space space-8"></div>

                    <!-- Distribution per Cabang -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="fa fa-building"></i> Distribusi per Cabang
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr class="info">
                                                    <th>Kode Cabang</th>
                                                    <th>Nama Cabang</th>
                                                    <th class="center">Jml Item</th>
                                                    <th class="center">Total Stok Awal</th>
                                                    <th class="center">Total Order</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($result_cabang)) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['kd_cabang']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['nama_cabang']); ?></td>
                                                    <td class="center"><?php echo number_format($row['jml_item']); ?></td>
                                                    <td class="center"><?php echo number_format($row['total_stok']); ?></td>
                                                    <td class="center"><?php echo number_format($row['total_order']); ?></td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <?php if (!empty($header['note'])) { ?>
                    <div class="space space-8"></div>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-info">
                                <strong>Catatan:</strong> <?php echo nl2br(htmlspecialchars($header['note'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <span class="bigger-120">
                        <span class="blue bolder"><?php include "../lib/subtitel.php"; ?></span>
                        &copy; <?php echo date('Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div><!-- /.main-container -->

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
