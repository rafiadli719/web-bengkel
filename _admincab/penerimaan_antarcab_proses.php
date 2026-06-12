<?php
/**
 * Proses Penerimaan Antar Cabang
 * Menampilkan detail pesanan dan memproses penerimaan (update stok)
 */
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user=$_SESSION['_iduser'];
$kd_cabang=$_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd=mysqli_query($koneksi,"SELECT
                                nama_user, password, user_akses, foto_user
                                FROM tbuser WHERE id='$id_user'");
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_user'];
$lvl_akses=$tm_cari['user_akses'];
$foto_user=$tm_cari['foto_user'];
if($foto_user=='') {
    $foto_user="file_upload/avatar.png";
}

// Data Cabang
$cari_kd=mysqli_query($koneksi,"SELECT
                                nama_cabang, tipe_cabang
                                FROM tbcabang
                                WHERE kode_cabang='$kd_cabang'");
$tm_cari=mysqli_fetch_array($cari_kd);
$nama_cabang=$tm_cari['nama_cabang'];
$tipe_cabang=$tm_cari['tipe_cabang'];

// Get order number
$no_order = isset($_GET['no']) ? mysqli_real_escape_string($koneksi, $_GET['no']) : '';

if(empty($no_order)){
    echo "<script>alert('No. Pesanan tidak valid');window.location='penerimaan_antarcab.php';</script>";
    exit;
}

// Get order header
$has_kd_tujuan = false;
$has_order_ke = false;

$qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'kd_cabang_tujuan'");
if($qcol && mysqli_num_rows($qcol)>0){ $has_kd_tujuan = true; }
$qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'order_ke'");
if($qcol && mysqli_num_rows($qcol)>0){ $has_order_ke = true; }

$tujuan_cond = [];
if($has_kd_tujuan){ $tujuan_cond[] = "oh.kd_cabang_tujuan = '$kd_cabang'"; }
if($has_order_ke){ $tujuan_cond[] = "oh.order_ke = '$kd_cabang'"; }
if(empty($tujuan_cond)){ $tujuan_cond[] = '1=0'; }

$q_header = mysqli_query($koneksi, "SELECT oh.*, c.nama_cabang as cabang_asal
                                     FROM tblorderjual_header oh
                                     LEFT JOIN tbcabang c ON c.kode_cabang = oh.kd_cabang
                                     WHERE oh.no_order = '$no_order'
                                     AND (".implode(' OR ', $tujuan_cond).")");

if(!$q_header || mysqli_num_rows($q_header) == 0){
    echo "<script>alert('Pesanan tidak ditemukan atau bukan untuk cabang Anda');window.location='penerimaan_antarcab.php';</script>";
    exit;
}

$header = mysqli_fetch_array($q_header);

// Check if already received
if($header['status'] == '1'){
    echo "<script>alert('Pesanan ini sudah diterima sebelumnya');window.location='penerimaan_antarcab.php';</script>";
    exit;
}

// Process receive
if(isset($_POST['btn_terima'])){
    $tanggal_terima = date('Y-m-d');

    mysqli_begin_transaction($koneksi);

    try {
        // Buat PO di cabang penerima dari pesanan penjualan antar cabang
        $prefix = "PS" . date('y');
        $last_query = mysqli_query($koneksi, "SELECT no_order FROM tblorder_header WHERE no_order LIKE '$prefix%' ORDER BY no_order DESC LIMIT 1");
        if ($last_query && mysqli_num_rows($last_query) > 0) {
            $last_row = mysqli_fetch_assoc($last_query);
            $last_num = (int)substr($last_row['no_order'], 4);
            $new_num = $last_num + 1;
        } else {
            $new_num = 1;
        }
        $no_po = $prefix . str_pad($new_num, 9, '0', STR_PAD_LEFT);

        $carabayar_src = isset($header['cara_bayar']) ? $header['cara_bayar'] : 'Tunai';
        $syarat_hari_src = isset($header['syarat_hari']) ? (int)$header['syarat_hari'] : 0;
        $payment_term = ($carabayar_src == 'Kredit') ? ('Kredit:' . $syarat_hari_src) : 'Tunai:0';

        // Calculate totals + insert detail
        $total_qty = 0;
        $total_order = 0;

        $q_items = mysqli_query($koneksi, "SELECT * FROM tblorderjual_detail WHERE no_order='$no_order'");
        if(!$q_items){
            throw new Exception('Gagal mengambil detail pesanan: ' . mysqli_error($koneksi));
        }

        while($item = mysqli_fetch_array($q_items)){
            $no_item = mysqli_real_escape_string($koneksi, $item['no_item']);
            $qty = (int)$item['quantity'];
            $harga = (float)$item['harga_jual'];
            $subtotal = $qty * $harga;

            if($qty <= 0){
                continue;
            }

            $total_qty += $qty;
            $total_order += $subtotal;

            $sql_detail = "INSERT INTO tblorder_detail
                            (no_order, no_item, harga_pokok, quantity, total, user, kd_cabang, status_trx)
                            VALUES
                            ('$no_po', '$no_item', '$harga', '$qty', '$subtotal', '$_nama', '$kd_cabang', '1')";
            if(!mysqli_query($koneksi, $sql_detail)){
                throw new Exception('Gagal menyimpan detail PO: ' . mysqli_error($koneksi));
            }
        }

        if($total_qty <= 0){
            throw new Exception('Tidak ada item valid untuk dibuat PO.');
        }

        $supplier_cabang_asal = mysqli_real_escape_string($koneksi, $header['kd_cabang']);
        $note_po = "Penerimaan Antar Cabang dari {$header['kd_cabang']} | Ref: $no_order";

        $sql_header = "INSERT INTO tblorder_header
                        (no_order, status, tanggal, tglkirim, no_supplier, note, total_qty, total_terima, total_order, user, Id_tabel, kd_cabang, status_pesanan, tipe_trx, no_penjualan, status_approval, po_type, payment_term)
                        VALUES
                        ('$no_po', '0', '$tanggal_terima', '', '$supplier_cabang_asal', '$note_po', '$total_qty', '0', '$total_order', '$_nama', '', '$kd_cabang', '0', 'ANTAR_CABANG', '$no_order', 'approved', 'regular', '$payment_term')";
        if(!mysqli_query($koneksi, $sql_header)){
            throw new Exception('Gagal menyimpan header PO: ' . mysqli_error($koneksi));
        }

        // Tandai pesanan antar cabang sudah diterima agar tidak muncul lagi
        $has_tanggal_terima = false;
        $has_user_terima = false;
        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'tanggal_terima'");
        if($qcol && mysqli_num_rows($qcol) > 0){ $has_tanggal_terima = true; }
        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'user_terima'");
        if($qcol && mysqli_num_rows($qcol) > 0){ $has_user_terima = true; }

        $set_parts = ["status='1'"];
        if($has_tanggal_terima){
            $set_parts[] = "tanggal_terima='$tanggal_terima'";
        }
        if($has_user_terima){
            $set_parts[] = "user_terima='$_nama'";
        }

        $sql_update = "UPDATE tblorderjual_header SET ".implode(', ', $set_parts)." WHERE no_order='$no_order'";
        if(!mysqli_query($koneksi, $sql_update)){
            throw new Exception('Gagal update status pesanan: ' . mysqli_error($koneksi));
        }

        mysqli_commit($koneksi);

        echo "<script>alert('Penerimaan berhasil! PO otomatis dibuat: $no_po\\nSilakan lanjut input Pembelian (nota) untuk menambah stok.');window.location='pembelian_add.php?po=$no_po';</script>";
        exit;

    } catch(Exception $e) {
        mysqli_rollback($koneksi);
        $error_msg = "Gagal memproses penerimaan: " . $e->getMessage();
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

    <!-- bootstrap & fontawesome -->
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
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand">
                    <small>
                        <i class="fa fa-leaf"></i>
                        <?php include "../lib/subtitel.php"; ?>
                    </small>
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
        <script type="text/javascript">
            try{ace.settings.loadState('main-container')}catch(e){}
        </script>

        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">
                try{ace.settings.loadState('sidebar')}catch(e){}
            </script>

            <?php include "menu_penjualan_unified.php"; ?>

            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="ace-icon fa fa-home home-icon"></i>
                            <a href="index.php">Home</a>
                        </li>
                        <li><a href="#">Antar Cabang</a></li>
                        <li><a href="penerimaan_antarcab.php">Penerimaan</a></li>
                        <li class="active">Proses Terima</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Proses Penerimaan
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                <?php echo $no_order; ?>
                            </small>
                        </h1>
                    </div>

                    <?php if(isset($error_msg)){ ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                    </div>
                    <?php } ?>

                    <div class="row">
                        <div class="col-xs-12 col-md-6">
                            <div class="widget-box">
                                <div class="widget-header widget-header-blue">
                                    <h4 class="widget-title">Informasi Pesanan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td width="40%"><strong>No. Pesanan</strong></td>
                                                <td><?php echo $header['no_order']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tanggal</strong></td>
                                                <td><?php echo date('d/m/Y', strtotime($header['tanggal'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dari Cabang</strong></td>
                                                <td>
                                                    <?php echo $header['cabang_asal']; ?>
                                                    <small class="text-muted">(<?php echo $header['kd_cabang']; ?>)</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Cara Bayar</strong></td>
                                                <td><?php echo $header['cara_bayar']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Keterangan</strong></td>
                                                <td><?php echo $header['note'] ? $header['note'] : '-'; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-md-6">
                            <div class="widget-box">
                                <div class="widget-header widget-header-green">
                                    <h4 class="widget-title">Ringkasan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <div class="well text-center">
                                                    <h3><?php echo number_format($header['total_qty'], 0); ?></h3>
                                                    <small>Total Qty</small>
                                                </div>
                                            </div>
                                            <div class="col-xs-6">
                                                <div class="well text-center">
                                                    <h3>Rp <?php echo number_format($header['total_order'], 0, ',', '.'); ?></h3>
                                                    <small>Total Nilai</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-6"></div>

                    <!-- Detail Items -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                Detail Item yang Akan Diterima
                            </div>

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">Kode Barang</th>
                                        <th width="35%">Nama Barang</th>
                                        <th class="right" width="10%">Qty</th>
                                        <th class="right" width="15%">Harga</th>
                                        <th class="right" width="15%">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_detail = mysqli_query($koneksi, "SELECT d.*, i.namaitem
                                                                         FROM tblorderjual_detail d
                                                                         LEFT JOIN tblitem i ON i.noitem = d.no_item
                                                                         WHERE d.no_order = '$no_order'");
                                    $no = 0;
                                    $grand_total = 0;
                                    while($detail = mysqli_fetch_array($q_detail)){
                                        $no++;
                                        $subtotal = $detail['quantity'] * $detail['harga_jual'];
                                        $grand_total += $subtotal;
                                    ?>
                                    <tr>
                                        <td><?php echo $no; ?></td>
                                        <td><?php echo $detail['no_item']; ?></td>
                                        <td><?php echo $detail['namaitem']; ?></td>
                                        <td class="right"><?php echo number_format($detail['quantity'], 0); ?></td>
                                        <td class="right"><?php echo number_format($detail['harga_jual'], 0, ',', '.'); ?></td>
                                        <td class="right"><?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="right">Grand Total:</th>
                                        <th class="right"><?php echo number_format($grand_total, 0, ',', '.'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="space-12"></div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-xs-12">
                            <form method="post" onsubmit="return confirm('Proses penerimaan? Stok akan ditambahkan ke cabang Anda.');">
                                <div class="btn-group btn-group-justified">
                                    <div class="btn-group">
                                        <button type="submit" name="btn_terima" class="btn btn-success btn-lg">
                                            <i class="fa fa-check"></i> Proses Terima
                                        </button>
                                    </div>
                                    <div class="btn-group">
                                        <a href="penerimaan_antarcab.php" class="btn btn-default btn-lg">
                                            <i class="fa fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content">
                    <?php include "../lib/footer.php"; ?>
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
