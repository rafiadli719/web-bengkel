<?php
/**
 * Daftar Transaksi Antar Cabang
 * Menampilkan semua transaksi antar cabang (kirim dan terima)
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
    $filter_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // kirim, terima, all
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $filter_cabang = isset($_GET['cabang']) ? mysqli_real_escape_string($koneksi, $_GET['cabang']) : '';
    $filter_tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-d', strtotime('-3 months'));
    $filter_tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');

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
    <link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />
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
        .status-sent { background: #5bc0de; color: white; }
        .type-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .type-kirim { background: #337ab7; color: white; }
        .type-terima { background: #5cb85c; color: white; }
        .summary-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .summary-box h4 {
            margin: 0 0 10px 0;
            color: #555;
        }
        .summary-item {
            display: inline-block;
            margin-right: 20px;
        }
        .summary-item span {
            font-weight: bold;
            font-size: 18px;
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
                        <li class="active">Daftar Transaksi</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Daftar Transaksi Antar Cabang
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                <?php echo $nama_cabang; ?> (<?php echo $kd_cabang; ?>)
                            </small>
                        </h1>
                    </div>

                    <!-- Summary Box -->
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

                    $tipe_cond = [];
                    if($has_tipe_transaksi){ $tipe_cond[] = "(tipe_transaksi = 'ANTAR_CABANG' OR tipe_transaksi = 'Antar Cabang')"; }
                    if($has_tipe_trx){ $tipe_cond[] = "(tipe_trx = 'ANTAR_CABANG' OR tipe_trx = 'Antar Cabang')"; }
                    if(empty($tipe_cond)){ $tipe_cond[] = '(1=0)'; }
                    $tipe_where_plain = "(".implode(' OR ', $tipe_cond).")";

                    $tujuan_where_plain = ($col_tujuan != '') ? "$col_tujuan = '$kd_cabang'" : '1=0';
                    $sum_total_plain = ($col_total != '') ? "COALESCE($col_total,0)" : '0';

                    // Calculate summary
                    $sum_kirim = mysqli_query($koneksi, "SELECT COUNT(*) as cnt, COALESCE(SUM($sum_total_plain),0) as total
                                                         FROM tblorderjual_header
                                                         WHERE kd_cabang='$kd_cabang'
                                                         AND $tipe_where_plain
                                                         AND tanggal BETWEEN '$filter_tgl_dari' AND '$filter_tgl_sampai'");
                    $r_kirim = ($sum_kirim) ? mysqli_fetch_array($sum_kirim) : ['cnt' => 0, 'total' => 0];

                    $sum_terima = mysqli_query($koneksi, "SELECT COUNT(*) as cnt, COALESCE(SUM($sum_total_plain),0) as total
                                                          FROM tblorderjual_header
                                                          WHERE $tujuan_where_plain
                                                          AND $tipe_where_plain
                                                          AND tanggal BETWEEN '$filter_tgl_dari' AND '$filter_tgl_sampai'");
                    $r_terima = ($sum_terima) ? mysqli_fetch_array($sum_terima) : ['cnt' => 0, 'total' => 0];
                    ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="summary-box" style="border-left: 4px solid #337ab7;">
                                <h4><i class="fa fa-arrow-up"></i> Pengiriman (Keluar)</h4>
                                <div class="summary-item">
                                    Transaksi: <span class="text-primary"><?php echo number_format($r_kirim['cnt']); ?></span>
                                </div>
                                <div class="summary-item">
                                    Total: <span class="text-primary">Rp <?php echo number_format($r_kirim['total'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="summary-box" style="border-left: 4px solid #5cb85c;">
                                <h4><i class="fa fa-arrow-down"></i> Penerimaan (Masuk)</h4>
                                <div class="summary-item">
                                    Transaksi: <span class="text-success"><?php echo number_format($r_terima['cnt']); ?></span>
                                </div>
                                <div class="summary-item">
                                    Total: <span class="text-success">Rp <?php echo number_format($r_terima['total'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-small">
                                    <h5 class="widget-title"><i class="fa fa-filter"></i> Filter</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="get" class="form-inline">
                                            <div class="form-group">
                                                <label>Tipe:</label>
                                                <select name="type" class="form-control input-sm">
                                                    <option value="all" <?php echo $filter_type=='all'?'selected':''; ?>>Semua</option>
                                                    <option value="kirim" <?php echo $filter_type=='kirim'?'selected':''; ?>>Pengiriman</option>
                                                    <option value="terima" <?php echo $filter_type=='terima'?'selected':''; ?>>Penerimaan</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select name="status" class="form-control input-sm">
                                                    <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>Semua</option>
                                                    <option value="pending" <?php echo $filter_status=='pending'?'selected':''; ?>>Pending</option>
                                                    <option value="completed" <?php echo $filter_status=='completed'?'selected':''; ?>>Selesai</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Cabang:</label>
                                                <select name="cabang" class="form-control input-sm">
                                                    <option value="">-- Semua --</option>
                                                    <?php
                                                    $q_cab = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang
                                                                                      WHERE kode_cabang != '$kd_cabang' AND status='1'
                                                                                      ORDER BY nama_cabang");
                                                    if($q_cab && mysqli_num_rows($q_cab) > 0){
                                                        while($r_cab = mysqli_fetch_array($q_cab)){
                                                            $sel = ($r_cab['kode_cabang'] == $filter_cabang) ? 'selected' : '';
                                                            echo "<option value='{$r_cab['kode_cabang']}' $sel>{$r_cab['nama_cabang']}</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Periode:</label>
                                                <input type="date" name="tgl_dari" class="form-control input-sm" value="<?php echo $filter_tgl_dari; ?>">
                                                <span>s/d</span>
                                                <input type="date" name="tgl_sampai" class="form-control input-sm" value="<?php echo $filter_tgl_sampai; ?>">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="fa fa-search"></i> Filter
                                            </button>
                                            <a href="antarcab_list.php" class="btn btn-sm btn-default">
                                                <i class="fa fa-refresh"></i> Reset
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <br>

                    <!-- Daftar Transaksi -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                Daftar Transaksi Antar Cabang
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th class="center" width="4%">No</th>
                                            <th class="center" width="8%">Tipe</th>
                                            <th width="12%">No. Transaksi</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="15%">Cabang Asal</th>
                                            <th width="15%">Cabang Tujuan</th>
                                            <th class="center" width="6%">Qty</th>
                                            <th class="right" width="12%">Total</th>
                                            <th class="center" width="8%">Status</th>
                                            <th class="center" width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Build WHERE clause based on filter
                                        $tipe_cond = [];
                                        if($has_tipe_transaksi){ $tipe_cond[] = "(oh.tipe_transaksi = 'ANTAR_CABANG' OR oh.tipe_transaksi = 'Antar Cabang')"; }
                                        if($has_tipe_trx){ $tipe_cond[] = "(oh.tipe_trx = 'ANTAR_CABANG' OR oh.tipe_trx = 'Antar Cabang')"; }
                                        if(empty($tipe_cond)){ $tipe_cond[] = '(1=0)'; }
                                        $where_conditions = ["(".implode(' OR ', $tipe_cond).")"];
                                        $where_conditions[] = "oh.tanggal BETWEEN '$filter_tgl_dari' AND '$filter_tgl_sampai'";

                                        if($filter_type == 'kirim'){
                                            $where_conditions[] = "oh.kd_cabang = '$kd_cabang'";
                                        } elseif($filter_type == 'terima'){
                                            if($col_tujuan != ''){
                                                $where_conditions[] = "oh.$col_tujuan = '$kd_cabang'";
                                            } else {
                                                $where_conditions[] = "1=0";
                                            }
                                        } else {
                                            if($col_tujuan != ''){
                                                $where_conditions[] = "(oh.kd_cabang = '$kd_cabang' OR oh.$col_tujuan = '$kd_cabang')";
                                            } else {
                                                $where_conditions[] = "oh.kd_cabang = '$kd_cabang'";
                                            }
                                        }

                                        if($filter_status == 'pending'){
                                            $where_conditions[] = "oh.status = '0'";
                                        } elseif($filter_status == 'completed'){
                                            $where_conditions[] = "oh.status = '1'";
                                        }

                                        if($filter_cabang != ''){
                                            if($col_tujuan != ''){
                                                $where_conditions[] = "(oh.kd_cabang = '$filter_cabang' OR oh.$col_tujuan = '$filter_cabang')";
                                            } else {
                                                $where_conditions[] = "oh.kd_cabang = '$filter_cabang'";
                                            }
                                        }

                                        $where = "WHERE " . implode(" AND ", $where_conditions);

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
                                        $expr_tujuan = ($col_tujuan != '') ? "oh.$col_tujuan" : "''";
                                        $sql = "SELECT oh.*,
                                                       $expr_tujuan as kd_tujuan,
                                                       c_asal.nama_cabang as cabang_asal_nama,
                                                       c_tujuan.nama_cabang as cabang_tujuan_nama
                                                FROM tblorderjual_header oh
                                                LEFT JOIN tbcabang c_asal ON c_asal.kode_cabang = oh.kd_cabang
                                                LEFT JOIN tbcabang c_tujuan ON c_tujuan.kode_cabang = $expr_tujuan
                                                $where
                                                ORDER BY oh.tanggal DESC, oh.no_order DESC
                                                LIMIT $limit OFFSET $offset";

                                        $result = mysqli_query($koneksi, $sql);
                                        $no = $offset;

                                        if($result && mysqli_num_rows($result) > 0){
                                            while($row = mysqli_fetch_array($result)){
                                                $no++;

                                                // Determine type
                                                $is_kirim = ($row['kd_cabang'] == $kd_cabang);
                                                $type_text = $is_kirim ? 'Kirim' : 'Terima';
                                                $type_class = $is_kirim ? 'type-kirim' : 'type-terima';

                                                // Determine status
                                                $status_text = ($row['status'] == '1') ? 'Selesai' : 'Pending';
                                                $status_class = ($row['status'] == '1') ? 'status-received' : 'status-pending';

                                                $row_total = 0;
                                                if($col_total != '' && isset($row[$col_total])){
                                                    $row_total = $row[$col_total];
                                                }
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no; ?></td>
                                            <td class="center">
                                                <span class="type-badge <?php echo $type_class; ?>">
                                                    <i class="fa fa-arrow-<?php echo $is_kirim?'up':'down'; ?>"></i>
                                                    <?php echo $type_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo $row['no_order']; ?></strong>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <small class="text-muted"><?php echo $row['kd_cabang']; ?></small><br>
                                                <?php echo $row['cabang_asal_nama']; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo $row['kd_tujuan']; ?></small><br>
                                                <?php echo $row['cabang_tujuan_nama']; ?>
                                            </td>
                                            <td class="center"><?php echo number_format($row['total_qty'], 0); ?></td>
                                            <td class="right"><?php echo number_format($row_total, 0, ',', '.'); ?></td>
                                            <td class="center">
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <a href="pesanan_penjualan_cab_cetak.php?nopesanan=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-info" title="Detail">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="pesanan_penjualan_cab_cetak.php?nopesanan=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-default" target="_blank" title="Cetak">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="10" class="center">
                                                <div class="alert alert-warning" style="margin:20px;">
                                                    <i class="fa fa-exclamation-circle"></i>
                                                    Tidak ada transaksi antar cabang yang ditemukan.
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
                                        <?php
                                        $query_string = http_build_query([
                                            'type' => $filter_type,
                                            'status' => $filter_status,
                                            'cabang' => $filter_cabang,
                                            'tgl_dari' => $filter_tgl_dari,
                                            'tgl_sampai' => $filter_tgl_sampai
                                        ]);
                                        ?>
                                        <?php if($page > 1){ ?>
                                        <li>
                                            <a href="?page=<?php echo $page-1; ?>&<?php echo $query_string; ?>">
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
                                            <a href="?page=<?php echo $i; ?>&<?php echo $query_string; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php } ?>

                                        <?php if($page < $total_pages){ ?>
                                        <li>
                                            <a href="?page=<?php echo $page+1; ?>&<?php echo $query_string; ?>">
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
