<?php
/**
 * Penerimaan dari Cabang Mitra (Eksternal)
 * Menampilkan daftar penjualan dari cabang mitra yang ditujukan ke cabang ini
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

    // Pagination
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
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

            <?php include "menu_dashboard.php"; ?>

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
                        <li><a href="#">Cabang Mitra</a></li>
                        <li class="active">Penerimaan Mitra</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Penerimaan dari Cabang Mitra
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                <?php echo $nama_cabang; ?> (<?php echo $kd_cabang; ?>)
                            </small>
                        </h1>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        Daftar penjualan dari cabang mitra (eksternal) yang ditujukan ke cabang Anda.
                        Klik <strong>"Terima"</strong> untuk memproses penerimaan dan update stok.
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
                                    <label>Dari Cabang Mitra:</label>
                                    <select name="cabang" class="form-control input-sm">
                                        <option value="">-- Semua --</option>
                                        <?php
                                        $has_status_col = false;
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tbcabang LIKE 'status'");
                                        if($qcol && mysqli_num_rows($qcol) > 0){
                                            $has_status_col = true;
                                        }

                                        $sql_cab = "SELECT kode_cabang, nama_cabang FROM tbcabang
                                                    WHERE kode_cabang != '$kd_cabang'
                                                    AND tipe_cabang = 'MITRA'";
                                        if($has_status_col){
                                            $sql_cab .= " AND status='1'";
                                        }
                                        $sql_cab .= " ORDER BY nama_cabang";

                                        $q_cab = mysqli_query($koneksi, $sql_cab);
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
                                <a href="penerimaan_mitra.php" class="btn btn-sm btn-default">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Daftar Transaksi -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                Penerimaan dari Cabang Mitra
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th class="center" width="5%">No</th>
                                            <th width="12%">No. Transaksi</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="18%">Dari Cabang Mitra</th>
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
                                        $has_total_order = false;
                                        $has_total_akhir = false;
                                        $has_total_jual = false;

                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'kd_cabang_tujuan'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_kd_tujuan = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'order_ke'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_order_ke = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'tipe_transaksi'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_tipe_transaksi = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'tipe_trx'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_tipe_trx = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'total_order'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_total_order = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'total_akhir'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_total_akhir = true; }
                                        $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tblorderjual_header LIKE 'total_jual'");
                                        if($qcol && mysqli_num_rows($qcol)>0){ $has_total_jual = true; }

                                        $col_tujuan = '';
                                        if($has_kd_tujuan){
                                            $col_tujuan = 'kd_cabang_tujuan';
                                        } elseif($has_order_ke){
                                            $col_tujuan = 'order_ke';
                                        }

                                        $col_total = '';
                                        if($has_total_order){
                                            $col_total = 'total_order';
                                        } elseif($has_total_akhir){
                                            $col_total = 'total_akhir';
                                        } elseif($has_total_jual){
                                            $col_total = 'total_jual';
                                        }

                                        $tujuan_cond = [];
                                        if($col_tujuan != ''){
                                            $tujuan_cond[] = "oh.$col_tujuan = '$kd_cabang'";
                                        }
                                        if(empty($tujuan_cond)){
                                            $tujuan_cond[] = '1=0';
                                        }

                                        $tipe_cond = [];
                                        if($has_tipe_transaksi){
                                            $tipe_cond[] = "oh.tipe_transaksi = 'MITRA_EKSTERNAL'";
                                            $tipe_cond[] = "oh.tipe_transaksi = 'MITRA'";
                                            $tipe_cond[] = "oh.tipe_transaksi = 'Mitra'";
                                        }
                                        if($has_tipe_trx){
                                            $tipe_cond[] = "oh.tipe_trx = 'MITRA_EKSTERNAL'";
                                            $tipe_cond[] = "oh.tipe_trx = 'MITRA'";
                                            $tipe_cond[] = "oh.tipe_trx = 'Mitra'";
                                        }
                                        if(empty($tipe_cond)){
                                            $tipe_cond[] = '1=0';
                                        }

                                        // Build WHERE clause
                                        $where = "WHERE (".implode(' OR ', $tujuan_cond).")
                                                  AND (".implode(' OR ', $tipe_cond).")";

                                        if($filter_status == 'pending'){
                                            $where .= " AND oh.status = '0'";
                                        } elseif($filter_status == 'received'){
                                            $where .= " AND oh.status = '1'";
                                        }

                                        if($filter_cabang != ''){
                                            $where .= " AND oh.kd_cabang = '$filter_cabang'";
                                        }

                                        // Count total
                                        $count_sql = "SELECT COUNT(*) as total FROM tblorderjual_header oh $where";
                                        $count_result = mysqli_query($koneksi, $count_sql);
                                        $total_records = 0;
                                        if($count_result){
                                            $tmp_cnt = mysqli_fetch_array($count_result);
                                            $total_records = isset($tmp_cnt['total']) ? $tmp_cnt['total'] : 0;
                                        }
                                        $total_pages = ceil($total_records / $limit);

                                        // Get data
                                        $sql = "SELECT oh.*,
                                                       c.nama_cabang as cabang_asal,
                                                       (SELECT COUNT(*) FROM tblorderjual_detail WHERE no_order=oh.no_order) as item_count
                                                FROM tblorderjual_header oh
                                                LEFT JOIN tbcabang c ON c.kode_cabang = oh.kd_cabang
                                                $where
                                                ORDER BY oh.tanggal DESC, oh.no_order DESC
                                                LIMIT $limit OFFSET $offset";

                                        $result = mysqli_query($koneksi, $sql);
                                        $no = $offset;

                                        if($result && mysqli_num_rows($result) > 0){
                                            while($row = mysqli_fetch_array($result)){
                                                $no++;
                                                $status_text = ($row['status'] == '1') ? 'Diterima' : 'Pending';
                                                $status_class = ($row['status'] == '1') ? 'status-received' : 'status-pending';

                                                $row_total = 0;
                                                if($col_total != '' && isset($row[$col_total])){
                                                    $row_total = $row[$col_total];
                                                }
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
                                            <td class="right"><?php echo number_format($row_total, 0, ',', '.'); ?></td>
                                            <td class="center">
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <a href="pesanan_penjualan_cab_cetak.php?nopesanan=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-info" title="Lihat Detail">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if($row['status'] == '0'){ ?>
                                                <a href="penerimaan_antarcab_proses.php?no=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-success" title="Proses Terima">
                                                    <i class="fa fa-check"></i> Terima
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
                                                    Tidak ada transaksi dari cabang mitra yang ditemukan.
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if($total_pages > 1){ ?>
                            <div class="row">
                                <div class="col-xs-12 col-sm-6">
                                    <div class="dataTables_info">
                                        Menampilkan <?php echo $offset+1; ?> - <?php echo min($offset+$limit, $total_records); ?>
                                        dari <?php echo $total_records; ?> data
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6">
                                    <ul class="pagination pull-right" style="margin:0;">
                                        <?php if($page > 1){ ?>
                                        <li>
                                            <a href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&cabang=<?php echo urlencode($filter_cabang); ?>">
                                                <i class="fa fa-angle-left"></i>
                                            </a>
                                        </li>
                                        <?php } ?>

                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        for($i = $start_page; $i <= $end_page; $i++){
                                        ?>
                                        <li class="<?php echo $i==$page?'active':''; ?>">
                                            <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&cabang=<?php echo urlencode($filter_cabang); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php } ?>

                                        <?php if($page < $total_pages){ ?>
                                        <li>
                                            <a href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&cabang=<?php echo urlencode($filter_cabang); ?>">
                                                <i class="fa fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                            <?php } ?>

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
