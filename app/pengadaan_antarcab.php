<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari  = mysqli_fetch_array($cari_kd);
$_nama     = $tm_cari['nama_user'];
$lvl_akses = $tm_cari['user_akses'];
$foto_user = $tm_cari['foto_user'] ?: "file_upload/avatar.png";

$cari_cab = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$tm_cab   = mysqli_fetch_array($cari_cab);
$nama_cabang = $tm_cab['nama_cabang'];
$tipe_cabang = $tm_cab['tipe_cabang'];
$is_pusat    = ($tipe_cabang == '1' || strtolower($tipe_cabang) == 'pusat');

$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : 'semua';
$filter_cabang = isset($_GET['cabang']) ? mysqli_real_escape_string($koneksi, $_GET['cabang']) : '';

$kd_safe = mysqli_real_escape_string($koneksi, $kd_cabang);

// Pastikan kolom jenis ada (auto-migration)
$col_j = mysqli_query($koneksi,"SHOW COLUMNS FROM tblorder_antarcab_header LIKE 'jenis'");
if(!$col_j || mysqli_num_rows($col_j)==0){
    mysqli_query($koneksi,"ALTER TABLE tblorder_antarcab_header ADD COLUMN jenis VARCHAR(10) DEFAULT 'pull' AFTER status");
}

$where   = "1=1";
if (!$is_pusat) {
    // Cabang melihat: PULL miliknya (kd_cabang_asal) ATAU PUSH untuk mereka (kd_cabang_tujuan)
    $where .= " AND (h.kd_cabang_asal = '$kd_safe' OR (COALESCE(h.jenis,'pull')='push' AND h.kd_cabang_tujuan='$kd_safe'))";
} elseif ($filter_cabang) {
    $where .= " AND (h.kd_cabang_asal = '$filter_cabang' OR h.kd_cabang_tujuan = '$filter_cabang')";
}
if ($filter_status && $filter_status != 'semua') {
    $where .= " AND h.status = '$filter_status'";
}

$sql = "SELECT h.*, c.nama_cabang AS nama_asal, ct.nama_cabang AS nama_tujuan
        FROM tblorder_antarcab_header h
        LEFT JOIN tbcabang c  ON c.kode_cabang = h.kd_cabang_asal
        LEFT JOIN tbcabang ct ON ct.kode_cabang = h.kd_cabang_tujuan
        WHERE $where ORDER BY h.created_at DESC";
$result = mysqli_query($koneksi, $sql);
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
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        .st-draft    { background:#999;    color:#fff; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:bold; }
        .st-terkirim { background:#f0ad4e; color:#fff; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:bold; }
        .st-diproses { background:#5bc0de; color:#fff; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:bold; }
        .st-dikirim  { background:#428bca; color:#fff; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:bold; }
        .st-selesai  { background:#5cb85c; color:#fff; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:bold; }
        .st-batal    { background:#d9534f; color:#fff; padding:3px 8px; border-radius:3px; font-size:11px; font-weight:bold; }
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
            <a href="index.php" class="navbar-brand">
                <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
            </a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="Profil" />
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
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"
               data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li><a href="#">Pengadaan</a></li>
                    <li class="active">Permintaan Antar Cabang</li>
                </ul>
            </div>

            <div class="page-content">
                <div class="page-header">
                    <h1>
                        Permintaan Barang Antar Cabang
                        <small>
                            <i class="ace-icon fa fa-angle-double-right"></i>
                            <?php echo htmlspecialchars($nama_cabang); ?>
                            <?php if($is_pusat): ?><span class="label label-warning">PUSAT</span><?php endif; ?>
                        </small>
                    </h1>
                </div>

                <?php if(isset($_GET['msg'])): $msg = $_GET['msg'];
                    $is_ok = in_array($msg,['ok','proses_ok','terima_ok','push_ok']);
                ?>
                <div class="alert alert-<?php echo $is_ok?'success':'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <?php if($msg=='ok')        echo '<i class="fa fa-check"></i> Permintaan berhasil dibuat dan dikirim ke pusat.';
                          elseif($msg=='proses_ok') echo '<i class="fa fa-check"></i> Barang segera dikirim ke cabang. Stok pusat sudah berkurang.';
                          elseif($msg=='terima_ok') echo '<i class="fa fa-check"></i> Penerimaan dikonfirmasi. Stok cabang sudah bertambah.';
                          elseif($msg=='push_ok')   echo '<i class="fa fa-check"></i> Pengiriman (Push) berhasil disimpan. Stok pusat sudah berkurang.';
                          else echo '<i class="fa fa-times"></i> Terjadi kesalahan. Silakan coba lagi.'; ?>
                </div>
                <?php endif; ?>

                <!-- Filter & Tombol Tambah -->
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-xs-12 col-sm-8">
                        <form method="get" class="form-inline">
                            <div class="form-group">
                                <label>Status:&nbsp;</label>
                                <select name="status" class="form-control input-sm">
                                    <option value="semua"    <?php echo $filter_status=='semua'?'selected':''; ?>>Semua Status</option>
                                    <option value="terkirim" <?php echo $filter_status=='terkirim'?'selected':''; ?>>Terkirim (Menunggu Pusat)</option>
                                    <option value="diproses" <?php echo $filter_status=='diproses'?'selected':''; ?>>Diproses</option>
                                    <option value="dikirim"  <?php echo $filter_status=='dikirim'?'selected':''; ?>>Dikirim (Dalam Perjalanan)</option>
                                    <option value="selesai"  <?php echo $filter_status=='selesai'?'selected':''; ?>>Selesai</option>
                                    <option value="draft"    <?php echo $filter_status=='draft'?'selected':''; ?>>Draft</option>
                                    <option value="batal"    <?php echo $filter_status=='batal'?'selected':''; ?>>Batal</option>
                                </select>
                            </div>
                            <?php if($is_pusat): ?>
                            &nbsp;
                            <div class="form-group">
                                <label>Cabang:&nbsp;</label>
                                <select name="cabang" class="form-control input-sm">
                                    <option value="">-- Semua Cabang --</option>
                                    <?php
                                    $q_cab = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang WHERE kode_cabang!='$kd_safe' ORDER BY nama_cabang");
                                    while($r_cab = mysqli_fetch_array($q_cab)){
                                        $sel = ($r_cab['kode_cabang']==$filter_cabang)?'selected':'';
                                        echo "<option value='{$r_cab['kode_cabang']}' $sel>".htmlspecialchars($r_cab['nama_cabang'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            &nbsp;<button type="submit" class="btn btn-sm btn-info"><i class="fa fa-search"></i> Filter</button>
                        </form>
                    </div>
                    <?php if(!$is_pusat): ?>
                    <div class="col-xs-12 col-sm-4 text-right">
                        <a href="pengadaan_antarcab_add.php" class="btn btn-sm btn-success">
                            <i class="fa fa-plus"></i> Buat Permintaan Baru
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="col-xs-12 col-sm-4 text-right">
                        <a href="pengadaan_antarcab_push.php" class="btn btn-sm btn-warning">
                            <i class="fa fa-truck"></i> Kirim ke Cabang (Inisiasi Pusat)
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tabel -->
                <div class="row">
                    <div class="col-xs-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr class="active">
                                        <th style="width:130px">No. Permintaan</th>
                                        <th style="width:95px">Tgl. Request</th>
                                        <th>Dari Cabang</th>
                                        <th>Ke Cabang</th>
                                        <th class="text-center" style="width:55px">Item</th>
                                        <th class="text-center" style="width:65px">Total Qty</th>
                                        <th style="width:110px">Status</th>
                                        <th>Oleh</th>
                                        <th class="text-center" style="width:130px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(!$result || mysqli_num_rows($result)==0): ?>
                                    <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">
                                        <i class="fa fa-inbox fa-2x"></i><br>Belum ada data permintaan.
                                    </td></tr>
                                <?php else: while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['no_order']); ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_request'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_asal'] ?: $row['kd_cabang_asal']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_tujuan'] ?: $row['kd_cabang_tujuan']); ?></td>
                                        <td class="text-center"><?php echo $row['total_item']; ?></td>
                                        <td class="text-center"><?php echo $row['total_qty']; ?></td>
                                        <td>
                                            <?php
                                            $label_map = ['draft'=>'Draft','terkirim'=>'Terkirim','diproses'=>'Diproses',
                                                          'dikirim'=>'Dikirim','selesai'=>'Selesai','batal'=>'Batal'];
                                            $st = $row['status'];
                                            echo "<span class=\"st-$st\">".(isset($label_map[$st])?$label_map[$st]:$st)."</span>";
                                            if($st=='dikirim' && $row['tanggal_kirim'])
                                                echo "<br><small class='text-muted'>".date('d/m',strtotime($row['tanggal_kirim']))."</small>";
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['user_request']); ?></td>
                                        <td class="text-center" style="white-space:nowrap;">
                                            <?php
                                            $jns_row = (isset($row['jenis']) && $row['jenis']) ? $row['jenis'] : 'pull';
                                            $can_terima = !$is_pusat && $st=='dikirim' &&
                                                (($jns_row=='push' && $row['kd_cabang_tujuan']==$kd_cabang) ||
                                                 ($jns_row!='push' && $row['kd_cabang_asal']==$kd_cabang));
                                            $no_enc = urlencode($row['no_order']);
                                            ?>
                                            <a href="pengadaan_antarcab_detail.php?no=<?php echo $no_enc; ?>"
                                               class="btn btn-xs btn-default"><i class="fa fa-eye"></i> Detail</a>
                                            <?php if($is_pusat && in_array($st,['terkirim','diproses'])): ?>
                                                <a href="pengadaan_antarcab_proses.php?no=<?php echo $no_enc; ?>"
                                                   class="btn btn-xs btn-primary"><i class="fa fa-send"></i> Proses</a>
                                            <?php elseif($can_terima): ?>
                                                <a href="pengadaan_antarcab_terima.php?no=<?php echo $no_enc; ?>"
                                                   class="btn btn-xs btn-success"><i class="fa fa-check"></i> Terima</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="footer"><div class="footer-inner"><div class="footer-content">
        <?php include "../lib/footer.php"; ?>
    </div></div></div>
</div>

<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
