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

// Load Tracking History
$qt = mysqli_query($koneksi, "SELECT * FROM tbldo_tracking
                              WHERE no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                              ORDER BY tanggal_update DESC, id DESC");
$tracking = [];
while($r = mysqli_fetch_assoc($qt)){ $tracking[] = $r; }

// Update Status
if(isset($_POST['btnupdate'])){
    $new_status = post('new_status');
    $lokasi = post('lokasi');
    $keterangan = post('keterangan');

    if($new_status === ''){
        $msg = 'Status harus dipilih';
    } else {
        // Validation: status progression
        $current = $h['status_do'];
        $allowed_transitions = [
            'draft' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_transit', 'cancelled'],
            'in_transit' => ['arrived', 'cancelled'],
            'arrived' => ['received', 'cancelled'],
            'received' => [], // Final state
            'cancelled' => [] // Final state
        ];

        if(!isset($allowed_transitions[$current]) || !in_array($new_status, $allowed_transitions[$current])){
            $msg = "Status tidak dapat diubah dari '$current' ke '$new_status'";
        } else {
            // Update header status
            mysqli_query($koneksi, "UPDATE tbldelivery_order_header
                                   SET status_do='".mysqli_real_escape_string($koneksi,$new_status)."',
                                       tanggal_update=NOW()
                                   WHERE no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");

            // Insert tracking record
            mysqli_query($koneksi, "INSERT INTO tbldo_tracking
                                   (no_do, status, keterangan, lokasi, updated_by, tanggal_update)
                                   VALUES
                                   ('".mysqli_real_escape_string($koneksi,$no_do)."',
                                    '".mysqli_real_escape_string($koneksi,$new_status)."',
                                    '".mysqli_real_escape_string($koneksi,$keterangan)."',
                                    '".mysqli_real_escape_string($koneksi,$lokasi)."',
                                    '".mysqli_real_escape_string($koneksi,$_nama)."',
                                    NOW())");

            $msg = 'Status DO berhasil diupdate ke: '.$new_status;
            $msg_type = 'success';

            // Reload header
            $qh = mysqli_query($koneksi, "SELECT doh.*, s.namasupplier
                                          FROM tbldelivery_order_header doh
                                          LEFT JOIN tblsupplier s ON s.nosupplier=doh.no_supplier
                                          WHERE doh.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");
            $h = mysqli_fetch_assoc($qh);

            // Reload tracking
            $qt = mysqli_query($koneksi, "SELECT * FROM tbldo_tracking
                                          WHERE no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                                          ORDER BY tanggal_update DESC, id DESC");
            $tracking = [];
            while($r = mysqli_fetch_assoc($qt)){ $tracking[] = $r; }
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
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 40px;
            padding-bottom: 20px;
            border-left: 2px solid #ccc;
        }
        .timeline-item:last-child {
            border-left-color: transparent;
        }
        .timeline-icon {
            position: absolute;
            left: -10px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #428bca;
            border: 2px solid #fff;
        }
        .timeline-icon.status-draft { background: #999; }
        .timeline-icon.status-confirmed { background: #5bc0de; }
        .timeline-icon.status-in_transit { background: #f0ad4e; }
        .timeline-icon.status-arrived { background: #428bca; }
        .timeline-icon.status-received { background: #5cb85c; }
        .timeline-icon.status-cancelled { background: #d9534f; }
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
                    <li><a href="do_list.php">Daftar Delivery Order</a></li>
                    <li class="active">Update Tracking</li>
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
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-map-marker"></i> Update Tracking DO #<?php echo htmlspecialchars($h['no_do']); ?></h4>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <div class="row">
                                <div class="col-sm-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <td width="40%" bgcolor="beige">No. DO</td>
                                            <td><?php echo htmlspecialchars($h['no_do']); ?></td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="beige">No. PO</td>
                                            <td><?php echo htmlspecialchars($h['no_po']); ?></td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="beige">Supplier</td>
                                            <td><?php echo htmlspecialchars($h['namasupplier']); ?></td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="beige">Status Saat Ini</td>
                                            <td><strong><?php echo strtoupper($h['status_do']); ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-sm-6">
                                    <?php if($h['status_do'] != 'received' && $h['status_do'] != 'cancelled'){ ?>
                                    <div class="well">
                                        <h4>Update Status</h4>
                                        <form method="post">
                                            <div class="form-group">
                                                <label>Status Baru</label>
                                                <select name="new_status" class="form-control" required>
                                                    <option value="">- Pilih Status -</option>
                                                    <?php
                                                    $status_options = [
                                                        'draft' => ['confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'],
                                                        'confirmed' => ['in_transit' => 'In Transit', 'cancelled' => 'Cancelled'],
                                                        'in_transit' => ['arrived' => 'Arrived', 'cancelled' => 'Cancelled'],
                                                        'arrived' => ['received' => 'Received', 'cancelled' => 'Cancelled']
                                                    ];
                                                    $current = $h['status_do'];
                                                    if(isset($status_options[$current])){
                                                        foreach($status_options[$current] as $val => $label){
                                                            echo "<option value=\"$val\">$label</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Lokasi</label>
                                                <input type="text" name="lokasi" class="form-control" placeholder="Lokasi saat ini (opsional)" />
                                            </div>
                                            <div class="form-group">
                                                <label>Keterangan</label>
                                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan update status"></textarea>
                                            </div>
                                            <button type="submit" name="btnupdate" class="btn btn-success"><i class="fa fa-check"></i> Update Status</button>
                                        </form>
                                    </div>
                                    <?php } else { ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> DO ini sudah dalam status final (<?php echo strtoupper($h['status_do']); ?>) dan tidak dapat diupdate.
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div></div>
                    </div>

                    <div class="widget-box">
                        <div class="widget-header widget-header-flat widget-header-small">
                            <h4 class="widget-title lighter"><i class="ace-icon fa fa-history"></i> Riwayat Tracking</h4>
                        </div>
                        <div class="widget-body"><div class="widget-main">
                            <?php if(count($tracking) > 0){ ?>
                            <div class="timeline">
                                <?php foreach($tracking as $t){ ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon status-<?php echo $t['status']; ?>"></div>
                                    <div>
                                        <h5 style="margin-top:0;">
                                            <strong><?php echo strtoupper($t['status']); ?></strong>
                                            <small class="text-muted pull-right">
                                                <i class="fa fa-clock-o"></i> <?php echo $t['tanggal_update']; ?>
                                            </small>
                                        </h5>
                                        <?php if($t['lokasi']){ ?>
                                        <p><i class="fa fa-map-marker"></i> <strong>Lokasi:</strong> <?php echo htmlspecialchars($t['lokasi']); ?></p>
                                        <?php } ?>
                                        <?php if($t['keterangan']){ ?>
                                        <p><?php echo htmlspecialchars($t['keterangan']); ?></p>
                                        <?php } ?>
                                        <p class="text-muted"><small>oleh: <?php echo htmlspecialchars($t['updated_by']); ?></small></p>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } else { ?>
                            <p class="text-muted">Belum ada riwayat tracking</p>
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
