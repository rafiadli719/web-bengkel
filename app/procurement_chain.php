<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";
$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".$id_user."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

function getv($k,$d=''){ return isset($_GET[$k])?$_GET[$k]:$d; }
$esc_cbg = mysqli_real_escape_string($koneksi, $kd_cabang);

$doc = trim(getv('doc',''));
$doc_type = '';
$chain = [
  'pr' => null,
  'po' => [],
  'do' => [],
  'pembelian' => []
];

if($doc !== ''){
    $doc_esc = mysqli_real_escape_string($koneksi, $doc);
    // Try PR
    $pr_h = null;
    $qpr = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_header WHERE kd_cabang='".$esc_cbg."' AND no_pr='".$doc_esc."' LIMIT 1");
    if($qpr && ($pr_h = mysqli_fetch_assoc($qpr))){
        $doc_type = 'PR';
        $chain['pr'] = $pr_h;
        // Load POs from PR
        $qpo = mysqli_query($koneksi, "SELECT h.no_order, h.tanggal, h.no_supplier, h.no_pr FROM tblorder_header h WHERE h.kd_cabang='".$esc_cbg."' AND h.no_pr='".$doc_esc."' ORDER BY h.tanggal DESC, h.no_order DESC");
        $po_nos = [];
        while($qpo && ($row = mysqli_fetch_assoc($qpo))){ $chain['po'][] = $row; $po_nos[] = $row['no_order']; }
        // Load DOs from POs
        if(!empty($po_nos)){
            $in = "'".implode("','", array_map(function($s) use($koneksi){return mysqli_real_escape_string($koneksi,$s);}, $po_nos))."'";
            $qdo = mysqli_query($koneksi, "SELECT no_do, no_po, tanggal_do, total_qty, status_do FROM tbldelivery_order_header WHERE kd_cabang='".$esc_cbg."' AND no_po IN (".$in.") ORDER BY tanggal_do DESC, no_do DESC");
            $do_nos = [];
            while($qdo && ($row = mysqli_fetch_assoc($qdo))){ $chain['do'][] = $row; $do_nos[] = $row['no_do']; }
            // Load Pembelian by DO or by PO
            if(!empty($do_nos)){
                $in2 = "'".implode("','", array_map(function($s) use($koneksi){return mysqli_real_escape_string($koneksi,$s);}, $do_nos))."'";
                $qpb = mysqli_query($koneksi, "SELECT notransaksi, no_do, no_order, tanggal, total_akhir FROM tblpembelian_header WHERE kd_cabang='".$esc_cbg."' AND no_do IN (".$in2.") ORDER BY tanggal DESC, notransaksi DESC");
                while($qpb && ($row = mysqli_fetch_assoc($qpb))){ $chain['pembelian'][] = $row; }
            }
            if(!empty($po_nos)){
                $in3 = "'".implode("','", array_map(function($s) use($koneksi){return mysqli_real_escape_string($koneksi,$s);}, $po_nos))."'";
                $qpb2 = mysqli_query($koneksi, "SELECT notransaksi, no_do, no_order, tanggal, total_akhir FROM tblpembelian_header WHERE kd_cabang='".$esc_cbg."' AND no_order IN (".$in3.") ORDER BY tanggal DESC, notransaksi DESC");
                while($qpb2 && ($row = mysqli_fetch_assoc($qpb2))){ $chain['pembelian'][] = $row; }
            }
        }
    }
    // Try PO if still empty
    if($doc_type==''){
        $qpo = mysqli_query($koneksi, "SELECT * FROM tblorder_header WHERE kd_cabang='".$esc_cbg."' AND no_order='".$doc_esc."' LIMIT 1");
        if($qpo && ($po_h = mysqli_fetch_assoc($qpo))){
            $doc_type = 'PO';
            // Link back PR
            if(!empty($po_h['no_pr'])){
                $qpr2 = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_header WHERE kd_cabang='".$esc_cbg."' AND no_pr='".mysqli_real_escape_string($koneksi, $po_h['no_pr'])."' LIMIT 1");
                if($qpr2 && ($pr_h2 = mysqli_fetch_assoc($qpr2))){ $chain['pr'] = $pr_h2; }
            }
            $chain['po'][] = $po_h;
            // DOs from this PO
            $qdo = mysqli_query($koneksi, "SELECT no_do, no_po, tanggal_do, total_qty, status_do FROM tbldelivery_order_header WHERE kd_cabang='".$esc_cbg."' AND no_po='".$doc_esc."' ORDER BY tanggal_do DESC, no_do DESC");
            $do_nos = [];
            while($qdo && ($row = mysqli_fetch_assoc($qdo))){ $chain['do'][] = $row; $do_nos[] = $row['no_do']; }
            // Pembelian from DO and PO
            if(!empty($do_nos)){
                $in2 = "'".implode("','", array_map(function($s) use($koneksi){return mysqli_real_escape_string($koneksi,$s);}, $do_nos))."'";
                $qpb = mysqli_query($koneksi, "SELECT notransaksi, no_do, no_order, tanggal, total_akhir FROM tblpembelian_header WHERE kd_cabang='".$esc_cbg."' AND no_do IN (".$in2.") ORDER BY tanggal DESC, notransaksi DESC");
                while($qpb && ($row = mysqli_fetch_assoc($qpb))){ $chain['pembelian'][] = $row; }
            }
            $qpb2 = mysqli_query($koneksi, "SELECT notransaksi, no_do, no_order, tanggal, total_akhir FROM tblpembelian_header WHERE kd_cabang='".$esc_cbg."' AND no_order='".$doc_esc."' ORDER BY tanggal DESC, notransaksi DESC");
            while($qpb2 && ($row = mysqli_fetch_assoc($qpb2))){ $chain['pembelian'][] = $row; }
        }
    }
    // Try DO
    if($doc_type==''){
        $qdo = mysqli_query($koneksi, "SELECT * FROM tbldelivery_order_header WHERE kd_cabang='".$esc_cbg."' AND no_do='".$doc_esc."' LIMIT 1");
        if($qdo && ($do_h = mysqli_fetch_assoc($qdo))){
            $doc_type = 'DO';
            $chain['do'][] = $do_h;
            // Link back PO
            if(!empty($do_h['no_po'])){
                $qpo2 = mysqli_query($koneksi, "SELECT * FROM tblorder_header WHERE kd_cabang='".$esc_cbg."' AND no_order='".mysqli_real_escape_string($koneksi,$do_h['no_po'])."' LIMIT 1");
                if($qpo2 && ($po_h2 = mysqli_fetch_assoc($qpo2))){
                    $chain['po'][] = $po_h2;
                    if(!empty($po_h2['no_pr'])){
                        $qpr3 = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_header WHERE kd_cabang='".$esc_cbg."' AND no_pr='".mysqli_real_escape_string($koneksi,$po_h2['no_pr'])."' LIMIT 1");
                        if($qpr3 && ($pr_h3 = mysqli_fetch_assoc($qpr3))){
                            $chain['pr'] = $pr_h3;
                        }
                    }
                }
            }
            // Pembelian by DO
            $qpb = mysqli_query($koneksi, "SELECT notransaksi, no_do, no_order, tanggal, total_akhir FROM tblpembelian_header WHERE kd_cabang='".$esc_cbg."' AND no_do='".$doc_esc."' ORDER BY tanggal DESC, notransaksi DESC");
            while($qpb && ($row = mysqli_fetch_assoc($qpb))){ $chain['pembelian'][] = $row; }
        }
    }
    // Try Pembelian
    if($doc_type==''){
        $qpb = mysqli_query($koneksi, "SELECT * FROM tblpembelian_header WHERE kd_cabang='".$esc_cbg."' AND notransaksi='".$doc_esc."' LIMIT 1");
        if($qpb && ($pb_h = mysqli_fetch_assoc($qpb))){
            $doc_type = 'INV';
            $chain['pembelian'][] = $pb_h;
            // Backtrack DO
            if(!empty($pb_h['no_do'])){
                $qdo2 = mysqli_query($koneksi, "SELECT * FROM tbldelivery_order_header WHERE kd_cabang='".$esc_cbg."' AND no_do='".mysqli_real_escape_string($koneksi,$pb_h['no_do'])."' LIMIT 1");
                if($qdo2 && ($do_h2 = mysqli_fetch_assoc($qdo2))){
                    $chain['do'][] = $do_h2;
                    if(!empty($do_h2['no_po'])){
                        $qpo3 = mysqli_query($koneksi, "SELECT * FROM tblorder_header WHERE kd_cabang='".$esc_cbg."' AND no_order='".mysqli_real_escape_string($koneksi,$do_h2['no_po'])."' LIMIT 1");
                        if($qpo3 && ($po_h3 = mysqli_fetch_assoc($qpo3))){
                            $chain['po'][] = $po_h3;
                            if(!empty($po_h3['no_pr'])){
                                $qpr4 = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_header WHERE kd_cabang='".$esc_cbg."' AND no_pr='".mysqli_real_escape_string($koneksi,$po_h3['no_pr'])."' LIMIT 1");
                                if($qpr4 && ($pr_h4 = mysqli_fetch_assoc($qpr4))){
                                    $chain['pr'] = $pr_h4;
                                }
                            }
                        }
                    }
                }
            }
            // Backtrack PO via no_order
            if(empty($chain['po']) && !empty($pb_h['no_order'])){
                $qpo4 = mysqli_query($koneksi, "SELECT * FROM tblorder_header WHERE kd_cabang='".$esc_cbg."' AND no_order='".mysqli_real_escape_string($koneksi,$pb_h['no_order'])."' LIMIT 1");
                if($qpo4 && ($po_h4 = mysqli_fetch_assoc($qpo4))){
                    $chain['po'][] = $po_h4;
                    if(!empty($po_h4['no_pr'])){
                        $qpr5 = mysqli_query($koneksi, "SELECT * FROM tblpurchase_request_header WHERE kd_cabang='".$esc_cbg."' AND no_pr='".mysqli_real_escape_string($koneksi,$po_h4['no_pr'])."' LIMIT 1");
                        if($qpr5 && ($pr_h5 = mysqli_fetch_assoc($qpr5))){
                            $chain['pr'] = $pr_h5;
                        }
                    }
                }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
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
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
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
                    <li><a href="#">Pembelian</a></li>
                    <li class="active">Document Chain Viewer</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <form class="form-inline" method="get">
                        <div class="form-group">
                            <label>Nomor Dokumen</label>
                            <input type="text" name="doc" class="form-control" placeholder="No PR/PO/DO/INV" value="<?php echo htmlspecialchars($doc); ?>" />
                        </div>
                        <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Cari</button>
                    </form>
                    <hr/>
                    <?php if($doc!==''){ ?>
                        <div class="alert alert-info">Hasil untuk: <strong><?php echo htmlspecialchars($doc); ?></strong> <?php echo $doc_type? '(Terdeteksi: '.$doc_type.')':''; ?></div>
                    <?php } ?>

                    <?php if($chain['pr']){ $h=$chain['pr']; ?>
                    <div class="panel panel-info">
                        <div class="panel-heading"><i class="fa fa-file-text-o"></i> Purchase Request: <strong><?php echo htmlspecialchars($h['no_pr']); ?></strong></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div><b>Tanggal</b>: <?php echo htmlspecialchars($h['tanggal_pr']); ?></div>
                                    <div><b>Status</b>: <?php echo htmlspecialchars($h['status_pr']); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div><b>Requester</b>: <?php echo htmlspecialchars($h['requester']); ?></div>
                                    <div><b>Departemen</b>: <?php echo htmlspecialchars($h['departemen']); ?></div>
                                </div>
                            </div>
                            <div class="text-right" style="margin-top:10px;">
                                <a class="btn btn-minier btn-primary" href="pr_add.php?no_pr=<?php echo urlencode($h['no_pr']); ?>">Buka PR</a>
                                <a class="btn btn-minier btn-success" href="pesanan_pembelian_add.php?pr=<?php echo urlencode($h['no_pr']); ?>">Buat PO</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="panel panel-primary">
                        <div class="panel-heading"><i class="fa fa-shopping-cart"></i> Purchase Order</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No. PO</th>
                                        <th>Tanggal</th>
                                        <th>Supplier</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(!empty($chain['po'])){ foreach($chain['po'] as $r){ ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['no_order']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                                        <td><?php echo htmlspecialchars($r['no_supplier']); ?></td>
                                        <td>
                                            <a class="btn btn-minier btn-primary" href="do_from_po.php?no_po=<?php echo urlencode($r['no_order']); ?>">Buka PO</a>
                                        </td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel panel-success">
                        <div class="panel-heading"><i class="fa fa-truck"></i> Delivery Order</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No. DO</th>
                                        <th>No. PO</th>
                                        <th>Tanggal</th>
                                        <th>Total Qty</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(!empty($chain['do'])){ foreach($chain['do'] as $r){ ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['no_do']); ?></td>
                                        <td><?php echo htmlspecialchars($r['no_po']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal_do']); ?></td>
                                        <td class="text-right"><?php echo (int)$r['total_qty']; ?></td>
                                        <td><?php echo htmlspecialchars($r['status_do']); ?></td>
                                        <td>
                                            <a class="btn btn-minier btn-primary" href="pembelian_add.php?do=<?php echo urlencode($r['no_do']); ?>">Buat Pembelian</a>
                                        </td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel panel-warning">
                        <div class="panel-heading"><i class="fa fa-credit-card"></i> Pembelian (Invoice/GR)</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>No. DO</th>
                                        <th>No. PO</th>
                                        <th>Tanggal</th>
                                        <th class="text-right">Total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(!empty($chain['pembelian'])){ foreach($chain['pembelian'] as $r){ ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['notransaksi']); ?></td>
                                        <td><?php echo htmlspecialchars($r['no_do']); ?></td>
                                        <td><?php echo htmlspecialchars($r['no_order']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                                        <td class="text-right"><?php echo number_format((float)$r['total_akhir'],0,',','.'); ?></td>
                                        <td>
                                            <a class="btn btn-minier btn-primary" href="pembelian_cetak.php?nopesanan=<?php echo urlencode($r['notransaksi']); ?>">Cetak</a>
                                        </td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
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
