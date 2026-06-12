<?php
/**
 * Penerimaan Antar Cabang
 * Menampilkan daftar pesanan penjualan dari cabang lain yang ditujukan ke cabang ini
 * untuk diproses menjadi penerimaan (menambah stok)
 */
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
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

    // Filter
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $filter_cabang = isset($_GET['cabang']) ? mysqli_real_escape_string($koneksi, $_GET['cabang']) : '';
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
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-pending { background: #f0ad4e; color: white; }
        .status-received { background: #5cb85c; color: white; }
        .status-partial { background: #5bc0de; color: white; }
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
                        <li class="active">Penerimaan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Penerimaan Antar Cabang
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                <?php echo $nama_cabang; ?> (<?php echo $kd_cabang; ?>)
                            </small>
                        </h1>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        Daftar pesanan dari cabang lain yang ditujukan ke cabang Anda.
                        Klik <strong>"Proses Terima"</strong> untuk membuat PO otomatis, lalu lanjut input Pembelian (nota) untuk menambah stok.
                    </div>

                    <!-- Filter -->
                    <div class="row">
                        <div class="col-xs-12">
                            <form method="get" class="form-inline" style="margin-bottom:15px;">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control input-sm">
                                        <option value="pending" <?php echo $filter_status=='pending'?'selected':''; ?>>Belum Diterima</option>
                                        <option value="received" <?php echo $filter_status=='received'?'selected':''; ?>>Sudah Diterima</option>
                                        <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>Semua</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Dari Cabang:</label>
                                    <select name="cabang" class="form-control input-sm">
                                        <option value="">-- Semua --</option>
                                        <?php
                                        $q_cab = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang
                                                                          WHERE kode_cabang != '$kd_cabang'
                                                                          ORDER BY nama_cabang");
                                        if($q_cab) {
                                            while($r_cab = mysqli_fetch_array($q_cab)){
                                                $sel = ($r_cab['kode_cabang'] == $filter_cabang) ? 'selected' : '';
                                                echo "<option value='{$r_cab['kode_cabang']}' $sel>{$r_cab['nama_cabang']}</option>";
                                            }
                                        } else {
                                            echo "<option value='' disabled>Gagal memuat data cabang</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-info">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Daftar Pesanan Masuk -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                Pesanan Masuk dari Cabang Lain
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th class="center" width="5%">No</th>
                                            <th width="12%">No. Pesanan</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="15%">Dari Cabang</th>
                                            <th class="center" width="8%">Total Item</th>
                                            <th class="center" width="8%">Total Qty</th>
                                            <th class="right" width="12%">Total</th>
                                            <th class="center" width="10%">Status</th>
                                            <th class="center" width="12%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $has_kd_tujuan = false;
                                        $has_order_ke = false;
                                        $has_tipe_transaksi = false;
                                        $has_tipe_trx = false;

                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'kd_cabang_tujuan'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_kd_tujuan = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'order_ke'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_order_ke = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'tipe_transaksi'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_tipe_transaksi = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'tipe_trx'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_tipe_trx = true; }

                                        $tujuan_cond = [];
                                        if($has_kd_tujuan){ $tujuan_cond[] = "oh.kd_cabang_tujuan = '$kd_cabang'"; }
                                        if($has_order_ke){ $tujuan_cond[] = "oh.order_ke = '$kd_cabang'"; }
                                        if(empty($tujuan_cond)){ $tujuan_cond[] = '1=0'; }

                                        $tipe_cond = [];
                                        if($has_tipe_transaksi){ $tipe_cond[] = "oh.tipe_transaksi = 'ANTAR_CABANG'"; }
                                        if($has_tipe_trx){ $tipe_cond[] = "oh.tipe_trx = 'ANTAR_CABANG' OR oh.tipe_trx = 'Antar Cabang'"; }
                                        if(empty($tipe_cond)){ $tipe_cond[] = '1=0'; }

                                        $where = "WHERE (".implode(' OR ', $tujuan_cond).") AND ((".implode(') OR (', $tipe_cond)."))";

                                        if($filter_status == 'pending'){
                                            $where .= " AND oh.status = '0'";
                                        } elseif($filter_status == 'received'){
                                            $where .= " AND oh.status = '1'";
                                        }

                                        if($filter_cabang != ''){
                                            $where .= " AND oh.kd_cabang = '$filter_cabang'";
                                        }

                                        $sql = "SELECT oh.*,
                                                       c.nama_cabang as cabang_asal,
                                                       (SELECT COUNT(*) FROM tblorderjual_detail WHERE no_order=oh.no_order) as item_count
                                                FROM tblorderjual_header oh
                                                LEFT JOIN tbcabang c ON c.kode_cabang = oh.kd_cabang
                                                $where
                                                ORDER BY oh.tanggal DESC, oh.no_order DESC";

                                        $result = mysqli_query($koneksi, $sql);
                                        $no = 0;

                                        if($result && mysqli_num_rows($result) > 0){
                                            while($row = mysqli_fetch_array($result)){
                                                $no++;
                                                $status_text = ($row['status'] == '1') ? 'Diterima' : 'Pending';
                                                $status_class = ($row['status'] == '1') ? 'status-received' : 'status-pending';
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no; ?></td>
                                            <td>
                                                <strong><?php echo $row['no_order']; ?></strong>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <small class="text-muted"><?php echo $row['kd_cabang']; ?></small><br>
                                                <?php echo $row['cabang_asal']; ?>
                                            </td>
                                            <td class="center"><?php echo $row['item_count']; ?></td>
                                            <td class="center"><?php echo number_format($row['total_qty'], 0); ?></td>
                                            <td class="right"><?php echo number_format($row['total_order'], 0, ',', '.'); ?></td>
                                            <td class="center">
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <?php if($row['status'] == '0'){ ?>
                                                <a href="penerimaan_antarcab_proses.php?no=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-success">
                                                    <i class="fa fa-check"></i> Proses Terima
                                                </a>
                                                <?php } else { ?>
                                                <a href="penerimaan_antarcab_detail.php?no=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-info">
                                                    <i class="fa fa-eye"></i> Lihat
                                                </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="9" class="center">
                                                <div class="alert alert-warning" style="margin:20px;">
                                                    <i class="fa fa-exclamation-circle"></i>
                                                    Tidak ada pesanan masuk yang ditemukan.
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
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

<?php
}
?>
