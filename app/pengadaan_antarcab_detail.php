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

$qh = mysqli_query($koneksi,"SELECT h.*,
    COALESCE(h.jenis,'pull') AS jenis_order,
    ca.nama_cabang AS nama_asal,
    ct.nama_cabang AS nama_tujuan
    FROM tblorder_antarcab_header h
    LEFT JOIN tbcabang ca ON ca.kode_cabang=h.kd_cabang_asal
    LEFT JOIN tbcabang ct ON ct.kode_cabang=h.kd_cabang_tujuan
    WHERE h.no_order='$no_order' LIMIT 1");
if(!$qh || mysqli_num_rows($qh)==0){ header("location:pengadaan_antarcab.php"); exit; }
$hdr = mysqli_fetch_assoc($qh);
$jenis_order = $hdr['jenis_order'];

// Akses: pusat boleh semua; cabang hanya miliknya
if(!$is_pusat){
    $ok = ($jenis_order=='push' && $hdr['kd_cabang_tujuan']==$kd_cabang)
       || ($jenis_order!='push' && $hdr['kd_cabang_asal']==$kd_cabang);
    if(!$ok){ header("location:pengadaan_antarcab.php"); exit; }
}

$qd = mysqli_query($koneksi,"SELECT d.*, i.namaitem, i.satuan FROM tblorder_antarcab_detail d
    LEFT JOIN tblitem i ON i.noitem=d.no_item
    WHERE d.no_order='$no_order' ORDER BY d.no_baris");
$details = [];
while($r = mysqli_fetch_assoc($qd)) $details[] = $r;

if($jenis_order=='push'){
    $label_pengirim = $hdr['nama_asal'];
    $label_penerima = $hdr['nama_tujuan'];
} else {
    $label_pengirim = $hdr['nama_tujuan'];
    $label_penerima = $hdr['nama_asal'];
}

$st = $hdr['status'];
$label_map = ['draft'=>'Draft','terkirim'=>'Terkirim','diproses'=>'Diproses',
              'dikirim'=>'Dikirim','selesai'=>'Selesai','batal'=>'Batal'];
$badge_class = ['draft'=>'default','terkirim'=>'warning','diproses'=>'info',
                'dikirim'=>'primary','selesai'=>'success','batal'=>'danger'];
$bc = $badge_class[$st] ?? 'default';
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
                    <li class="active">Detail <?php echo htmlspecialchars($no_order); ?></li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Detail Pengadaan Antar Cabang
                        <small><i class="ace-icon fa fa-angle-double-right"></i>
                            <?php echo htmlspecialchars($no_order); ?>
                            <span class="label label-<?php echo $bc; ?>"><?php echo $label_map[$st]??$st; ?></span>
                            <?php if($jenis_order=='push'): ?>
                            <span class="label label-info">PUSH</span>
                            <?php endif; ?>
                        </small>
                    </h1>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-sm-8">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6">
                                <table class="table table-bordered table-condensed">
                                    <tr class="active"><th colspan="2"><i class="fa fa-file-text-o"></i> Info Transaksi</th></tr>
                                    <tr><td style="width:130px"><b>No. Transaksi</b></td><td><strong><?php echo htmlspecialchars($no_order); ?></strong></td></tr>
                                    <tr><td><b>Tipe</b></td><td><?php echo $jenis_order=='push'
                                        ? '<span class="label label-info">Push (Pusat Inisiasi)</span>'
                                        : '<span class="label label-default">Pull (Request Cabang)</span>'; ?></td></tr>
                                    <tr><td><b>Status</b></td><td><span class="label label-<?php echo $bc; ?>"><?php echo $label_map[$st]??$st; ?></span></td></tr>
                                    <tr><td><b>Tgl. Request</b></td><td><?php echo date('d/m/Y',strtotime($hdr['tanggal_request'])); ?></td></tr>
                                    <?php if($hdr['tanggal_kirim']): ?>
                                    <tr><td><b>Tgl. Kirim</b></td><td><?php echo date('d/m/Y',strtotime($hdr['tanggal_kirim'])); ?></td></tr>
                                    <?php endif; ?>
                                    <?php if($hdr['tanggal_terima']): ?>
                                    <tr><td><b>Tgl. Terima</b></td><td><?php echo date('d/m/Y',strtotime($hdr['tanggal_terima'])); ?></td></tr>
                                    <?php endif; ?>
                                    <?php if($hdr['catatan']): ?>
                                    <tr><td><b>Catatan</b></td><td><?php echo htmlspecialchars($hdr['catatan']); ?></td></tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <table class="table table-bordered table-condensed">
                                    <tr class="active"><th colspan="2"><i class="fa fa-exchange"></i> Alur Barang</th></tr>
                                    <tr><td style="width:130px"><b>Pengirim</b></td><td><?php echo htmlspecialchars($label_pengirim?:'—'); ?></td></tr>
                                    <tr><td><b>Penerima</b></td><td><?php echo htmlspecialchars($label_penerima?:'—'); ?></td></tr>
                                    <tr><td><b>Dibuat Oleh</b></td><td><?php echo htmlspecialchars($hdr['user_request']?:'—'); ?></td></tr>
                                    <?php if($hdr['user_proses']): ?>
                                    <tr><td><b>Diproses Oleh</b></td><td><?php echo htmlspecialchars($hdr['user_proses']); ?></td></tr>
                                    <?php endif; ?>
                                    <tr><td><b>Total Item</b></td><td><?php echo $hdr['total_item']; ?> item</td></tr>
                                    <tr><td><b>Total Qty</b></td><td><?php echo $hdr['total_qty']; ?></td></tr>
                                    <tr><td><b>Total Nilai</b></td><td><b>Rp <?php echo number_format($hdr['total_nilai'],0,',','.'); ?></b></td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="well well-sm" style="margin-bottom:15px;">
                            <strong><i class="fa fa-flag"></i> Progress:</strong>
                            <span style="margin-left:10px;">
                                <span class="<?php echo in_array($st,['terkirim','diproses','dikirim','selesai'])?'text-success':'text-muted'; ?>">
                                    <i class="fa fa-check-circle"></i> Request
                                </span>
                                &rarr;
                                <span class="<?php echo in_array($st,['dikirim','selesai'])?'text-success':'text-muted'; ?>">
                                    <i class="fa fa-check-circle"></i> Dikirim
                                </span>
                                &rarr;
                                <span class="<?php echo $st=='selesai'?'text-success':'text-muted'; ?>">
                                    <i class="fa fa-check-circle"></i> Diterima
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4 text-right">
                        <a href="pengadaan_antarcab.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
                        &nbsp;
                        <a href="pengadaan_antarcab_print.php?no=<?php echo urlencode($no_order); ?>" target="_blank" class="btn btn-info">
                            <i class="fa fa-print"></i> Cetak Nota
                        </a>
                        <?php if($is_pusat && in_array($st,['terkirim','diproses'])): ?>
                        <br/><br/>
                        <a href="pengadaan_antarcab_proses.php?no=<?php echo urlencode($no_order); ?>" class="btn btn-primary">
                            <i class="fa fa-send"></i> Proses Kirim
                        </a>
                        <?php endif; ?>
                        <?php
                        $can_terima = false;
                        if(!$is_pusat && $st=='dikirim'){
                            if($jenis_order=='push' && $hdr['kd_cabang_tujuan']==$kd_cabang) $can_terima=true;
                            if($jenis_order!='push' && $hdr['kd_cabang_asal']==$kd_cabang)   $can_terima=true;
                        }
                        if($can_terima):
                        ?>
                        <br/><br/>
                        <a href="pengadaan_antarcab_terima.php?no=<?php echo urlencode($no_order); ?>" class="btn btn-success">
                            <i class="fa fa-check-circle"></i> Konfirmasi Terima
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="active">
                                        <th style="width:35px">#</th>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center">Qty Request</th>
                                        <th class="text-center">Qty Kirim</th>
                                        <th class="text-center">Qty Terima</th>
                                        <th class="text-right">HPP (Rp)</th>
                                        <th class="text-right">Subtotal (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(empty($details)): ?>
                                <tr><td colspan="8" class="text-center text-muted">Tidak ada detail barang.</td></tr>
                                <?php else: foreach($details as $i=>$d): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i+1; ?></td>
                                    <td><?php echo htmlspecialchars($d['no_item']); ?></td>
                                    <td><?php echo htmlspecialchars($d['namaitem']?:'—'); ?></td>
                                    <td class="text-center"><?php echo $d['qty_request']; ?></td>
                                    <td class="text-center"><?php echo $d['qty_kirim']??0; ?></td>
                                    <td class="text-center"><?php echo $d['qty_terima']??0; ?></td>
                                    <td class="text-right"><?php echo number_format($d['harga_pokok'],0,',','.'); ?></td>
                                    <td class="text-right"><?php echo number_format($d['subtotal'],0,',','.'); ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="7" class="text-right">Total Nilai</th>
                                        <th class="text-right">Rp <?php echo number_format($hdr['total_nilai'],0,',','.'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
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
