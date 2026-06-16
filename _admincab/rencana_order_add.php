<?php
/**
 * RENCANA ORDER ADD - Form Buat Rencana Order Baru
 * Menampilkan item yang perlu diorder berdasarkan kalkulasi MIN/MAX
 */
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user=$_SESSION['_iduser'];
$kd_cabang=$_SESSION['_cabang'];
include "../config/koneksi.php";
require_once "lib/MinMaxCalculator.php";

$cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_user'];
$foto_user=$tm_cari['foto_user'];
if($foto_user=='') {
    $foto_user="file_upload/avatar.png";
}

// Data Cabang
$cari_kd=mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$tm_cari=mysqli_fetch_array($cari_kd);
$nama_cabang=$tm_cari['nama_cabang'];

$success_message = '';
$error_message = '';

// Get all cabang for filter
$cabang_list = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang WHERE status='aktif' ORDER BY nama_cabang");
if(!$cabang_list){
    $cabang_list = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang");
}
if(!$cabang_list){
    $error_message = 'Gagal mengambil daftar cabang.';
}

// Get all suppliers
$supplier_list = mysqli_query($koneksi, "SELECT kode_supplier, nama_supplier, tempo_hari, kategori_tempo FROM tblsupplier_tempo WHERE is_active=1 ORDER BY nama_supplier");
$suppliers = [];
while ($s = mysqli_fetch_assoc($supplier_list)) {
    $suppliers[$s['kode_supplier']] = $s;
}

// Generate new rencana order number
$tgl_skr = date('Y-m-d');
$prefix = "RO" . date('Ymd');
$last_no = mysqli_query($koneksi, "SELECT no_rencana FROM tblrencana_order_header WHERE no_rencana LIKE '$prefix%' ORDER BY no_rencana DESC LIMIT 1");
if (mysqli_num_rows($last_no) > 0) {
    $row = mysqli_fetch_array($last_no);
    $last_num = (int)substr($row['no_rencana'], -4);
    $new_num = $last_num + 1;
} else {
    $new_num = 1;
}
$no_rencana = $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);

// Period calculation (84 days)
$periode_akhir = date('Y-m-d');
$periode_awal = date('Y-m-d', strtotime('-84 days'));

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rencana'])) {
    $items = isset($_POST['items']) ? $_POST['items'] : [];

    if (empty($items)) {
        $error_message = "Pilih minimal satu item untuk dibuat rencana order.";
    } else {
        // Start transaction
        mysqli_begin_transaction($koneksi);

        try {
            // Calculate totals
            $total_item = count($items);
            $total_qty = 0;
            $total_nilai = 0;

            foreach ($items as $no_item => $item_data) {
                $qty1 = (int)$item_data['order1_qty'];
                $qty2 = (int)$item_data['order2_qty'];
                $harga = (float)$item_data['harga'];
                $total_qty += $qty1 + $qty2;
                $total_nilai += ($qty1 + $qty2) * $harga;
            }

            // Insert header
            $sql_header = "INSERT INTO tblrencana_order_header
                (no_rencana, tanggal, periode_awal, periode_akhir, total_item, total_qty, total_nilai, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())";

            $stmt = mysqli_prepare($koneksi, $sql_header);
            mysqli_stmt_bind_param($stmt, "ssssiids", $no_rencana, $tgl_skr, $periode_awal, $periode_akhir, $total_item, $total_qty, $total_nilai, $_nama);
            mysqli_stmt_execute($stmt);

            // Insert details
            foreach ($items as $no_item => $item_data) {
                $nama_item = $item_data['nama_item'];
                $jenis = $item_data['jenis'];
                $harga = (float)$item_data['harga'];
                $order1_qty = (int)$item_data['order1_qty'];
                $order1_supplier = $item_data['order1_supplier'];
                $order2_qty = (int)$item_data['order2_qty'];
                $order2_supplier = $item_data['order2_supplier'];

                $order1_nilai = $order1_qty * $harga;
                $order2_nilai = $order2_qty * $harga;

                $sql_detail = "INSERT INTO tblrencana_order_detail
                    (no_rencana, no_item, nama_item, jenis, harga,
                     order1_qty, order1_qty_final, order1_supplier, order1_supplier_final, order1_nilai,
                     order2_qty, order2_qty_final, order2_supplier, order2_supplier_final, order2_nilai)
                    VALUES (?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($koneksi, $sql_detail);
                mysqli_stmt_bind_param($stmt, "ssssdisssdisssd",
                    $no_rencana, $no_item, $nama_item, $jenis, $harga,
                    $order1_qty, $order1_qty, $order1_supplier, $order1_supplier, $order1_nilai,
                    $order2_qty, $order2_qty, $order2_supplier, $order2_supplier, $order2_nilai
                );
                mysqli_stmt_execute($stmt);

                // Insert detail per cabang (if cabang data available)
                if (isset($item_data['cabang'])) {
                    foreach ($item_data['cabang'] as $cab => $cab_data) {
                        $stok_awal = (int)$cab_data['stok'];
                        $min_stok = (int)$cab_data['min'];
                        $max_stok = (int)$cab_data['max'];
                        $kategori = $cab_data['kategori'];
                        $order_qty = (int)$cab_data['order_qty'];

                        $sql_cab = "INSERT INTO tblrencana_order_detail_cabang
                            (no_rencana, no_item, kd_cabang, stok_awal, min_stok, max_stok, kategori, order_qty)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                        $stmt = mysqli_prepare($koneksi, $sql_cab);
                        mysqli_stmt_bind_param($stmt, "sssiiisi",
                            $no_rencana, $no_item, $cab, $stok_awal, $min_stok, $max_stok, $kategori, $order_qty
                        );
                        mysqli_stmt_execute($stmt);
                    }
                }
            }

            mysqli_commit($koneksi);
            $success_message = "Rencana Order <strong>$no_rencana</strong> berhasil dibuat!";

            // Redirect to detail page
            header("Location: rencana_order_detail.php?no=" . urlencode($no_rencana) . "&created=1");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error_message = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}

// Get items that need ordering
$calculator = new MinMaxCalculator($koneksi);
$filter_cabang = isset($_GET['cabang']) ? $_GET['cabang'] : null;
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

$items_need_order = $calculator->getItemPerluOrder($filter_cabang, $filter_kategori, $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .item-row { transition: background-color 0.3s; }
        .item-row.selected { background-color: #dff0d8 !important; }
        .item-row:hover { background-color: #f5f5f5; }
        .status-urgent { color: #d9534f; font-weight: bold; }
        .status-warning { color: #f0ad4e; font-weight: bold; }
        .kategori-A { background-color: #dff0d8; }
        .kategori-B { background-color: #d9edf7; }
        .kategori-C { background-color: #fcf8e3; }
        .kategori-D { background-color: #f2dede; }
        .summary-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .fixed-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 2px solid #438eb9;
            padding: 15px;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        .content-with-bottom-bar {
            padding-bottom: 100px;
        }
        .qty-input {
            width: 70px;
            text-align: center;
        }
        .supplier-select {
            width: 120px;
        }
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
                        <li class="active">Buat Rencana Order</li>
                    </ul>
                </div>

                <div class="page-content content-with-bottom-bar">
                    <div class="page-header">
                        <h1>
                            Buat Rencana Order
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                No: <strong><?php echo $no_rencana; ?></strong> | Periode: <?php echo date('d/m/Y', strtotime($periode_awal)); ?> - <?php echo date('d/m/Y', strtotime($periode_akhir)); ?>
                            </small>
                        </h1>
                    </div>

                    <?php if (!empty($error_message)) { ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                    <?php } ?>

                    <?php if (!empty($success_message)) { ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check"></i> <?php echo $success_message; ?>
                    </div>
                    <?php } ?>

                    <!-- Filter Section -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Filter Item</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="GET" class="form-inline">
                                            <div class="form-group">
                                                <label>Cabang:</label>
                                                <select name="cabang" class="form-control">
                                                    <option value="">Semua Cabang</option>
                                                    <?php
                                                    if($cabang_list){
                                                        mysqli_data_seek($cabang_list, 0);
                                                        while ($cab = mysqli_fetch_assoc($cabang_list)) {
                                                            $selected = ($filter_cabang == $cab['kode_cabang']) ? 'selected' : '';
                                                            echo "<option value='{$cab['kode_cabang']}' $selected>{$cab['nama_cabang']}</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group" style="margin-left: 15px;">
                                                <label>Kategori:</label>
                                                <select name="kategori" class="form-control">
                                                    <option value="">Semua</option>
                                                    <option value="A" <?php echo $filter_kategori == 'A' ? 'selected' : ''; ?>>A - Fast Moving</option>
                                                    <option value="B" <?php echo $filter_kategori == 'B' ? 'selected' : ''; ?>>B - Medium Moving</option>
                                                    <option value="C" <?php echo $filter_kategori == 'C' ? 'selected' : ''; ?>>C - Slow Moving</option>
                                                </select>
                                            </div>
                                            <div class="form-group" style="margin-left: 15px;">
                                                <label>Limit:</label>
                                                <select name="limit" class="form-control">
                                                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                                    <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                                                    <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-info" style="margin-left: 15px;">
                                                <i class="fa fa-filter"></i> Filter
                                            </button>
                                            <button type="button" id="btnRecalculate" class="btn btn-warning" style="margin-left: 10px;">
                                                <i class="fa fa-refresh"></i> Hitung Ulang MIN/MAX
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space space-8"></div>

                    <!-- Items Table -->
                    <form method="POST" id="formRencanaOrder">
                        <input type="hidden" name="submit_rencana" value="1">

                        <div class="row">
                            <div class="col-xs-12">
                                <div class="table-header">
                                    <div class="pull-left">
                                        <label>
                                            <input type="checkbox" id="selectAll"> Pilih Semua
                                        </label>
                                    </div>
                                    <div class="pull-right">
                                        Total Item Perlu Order: <strong><?php echo count($items_need_order); ?></strong>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="tableItems">
                                        <thead>
                                            <tr class="info">
                                                <th class="center" width="3%"><input type="checkbox" id="checkHeader"></th>
                                                <th width="8%">No Item</th>
                                                <th width="15%">Nama Item</th>
                                                <th width="5%">Ktg</th>
                                                <th class="center" width="5%">Stok</th>
                                                <th class="center" width="5%">MIN</th>
                                                <th class="center" width="5%">MAX</th>
                                                <th class="center" width="5%">Status</th>
                                                <th class="right" width="8%">Harga</th>
                                                <th class="center" width="8%">ORDER 1</th>
                                                <th width="10%">Supplier 1</th>
                                                <th class="center" width="8%">ORDER 2</th>
                                                <th width="10%">Supplier 2</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (!empty($items_need_order)) {
                                                foreach ($items_need_order as $item) {
                                                    $status_class = $item['status_stok'] == 'URGENT' ? 'status-urgent' : 'status-warning';
                                                    $kategori_class = 'kategori-' . $item['kategori'];
                                                    $no_item = htmlspecialchars($item['no_item']);
                                                    $default_supplier1 = $item['supplier1'] ?? '';
                                                    $default_supplier2 = $item['supplier2'] ?? 'NSA';

                                                    // Calculate suggested order qty
                                                    $shortage = max(0, $item['max_stok'] - $item['stok_saat_ini']);
                                                    $suggest_qty = $shortage;
                                            ?>
                                            <tr class="item-row <?php echo $kategori_class; ?>" data-item="<?php echo $no_item; ?>">
                                                <td class="center">
                                                    <input type="checkbox" class="item-check" name="items[<?php echo $no_item; ?>][selected]" value="1">
                                                </td>
                                                <td>
                                                    <strong><?php echo $no_item; ?></strong>
                                                    <input type="hidden" name="items[<?php echo $no_item; ?>][nama_item]" value="<?php echo htmlspecialchars($item['nama_item']); ?>">
                                                    <input type="hidden" name="items[<?php echo $no_item; ?>][jenis]" value="<?php echo htmlspecialchars($item['jenis'] ?? ''); ?>">
                                                    <input type="hidden" name="items[<?php echo $no_item; ?>][harga]" value="<?php echo $item['harga']; ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($item['nama_item']); ?></td>
                                                <td class="center">
                                                    <span class="badge badge-<?php echo strtolower($item['kategori']); ?>" title="<?php echo $item['kategori_desc']; ?>">
                                                        <?php echo $item['kategori']; ?>
                                                    </span>
                                                </td>
                                                <td class="center"><?php echo number_format($item['stok_saat_ini']); ?></td>
                                                <td class="center"><?php echo number_format($item['min_stok']); ?></td>
                                                <td class="center"><?php echo number_format($item['max_stok']); ?></td>
                                                <td class="center <?php echo $status_class; ?>"><?php echo $item['status_stok']; ?></td>
                                                <td class="right"><?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                                <td class="center">
                                                    <input type="number" name="items[<?php echo $no_item; ?>][order1_qty]"
                                                           class="form-control qty-input order1-qty" value="<?php echo $suggest_qty; ?>" min="0"
                                                           data-harga="<?php echo $item['harga']; ?>">
                                                </td>
                                                <td>
                                                    <select name="items[<?php echo $no_item; ?>][order1_supplier]" class="form-control supplier-select">
                                                        <option value="">-Pilih-</option>
                                                        <?php foreach ($suppliers as $code => $sup) {
                                                            if ($sup['kategori_tempo'] == 'cepat') {
                                                                $selected = ($code == $default_supplier1) ? 'selected' : '';
                                                                echo "<option value='$code' $selected>{$sup['nama_supplier']}</option>";
                                                            }
                                                        } ?>
                                                    </select>
                                                </td>
                                                <td class="center">
                                                    <input type="number" name="items[<?php echo $no_item; ?>][order2_qty]"
                                                           class="form-control qty-input order2-qty" value="0" min="0"
                                                           data-harga="<?php echo $item['harga']; ?>">
                                                </td>
                                                <td>
                                                    <select name="items[<?php echo $no_item; ?>][order2_supplier]" class="form-control supplier-select">
                                                        <option value="">-Pilih-</option>
                                                        <?php foreach ($suppliers as $code => $sup) {
                                                            if ($sup['kategori_tempo'] == 'lambat') {
                                                                $selected = ($code == $default_supplier2) ? 'selected' : '';
                                                                echo "<option value='$code' $selected>{$sup['nama_supplier']}</option>";
                                                            }
                                                        } ?>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php
                                                }
                                            } else {
                                            ?>
                                            <tr>
                                                <td colspan="13" class="center">
                                                    <div class="alert alert-success">
                                                        <i class="fa fa-check-circle"></i>
                                                        Tidak ada item yang perlu diorder saat ini. Semua stok dalam kondisi baik.
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Fixed Bottom Bar -->
                        <div class="fixed-bottom-bar">
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="summary-box">
                                        <strong>Item Dipilih:</strong> <span id="selectedCount">0</span>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="summary-box">
                                        <strong>Total Qty ORDER 1:</strong> <span id="totalQty1">0</span>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="summary-box">
                                        <strong>Total Qty ORDER 2:</strong> <span id="totalQty2">0</span>
                                    </div>
                                </div>
                                <div class="col-sm-3 text-right">
                                    <a href="rencana_order.php" class="btn btn-default">
                                        <i class="fa fa-arrow-left"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
                                        <i class="fa fa-save"></i> Simpan Rencana Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->
    </div><!-- /.main-container -->

    <!-- basic scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
    $(document).ready(function() {
        // Select all checkbox
        $('#selectAll, #checkHeader').change(function() {
            var isChecked = $(this).is(':checked');
            $('.item-check').prop('checked', isChecked);
            updateRowHighlight();
            updateSummary();
        });

        // Individual checkbox
        $('.item-check').change(function() {
            updateRowHighlight();
            updateSummary();
        });

        // Qty change
        $('.order1-qty, .order2-qty').on('input', function() {
            updateSummary();
        });

        function updateRowHighlight() {
            $('.item-row').each(function() {
                var checkbox = $(this).find('.item-check');
                if (checkbox.is(':checked')) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        }

        function updateSummary() {
            var selectedCount = 0;
            var totalQty1 = 0;
            var totalQty2 = 0;

            $('.item-check:checked').each(function() {
                selectedCount++;
                var row = $(this).closest('tr');
                totalQty1 += parseInt(row.find('.order1-qty').val()) || 0;
                totalQty2 += parseInt(row.find('.order2-qty').val()) || 0;
            });

            $('#selectedCount').text(selectedCount);
            $('#totalQty1').text(totalQty1.toLocaleString());
            $('#totalQty2').text(totalQty2.toLocaleString());
        }

        // Recalculate MIN/MAX
        $('#btnRecalculate').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menghitung...');

            $.ajax({
                url: '_ajax/ajax-minmax-calculation.php',
                method: 'POST',
                data: { action: 'recalculate' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Kalkulasi MIN/MAX berhasil. Halaman akan dimuat ulang.');
                        location.reload();
                    } else {
                        alert('Gagal: ' + response.error);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menghubungi server.');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Hitung Ulang MIN/MAX');
                }
            });
        });

        // Form submit validation
        $('#formRencanaOrder').submit(function(e) {
            var selectedCount = $('.item-check:checked').length;
            if (selectedCount === 0) {
                e.preventDefault();
                alert('Pilih minimal satu item untuk dibuat rencana order.');
                return false;
            }

            // Remove unchecked items from submission
            $('.item-check:not(:checked)').each(function() {
                $(this).closest('tr').find('input, select').prop('disabled', true);
            });

            return true;
        });

        // Initial summary
        updateSummary();
    });
    </script>
</body>
</html>
