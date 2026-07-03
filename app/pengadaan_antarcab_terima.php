<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi,"SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama     = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

$kd_safe = mysqli_real_escape_string($koneksi, $kd_cabang);
$cari_cab = mysqli_query($koneksi,"SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_safe'");
$tm_cab   = mysqli_fetch_array($cari_cab);
$nama_cabang = $tm_cab['nama_cabang'];
$tipe_cabang = $tm_cab['tipe_cabang'];
$is_pusat    = ($tipe_cabang=='1' || strtolower($tipe_cabang)=='pusat');

$no_order = isset($_GET['no']) ? mysqli_real_escape_string($koneksi, $_GET['no']) : '';
if(!$no_order){ header("location:pengadaan_antarcab.php"); exit; }

$qh = mysqli_query($koneksi,"SELECT h.*, COALESCE(h.jenis,'pull') AS jenis_order,
    ct.nama_cabang AS nama_tujuan, ca.nama_cabang AS nama_asal
    FROM tblorder_antarcab_header h
    LEFT JOIN tbcabang ct ON ct.kode_cabang=h.kd_cabang_tujuan
    LEFT JOIN tbcabang ca ON ca.kode_cabang=h.kd_cabang_asal
    WHERE h.no_order='$no_order' LIMIT 1");
if(!$qh || mysqli_num_rows($qh)==0){ header("location:pengadaan_antarcab.php"); exit; }
$header      = mysqli_fetch_assoc($qh);
$jenis_order = $header['jenis_order'];

// PULL: penerima=kd_cabang_asal; PUSH: penerima=kd_cabang_tujuan
if(!$is_pusat){
    $is_recipient = ($jenis_order=='push' && $header['kd_cabang_tujuan']==$kd_cabang)
                 || ($jenis_order!='push' && $header['kd_cabang_asal']==$kd_cabang);
    if(!$is_recipient){ header("location:pengadaan_antarcab.php"); exit; }
}
if($header['status'] != 'dikirim'){ header("location:pengadaan_antarcab.php"); exit; }

$qd      = mysqli_query($koneksi,"SELECT d.*, i.namaitem FROM tblorder_antarcab_detail d
    LEFT JOIN tblitem i ON i.noitem=d.no_item
    WHERE d.no_order='$no_order' ORDER BY d.no_baris");
$details = [];
while($r = mysqli_fetch_assoc($qd)) $details[] = $r;

$error_msg = '';

if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['btnterima'])){
    $tgl_terima = mysqli_real_escape_string($koneksi, $_POST['tgl_terima'] ?? date('Y-m-d'));
    $item_count = (int)($_POST['item_count'] ?? 0);

    $items_terima = [];
    for($i=1; $i<=$item_count; $i++){
        $items_terima[] = [
            'no_item'    => $_POST["no_item_$i"]     ?? '',
            'no_baris'   => (int)($_POST["no_baris_$i"]    ?? $i),
            'qty_terima' => (int)($_POST["qty_terima_$i"]  ?? 0),
            'harga_pokok'=> (float)($_POST["hpp_$i"]       ?? 0),
        ];
    }

    $total_qty_terima = array_sum(array_column($items_terima,'qty_terima'));
    if($total_qty_terima == 0){ $error_msg = "Minimal 1 barang dengan qty terima > 0."; }

    if(!$error_msg){
        $col_check     = mysqli_query($koneksi,"SHOW COLUMNS FROM tbstok LIKE 'kd_cabang'");
        $has_kd_cabang = ($col_check && mysqli_num_rows($col_check)>0);
        // PUSH: stok masuk ke kd_cabang_tujuan (cabang penerima); PULL: ke kd_cabang_asal
        $kd_penerima   = ($jenis_order=='push')
            ? mysqli_real_escape_string($koneksi, $header['kd_cabang_tujuan'])
            : mysqli_real_escape_string($koneksi, $header['kd_cabang_asal']);

        mysqli_begin_transaction($koneksi);
        try {
            $total_nilai = 0;
            foreach($items_terima as $it){
                $ni  = mysqli_real_escape_string($koneksi, $it['no_item']);
                $qt  = (int)$it['qty_terima'];
                $hpp = (float)$it['harga_pokok'];
                $nb  = (int)$it['no_baris'];
                $sub = $qt * $hpp;
                $total_nilai += $sub;

                $qu = mysqli_query($koneksi,"UPDATE tblorder_antarcab_detail
                    SET qty_terima='$qt'
                    WHERE no_order='$no_order' AND no_baris='$nb'");
                if(!$qu) throw new Exception(mysqli_error($koneksi));

                if($qt > 0){
                    $no_safe = mysqli_real_escape_string($koneksi, $no_order);
                    // PUSH: pengirim=nama_asal(pusat); PULL: pengirim=nama_tujuan(pusat)
                    $nm_pengirim = ($jenis_order=='push') ? $header['nama_asal'] : $header['nama_tujuan'];
                    $ket     = mysqli_real_escape_string($koneksi, 'Penerimaan AC - Dari '.$nm_pengirim);
                    if($has_kd_cabang){
                        $qs = mysqli_query($koneksi,"INSERT INTO tbstok (no_transaksi,no_item,tanggal,tipe,masuk,keluar,keterangan,kd_cabang)
                            VALUES ('$no_safe','$ni','$tgl_terima','2','$qt','0','$ket','$kd_penerima')");
                    } else {
                        $qs = mysqli_query($koneksi,"INSERT INTO tbstok (no_transaksi,no_item,tanggal,tipe,masuk,keluar,keterangan)
                            VALUES ('$no_safe','$ni','$tgl_terima','2','$qt','0','$ket')");
                    }
                    if(!$qs) throw new Exception(mysqli_error($koneksi));
                }
            }

            $qu = mysqli_query($koneksi,"UPDATE tblorder_antarcab_header
                SET status='selesai', tanggal_terima='$tgl_terima',
                    total_qty='$total_qty_terima', total_nilai='$total_nilai'
                WHERE no_order='$no_order'");
            if(!$qu) throw new Exception(mysqli_error($koneksi));

            mysqli_commit($koneksi);
            header("location:pengadaan_antarcab.php?msg=terima_ok");
            exit;
        } catch(Exception $e){
            mysqli_rollback($koneksi);
            $error_msg = "Gagal konfirmasi: ".$e->getMessage();
        }
    }
}
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
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css"/>
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
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="pengadaan_antarcab.php">Permintaan Antar Cabang</a></li>
                    <li class="active">Konfirmasi Terima</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Konfirmasi Penerimaan Barang
                        <small><i class="ace-icon fa fa-angle-double-right"></i> <?php echo htmlspecialchars($no_order); ?></small>
                    </h1>
                </div>

                <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <i class="fa fa-times-circle"></i> <?php echo $error_msg; ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xs-12 col-sm-7">
                        <table class="table table-bordered table-condensed">
                            <tr class="success"><th colspan="2"><i class="fa fa-truck"></i> Paket Dikirim ke <?php echo htmlspecialchars($nama_cabang); ?></th></tr>
                            <tr><td style="width:160px"><b>No. Permintaan</b></td><td><strong><?php echo htmlspecialchars($header['no_order']); ?></strong></td></tr>
                            <tr><td><b>Dari (Pengirim)</b></td><td><?php
                                $nm_kirim = ($jenis_order=='push') ? $header['nama_asal'] : $header['nama_tujuan'];
                                $kd_kirim = ($jenis_order=='push') ? $header['kd_cabang_asal'] : $header['kd_cabang_tujuan'];
                                echo htmlspecialchars($nm_kirim?:$kd_kirim);
                            ?></td></tr>
                            <tr><td><b>Tgl. Dikirim</b></td><td><?php echo $header['tanggal_kirim'] ? date('d/m/Y',strtotime($header['tanggal_kirim'])) : '—'; ?></td></tr>
                            <tr><td><b>Diproses Oleh</b></td><td><?php echo htmlspecialchars($header['user_proses']?:'—'); ?></td></tr>
                            <?php if($header['catatan']): ?><tr><td><b>Catatan</b></td><td><?php echo htmlspecialchars($header['catatan']); ?></td></tr><?php endif; ?>
                        </table>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Isi qty yang <strong>benar-benar diterima</strong>. Boleh lebih sedikit dari qty kirim jika ada kekurangan.
                    Stok <?php echo htmlspecialchars($nama_cabang); ?> akan bertambah sesuai qty terima.
                </div>

                <form method="post">
                    <div class="row">
                        <div class="col-xs-12 col-sm-5">
                            <div class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Tgl. Terima <span class="text-danger">*</span></label>
                                    <div class="col-sm-5">
                                        <input type="date" name="tgl_terima" class="form-control" value="<?php echo date('Y-m-d'); ?>" required/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr class="active">
                                            <th>#</th>
                                            <th>Kode Barang</th>
                                            <th>Nama Barang</th>
                                            <th class="text-center">Qty Diminta</th>
                                            <th class="text-center">Qty Dikirim</th>
                                            <th class="text-center">Qty Terima <span class="text-danger">*</span></th>
                                            <th class="text-right">HPP (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($details as $idx=>$d):
                                        $baris_n       = $idx+1;
                                        $ni_s          = mysqli_real_escape_string($koneksi, $d['no_item']);
                                        $qhpp          = mysqli_query($koneksi,"SELECT hargapokok FROM tblitem WHERE noitem='$ni_s' LIMIT 1");
                                        $hpp           = ($qhpp && mysqli_num_rows($qhpp)>0) ? (float)mysqli_fetch_row($qhpp)[0] : (float)$d['harga_pokok'];
                                        $qty_kirim_val = (int)$d['qty_kirim'] ?: (int)$d['qty_request'];
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $baris_n; ?></td>
                                        <td><?php echo htmlspecialchars($d['no_item']); ?></td>
                                        <td><?php echo htmlspecialchars($d['namaitem']?:'—'); ?></td>
                                        <td class="text-center"><?php echo $d['qty_request']; ?></td>
                                        <td class="text-center"><strong><?php echo $qty_kirim_val; ?></strong></td>
                                        <td class="text-center">
                                            <input type="hidden" name="no_item_<?php echo $baris_n; ?>" value="<?php echo htmlspecialchars($d['no_item']); ?>"/>
                                            <input type="hidden" name="no_baris_<?php echo $baris_n; ?>" value="<?php echo $d['no_baris']; ?>"/>
                                            <input type="hidden" name="hpp_<?php echo $baris_n; ?>" value="<?php echo $hpp; ?>"/>
                                            <input type="number" name="qty_terima_<?php echo $baris_n; ?>"
                                                   class="form-control input-sm text-center"
                                                   min="0" value="<?php echo $qty_kirim_val; ?>"
                                                   style="width:70px;margin:auto;"/>
                                        </td>
                                        <td class="text-right"><?php echo number_format($hpp,0,',','.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" name="item_count" value="<?php echo count($details); ?>"/>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom:30px;">
                        <div class="col-xs-12">
                            <a href="pengadaan_antarcab.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Batal</a>
                            &nbsp;
                            <button type="submit" name="btnterima" class="btn btn-success btn-lg"
                                onclick="return confirm('Yakin konfirmasi terima? Stok cabang akan bertambah sesuai qty terima.')">
                                <i class="fa fa-check-circle"></i> Konfirmasi Barang Sudah Diterima
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="footer"><div class="footer-inner"><div class="footer-content"><?php include "../lib/footer.php"; ?></div></div></div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
