<?php
/**
 * RENCANA ORDER - Halaman List
 * Menampilkan daftar rencana order berdasarkan kalkulasi MIN/MAX
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
    $pwd=$tm_cari['password'];
    $lvl_akses=$tm_cari['user_akses'];
    $foto_user=$tm_cari['foto_user'];
    if($foto_user=='') {
        $foto_user="file_upload/avatar.png";
    }

    // ------- Data Cabang ----------
    $cari_kd=mysqli_query($koneksi,"SELECT
                                    nama_cabang, tipe_cabang
                                    FROM tbcabang
                                    WHERE kode_cabang='$kd_cabang'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $nama_cabang=$tm_cari['nama_cabang'];
    $tipe_cabang=$tm_cari['tipe_cabang'];
    // --------------------

    $tgl_skr=date('d');
    $bulan_skr=date('m');
    $thn_skr=date('Y');

    // == Default ==
    $txtkey="";
    $txtcari="";

    // Pagination variables
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Filter status
    $filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';

    // Count total records
    $where_clause = "1=1";
    if (!empty($filter_status)) {
        $where_clause .= " AND status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
    }

    $count_query = "SELECT COUNT(*) as total FROM tblrencana_order_header WHERE $where_clause";
    $count_result = mysqli_query($koneksi, $count_query);
    $total_records = $count_result ? mysqli_fetch_array($count_result)['total'] : 0;
    $total_pages = ceil($total_records / $limit);

    $hasil_cari = "Menampilkan data terbaru - Total: $total_records data (Halaman $page dari $total_pages)";

    $sql_query = "SELECT * FROM tblrencana_order_header
                 WHERE $where_clause
                 ORDER BY tanggal DESC, no_rencana DESC
                 LIMIT $limit OFFSET $offset";

    // Get summary stats
    $stats_query = "SELECT
                        status,
                        COUNT(*) as jumlah,
                        SUM(total_item) as total_item,
                        SUM(total_nilai) as total_nilai
                    FROM tblrencana_order_header
                    GROUP BY status";
    $stats_result = mysqli_query($koneksi, $stats_query);
    $stats = [];
    if ($stats_result) {
        while ($row = mysqli_fetch_assoc($stats_result)) {
            $stats[$row['status']] = $row;
        }
    }

    // Get items needing order count
    $urgent_query = "SELECT COUNT(*) as total FROM view_item_order_segera WHERE status_stok = 'URGENT'";
    $urgent_result = mysqli_query($koneksi, $urgent_query);
    $urgent_count = $urgent_result ? mysqli_fetch_array($urgent_result)['total'] : 0;

    $warning_query = "SELECT COUNT(*) as total FROM view_item_order_segera WHERE status_stok = 'WARNING'";
    $warning_result = mysqli_query($koneksi, $warning_query);
    $warning_count = $warning_result ? mysqli_fetch_array($warning_result)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title><?php include "../lib/titel.php"; ?></title>

        <meta name="description" content="Rencana Order" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

        <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <!-- ace settings handler -->
        <script src="assets/js/ace-extra.min.js"></script>

        <style>
            .stat-box {
                background: #fff;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 15px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
            }
            .stat-box h3 {
                margin: 0;
                font-size: 28px;
                font-weight: bold;
            }
            .stat-box p {
                margin: 5px 0 0 0;
                color: #666;
                font-size: 12px;
            }
            .stat-urgent { border-left: 4px solid #d9534f; }
            .stat-warning { border-left: 4px solid #f0ad4e; }
            .stat-draft { border-left: 4px solid #5bc0de; }
            .stat-approved { border-left: 4px solid #5cb85c; }
            .badge-urgent { background-color: #d9534f; }
            .badge-warning { background-color: #f0ad4e; }
            .badge-draft { background-color: #5bc0de; }
            .badge-approved { background-color: #5cb85c; }
            .badge-ordered { background-color: #337ab7; }
            .badge-completed { background-color: #999; }
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
                    <table>
                        <tr>
                            <td width="20%">
                                <a href="index.php" class="navbar-brand">
                                    <small>
                                        <i class="fa fa-leaf"></i>
                                        <?php include "../lib/subtitel.php"; ?>
                                    </small>
                                </a>
                            </td>
                            <td></td>
                        </tr>
                    </table>
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
                                <li>
                                    <a href="change_pwd.php">
                                        <i class="ace-icon fa fa-cog"></i>
                                        Change Password
                                    </a>
                                </li>
                                <li>
                                    <a href="profile.php">
                                        <i class="ace-icon fa fa-user"></i>
                                        Profile
                                    </a>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="logout.php">
                                        <i class="ace-icon fa fa-power-off"></i>
                                        Logout
                                    </a>
                                </li>
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
                            <li>
                                <a href="#">Pembelian</a>
                            </li>
                            <li class="active">Rencana Order</li>
                        </ul>
                    </div>

                    <div class="page-content">
                        <div class="page-header">
                            <h1>
                                Rencana Order
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Berdasarkan Kalkulasi MIN/MAX Stok
                                </small>
                            </h1>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-xs-6 col-sm-3">
                                <div class="stat-box stat-urgent">
                                    <h3 class="text-danger"><?php echo $urgent_count; ?></h3>
                                    <p>Item URGENT</p>
                                    <small>Stok &lt; 50% MIN</small>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="stat-box stat-warning">
                                    <h3 class="text-warning"><?php echo $warning_count; ?></h3>
                                    <p>Item WARNING</p>
                                    <small>Stok &lt; MIN</small>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="stat-box stat-draft">
                                    <h3 class="text-info"><?php echo isset($stats['draft']) ? $stats['draft']['jumlah'] : 0; ?></h3>
                                    <p>Draft Order</p>
                                    <small>Menunggu Approval</small>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="stat-box stat-approved">
                                    <h3 class="text-success"><?php echo isset($stats['approved']) ? $stats['approved']['jumlah'] : 0; ?></h3>
                                    <p>Approved</p>
                                    <small>Siap Diorder</small>
                                </div>
                            </div>
                        </div>

                        <div class="space space-8"></div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-xs-12 col-sm-3">
                                <a href="rencana_order_add.php" class="btn btn-success btn-block">
                                    <i class="fa fa-plus"></i> Buat Rencana Order Baru
                                </a>
                            </div>
                            <div class="col-xs-12 col-sm-3">
                                <a href="procurement_dashboard.php" class="btn btn-info btn-block">
                                    <i class="fa fa-dashboard"></i> Dashboard MIN/MAX
                                </a>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="pull-right">
                                    <form method="GET" style="display: inline-block;">
                                        <label>Status: </label>
                                        <select name="status" onchange="this.form.submit()" class="form-control" style="display: inline-block; width: auto;">
                                            <option value="">Semua</option>
                                            <option value="draft" <?php echo $filter_status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="ordered" <?php echo $filter_status == 'ordered' ? 'selected' : ''; ?>>Ordered</option>
                                            <option value="partial" <?php echo $filter_status == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>

                                        <label style="margin-left: 15px;">Tampilkan: </label>
                                        <select name="limit" onchange="this.form.submit()" class="form-control" style="display: inline-block; width: auto;">
                                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                        </select>
                                        <label> data</label>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="space space-8"></div>

                        <!-- Data Table -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="table-header">
                                    <?php echo $hasil_cari; ?>
                                </div>
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr class="info">
                                            <th class="center" width="5%">No</th>
                                            <th width="12%">No. Rencana</th>
                                            <th class="center" width="10%">Tanggal</th>
                                            <th width="15%">Periode Analisis</th>
                                            <th class="center" width="8%">Jml Item</th>
                                            <th class="right" width="12%">Total Nilai</th>
                                            <th class="center" width="10%">Status</th>
                                            <th width="12%">Dibuat Oleh</th>
                                            <th class="center" width="16%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = mysqli_query($koneksi, $sql_query);
                                        $no = $offset + 1;

                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $badge_class = 'badge-draft';
                                                switch ($row['status']) {
                                                    case 'approved': $badge_class = 'badge-approved'; break;
                                                    case 'ordered': $badge_class = 'badge-ordered'; break;
                                                    case 'partial': $badge_class = 'badge-warning'; break;
                                                    case 'completed': $badge_class = 'badge-completed'; break;
                                                }
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['no_rencana']); ?></strong></td>
                                            <td class="center"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($row['periode_awal'])); ?> -
                                                <?php echo date('d/m/Y', strtotime($row['periode_akhir'])); ?>
                                            </td>
                                            <td class="center"><?php echo number_format($row['total_item']); ?></td>
                                            <td class="right">Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?></td>
                                            <td class="center">
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo strtoupper($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                            <td class="center">
                                                <div class="btn-group">
                                                    <a href="rencana_order_detail.php?no=<?php echo urlencode($row['no_rencana']); ?>"
                                                       class="btn btn-xs btn-info" title="Lihat Detail">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <?php if ($row['status'] == 'draft') { ?>
                                                    <a href="rencana_order_edit.php?no=<?php echo urlencode($row['no_rencana']); ?>"
                                                       class="btn btn-xs btn-warning" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="rencana_order_approve.php?no=<?php echo urlencode($row['no_rencana']); ?>"
                                                       class="btn btn-xs btn-success" title="Approve">
                                                        <i class="fa fa-check"></i>
                                                    </a>
                                                    <?php } ?>
                                                    <?php if ($row['status'] == 'approved') { ?>
                                                    <a href="po_from_rencana.php?no=<?php echo urlencode($row['no_rencana']); ?>"
                                                       class="btn btn-xs btn-primary" title="Buat PO">
                                                        <i class="fa fa-shopping-cart"></i>
                                                    </a>
                                                    <?php } ?>
                                                    <a href="rencana_order_print.php?no=<?php echo urlencode($row['no_rencana']); ?>"
                                                       class="btn btn-xs btn-default" title="Print" target="_blank">
                                                        <i class="fa fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="9" class="center">
                                                <div class="alert alert-info">
                                                    <i class="fa fa-info-circle"></i>
                                                    Belum ada data rencana order.
                                                    <a href="rencana_order_add.php">Klik di sini</a> untuk membuat rencana order baru berdasarkan kalkulasi MIN/MAX.
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1) { ?>
                                <div class="text-center">
                                    <ul class="pagination">
                                        <?php if ($page > 1) { ?>
                                        <li>
                                            <a href="?page=1&limit=<?php echo $limit; ?>&status=<?php echo $filter_status; ?>">
                                                <i class="fa fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&status=<?php echo $filter_status; ?>">
                                                <i class="fa fa-angle-left"></i>
                                            </a>
                                        </li>
                                        <?php } ?>

                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);

                                        for ($i = $start_page; $i <= $end_page; $i++) {
                                        ?>
                                        <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                            <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&status=<?php echo $filter_status; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php } ?>

                                        <?php if ($page < $total_pages) { ?>
                                        <li>
                                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&status=<?php echo $filter_status; ?>">
                                                <i class="fa fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="?page=<?php echo $total_pages; ?>&limit=<?php echo $limit; ?>&status=<?php echo $filter_status; ?>">
                                                <i class="fa fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <?php } ?>
                            </div>
                        </div>

                    </div><!-- /.page-content -->
                </div>
            </div><!-- /.main-content -->

            <div class="footer">
                <div class="footer-inner">
                    <div class="footer-content">
                        <span class="bigger-120">
                            <span class="blue bolder"><?php include "../lib/subtitel.php"; ?></span>
                            &copy; <?php echo date('Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
                <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
            </a>
        </div><!-- /.main-container -->

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script type="text/javascript">
            if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
        </script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>
    </body>
</html>
<?php } ?>
