<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    $cari_kd=mysqli_query($koneksi,"SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $_nama=$tm_cari['nama_user'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

    $cari_kd=mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];

    $noretur   = isset($_GET['noretur'])  ? mysqli_real_escape_string($koneksi, $_GET['noretur']) : '';
    $sukses    = isset($_GET['sukses'])   ? (int)$_GET['sukses']   : 0;
    $approved  = isset($_GET['approved']) ? (int)$_GET['approved'] : 0;
    if($noretur == '') { header("location:retur_penjualan.php"); exit; }

    $hdr = mysqli_query($koneksi, "SELECT h.*, DATE_FORMAT(h.tanggal,'%d/%m/%Y') AS tgl_fmt,
                                          p.namapelanggan, ph.no_pelanggan
                                   FROM tblretur_penjualan_header h
                                   LEFT JOIN tblpenjualan_header ph ON ph.notransaksi=h.nopembelian
                                   LEFT JOIN tblpelanggan p ON p.nopelanggan=ph.no_pelanggan
                                   WHERE h.noretur='$noretur' AND h.kd_cabang='$kd_cabang'");
    if(mysqli_num_rows($hdr) == 0) { header("location:retur_penjualan.php"); exit; }
    $h = mysqli_fetch_assoc($hdr);

    $det = mysqli_query($koneksi, "SELECT d.*, COALESCE(i.namaitem, d.no_item) AS namaitem,
                                          j.jenis_penggantian
                                   FROM tblretur_penjualan_detail d
                                   LEFT JOIN tblitem i ON i.noitem=d.no_item
                                   LEFT JOIN tbjenis_penggantian_retur j ON j.id=d.jenis_penggantian
                                   WHERE d.no_retur='$noretur'
                                   ORDER BY d.id");
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
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
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
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
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
                        <li><a href="penjualan.php">Penjualan</a></li>
                        <li><a href="retur_penjualan.php">Retur Penjualan</a></li>
                        <li class="active">Detail Retur</li>
                    </ul>
                </div>

                <div class="page-content">
                    <?php if($sukses): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <strong>Retur penjualan berhasil disimpan!</strong>
                        No. Retur: <strong><?php echo htmlspecialchars($noretur); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if($approved): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <strong>Retur penjualan berhasil disetujui!</strong> Status diubah menjadi <strong>Selesai</strong>.
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-undo"></i> Detail Retur Penjualan</h4>
                                    <div class="widget-toolbar">
                                        <?php if($h['status_retur'] == '0'): ?>
                                        <a href="retur_penjualan_approve.php?noretur=<?php echo urlencode($noretur); ?>"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Setujui retur ini? Status akan berubah menjadi Selesai.');">
                                            <i class="fa fa-check"></i> Setujui
                                        </a>
                                        <a href="retur_penjualan_batal.php?noretur=<?php echo urlencode($noretur); ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Batalkan retur ini? Data akan dihapus permanen.');">
                                            <i class="fa fa-trash"></i> Batal
                                        </a>
                                        <?php endif; ?>
                                        <a href="retur_penjualan_cetak.php?noretur=<?php echo urlencode($noretur); ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fa fa-print"></i> Cetak
                                        </a>
                                        <a href="retur_penjualan_rst.php?_key=&_cari=" class="btn btn-sm btn-default">
                                            <i class="fa fa-list"></i> Kembali ke List
                                        </a>
                                    </div>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6">
                                                <table class="table table-condensed">
                                                    <tr><td width="40%"><strong>No. Retur</strong></td><td><?php echo htmlspecialchars($h['noretur']); ?></td></tr>
                                                    <tr><td><strong>No. Penjualan</strong></td><td><?php echo htmlspecialchars($h['nopembelian']); ?></td></tr>
                                                    <tr><td><strong>Pelanggan</strong></td><td><?php echo htmlspecialchars($h['namapelanggan']); ?></td></tr>
                                                    <tr><td><strong>Tanggal Retur</strong></td><td><?php echo htmlspecialchars($h['tgl_fmt']); ?></td></tr>
                                                    <tr><td><strong>Status</strong></td><td>
                                                        <?php echo ($h['status_retur']=='1') ? '<span class="label label-success">Selesai</span>' : '<span class="label label-warning">Proses</span>'; ?>
                                                    </td></tr>
                                                    <tr><td><strong>Keterangan</strong></td><td><?php echo htmlspecialchars($h['note']); ?></td></tr>
                                                    <tr><td><strong>User</strong></td><td><?php echo htmlspecialchars($h['user']); ?></td></tr>
                                                </table>
                                            </div>
                                            <div class="col-xs-12 col-sm-4 col-sm-offset-2">
                                                <table class="table table-condensed table-bordered">
                                                    <tr><td><strong>Total Qty</strong></td><td align="right"><?php echo number_format($h['total_qty_retur'],0,',','.'); ?></td></tr>
                                                    <tr><td><strong>Total Nilai Retur</strong></td><td align="right"><strong><?php echo number_format($h['total_akhir'],0,',','.'); ?></strong></td></tr>
                                                </table>
                                            </div>
                                        </div>
                                        <hr />
                                        <table class="table table-bordered table-striped">
                                            <thead style="background:#5a7a35;color:#fff;">
                                                <tr>
                                                    <th class="center" width="5%">No</th>
                                                    <th width="12%">Kode Item</th>
                                                    <th width="28%">Nama Item</th>
                                                    <th class="center" width="7%">Qty</th>
                                                    <th align="right" width="12%">Harga Jual</th>
                                                    <th align="right" width="12%">Subtotal</th>
                                                    <th width="12%">Alasan</th>
                                                    <th width="14%">Jenis Penggantian</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $no=1; while($d=mysqli_fetch_assoc($det)): ?>
                                            <tr>
                                                <td class="center"><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($d['no_item']); ?></td>
                                                <td><?php echo htmlspecialchars($d['namaitem']); ?></td>
                                                <td class="center"><?php echo $d['quantity']; ?></td>
                                                <td align="right"><?php echo number_format($d['harga_pokok'],0,',','.'); ?></td>
                                                <td align="right"><?php echo number_format($d['total'],0,',','.'); ?></td>
                                                <td><?php echo htmlspecialchars($d['alasan_retur']); ?></td>
                                                <td><?php echo htmlspecialchars($d['jenis_penggantian']); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content"><?php include "../lib/footer.php"; ?></div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
<?php } ?>
