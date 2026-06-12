<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

// User info
$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

function post($k,$d=''){return isset($_POST[$k])?trim($_POST[$k]):$d;}
function get($k,$d=''){return isset($_GET[$k])?trim($_GET[$k]):$d;}

$no_do = get('no_do', '');
$msg = '';
$msg_type = 'warning';

if($no_do === ''){
    header('Location: do_list.php');
    exit;
}

// Load DO Header
$qh = mysqli_query($koneksi, "SELECT doh.*, s.namasupplier
                              FROM tbldelivery_order_header doh
                              LEFT JOIN tblsupplier s ON s.nosupplier=doh.no_supplier
                              WHERE doh.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");
$h = mysqli_fetch_assoc($qh);
if(!$h){
    echo '<script>alert("DO tidak ditemukan."); window.location="do_list.php";</script>';
    exit;
}

// Check if DO is in receivable status
if(!in_array($h['status_do'], ['arrived', 'in_transit', 'confirmed'])){
    echo '<script>alert("DO dengan status '.$h['status_do'].' tidak dapat diterima."); window.location="do_list.php";</script>';
    exit;
}

// Load DO Detail
$qd = mysqli_query($koneksi, "SELECT d.*, i.namaitem, i.satuan
                              FROM tbldelivery_order_detail d
                              LEFT JOIN tblitem i ON i.noitem=d.no_item
                              WHERE d.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                              ORDER BY d.nobaris, d.id ASC");
$items = [];
while($r = mysqli_fetch_assoc($qd)){ $items[] = $r; }

// Process Receive
if(isset($_POST['btnreceive'])){
    $tanggal_terima = post('tanggal_terima', date('Y-m-d'));
    $keterangan_qc = post('keterangan_qc');
    $receive_items = isset($_POST['items']) ? $_POST['items'] : [];

    $has_receive = false;
    foreach($receive_items as $it){
        if(((int)$it['qty_terima']) > 0){
            $has_receive = true;
            break;
        }
    }

    if(!$has_receive){
        $msg = 'Belum ada qty yang diterima';
    } else {
        // Validation: qty_terima + qty_reject <= qty_kirim
        $violations = [];
        foreach($items as $item){
            $no_item = $item['no_item'];
            if(!isset($receive_items[$no_item])) continue;

            $qty_kirim = (int)$item['qty_kirim'];
            $qty_terima = max(0, (int)$receive_items[$no_item]['qty_terima']);
            $qty_reject = max(0, (int)$receive_items[$no_item]['qty_reject']);

            if(($qty_terima + $qty_reject) > $qty_kirim){
                $violations[] = $no_item;
            }
        }

        if(!empty($violations)){
            $msg = 'Total qty terima + reject melebihi qty kirim untuk item: '.implode(', ', $violations);
        } else {
            // Begin transaction
            mysqli_begin_transaction($koneksi);
            try {
                $total_terima = 0;
                $total_reject = 0;

                foreach($items as $item){
                    $no_item = $item['no_item'];
                    if(!isset($receive_items[$no_item])) continue;

                    $qty_terima = max(0, (int)$receive_items[$no_item]['qty_terima']);
                    $qty_reject = max(0, (int)$receive_items[$no_item]['qty_reject']);

                    if($qty_terima <= 0 && $qty_reject <= 0) continue;

                    // Update DO detail
                    mysqli_query($koneksi, "UPDATE tbldelivery_order_detail
                                           SET qty_terima = {$qty_terima},
                                               qty_reject = {$qty_reject}
                                           WHERE no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                                           AND no_item='".mysqli_real_escape_string($koneksi,$no_item)."'");

                    // Post to stock (only accepted qty)
                    if($qty_terima > 0){
                        mysqli_query($koneksi, "INSERT INTO tbstok
                                               (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                                               VALUES
                                               ('2', '".mysqli_real_escape_string($koneksi,$no_do)."',
                                                '".mysqli_real_escape_string($koneksi,$no_item)."',
                                                '".mysqli_real_escape_string($koneksi,$tanggal_terima)."',
                                                {$qty_terima}, 0,
                                                'Penerimaan DO',
                                                '".mysqli_real_escape_string($koneksi,$kd_cabang)."')");
                    }

                    // Update PO qty_terima
                    if($qty_terima > 0){
                        mysqli_query($koneksi, "UPDATE tblorder_detail
                                               SET qty_terima = qty_terima + {$qty_terima}
                                               WHERE no_order='".mysqli_real_escape_string($koneksi,$h['no_po'])."'
                                               AND no_item='".mysqli_real_escape_string($koneksi,$no_item)."'");
                    }

                    $total_terima += $qty_terima;
                    $total_reject += $qty_reject;
                }

                // Update DO header
                mysqli_query($koneksi, "UPDATE tbldelivery_order_header
                                       SET status_do='received',
                                           tanggal_terima='".mysqli_real_escape_string($koneksi,$tanggal_terima)."',
                                           tanggal_update=NOW()
                                       WHERE no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");

                // Insert tracking
                $ket_tracking = "Barang diterima. Total diterima: {$total_terima}";
                if($total_reject > 0){
                    $ket_tracking .= ", Total reject: {$total_reject}";
                }
                if($keterangan_qc){
                    $ket_tracking .= ". QC: ".$keterangan_qc;
                }

                mysqli_query($koneksi, "INSERT INTO tbldo_tracking
                                       (no_do, status, keterangan, lokasi, updated_by, tanggal_update)
                                       VALUES
                                       ('".mysqli_real_escape_string($koneksi,$no_do)."',
                                        'received',
                                        '".mysqli_real_escape_string($koneksi,$ket_tracking)."',
                                        'Gudang',
                                        '".mysqli_real_escape_string($koneksi,$_nama)."',
                                        NOW())");

                mysqli_commit($koneksi);

                $msg = 'Barang berhasil diterima. Total diterima: '.$total_terima.($total_reject > 0 ? ', Total reject: '.$total_reject : '');
                $msg_type = 'success';

                // Reload data
                $qh = mysqli_query($koneksi, "SELECT doh.*, s.namasupplier
                                              FROM tbldelivery_order_header doh
                                              LEFT JOIN tblsupplier s ON s.nosupplier=doh.no_supplier
                                              WHERE doh.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");
                $h = mysqli_fetch_assoc($qh);

                $qd = mysqli_query($koneksi, "SELECT d.*, i.namaitem, i.satuan
                                              FROM tbldelivery_order_detail d
                                              LEFT JOIN tblitem i ON i.noitem=d.no_item
                                              WHERE d.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                                              ORDER BY d.nobaris, d.id ASC");
                $items = [];
                while($r = mysqli_fetch_assoc($qd)){ $items[] = $r; }

            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                $msg = 'Error: '.$e->getMessage();
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
                    <li><a href="do_list.php">Daftar Delivery Order</a></li>
                    <li class="active">Terima Barang</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row"><div class="col-xs-12">
                    <?php if($msg!=''){ echo '<div class="alert alert-'.$msg_type.'">'.htmlspecialchars($msg).'</div>'; } ?>

                    <div class="clearfix" style="margin-bottom:10px;">
                        <a class="btn btn-default" href="do_list.php"><i class="fa fa-arrow-left"></i> Kembali</a>
                        <a class="btn btn-primary" href="do_detail.php?no_do=<?php echo urlencode($no_do); ?>"><i class="fa fa-search"></i> Lihat Detail</a>
                    </div>

                    <div class="widget-box">
                        <div class="widget-header widget-header-blue widget-header-flat">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-check-square"></i> Terima Barang - DO #<?php echo htmlspecialchars($h['no_do']); ?></h4>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <table class="table table-bordered">
                                <tr>
                                    <td width="20%" bgcolor="beige">No. DO</td>
                                    <td width="30%"><?php echo htmlspecialchars($h['no_do']); ?></td>
                                    <td width="20%" bgcolor="beige">Status</td>
                                    <td width="30%"><strong><?php echo strtoupper($h['status_do']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td bgcolor="beige">No. PO</td>
                                    <td><?php echo htmlspecialchars($h['no_po']); ?></td>
                                    <td bgcolor="beige">Tanggal DO</td>
                                    <td><?php echo htmlspecialchars($h['tanggal_do']); ?></td>
                                </tr>
                                <tr>
                                    <td bgcolor="beige">Supplier</td>
                                    <td colspan="3"><?php echo htmlspecialchars($h['no_supplier']); ?> - <?php echo htmlspecialchars($h['namasupplier']); ?></td>
                                </tr>
                            </table>

                            <?php if($h['status_do'] == 'received'){ ?>
                            <div class="alert alert-success">
                                <i class="fa fa-check"></i> DO ini sudah diterima pada tanggal <?php echo $h['tanggal_terima']; ?>
                            </div>
                            <?php } else { ?>
                            <form method="post" id="formReceive">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Tanggal Terima</label>
                                            <input type="date" name="tanggal_terima" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Keterangan QC (opsional)</label>
                                            <input type="text" name="keterangan_qc" class="form-control" placeholder="Hasil Quality Control" />
                                        </div>
                                    </div>
                                </div>

                                <div class="table-header">Item yang Dikirim</div>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width:5%">No</th>
                                            <th style="width:15%">Kode Item</th>
                                            <th>Nama Item</th>
                                            <th class="text-center" style="width:10%">Qty Kirim</th>
                                            <th class="text-center" style="width:12%">Qty Terima</th>
                                            <th class="text-center" style="width:12%">Qty Reject</th>
                                            <th class="text-center" style="width:10%">Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i=0; foreach($items as $item){ $i++; $ni = $item['no_item']; ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo htmlspecialchars($ni); ?></td>
                                            <td><?php echo htmlspecialchars($item['namaitem']); ?></td>
                                            <td class="text-center"><strong><?php echo (int)$item['qty_kirim']; ?></strong></td>
                                            <td>
                                                <input type="number" class="form-control text-center qty-input" name="items[<?php echo htmlspecialchars($ni); ?>][qty_terima]" min="0" max="<?php echo (int)$item['qty_kirim']; ?>" value="<?php echo (int)$item['qty_kirim']; ?>" data-item="<?php echo htmlspecialchars($ni); ?>" data-max="<?php echo (int)$item['qty_kirim']; ?>" />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control text-center qty-input" name="items[<?php echo htmlspecialchars($ni); ?>][qty_reject]" min="0" max="<?php echo (int)$item['qty_kirim']; ?>" value="0" data-item="<?php echo htmlspecialchars($ni); ?>" data-max="<?php echo (int)$item['qty_kirim']; ?>" />
                                            </td>
                                            <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                                <div class="form-group">
                                    <button type="submit" name="btnreceive" class="btn btn-success btn-lg">
                                        <i class="fa fa-check"></i> Terima Barang
                                    </button>
                                    <a href="do_list.php" class="btn btn-default">Batal</a>
                                </div>
                            </form>
                            <?php } ?>
                        </div></div>
                    </div>

                    <?php if($h['status_do'] == 'received'){ ?>
                    <!-- Show received items -->
                    <div class="widget-box">
                        <div class="widget-header widget-header-flat widget-header-small">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-list"></i> Barang yang Diterima</h4>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th style="width:15%">Kode Item</th>
                                        <th>Nama Item</th>
                                        <th class="text-right" style="width:10%">Qty Kirim</th>
                                        <th class="text-right" style="width:10%">Qty Terima</th>
                                        <th class="text-right" style="width:10%">Qty Reject</th>
                                        <th class="text-center" style="width:10%">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach($items as $item){ $i++; ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td><?php echo htmlspecialchars($item['no_item']); ?></td>
                                        <td><?php echo htmlspecialchars($item['namaitem']); ?></td>
                                        <td class="text-right"><?php echo (int)$item['qty_kirim']; ?></td>
                                        <td class="text-right"><strong><?php echo (int)$item['qty_terima']; ?></strong></td>
                                        <td class="text-right"><?php echo (int)$item['qty_reject']; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <div class="clearfix" style="margin-top:10px;">
                                <a href="pembelian_add.php?do=<?php echo urlencode($no_do); ?>" class="btn btn-success">
                                    <i class="fa fa-file-text"></i> Buat Invoice Pembelian
                                </a>
                            </div>
                        </div></div>
                    </div>
                    <?php } ?>
                </div></div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
<script>
$(document).ready(function(){
    // Validate qty_terima + qty_reject <= qty_kirim
    $('.qty-input').on('change', function(){
        var item = $(this).data('item');
        var max = parseInt($(this).data('max'));
        var qty_terima = parseInt($('input[name="items['+item+'][qty_terima]"]').val()) || 0;
        var qty_reject = parseInt($('input[name="items['+item+'][qty_reject]"]').val()) || 0;

        if((qty_terima + qty_reject) > max){
            alert('Total qty terima + reject tidak boleh melebihi qty kirim untuk item ' + item);
            $(this).val(0);
        }
    });
});
</script>
</body>
</html>
