<?php
/**
 * Penjualan dari Sales Order (SO)
 * Menampilkan daftar Sales Order yang siap dikonversi menjadi Penjualan/Faktur
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

    // Pagination
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Search
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'open';
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
        .status-open { background: #f0ad4e; color: white; }
        .status-partial { background: #5bc0de; color: white; }
        .status-completed { background: #5cb85c; color: white; }
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
                        <li><a href="#">Penjualan</a></li>
                        <li class="active">Penjualan dari SO</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Penjualan dari Sales Order
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Konversi SO menjadi Faktur Penjualan
                            </small>
                        </h1>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        Pilih Sales Order (SO) yang akan dikonversi menjadi Faktur Penjualan.
                        Hanya SO dengan status <strong>"Open"</strong> yang dapat diproses.
                    </div>

                    <!-- Filter & Search -->
                    <div class="row">
                        <div class="col-xs-12">
                            <form method="get" class="form-inline" style="margin-bottom:15px;">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control input-sm">
                                        <option value="open" <?php echo $filter_status=='open'?'selected':''; ?>>Open (Belum Proses)</option>
                                        <option value="partial" <?php echo $filter_status=='partial'?'selected':''; ?>>Partial</option>
                                        <option value="completed" <?php echo $filter_status=='completed'?'selected':''; ?>>Completed</option>
                                        <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>Semua</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cari:</label>
                                    <input type="text" name="search" class="form-control input-sm"
                                           placeholder="No. SO / Pelanggan" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <button type="submit" class="btn btn-sm btn-info">
                                    <i class="fa fa-search"></i> Cari
                                </button>
                                <a href="penjualan_dari_so.php" class="btn btn-sm btn-default">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Daftar SO -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                Daftar Sales Order
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th class="center" width="5%">No</th>
                                            <th width="12%">No. SO</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="18%">Pelanggan</th>
                                            <th width="12%">Sales</th>
                                            <th class="center" width="8%">Qty</th>
                                            <th class="right" width="12%">Total</th>
                                            <th class="center" width="10%">Status</th>
                                            <th class="center" width="13%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Build WHERE clause
                                        $where = "WHERE oh.kd_cabang = '$kd_cabang'
                                                  AND (oh.tipe_transaksi IS NULL OR oh.tipe_transaksi = '' OR oh.tipe_transaksi = 'NORMAL')";

                                        if($filter_status == 'open'){
                                            $where .= " AND oh.status = '0'";
                                        } elseif($filter_status == 'partial'){
                                            $where .= " AND oh.status = '2'";
                                        } elseif($filter_status == 'completed'){
                                            $where .= " AND oh.status = '1'";
                                        }

                                        if($search != ''){
                                            $where .= " AND (oh.no_order LIKE '%$search%' OR p.namapelanggan LIKE '%$search%')";
                                        }

                                        // Count total
                                        $count_sql = "SELECT COUNT(*) as total
                                                      FROM tblorderjual_header oh
                                                      LEFT JOIN tblpelanggan p ON p.nopelanggan = oh.no_pelanggan
                                                      $where";
                                        $count_result = mysqli_query($koneksi, $count_sql);
                                        $total_records = ($count_result && $row_count = mysqli_fetch_array($count_result)) ? $row_count['total'] : 0;
                                        $total_pages = ($total_records > 0) ? ceil($total_records / $limit) : 0;

                                        // Get data
                                        $sql = "SELECT oh.*,
                                                       p.namapelanggan,
                                                       s.nama_sales,
                                                       (SELECT COUNT(*) FROM tblorderjual_detail WHERE no_order=oh.no_order) as item_count
                                                FROM tblorderjual_header oh
                                                LEFT JOIN tblpelanggan p ON p.nopelanggan = oh.no_pelanggan
                                                LEFT JOIN tblsales s ON s.id_sales = oh.no_sales
                                                $where
                                                ORDER BY oh.tanggal DESC, oh.no_order DESC
                                                LIMIT $limit OFFSET $offset";

                                        $result = mysqli_query($koneksi, $sql);
                                        $no = $offset;

                                        if($result && mysqli_num_rows($result) > 0){
                                            while($row = mysqli_fetch_array($result)){
                                                $no++;

                                                // Determine status
                                                if($row['status'] == '1'){
                                                    $status_text = 'Completed';
                                                    $status_class = 'status-completed';
                                                } elseif($row['status'] == '2'){
                                                    $status_text = 'Partial';
                                                    $status_class = 'status-partial';
                                                } else {
                                                    $status_text = 'Open';
                                                    $status_class = 'status-open';
                                                }
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no; ?></td>
                                            <td>
                                                <strong><?php echo $row['no_order']; ?></strong>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <small class="text-muted"><?php echo $row['no_pelanggan']; ?></small><br>
                                                <?php echo $row['namapelanggan']; ?>
                                            </td>
                                            <td><?php echo $row['nama_sales']; ?></td>
                                            <td class="center"><?php echo number_format($row['total_qty'], 0); ?></td>
                                            <td class="right"><?php echo number_format($row['total_order'], 0, ',', '.'); ?></td>
                                            <td class="center">
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <a href="pesanan_penjualan_detail.php?no=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-info" title="Lihat Detail">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if($row['status'] == '0' || $row['status'] == '2'){ ?>
                                                <a href="penjualan_add.php?so=<?php echo urlencode($row['no_order']); ?>"
                                                   class="btn btn-xs btn-success" title="Proses Jual">
                                                    <i class="fa fa-arrow-right"></i> Proses
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
                                                    Tidak ada Sales Order yang ditemukan.
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
                                            <a href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
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
                                            <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php } ?>

                                        <?php if($page < $total_pages){ ?>
                                        <li>
                                            <a href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
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
