<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// User info
$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".$id_user."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

function post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function get($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function table_exists($koneksi, $table){
    $t = mysqli_real_escape_string($koneksi, $table);
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$t}' LIMIT 1";
    $rs = mysqli_query($koneksi, $sql);
    if(!$rs) return false;
    $row = mysqli_fetch_row($rs);
    return $row ? true : false;
}

function generate_no_do($koneksi, $kd_cabang, $header_table){
    $tahun = date('Y');
    $bulan = date('m');
    $prefix = 'DO'.$tahun.$bulan.$kd_cabang;
    $pref = mysqli_real_escape_string($koneksi, $prefix);
    $tbl = preg_replace('/[^a-zA-Z0-9_]/','', $header_table);
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(no_do, -5) AS UNSIGNED)), 0) + 1 AS urut FROM {$tbl} WHERE no_do LIKE '{$pref}%'";
    $urut = 1;
    if($rs = mysqli_query($koneksi, $sql)){
        $r = mysqli_fetch_assoc($rs);
        if($r && isset($r['urut'])){ $urut = (int)$r['urut']; }
    }
    return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
}

$no_po = get('no_po', '');
$msg = '';
$po_h = null; $po_d = [];
$pr_h = null; $pr_d = [];
$do_list = [];
$po_list = [];
$do_header_table = '';
$do_detail_table = '';
if(table_exists($koneksi, 'tbldelivery_order_header')){
    $do_header_table = 'tbldelivery_order_header';
    $do_detail_table = 'tbldelivery_order_detail';
} elseif (table_exists($koneksi, 'tbldo_header')) {
    $do_header_table = 'tbldo_header';
    $do_detail_table = 'tbldo_detail';
}

// Load PO
if(isset($_POST['btnload_po'])){
    $no_po = trim(post('no_po'));
}

if($no_po!=''){
    $qh = mysqli_query($koneksi, "SELECT no_order, no_supplier, tanggal, kd_cabang, no_pr FROM tblorder_header WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
    $po_h = mysqli_fetch_assoc($qh);
    if($po_h){
        $qd = mysqli_query($koneksi, "SELECT no_item, quantity, qty_terima, harga_pokok FROM tblorder_detail WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
        while($r = mysqli_fetch_assoc($qd)){
            $sisa = (int)$r['quantity'] - (int)$r['qty_terima'];
            if($sisa>0){
                $po_d[] = [
                    'no_item'=>$r['no_item'],
                    'qty_po'=>(int)$r['quantity'],
                    'qty_terima_po'=>(int)$r['qty_terima'],
                    'qty_sisa'=>$sisa,
                    'harga_pokok'=>(float)$r['harga_pokok']
                ];
            }
        }
        $po_no_pr = isset($po_h['no_pr']) ? trim($po_h['no_pr']) : '';
        if($po_no_pr !== ''){
            $esc_pr = mysqli_real_escape_string($koneksi, $po_no_pr);
            $qhpr = mysqli_query($koneksi, "SELECT no_pr, tanggal_pr, status_pr, requester, departemen, alasan FROM tblpurchase_request_header WHERE no_pr='".$esc_pr."' LIMIT 1");
            $pr_h = $qhpr ? mysqli_fetch_assoc($qhpr) : null;
            $qdpr = mysqli_query($koneksi, "SELECT no_item, quantity, qty_po FROM tblpurchase_request_detail WHERE no_pr='".$esc_pr."' ORDER BY id ASC");
            while($qdpr && ($rr = mysqli_fetch_assoc($qdpr))){
                $qty = (int)$rr['quantity'];
                $qtypo = (int)$rr['qty_po'];
                $pr_d[] = [
                    'no_item'=>$rr['no_item'],
                    'qty_pr'=>$qty,
                    'qty_po'=>$qtypo,
                    'qty_sisa'=> max(0, $qty - $qtypo)
                ];
            }
        }
        if($do_header_table==='tbldelivery_order_header'){
            $qdo = mysqli_query($koneksi, "SELECT no_do, tanggal_do, total_qty, status_do FROM tbldelivery_order_header WHERE no_po='".mysqli_real_escape_string($koneksi,$no_po)."' ORDER BY tanggal_do DESC, no_do DESC");
            while($qdo && ($drow = mysqli_fetch_assoc($qdo))){
                $do_list[] = [
                    'no_do' => $drow['no_do'],
                    'tanggal_do' => $drow['tanggal_do'],
                    'total_qty' => (int)$drow['total_qty'],
                    'status_do' => $drow['status_do']
                ];
            }
        } elseif ($do_header_table==='tbldo_header') {
            $qdo = mysqli_query($koneksi, "SELECT h.no_do, h.tanggal AS tanggal_do, COALESCE(SUM(d.qty_accepted),0) AS total_qty, h.status AS status_do
                                           FROM tbldo_header h
                                           LEFT JOIN tbldo_detail d ON h.no_do = d.no_do
                                           WHERE h.ref_po='".mysqli_real_escape_string($koneksi,$no_po)."'
                                           GROUP BY h.no_do
                                           ORDER BY h.tanggal DESC, h.no_do DESC");
            while($qdo && ($drow = mysqli_fetch_assoc($qdo))){
                $do_list[] = [
                    'no_do' => $drow['no_do'],
                    'tanggal_do' => $drow['tanggal_do'],
                    'total_qty' => (int)$drow['total_qty'],
                    'status_do' => $drow['status_do']
                ];
            }
        }
    } else {
        $msg = 'PO tidak ditemukan';
    }
}

// Save DO (receive)
if(isset($_POST['btnreceive_do'])){
    $no_po = post('no_po');
    $no_supplier = post('no_supplier');
    $alamat_kirim = post('alamat_kirim');
    $tanggal_do = date('Y-m-d');
    $created_by = $_nama;

    // Items
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $has_qty = false; foreach($items as $ni=>$it){ if(((int)$it['qty_terima'])>0){ $has_qty = true; break; }}
    if(!$has_qty){
        $msg = 'Qty terima belum diisi';
    } else {
        // Server-side validation: ensure qty_kirim/qty_terima <= sisa PO per item
        $po_map = [];
        $qm = mysqli_query($koneksi, "SELECT no_item, quantity, qty_terima FROM tblorder_detail WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
        while($r2 = mysqli_fetch_assoc($qm)){
            $po_map[$r2['no_item']] = max(0, ((int)$r2['quantity'] - (int)$r2['qty_terima']));
        }
        $violations = [];
        foreach($items as $no_item=>$it){
            $sisa = isset($po_map[$no_item]) ? (int)$po_map[$no_item] : 0;
            $qk = max(0, (int)$it['qty_kirim']);
            $qt = max(0, (int)$it['qty_terima']);
            if($qk > $sisa || $qt > $sisa){
                $violations[] = $no_item;
            }
        }
        if(!empty($violations)){
            $msg = 'Qty melebihi sisa PO untuk item: '.implode(', ', $violations);
        } else {
        $db_error = '';
        if($do_header_table===''){
            $msg = 'Tabel Delivery Order tidak ditemukan di database. Hubungi admin.';
        } else {
            mysqli_begin_transaction($koneksi);

            $no_do = generate_no_do($koneksi, $kd_cabang, $do_header_table);

            if($no_supplier==''){
                $qh = mysqli_query($koneksi, "SELECT no_supplier FROM tblorder_header WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."'");
                if($qh){ $r = mysqli_fetch_assoc($qh); $no_supplier = $r? $r['no_supplier'] : ''; }
            }

            if($do_header_table==='tbldelivery_order_header'){
                $q1 = mysqli_query($koneksi, "INSERT INTO tbldelivery_order_header 
                    (no_do, no_po, no_supplier, tanggal_do, tanggal_kirim, tanggal_estimasi_tiba, alamat_kirim, total_qty, status_do, kd_cabang, created_by)
                    VALUES
                    ('{$no_do}', '".mysqli_real_escape_string($koneksi,$no_po)."', '".mysqli_real_escape_string($koneksi,$no_supplier)."', '{$tanggal_do}', '{$tanggal_do}', '{$tanggal_do}', '".mysqli_real_escape_string($koneksi,$alamat_kirim)."', 0, 'received', '".mysqli_real_escape_string($koneksi,$kd_cabang)."', '".mysqli_real_escape_string($koneksi,$created_by)."')");
                if(!$q1){ $db_error = mysqli_error($koneksi); }
            } else {
                $q1 = mysqli_query($koneksi, "INSERT INTO tbldo_header 
                    (no_do, tanggal, no_supplier, ref_po, note, status, `user`, kd_cabang)
                    VALUES
                    ('{$no_do}', '{$tanggal_do}', '".mysqli_real_escape_string($koneksi,$no_supplier)."', '".mysqli_real_escape_string($koneksi,$no_po)."', '".mysqli_real_escape_string($koneksi,$alamat_kirim)."', 'received', '".mysqli_real_escape_string($koneksi,$created_by)."', '".mysqli_real_escape_string($koneksi,$kd_cabang)."')");
                if(!$q1){ $db_error = mysqli_error($koneksi); }
            }

            $total_qty = 0;
            if($db_error==''){
                foreach($items as $no_item=>$it){
                    $qty_kirim = max(0, (int)$it['qty_kirim']);
                    $qty_terima = max(0, (int)$it['qty_terima']);
                    $qty_po = max(0, (int)$it['qty_po']);
                    if($qty_kirim<=0 && $qty_terima<=0) continue;

                    if($do_detail_table==='tbldelivery_order_detail'){
                        $q2 = mysqli_query($koneksi, "INSERT INTO tbldelivery_order_detail
                            (no_do, nobaris, no_item, qty_po, qty_kirim, qty_terima, qty_reject)
                            VALUES
                            ('{$no_do}', 0, '".mysqli_real_escape_string($koneksi,$no_item)."', {$qty_po}, {$qty_kirim}, {$qty_terima}, 0)");
                    } else {
                        $q2 = mysqli_query($koneksi, "INSERT INTO tbldo_detail
                            (no_do, nobaris, no_item, qty_po, qty_do, qty_accepted, qty_reject, note_line)
                            VALUES
                            ('{$no_do}', 0, '".mysqli_real_escape_string($koneksi,$no_item)."', {$qty_po}, {$qty_kirim}, {$qty_terima}, 0, '')");
                    }
                    if(!$q2){ $db_error = mysqli_error($koneksi); break; }
                    $total_qty += $qty_terima;

                    if($qty_terima>0){
                        $q3 = mysqli_query($koneksi, "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                            VALUES ('2', '".mysqli_real_escape_string($koneksi,$no_do)."', '".mysqli_real_escape_string($koneksi,$no_item)."', '{$tanggal_do}', {$qty_terima}, 0, 'Penerimaan (DO)', '".mysqli_real_escape_string($koneksi,$kd_cabang)."')");
                        if(!$q3){ $db_error = mysqli_error($koneksi); break; }
                    }

                    $q4 = mysqli_query($koneksi, "UPDATE tblorder_detail SET qty_terima = qty_terima + {$qty_terima}
                        WHERE no_order='".mysqli_real_escape_string($koneksi,$no_po)."' AND no_item='".mysqli_real_escape_string($koneksi,$no_item)."'");
                    if(!$q4){ $db_error = mysqli_error($koneksi); break; }
                }
            }

            if($db_error==''){
                if($do_header_table==='tbldelivery_order_header'){
                    $q5 = mysqli_query($koneksi, "UPDATE tbldelivery_order_header SET total_qty={$total_qty} WHERE no_do='{$no_do}'");
                    if(!$q5){ $db_error = mysqli_error($koneksi); }
                }
            }
            if($db_error==''){
                $q6 = mysqli_query($koneksi, "INSERT INTO tbldo_tracking (no_do, status, keterangan, lokasi, updated_by) VALUES ('{$no_do}', 'received', 'Barang diterima', '', '".mysqli_real_escape_string($koneksi,$created_by)."')");
                if(!$q6){ $db_error = mysqli_error($koneksi); }
            }

            if($db_error!=''){
                mysqli_rollback($koneksi);
                $msg = 'Gagal menyimpan DO: '.htmlspecialchars($db_error);
            } else {
                mysqli_commit($koneksi);
                $msg = 'DO tersimpan: '.$no_do;
                header('Location: do_from_po.php?no_po='.urlencode($no_po).'&success_do='.urlencode($no_do));
                exit;
            }
        }
        }
    }
}

$success_do = get('success_do','');
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
        <?php include "menu_pembelian02.php"; ?>
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
                    <li class="active">Delivery Order (DO)</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <?php if($msg!=''){ echo '<div class="alert alert-warning">'.htmlspecialchars($msg).'</div>'; } ?>
                    <?php if($success_do!=''){ echo '<div class="alert alert-success">DO berhasil dibuat: <strong>'.htmlspecialchars($success_do).'</strong> - lanjutkan ke <a class="btn btn-minier btn-primary" href="pembelian_add.php?do='.urlencode($success_do).'">Pembelian</a></div>'; } ?>

                    <div class="widget-box">
                        <div class="widget-header widget-header-blue widget-header-flat">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-truck"></i> Buat DO dari PO</h4>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <form class="form-inline" method="post">
                                <div class="form-group">
                                    <label>No. PO</label>
                                    <input type="text" class="form-control" name="no_po" value="<?php echo htmlspecialchars($no_po); ?>" placeholder="Nomor PO" required />
                                </div>
                                <button type="submit" name="btnload_po" class="btn btn-info"><i class="fa fa-search"></i> Load PO</button>
                            </form>
                            <hr/>
                            <?php if(!$po_h){ ?>
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue widget-header-flat">
                                    <h4 class="widget-title lighter"><i class="ace-icon fa fa-list"></i> Daftar PO Terbaru</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>No. PO</th>
                                                    <th>Tanggal</th>
                                                    <th>Supplier</th>
                                                    <th class="text-right">Sisa Qty</th>
                                                    <th class="text-right">Jml DO</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($po_list)){ ?>
                                                    <tr><td colspan="7" class="text-center">Tidak ada data</td></tr>
                                                <?php } else { $i=0; foreach($po_list as $r){ $i++; ?>
                                                    <tr>
                                                        <td><?php echo $i; ?></td>
                                                        <td><?php echo htmlspecialchars($r['no_order']); ?></td>
                                                        <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                                                        <td><?php echo htmlspecialchars($r['no_supplier']); ?></td>
                                                        <td class="text-right"><?php echo (int)$r['sisa_qty']; ?></td>
                                                        <td class="text-right"><?php echo (int)$r['jml_do']; ?></td>
                                                        <td>
                                                            <a class="btn btn-minier btn-primary" href="do_from_po.php?no_po=<?php echo urlencode($r['no_order']); ?>"><i class="fa fa-folder-open"></i> Buka</a>
                                                        </td>
                                                    </tr>
                                                <?php } } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if($po_h){ ?>
                            <form method="post">
                                <input type="hidden" name="no_po" value="<?php echo htmlspecialchars($po_h['no_order']); ?>" />
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="control-label">Supplier</label>
                                            <input type="text" class="form-control" name="no_supplier" value="<?php echo htmlspecialchars($po_h['no_supplier']); ?>" readonly />
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Alamat Kirim</label>
                                            <input type="text" class="form-control" value="" placeholder="Klik 'Buat Penerimaan (DO)' untuk mengisi" readonly />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-12 text-right" style="margin-bottom:10px;">
                                        <button class="btn btn-primary btn-sm" type="button" data-toggle="collapse" data-target="#do-list-collapse" aria-expanded="false" aria-controls="do-list-collapse">
                                            <i class="fa fa-list"></i> Lihat DO Terkait (<?php echo count($do_list); ?>)
                                        </button>
                                        <?php if($pr_h){ ?>
                                        <button class="btn btn-info btn-sm" type="button" data-toggle="collapse" data-target="#pr-collapse" aria-expanded="false" aria-controls="pr-collapse">
                                            <i class="fa fa-file-text-o"></i> Lihat PR Terkait
                                        </button>
                                        <?php } ?>
                                        <button class="btn btn-success btn-sm" type="button" data-toggle="collapse" data-target="#receive-collapse" aria-expanded="false" aria-controls="receive-collapse">
                                            <i class="fa fa-truck"></i> Buat Penerimaan (DO)
                                        </button>
                                    </div>
                                </div>
                                <div id="do-list-collapse" class="collapse">
                                    <div class="table-responsive">
                                        <table class="table table-condensed table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No DO</th>
                                                    <th>Tanggal DO</th>
                                                    <th class="text-right">Total Qty</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($do_list)){ ?>
                                                <tr><td colspan="5" class="text-center">Belum ada DO untuk PO ini</td></tr>
                                                <?php } else { foreach($do_list as $d){ ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($d['no_do']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['tanggal_do']); ?></td>
                                                    <td class="text-right"><?php echo (int)$d['total_qty']; ?></td>
                                                    <td><?php echo htmlspecialchars($d['status_do']); ?></td>
                                                    <td>
                                                        <a class="btn btn-minier btn-primary" href="pembelian_add.php?do=<?php echo urlencode($d['no_do']); ?>">Pembelian</a>
                                                    </td>
                                                </tr>
                                                <?php } } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php if($pr_h){ ?>
                                <div id="pr-collapse" class="collapse">
                                <div class="row">
                                    <div class="col-xs-12">
                                        <div class="panel panel-info">
                                            <div class="panel-heading">Purchase Request Terkait: <strong><?php echo htmlspecialchars($pr_h['no_pr']); ?></strong></div>
                                            <div class="panel-body">
                                                <div class="row" style="margin-bottom:10px;">
                                                    <div class="col-sm-6">
                                                        <div><strong>Status</strong>: <?php echo htmlspecialchars($pr_h['status_pr']); ?></div>
                                                        <div><strong>Tanggal</strong>: <?php echo htmlspecialchars($pr_h['tanggal_pr']); ?></div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div><strong>Requester</strong>: <?php echo htmlspecialchars($pr_h['requester']); ?></div>
                                                        <div><strong>Departemen</strong>: <?php echo htmlspecialchars($pr_h['departemen']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-condensed table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>No</th>
                                                                <th>Kode Item</th>
                                                                <th class="text-right">Qty PR</th>
                                                                <th class="text-right">Sudah PO</th>
                                                                <th class="text-right">Sisa PR</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $i=0; foreach($pr_d as $r){ $i++; ?>
                                                            <tr>
                                                                <td><?php echo $i; ?></td>
                                                                <td><?php echo htmlspecialchars($r['no_item']); ?></td>
                                                                <td class="text-right"><?php echo (int)$r['qty_pr']; ?></td>
                                                                <td class="text-right"><?php echo (int)$r['qty_po']; ?></td>
                                                                <td class="text-right"><?php echo (int)$r['qty_sisa']; ?></td>
                                                            </tr>
                                                            <?php } if(empty($pr_d)){ ?>
                                                            <tr><td colspan="5" class="text-center">Tidak ada item PR</td></tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <div id="receive-collapse" class="collapse">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="control-label">Alamat Kirim</label>
                                            <input type="text" class="form-control" name="alamat_kirim" value="" placeholder="Alamat Penerimaan" />
                                        </div>
                                    </div>
                                </div>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Item</th>
                                            <th class="text-right">Qty PO</th>
                                            <th class="text-right">Qty Sisa</th>
                                            <th class="text-right">Qty Kirim</th>
                                            <th class="text-right">Qty Terima</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i=0; foreach($po_d as $d){ $i++; $ni = $d['no_item']; ?>
                                        <tr>
                                            <td><?php echo $i; ?></td>
                                            <td><?php echo htmlspecialchars($ni); ?></td>
                                            <td class="text-right"><?php echo (int)$d['qty_po']; ?></td>
                                            <td class="text-right"><?php echo (int)$d['qty_sisa']; ?></td>
                                            <td class="text-right"><input class="form-control" style="width:100px" type="number" name="items[<?php echo htmlspecialchars($ni); ?>][qty_kirim]" min="0" max="<?php echo (int)$d['qty_sisa']; ?>" value="<?php echo (int)$d['qty_sisa']; ?>" /></td>
                                            <td class="text-right"><input class="form-control" style="width:100px" type="number" name="items[<?php echo htmlspecialchars($ni); ?>][qty_terima]" min="0" max="<?php echo (int)$d['qty_sisa']; ?>" value="<?php echo (int)$d['qty_sisa']; ?>" /></td>
                                            <input type="hidden" name="items[<?php echo htmlspecialchars($ni); ?>][qty_po]" value="<?php echo (int)$d['qty_po']; ?>" />
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <div class="form-group">
                                    <button type="submit" name="btnreceive_do" class="btn btn-success"><i class="fa fa-check"></i> Simpan & Terima</button>
                                </div>
                                </div>
                            </form>
                            <?php } ?>
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
