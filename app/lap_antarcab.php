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

$tgl_dari   = isset($_GET['tgl_dari'])   ? mysqli_real_escape_string($koneksi, $_GET['tgl_dari'])   : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_sampai']) : date('Y-m-d');
$f_status   = isset($_GET['f_status'])   ? mysqli_real_escape_string($koneksi, $_GET['f_status'])   : 'semua';
$f_cabang   = isset($_GET['f_cabang'])   ? mysqli_real_escape_string($koneksi, $_GET['f_cabang'])   : '';
$f_jenis    = isset($_GET['f_jenis'])    ? mysqli_real_escape_string($koneksi, $_GET['f_jenis'])    : 'semua';

$where = "h.tanggal_request BETWEEN '$tgl_dari' AND '$tgl_sampai'";
if(!$is_pusat){
    $where .= " AND (h.kd_cabang_asal='$kd_safe' OR (COALESCE(h.jenis,'pull')='push' AND h.kd_cabang_tujuan='$kd_safe'))";
} else {
    if($f_cabang) $where .= " AND (h.kd_cabang_asal='$f_cabang' OR h.kd_cabang_tujuan='$f_cabang')";
}
if($f_status!='semua') $where .= " AND h.status='$f_status'";
if($f_jenis!='semua')  $where .= " AND COALESCE(h.jenis,'pull')='$f_jenis'";

$sql = "SELECT h.*, COALESCE(h.jenis,'pull') AS jenis_order,
    ca.nama_cabang AS nama_asal, ct.nama_cabang AS nama_tujuan
    FROM tblorder_antarcab_header h
    LEFT JOIN tbcabang ca ON ca.kode_cabang=h.kd_cabang_asal
    LEFT JOIN tbcabang ct ON ct.kode_cabang=h.kd_cabang_tujuan
    WHERE $where ORDER BY h.tanggal_request DESC, h.no_order DESC";
$result = mysqli_query($koneksi,$sql);

$sum_total = $sum_selesai = $sum_dikirim = $sum_nilai = 0;
$rows_data = [];
if($result){
    while($r=mysqli_fetch_assoc($result)){
        $rows_data[] = $r;
        $sum_total++;
        if($r['status']=='selesai'){ $sum_selesai++; $sum_nilai += $r['total_nilai']; }
        if($r['status']=='dikirim') $sum_dikirim++;
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
    <style>
        .st-terkirim { background:#f0ad4e; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
        .st-diproses { background:#5bc0de; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
        .st-dikirim  { background:#428bca; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
        .st-selesai  { background:#5cb85c; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
        .st-batal    { background:#d9534f; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
        .st-draft    { background:#999;    color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
    </style>
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
                    <li><a href="#">Laporan</a></li>
                    <li class="active">Laporan Antar Cabang</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <h1>Laporan Transaksi Antar Cabang
                        <small><i class="ace-icon fa fa-angle-double-right"></i> <?php echo htmlspecialchars($nama_cabang); ?></small>
                    </h1>
                </div>

                <!-- Filter -->
                <div class="widget-box" style="margin-bottom:15px;">
                    <div class="widget-body">
                        <div class="widget-main">
                            <form method="get" class="form-inline">
                                <div class="form-group">
                                    <label>Dari:&nbsp;</label>
                                    <input type="date" name="tgl_dari" class="form-control input-sm" value="<?php echo $tgl_dari; ?>"/>
                                </div>
                                &nbsp;
                                <div class="form-group">
                                    <label>s/d:&nbsp;</label>
                                    <input type="date" name="tgl_sampai" class="form-control input-sm" value="<?php echo $tgl_sampai; ?>"/>
                                </div>
                                &nbsp;
                                <div class="form-group">
                                    <label>Status:&nbsp;</label>
                                    <select name="f_status" class="form-control input-sm">
                                        <option value="semua"   <?php echo $f_status=='semua'?'selected':''; ?>>Semua Status</option>
                                        <option value="terkirim"<?php echo $f_status=='terkirim'?'selected':''; ?>>Terkirim</option>
                                        <option value="dikirim" <?php echo $f_status=='dikirim'?'selected':''; ?>>Dikirim</option>
                                        <option value="selesai" <?php echo $f_status=='selesai'?'selected':''; ?>>Selesai</option>
                                        <option value="batal"   <?php echo $f_status=='batal'?'selected':''; ?>>Batal</option>
                                    </select>
                                </div>
                                &nbsp;
                                <div class="form-group">
                                    <label>Jenis:&nbsp;</label>
                                    <select name="f_jenis" class="form-control input-sm">
                                        <option value="semua" <?php echo $f_jenis=='semua'?'selected':''; ?>>Semua</option>
                                        <option value="pull"  <?php echo $f_jenis=='pull'?'selected':''; ?>>Pull (Request)</option>
                                        <option value="push"  <?php echo $f_jenis=='push'?'selected':''; ?>>Push (Inisiasi Pusat)</option>
                                    </select>
                                </div>
                                <?php if($is_pusat): ?>
                                &nbsp;
                                <div class="form-group">
                                    <label>Cabang:&nbsp;</label>
                                    <select name="f_cabang" class="form-control input-sm">
                                        <option value="">-- Semua --</option>
                                        <?php
                                        $qc = mysqli_query($koneksi,"SELECT kode_cabang, nama_cabang FROM tbcabang WHERE kode_cabang!='$kd_safe' ORDER BY nama_cabang");
                                        while($rc=mysqli_fetch_assoc($qc)){
                                            $sel = ($rc['kode_cabang']==$f_cabang)?'selected':'';
                                            echo "<option value='{$rc['kode_cabang']}' $sel>".htmlspecialchars($rc['nama_cabang'])."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                &nbsp;
                                <button type="submit" class="btn btn-sm btn-info"><i class="fa fa-search"></i> Cari</button>
                                &nbsp;
                                <a href="lap_antarcab.php" class="btn btn-sm btn-default"><i class="fa fa-refresh"></i> Reset</a>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-xs-6 col-sm-3">
                        <div class="widget-box widget-color-blue2">
                            <div class="widget-header"><h4 class="widget-title" style="font-size:12px;">Total Transaksi</h4></div>
                            <div class="widget-body"><div class="widget-main">
                                <div class="h3 lighter"><?php echo $sum_total; ?></div>
                                <p style="font-size:11px;color:#aaa;">transaksi</p>
                            </div></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="widget-box widget-color-orange">
                            <div class="widget-header"><h4 class="widget-title" style="font-size:12px;">Dalam Perjalanan</h4></div>
                            <div class="widget-body"><div class="widget-main">
                                <div class="h3 lighter"><?php echo $sum_dikirim; ?></div>
                                <p style="font-size:11px;color:#aaa;">belum diterima</p>
                            </div></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="widget-box widget-color-green">
                            <div class="widget-header"><h4 class="widget-title" style="font-size:12px;">Selesai</h4></div>
                            <div class="widget-body"><div class="widget-main">
                                <div class="h3 lighter"><?php echo $sum_selesai; ?></div>
                                <p style="font-size:11px;color:#aaa;">transaksi</p>
                            </div></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="widget-box widget-color-purple">
                            <div class="widget-header"><h4 class="widget-title" style="font-size:12px;">Nilai (Selesai)</h4></div>
                            <div class="widget-body"><div class="widget-main">
                                <div class="h5 lighter">Rp <?php echo number_format($sum_nilai,0,',','.'); ?></div>
                                <p style="font-size:11px;color:#aaa;">HPP kumulatif</p>
                            </div></div>
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="row">
                    <div class="col-xs-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr class="active">
                                        <th>No. Transaksi</th>
                                        <th>Tgl. Request</th>
                                        <th>Pengirim</th>
                                        <th>Penerima</th>
                                        <th class="text-center">Jenis</th>
                                        <th class="text-center">Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-right">Nilai (Rp)</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width:70px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(empty($rows_data)): ?>
                                <tr><td colspan="10" class="text-center text-muted" style="padding:30px;">
                                    <i class="fa fa-inbox fa-2x"></i><br/>Tidak ada data untuk periode dan filter yang dipilih.
                                </td></tr>
                                <?php else: foreach($rows_data as $row):
                                    $jns      = $row['jenis_order'];
                                    $pengirim = ($jns=='push') ? $row['nama_asal'] : $row['nama_tujuan'];
                                    $penerima = ($jns=='push') ? $row['nama_tujuan'] : $row['nama_asal'];
                                    $st       = $row['status'];
                                ?>
                                <tr>
                                    <td>
                                        <a href="pengadaan_antarcab_detail.php?no=<?php echo urlencode($row['no_order']); ?>">
                                            <strong><?php echo htmlspecialchars($row['no_order']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y',strtotime($row['tanggal_request'])); ?></td>
                                    <td><?php echo htmlspecialchars($pengirim?:'—'); ?></td>
                                    <td><?php echo htmlspecialchars($penerima?:'—'); ?></td>
                                    <td class="text-center">
                                        <?php echo $jns=='push'
                                            ? '<span style="background:#5bc0de;color:#fff;padding:2px 6px;border-radius:3px;font-size:10px;">Push</span>'
                                            : '<span style="background:#888;color:#fff;padding:2px 6px;border-radius:3px;font-size:10px;">Pull</span>'; ?>
                                    </td>
                                    <td class="text-center"><?php echo $row['total_item']; ?></td>
                                    <td class="text-center"><?php echo $row['total_qty']; ?></td>
                                    <td class="text-right"><?php echo number_format($row['total_nilai'],0,',','.'); ?></td>
                                    <td class="text-center"><span class="st-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span></td>
                                    <td class="text-center">
                                        <a href="pengadaan_antarcab_detail.php?no=<?php echo urlencode($row['no_order']); ?>"
                                           class="btn btn-xs btn-default" title="Detail"><i class="fa fa-eye"></i></a>
                                        <a href="pengadaan_antarcab_print.php?no=<?php echo urlencode($row['no_order']); ?>"
                                           target="_blank" class="btn btn-xs btn-info" title="Cetak"><i class="fa fa-print"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
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
