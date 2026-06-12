<?php
/**
 * Pembelian dari PO - Daftar PO yang siap diproses menjadi Pembelian
 * Menampilkan PO yang sudah approved dan belum sepenuhnya diproses
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
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'ready';
    $filter_supplier = isset($_GET['supplier']) ? mysqli_real_escape_string($koneksi, $_GET['supplier']) : '';
    $filter_keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, $_GET['keyword']) : '';
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
        .status-approved { background: #5cb85c; color: white; }
        .status-partial { background: #f0ad4e; color: white; }
        .status-complete { background: #5bc0de; color: white; }
        .progress-bar-container {
            background: #f5f5f5;
            border-radius: 3px;
            height: 20px;
            position: relative;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }
        .progress-text {
            position: absolute;
            width: 100%;
            text-align: center;
            line-height: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .table-hover tbody tr:hover {
            background-color: #f9f9f9;
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

            <?php include "menu_pembelian02.php"; ?>

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
                        <li><a href="pembelian.php">Pembelian</a></li>
                        <li class="active">Dari PO</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Pembelian dari PO
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Proses PO menjadi Pembelian
                            </small>
                        </h1>
                    </div>

                    <!-- Filter -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-small">
                                    <h5 class="widget-title">Filter</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="get" class="form-inline">
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <select name="status" class="form-control input-sm">
                                                    <option value="ready" <?php echo $filter_status=='ready'?'selected':''; ?>>Siap Diproses</option>
                                                    <option value="partial" <?php echo $filter_status=='partial'?'selected':''; ?>>Sebagian Diterima</option>
                                                    <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>Semua</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Supplier:</label>
                                                <select name="supplier" class="form-control input-sm">
                                                    <option value="">-- Semua Supplier --</option>
                                                    <?php
                                                    $q_sup = mysqli_query($koneksi, "SELECT nosupplier, namasupplier FROM tblsupplier ORDER BY namasupplier");
                                                    while($r_sup = mysqli_fetch_array($q_sup)){
                                                        $sel = ($r_sup['nosupplier'] == $filter_supplier) ? 'selected' : '';
                                                        echo "<option value='{$r_sup['nosupplier']}' $sel>{$r_sup['namasupplier']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Keyword:</label>
                                                <input type="text" name="keyword" class="form-control input-sm"
                                                       placeholder="No. PO / Item" value="<?php echo htmlspecialchars($filter_keyword); ?>">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="fa fa-search"></i> Cari
                                            </button>
                                            <a href="pembelian_dari_po.php" class="btn btn-sm btn-default">
                                                <i class="fa fa-refresh"></i> Reset
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-6"></div>

                    <!-- Daftar PO -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-header">
                                Daftar PO yang Siap Diproses
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th class="center" width="5%">No</th>
                                            <th width="12%">No. PO</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="20%">Supplier</th>
                                            <th class="center" width="8%">Total Item</th>
                                            <th class="right" width="12%">Total Order</th>
                                            <th width="15%">Progress</th>
                                            <th class="center" width="8%">Status</th>
                                            <th class="center" width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Build query
                                        $where_clause = "WHERE oh.kd_cabang='$kd_cabang'";

                                        // Filter by status
                                        if($filter_status == 'ready'){
                                            $where_clause .= " AND (oh.status_approval='approved' OR oh.status_approval IS NULL OR oh.status_approval='')";
                                            $where_clause .= " AND oh.status='0'"; // Belum selesai
                                        } elseif($filter_status == 'partial'){
                                            $where_clause .= " AND oh.status='0'";
                                            $where_clause .= " AND oh.total_terima > 0 AND oh.total_terima < oh.total_qty";
                                        }

                                        if($filter_supplier != ''){
                                            $where_clause .= " AND oh.no_supplier='$filter_supplier'";
                                        }

                                        if($filter_keyword != ''){
                                            $where_clause .= " AND (oh.no_order LIKE '%$filter_keyword%'
                                                              OR s.namasupplier LIKE '%$filter_keyword%')";
                                        }

                                        $sql = "SELECT oh.*, s.namasupplier,
                                                    (SELECT COUNT(*) FROM tblorder_detail WHERE no_order=oh.no_order) as item_count
                                                FROM tblorder_header oh
                                                LEFT JOIN tblsupplier s ON s.nosupplier = oh.no_supplier
                                                $where_clause
                                                ORDER BY oh.tanggal DESC, oh.no_order DESC";

                                        $result = mysqli_query($koneksi, $sql);
                                        $no = 0;

                                        if(mysqli_num_rows($result) > 0){
                                            while($row = mysqli_fetch_array($result)){
                                                $no++;
                                                $total_qty = (int)$row['total_qty'];
                                                $total_terima = (int)$row['total_terima'];
                                                $progress = ($total_qty > 0) ? round(($total_terima / $total_qty) * 100) : 0;

                                                // Status badge
                                                if($row['status'] == '1'){
                                                    $status_class = 'status-complete';
                                                    $status_text = 'Selesai';
                                                } elseif($total_terima > 0){
                                                    $status_class = 'status-partial';
                                                    $status_text = 'Sebagian';
                                                } else {
                                                    $status_class = 'status-approved';
                                                    $status_text = 'Siap';
                                                }

                                                // Progress bar color
                                                if($progress >= 100) $bar_color = '#5cb85c';
                                                elseif($progress >= 50) $bar_color = '#5bc0de';
                                                else $bar_color = '#f0ad4e';
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no; ?></td>
                                            <td>
                                                <strong><?php echo $row['no_order']; ?></strong>
                                                <?php if($row['tipe_trx'] == 'UPLOAD'){ ?>
                                                    <span class="label label-info label-sm">Upload</span>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <small class="text-muted"><?php echo $row['no_supplier']; ?></small><br>
                                                <?php echo $row['namasupplier']; ?>
                                            </td>
                                            <td class="center"><?php echo $row['item_count']; ?> item</td>
                                            <td class="right"><?php echo number_format($row['total_order'], 0, ',', '.'); ?></td>
                                            <td>
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill" style="width:<?php echo $progress; ?>%;background:<?php echo $bar_color; ?>"></div>
                                                    <span class="progress-text"><?php echo $total_terima; ?> / <?php echo $total_qty; ?> (<?php echo $progress; ?>%)</span>
                                                </div>
                                            </td>
                                            <td class="center">
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown" class="btn dropdown-toggle btn-xs btn-primary">
                                                        Aksi <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li>
                                                            <a href="pesanan_pembelian_detail.php?nopesanan=<?php echo $row['no_order']; ?>">
                                                                <i class="fa fa-eye"></i> Lihat Detail
                                                            </a>
                                                        </li>
                                                        <?php if($row['status'] != '1'){ ?>
                                                        <li>
                                                            <a href="pembelian_add.php?po=<?php echo $row['no_order']; ?>">
                                                                <i class="fa fa-shopping-cart text-success"></i> Proses Pembelian
                                                            </a>
                                                        </li>
                                                        <?php } ?>
                                                        <li>
                                                            <a href="pesanan_pembelian_cetak.php?nopesanan=<?php echo $row['no_order']; ?>" target="_blank">
                                                                <i class="fa fa-print"></i> Cetak PO
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="9" class="center">
                                                <div class="alert alert-info" style="margin:20px;">
                                                    <i class="fa fa-info-circle"></i>
                                                    Tidak ada PO yang ditemukan dengan kriteria filter ini.
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="space-12"></div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <a href="pembelian_add.php" class="btn btn-success btn-block">
                                <i class="fa fa-plus"></i> Input Pembelian Manual
                            </a>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <a href="pesanan_pembelian_upload.php" class="btn btn-info btn-block">
                                <i class="fa fa-upload"></i> Upload PO dari Excel
                            </a>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <a href="pembelian.php" class="btn btn-default btn-block">
                                <i class="fa fa-list"></i> Daftar Pembelian
                            </a>
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

        <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
            <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
        </a>
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
