<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";
require_once "lib/MinMaxCalculator.php";

$quser = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".$id_user."'");
$u = mysqli_fetch_assoc($quser);
$_nama = $u ? $u['nama_user'] : '';
$foto_user = ($u && $u['foto_user']) ? $u['foto_user'] : 'file_upload/avatar.png';

function getv($k,$d=''){ return isset($_GET[$k])?$_GET[$k]:$d; }
$esc_cbg = mysqli_real_escape_string($koneksi, $kd_cabang);

$lim = 20;
$pr_res = mysqli_query($koneksi, "SELECT h.no_pr, h.tanggal_pr, h.requester, h.status_pr,
                   (SELECT COUNT(1) FROM tblpurchase_request_detail d WHERE d.no_pr=h.no_pr) AS jml_item,
                   (SELECT COALESCE(SUM(d.quantity),0) FROM tblpurchase_request_detail d WHERE d.no_pr=h.no_pr) AS tot_qty
            FROM tblpurchase_request_header h
            WHERE h.kd_cabang='".$esc_cbg."'
            ORDER BY h.tanggal_pr DESC, h.no_pr DESC
            LIMIT ".$lim);
$po_res = mysqli_query($koneksi, "SELECT h.no_order, h.tanggal, h.no_supplier, COALESCE(h.no_pr,'') AS no_pr,
                   (SELECT COALESCE(SUM(GREATEST(d.quantity - d.qty_terima,0)),0) FROM tblorder_detail d WHERE d.no_order=h.no_order) AS sisa_qty,
                   (SELECT COUNT(1) FROM tbldelivery_order_header doh WHERE doh.no_po=h.no_order) AS jml_do
            FROM tblorder_header h
            WHERE h.kd_cabang='".$esc_cbg."'
            ORDER BY h.tanggal DESC, h.no_order DESC
            LIMIT ".$lim);
$do_res = mysqli_query($koneksi, "SELECT no_do, no_po, tanggal_do, total_qty, status_do
            FROM tbldelivery_order_header
            WHERE kd_cabang='".$esc_cbg."'
            ORDER BY tanggal_do DESC, no_do DESC
            LIMIT ".$lim);

// Get cabang list for filter
$cabangList = [];
$qCabang = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang");
while ($c = mysqli_fetch_assoc($qCabang)) {
    $cabangList[] = $c;
}

// Get MIN/MAX stats if calculator available
$calculator = new MinMaxCalculator($koneksi);
$cabangStats = $calculator->getCabangStats();
$itemsNeedOrder = $calculator->getItemPerluOrder(null, null, 50);
$itemsPerSupplier = $calculator->getItemPerSupplier();
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
        .stat-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            color: #fff;
        }
        .stat-box.urgent { background: #d9534f; }
        .stat-box.warning { background: #f0ad4e; }
        .stat-box.info { background: #5bc0de; }
        .stat-box.success { background: #5cb85c; }
        .stat-box h3 { margin: 0 0 5px 0; font-size: 28px; }
        .stat-box small { opacity: 0.8; }
        .kategori-badge { padding: 3px 8px; border-radius: 3px; font-weight: bold; }
        .kategori-A { background: #5cb85c; color: #fff; }
        .kategori-B { background: #5bc0de; color: #fff; }
        .kategori-C { background: #f0ad4e; color: #fff; }
        .kategori-D { background: #d9534f; color: #fff; }
        .kategori-E { background: #777; color: #fff; }
        .status-urgent { color: #d9534f; font-weight: bold; }
        .status-warning { color: #f0ad4e; font-weight: bold; }
        .status-ok { color: #5cb85c; }
        .minmax-table th { background: #f5f5f5; }
        .weekly-data { font-size: 11px; color: #666; }
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
                    <li class="active">Procurement Dashboard</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Procurement Dashboard <small><i class="fa fa-angle-double-right"></i> MIN/MAX & Rencana Order</small></h1>
                </div>

                <div class="row"><div class="col-xs-12">

                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#tab-minmax" aria-controls="tab-minmax" role="tab" data-toggle="tab"><i class="fa fa-bar-chart"></i> MIN/MAX Stok</a></li>
                        <li role="presentation"><a href="#tab-order" aria-controls="tab-order" role="tab" data-toggle="tab"><i class="fa fa-warning"></i> Item Perlu Order</a></li>
                        <li role="presentation"><a href="#tab-supplier" aria-controls="tab-supplier" role="tab" data-toggle="tab"><i class="fa fa-truck"></i> Order per Supplier</a></li>
                        <li role="presentation"><a href="#tab-pr" aria-controls="tab-pr" role="tab" data-toggle="tab"><i class="fa fa-file-text-o"></i> PR</a></li>
                        <li role="presentation"><a href="#tab-po" aria-controls="tab-po" role="tab" data-toggle="tab"><i class="fa fa-shopping-cart"></i> PO</a></li>
                        <li role="presentation"><a href="#tab-do" aria-controls="tab-do" role="tab" data-toggle="tab"><i class="fa fa-archive"></i> DO</a></li>
                    </ul>
                    <div class="tab-content" style="padding:15px; background:#fff; border:1px solid #ddd; border-top:none;">

                        <!-- TAB MIN/MAX STOK -->
                        <div role="tabpanel" class="tab-pane active" id="tab-minmax">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="pull-right" style="margin-bottom:15px;">
                                        <button type="button" class="btn btn-primary btn-sm" id="btnRecalculate">
                                            <i class="fa fa-refresh"></i> Hitung Ulang MIN/MAX
                                        </button>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>

                            <div class="row">
                                <?php
                                $totalUrgent = 0;
                                $totalWarning = 0;
                                $totalItems = 0;
                                foreach ($cabangStats as $stat) {
                                    $totalUrgent += (int)$stat['jml_urgent'];
                                    $totalWarning += (int)$stat['jml_perlu_order'];
                                    $totalItems += (int)$stat['total_item'];
                                }
                                ?>
                                <div class="col-md-3">
                                    <div class="stat-box urgent">
                                        <h3><?php echo $totalUrgent; ?></h3>
                                        <small><i class="fa fa-exclamation-triangle"></i> Item URGENT</small><br>
                                        <small>Stok < 50% MIN</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box warning">
                                        <h3><?php echo $totalWarning; ?></h3>
                                        <small><i class="fa fa-warning"></i> Perlu Order</small><br>
                                        <small>Stok < MIN</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box info">
                                        <h3><?php echo $totalItems; ?></h3>
                                        <small><i class="fa fa-cubes"></i> Total Item</small><br>
                                        <small>Dalam sistem</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box success">
                                        <h3><?php echo count($cabangStats); ?></h3>
                                        <small><i class="fa fa-building"></i> Cabang</small><br>
                                        <small>Aktif</small>
                                    </div>
                                </div>
                            </div>

                            <h4><i class="fa fa-building"></i> Statistik Per Cabang</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped minmax-table">
                                    <thead>
                                        <tr>
                                            <th>Cabang</th>
                                            <th class="text-center">Total Item</th>
                                            <th class="text-center">Perlu Order</th>
                                            <th class="text-center">Urgent</th>
                                            <th class="text-center">Kat A</th>
                                            <th class="text-center">Kat B</th>
                                            <th class="text-center">Kat C</th>
                                            <th class="text-center">Kat D</th>
                                            <th class="text-center">Kat E</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($cabangStats) > 0): ?>
                                            <?php foreach ($cabangStats as $stat): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($stat['nama_cabang'] ?: $stat['kode_cabang']); ?></strong></td>
                                                <td class="text-center"><?php echo (int)$stat['total_item']; ?></td>
                                                <td class="text-center <?php echo $stat['jml_perlu_order'] > 0 ? 'status-warning' : ''; ?>"><?php echo (int)$stat['jml_perlu_order']; ?></td>
                                                <td class="text-center <?php echo $stat['jml_urgent'] > 0 ? 'status-urgent' : ''; ?>"><?php echo (int)$stat['jml_urgent']; ?></td>
                                                <td class="text-center"><span class="kategori-badge kategori-A"><?php echo (int)$stat['kat_A']; ?></span></td>
                                                <td class="text-center"><span class="kategori-badge kategori-B"><?php echo (int)$stat['kat_B']; ?></span></td>
                                                <td class="text-center"><span class="kategori-badge kategori-C"><?php echo (int)$stat['kat_C']; ?></span></td>
                                                <td class="text-center"><span class="kategori-badge kategori-D"><?php echo (int)$stat['kat_D']; ?></span></td>
                                                <td class="text-center"><span class="kategori-badge kategori-E"><?php echo (int)$stat['kat_E']; ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="9" class="text-center text-muted">Belum ada data MIN/MAX. Klik "Hitung Ulang MIN/MAX" untuk generate.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="well well-sm">
                                <strong>Keterangan Kategori:</strong><br>
                                <span class="kategori-badge kategori-A">A</span> Fast Moving (interval 1-3 hari) |
                                <span class="kategori-badge kategori-B">B</span> Medium Moving (interval 4-12 hari) |
                                <span class="kategori-badge kategori-C">C</span> Slow Moving (interval >12 hari) |
                                <span class="kategori-badge kategori-D">D</span> Dead Stock (>30 hari) |
                                <span class="kategori-badge kategori-E">E</span> Non-Stock (0 transaksi)
                            </div>
                        </div>

                        <!-- TAB ITEM PERLU ORDER -->
                        <div role="tabpanel" class="tab-pane" id="tab-order">
                            <div class="row" style="margin-bottom:15px;">
                                <div class="col-md-3">
                                    <label>Filter Cabang:</label>
                                    <select id="filterCabang" class="form-control input-sm">
                                        <option value="">-- Semua Cabang --</option>
                                        <?php foreach ($cabangList as $cab): ?>
                                        <option value="<?php echo htmlspecialchars($cab['kode_cabang']); ?>"><?php echo htmlspecialchars($cab['nama_cabang']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Kategori:</label>
                                    <select id="filterKategori" class="form-control input-sm">
                                        <option value="">-- Semua --</option>
                                        <option value="A">A - Fast</option>
                                        <option value="B">B - Medium</option>
                                        <option value="C">C - Slow</option>
                                        <option value="D">D - Dead</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Cari Item:</label>
                                    <input type="text" id="searchItem" class="form-control input-sm" placeholder="Kode/nama item...">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-info btn-sm" id="btnFilterOrder"><i class="fa fa-search"></i> Filter</button>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-success btn-sm" id="btnGenerateRencana"><i class="fa fa-plus"></i> Buat Rencana Order</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover table-condensed" id="tblItemsOrder">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="checkAll"></th>
                                            <th>Kode Item</th>
                                            <th>Nama Item</th>
                                            <th>Cabang</th>
                                            <th class="text-center">Stok</th>
                                            <th class="text-center">ROP</th>
                                            <th class="text-center">MIN</th>
                                            <th class="text-center">MAX</th>
                                            <th class="text-center">LT</th>
                                            <th class="text-center">Avg/hr</th>
                                            <th class="text-center">Kat</th>
                                            <th class="text-center">Saran</th>
                                            <th>Last Sale</th>
                                            <th>Status Stok</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyItemsOrder">
                                        <?php if (count($itemsNeedOrder) > 0): ?>
                                            <?php foreach ($itemsNeedOrder as $item):
                                                $rop = isset($item['rop']) ? (int)$item['rop'] : 0;
                                                $stok = (float)$item['stok_saat_ini'];
                                                $min  = (float)$item['min_stok'];
                                                $lt   = isset($item['lead_time_hari']) ? (int)$item['lead_time_hari'] : 3;
                                                $avgHari = isset($item['avg_interval_hari']) ? round(7/$item['avg_interval_hari'],1) : 0;
                                                // badge stok
                                                if ($stok <= $rop) {
                                                    $stokClass = 'danger';
                                                    $stokLabel = 'Titik Pesan';
                                                } elseif ($stok <= $min) {
                                                    $stokClass = 'warning';
                                                    $stokLabel = 'Dibawah MIN';
                                                } else {
                                                    $stokClass = 'success';
                                                    $stokLabel = 'Aman';
                                                }
                                                $lastSale = !empty($item['last_sale_date']) ? date('d/m/y', strtotime($item['last_sale_date'])) : '-';
                                                $lastSaleClass = '';
                                                if (!empty($item['last_sale_date'])) {
                                                    $diffDays = (time() - strtotime($item['last_sale_date'])) / 86400;
                                                    if ($diffDays > 60) $lastSaleClass = 'text-muted';
                                                }
                                                $isManual = !empty($item['min_max_manual']);
                                            ?>
                                            <tr data-item="<?php echo htmlspecialchars($item['no_item']); ?>" data-cabang="<?php echo htmlspecialchars($item['kd_cabang']); ?>">
                                                <td><input type="checkbox" class="item-check" value="<?php echo htmlspecialchars($item['no_item'].'|'.$item['kd_cabang']); ?>"></td>
                                                <td><small><?php echo htmlspecialchars($item['no_item']); ?></small></td>
                                                <td><?php echo htmlspecialchars($item['nama_item']); ?>
                                                    <?php if ($isManual): ?><span class="label label-info" title="Override manual">M</span><?php endif; ?>
                                                </td>
                                                <td><small><?php echo htmlspecialchars($item['nama_cabang'] ?: $item['kd_cabang']); ?></small></td>
                                                <td class="text-center"><strong><?php echo (int)$stok; ?></strong></td>
                                                <td class="text-center text-danger"><strong><?php echo $rop; ?></strong></td>
                                                <td class="text-center mm-min" data-val="<?php echo (int)$min; ?>"><?php echo (int)$min; ?></td>
                                                <td class="text-center mm-max" data-val="<?php echo (int)$item['max_stok']; ?>"><?php echo (int)$item['max_stok']; ?></td>
                                                <td class="text-center"><small><?php echo $lt; ?>h</small></td>
                                                <td class="text-center"><small><?php echo $avgHari; ?>/hr</small></td>
                                                <td class="text-center"><span class="kategori-badge kategori-<?php echo $item['kategori']; ?>"><?php echo $item['kategori']; ?></span></td>
                                                <td class="text-center"><strong><?php echo (int)$item['qty_order_suggest']; ?></strong></td>
                                                <td class="<?php echo $lastSaleClass; ?>"><small><?php echo $lastSale; ?></small></td>
                                                <td><span class="label label-<?php echo $stokClass; ?>"><?php echo $stokLabel; ?></span></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-default btn-edit-mm" title="Override MIN/MAX"><i class="fa fa-pencil"></i></button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="15" class="text-center text-muted">Tidak ada item yang perlu order saat ini</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB ORDER PER SUPPLIER (F3) -->
                        <div role="tabpanel" class="tab-pane" id="tab-supplier">
                            <p class="text-muted"><i class="fa fa-info-circle"></i> Item yang stok &lt; MIN, dikelompokkan per supplier utama. Klik baris untuk lihat detail item.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr class="active">
                                            <th>Supplier</th>
                                            <th class="text-center">Jml Item</th>
                                            <th class="text-right">Total Qty Kurang</th>
                                            <th class="text-right">Est. Nilai</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($itemsPerSupplier) > 0): ?>
                                            <?php foreach ($itemsPerSupplier as $sup): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($sup['nama_supplier']); ?></strong>
                                                    <?php if ($sup['kd_supplier']): ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($sup['kd_supplier']); ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><span class="badge"><?php echo (int)$sup['jml_item']; ?></span></td>
                                                <td class="text-right"><?php echo number_format((float)$sup['total_qty_kurang'], 0, ',', '.'); ?></td>
                                                <td class="text-right">Rp <?php echo number_format((float)$sup['est_nilai'], 0, ',', '.'); ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-info btn-detail-supplier"
                                                            data-supplier="<?php echo htmlspecialchars($sup['kd_supplier']); ?>"
                                                            data-nama="<?php echo htmlspecialchars($sup['nama_supplier']); ?>">
                                                        <i class="fa fa-list"></i> Detail
                                                    </button>
                                                    <a href="pr_add.php?supplier=<?php echo urlencode($sup['kd_supplier']); ?>" class="btn btn-xs btn-success">
                                                        <i class="fa fa-plus"></i> Buat PR
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted">Tidak ada item yang perlu order</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Area detail supplier items -->
                            <div id="supplierDetail" style="display:none; margin-top:15px;">
                                <h4 id="supplierDetailTitle"><i class="fa fa-list"></i> Detail Item</h4>
                                <div id="supplierDetailBody"></div>
                            </div>
                        </div>

                        <!-- TAB PR -->
                        <div role="tabpanel" class="tab-pane" id="tab-pr">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No. PR</th>
                                            <th>Tanggal</th>
                                            <th>Requester</th>
                                            <th class="text-right">Jumlah Item</th>
                                            <th class="text-right">Total Qty</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($pr_res && mysqli_num_rows($pr_res)>0){ mysqli_data_seek($pr_res, 0); while($r=mysqli_fetch_assoc($pr_res)){ ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['no_pr']); ?></td>
                                            <td><?php echo htmlspecialchars($r['tanggal_pr']); ?></td>
                                            <td><?php echo htmlspecialchars($r['requester']); ?></td>
                                            <td class="text-right">&nbsp;<?php echo (int)$r['jml_item']; ?></td>
                                            <td class="text-right">&nbsp;<?php echo (int)$r['tot_qty']; ?></td>
                                            <td><?php echo htmlspecialchars($r['status_pr']); ?></td>
                                            <td>
                                                <a class="btn btn-minier btn-primary" href="pr_add.php?no_pr=<?php echo urlencode($r['no_pr']); ?>"><i class="fa fa-folder-open"></i> Buka</a>
                                                <a class="btn btn-minier btn-success" href="pesanan_pembelian_add.php?pr=<?php echo urlencode($r['no_pr']); ?>"><i class="fa fa-shopping-cart"></i> Buat PO</a>
                                            </td>
                                        </tr>
                                        <?php } } else { ?>
                                        <tr><td colspan="7" class="text-center">Tidak ada data</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB PO -->
                        <div role="tabpanel" class="tab-pane" id="tab-po">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No. PO</th>
                                            <th>Tanggal</th>
                                            <th>Supplier</th>
                                            <th class="text-right">Sisa Qty</th>
                                            <th class="text-right">Jml DO</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($po_res && mysqli_num_rows($po_res)>0){ mysqli_data_seek($po_res, 0); while($r=mysqli_fetch_assoc($po_res)){ ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['no_order']); ?></td>
                                            <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                                            <td><?php echo htmlspecialchars($r['no_supplier']); ?></td>
                                            <td class="text-right">&nbsp;<?php echo (int)$r['sisa_qty']; ?></td>
                                            <td class="text-right">&nbsp;<?php echo (int)$r['jml_do']; ?></td>
                                            <td>
                                                <a class="btn btn-minier btn-primary" href="do_from_po.php?no_po=<?php echo urlencode($r['no_order']); ?>"><i class="fa fa-folder-open"></i> Buka</a>
                                            </td>
                                        </tr>
                                        <?php } } else { ?>
                                        <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB DO -->
                        <div role="tabpanel" class="tab-pane" id="tab-do">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No. DO</th>
                                            <th>No. PO</th>
                                            <th>Tanggal</th>
                                            <th class="text-right">Total Qty</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($do_res && mysqli_num_rows($do_res)>0){ mysqli_data_seek($do_res, 0); while($r=mysqli_fetch_assoc($do_res)){ ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['no_do']); ?></td>
                                            <td><?php echo htmlspecialchars($r['no_po']); ?></td>
                                            <td><?php echo htmlspecialchars($r['tanggal_do']); ?></td>
                                            <td class="text-right">&nbsp;<?php echo (int)$r['total_qty']; ?></td>
                                            <td><?php echo htmlspecialchars($r['status_do']); ?></td>
                                            <td>
                                                <a class="btn btn-minier btn-primary" href="pembelian_add.php?do=<?php echo urlencode($r['no_do']); ?>"><i class="fa fa-external-link"></i> Pembelian</a>
                                            </td>
                                        </tr>
                                        <?php } } else { ?>
                                        <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
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
<!-- Modal inline edit MIN/MAX -->
<div class="modal fade" id="modalEditMM" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-pencil"></i> Override MIN/MAX</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mmNoItem">
                <input type="hidden" id="mmKdCabang">
                <div class="form-group">
                    <label>Item:</label>
                    <p id="mmNamaItem" class="form-control-static text-info"></p>
                </div>
                <div class="form-group">
                    <label>MIN Stok:</label>
                    <input type="number" id="mmMin" class="form-control input-sm" min="0">
                </div>
                <div class="form-group">
                    <label>MAX Stok:</label>
                    <input type="number" id="mmMax" class="form-control input-sm" min="0">
                </div>
                <div class="alert alert-info" style="padding:8px; font-size:12px;">
                    <i class="fa fa-info-circle"></i> Override manual tidak akan tertimpa saat "Hitung Ulang MIN/MAX". Klik <strong>Reset Auto</strong> untuk kembali ke kalkulasi otomatis.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" id="btnResetAuto"><i class="fa fa-undo"></i> Reset Auto</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveMM"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Recalculate MIN/MAX
    $('#btnRecalculate').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menghitung...');

        $.ajax({
            url: '_ajax/ajax-minmax-calculation.php',
            type: 'POST',
            data: { action: 'recalculate' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert('Kalkulasi MIN/MAX berhasil! Halaman akan di-refresh.');
                    location.reload();
                } else {
                    alert('Error: ' + res.error);
                    btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Hitung Ulang MIN/MAX');
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menghitung MIN/MAX');
                btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Hitung Ulang MIN/MAX');
            }
        });
    });

    // Filter items need order
    $('#btnFilterOrder').click(function() {
        var cabang = $('#filterCabang').val();
        var kategori = $('#filterKategori').val();
        var search = $('#searchItem').val();

        $.ajax({
            url: '_ajax/ajax-minmax-calculation.php',
            type: 'GET',
            data: { action: 'search_items', kd_cabang: cabang, kategori: kategori, q: search },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var html = '';
                    if (res.data.length > 0) {
                        res.data.forEach(function(item) {
                            var rop = parseInt(item.rop) || 0;
                            var stok = parseInt(item.stok_saat_ini) || 0;
                            var min = parseInt(item.min_stok) || 0;
                            var qtySuggest = Math.max(0, item.max_stok - stok);
                            var lt = item.lead_time_hari || 3;
                            var stokLabel, stokClass;
                            if (stok <= rop) { stokLabel='Titik Pesan'; stokClass='danger'; }
                            else if (stok <= min) { stokLabel='Dibawah MIN'; stokClass='warning'; }
                            else { stokLabel='Aman'; stokClass='success'; }
                            var lastSale = item.last_sale_date ? item.last_sale_date : '-';

                            html += '<tr data-item="'+item.no_item+'" data-cabang="'+item.kd_cabang+'">';
                            html += '<td><input type="checkbox" class="item-check" value="' + item.no_item + '|' + item.kd_cabang + '"></td>';
                            html += '<td><small>'+item.no_item+'</small></td>';
                            html += '<td>'+item.nama_item+'</td>';
                            html += '<td><small>'+(item.nama_cabang||item.kd_cabang)+'</small></td>';
                            html += '<td class="text-center"><strong>'+stok+'</strong></td>';
                            html += '<td class="text-center text-danger"><strong>'+rop+'</strong></td>';
                            html += '<td class="text-center mm-min" data-val="'+min+'">'+min+'</td>';
                            html += '<td class="text-center mm-max" data-val="'+item.max_stok+'">'+item.max_stok+'</td>';
                            html += '<td class="text-center"><small>'+lt+'h</small></td>';
                            html += '<td class="text-center"><small>'+(item.avg_interval_hari||0)+'</small></td>';
                            html += '<td class="text-center"><span class="kategori-badge kategori-'+item.kategori+'">'+item.kategori+'</span></td>';
                            html += '<td class="text-center"><strong>'+qtySuggest+'</strong></td>';
                            html += '<td><small>'+lastSale+'</small></td>';
                            html += '<td><span class="label label-'+stokClass+'">'+stokLabel+'</span></td>';
                            html += '<td class="text-center"><button type="button" class="btn btn-xs btn-default btn-edit-mm" title="Override"><i class="fa fa-pencil"></i></button></td>';
                            html += '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="15" class="text-center text-muted">Tidak ada item yang sesuai filter</td></tr>';
                    }
                    $('#tbodyItemsOrder').html(html);
                }
            }
        });
    });

    // Check all
    $('#checkAll').change(function() {
        $('.item-check').prop('checked', $(this).is(':checked'));
    });

    // Inline edit MIN/MAX
    $(document).on('click', '.btn-edit-mm', function() {
        var tr = $(this).closest('tr');
        var noItem = tr.data('item');
        var kdCabang = tr.data('cabang');
        var namaItem = tr.find('td:eq(2)').text().trim();
        var minVal = tr.find('.mm-min').data('val');
        var maxVal = tr.find('.mm-max').data('val');

        $('#mmNoItem').val(noItem);
        $('#mmKdCabang').val(kdCabang);
        $('#mmNamaItem').text(namaItem);
        $('#mmMin').val(minVal);
        $('#mmMax').val(maxVal);
        $('#modalEditMM').modal('show');
    });

    $('#btnSaveMM').click(function() {
        var noItem = $('#mmNoItem').val();
        var kdCabang = $('#mmKdCabang').val();
        var min = parseInt($('#mmMin').val()) || 0;
        var max = parseInt($('#mmMax').val()) || 0;
        if (max < min) { alert('MAX harus >= MIN'); return; }

        $.ajax({
            url: '_ajax/ajax-minmax-calculation.php',
            type: 'POST',
            data: { action: 'set_manual', no_item: noItem, kd_cabang: kdCabang, min_stok: min, max_stok: max },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#modalEditMM').modal('hide');
                    // Update row inline tanpa reload
                    var tr = $('#tbodyItemsOrder tr[data-item="'+noItem+'"][data-cabang="'+kdCabang+'"]');
                    tr.find('.mm-min').text(min).data('val', min);
                    tr.find('.mm-max').text(max).data('val', max);
                    alert('MIN/MAX berhasil diupdate (manual)');
                } else {
                    alert('Error: ' + (res.error||'Gagal simpan'));
                }
            }
        });
    });

    $('#btnResetAuto').click(function() {
        var noItem = $('#mmNoItem').val();
        var kdCabang = $('#mmKdCabang').val();
        if (!confirm('Reset ke kalkulasi otomatis?')) return;

        $.ajax({
            url: '_ajax/ajax-minmax-calculation.php',
            type: 'POST',
            data: { action: 'reset_manual', no_item: noItem, kd_cabang: kdCabang },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#modalEditMM').modal('hide');
                    alert('Reset ke kalkulasi otomatis. Klik "Hitung Ulang MIN/MAX" untuk update nilai.');
                } else {
                    alert('Error: ' + (res.error||'Gagal reset'));
                }
            }
        });
    });

    // Detail supplier items
    $(document).on('click', '.btn-detail-supplier', function() {
        var kdSupplier = $(this).data('supplier');
        var namaSupplier = $(this).data('nama');

        $.ajax({
            url: '_ajax/ajax-minmax-calculation.php',
            type: 'GET',
            data: { action: 'supplier_items', kd_supplier: kdSupplier },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#supplierDetailTitle').html('<i class="fa fa-list"></i> Detail Item — <strong>' + namaSupplier + '</strong>');
                    var html = '<table class="table table-condensed table-bordered table-striped">';
                    html += '<thead><tr><th>Kode</th><th>Nama Item</th><th class="text-center">Stok</th><th class="text-center">MIN</th><th class="text-center">MAX</th><th class="text-center">Kat</th><th class="text-center">Saran Order</th><th class="text-right">Est Nilai</th></tr></thead><tbody>';
                    if (res.data.length > 0) {
                        res.data.forEach(function(r) {
                            html += '<tr>';
                            html += '<td><small>'+r.no_item+'</small></td>';
                            html += '<td>'+r.nama_item+'</td>';
                            html += '<td class="text-center">'+r.stok_saat_ini+'</td>';
                            html += '<td class="text-center">'+r.min_stok+'</td>';
                            html += '<td class="text-center">'+r.max_stok+'</td>';
                            html += '<td class="text-center"><span class="kategori-badge kategori-'+r.kategori+'">'+r.kategori+'</span></td>';
                            html += '<td class="text-center"><strong>'+r.qty_order_suggest+'</strong></td>';
                            html += '<td class="text-right">Rp '+Number(r.est_nilai).toLocaleString('id-ID')+'</td>';
                            html += '</tr>';
                        });
                    } else {
                        html += '<tr><td colspan="8" class="text-center text-muted">Tidak ada item</td></tr>';
                    }
                    html += '</tbody></table>';
                    $('#supplierDetailBody').html(html);
                    $('#supplierDetail').show();
                    $('html, body').animate({ scrollTop: $('#supplierDetail').offset().top - 60 }, 400);
                }
            }
        });
    });

    // Generate rencana order
    $('#btnGenerateRencana').click(function() {
        var selected = [];
        $('.item-check:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            alert('Pilih minimal 1 item untuk dibuat rencana order');
            return;
        }

        if (confirm('Buat rencana order untuk ' + selected.length + ' item?')) {
            alert('Fitur dalam pengembangan');
        }
    });
});
</script>
</body>
</html>
