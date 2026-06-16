<?php
/**
 * Laporan Penerimaan Antar Cabang
 * Menampilkan laporan penerimaan barang dari cabang lain
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
    $filter_cabang = isset($_GET['cabang']) ? mysqli_real_escape_string($koneksi, $_GET['cabang']) : '';
    $filter_tgl_dari = isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : date('Y-m-01');
    $filter_tgl_sampai = isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : date('Y-m-d');
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

    <script src="assets/js/ace-extra.min.js"></script>

    <style>
        .summary-box { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .summary-box h4 { margin: 0 0 10px; color: #555; }
        @media print {
            .no-print { display: none !important; }
            .main-content { margin: 0 !important; }
            #sidebar, .navbar, .footer, .breadcrumbs { display: none !important; }
        }
    </style>
</head>

<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state no-print">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
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
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User" />
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
        <div id="sidebar" class="sidebar responsive ace-save-state no-print">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state no-print" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Laporan</a></li>
                        <li><a href="#">Antar Cabang</a></li>
                        <li class="active">Penerimaan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Laporan Penerimaan Antar Cabang
                            <small><i class="ace-icon fa fa-angle-double-right"></i> <?php echo $nama_cabang; ?></small>
                        </h1>
                    </div>

                    <!-- Filter -->
                    <div class="row no-print">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-small">
                                    <h5 class="widget-title"><i class="fa fa-filter"></i> Filter</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form method="get" class="form-inline">
                                            <div class="form-group">
                                                <label>Dari Cabang:</label>
                                                <select name="cabang" class="form-control input-sm">
                                                    <option value="">-- Semua --</option>
                                                    <?php
                                                    $q_cab = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang
                                                                                      WHERE kode_cabang != '$kd_cabang' AND status='1'
                                                                                      ORDER BY nama_cabang");
                                                    while($r_cab = mysqli_fetch_array($q_cab)){
                                                        $sel = ($r_cab['kode_cabang'] == $filter_cabang) ? 'selected' : '';
                                                        echo "<option value='{$r_cab['kode_cabang']}' $sel>{$r_cab['nama_cabang']}</option>";
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
                                            <button type="button" class="btn btn-sm btn-default" onclick="window.print()">
                                                <i class="fa fa-print"></i> Cetak
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <br>

                    <!-- Summary -->
                    <?php
                    $where = "WHERE oh.kd_cabang_tujuan = '$kd_cabang'
                              AND oh.tipe_transaksi IN ('ANTAR_CABANG', 'MITRA_EKSTERNAL')
                              AND oh.tanggal BETWEEN '$filter_tgl_dari' AND '$filter_tgl_sampai'";
                    if($filter_cabang != ''){
                        $where .= " AND oh.kd_cabang = '$filter_cabang'";
                    }

                    $sum = mysqli_query($koneksi, "SELECT COUNT(*) as cnt, COALESCE(SUM(total_qty),0) as qty, COALESCE(SUM(total_order),0) as total
                                                    FROM tblorderjual_header oh $where");
                    $r_sum = mysqli_fetch_array($sum);

                    $sum_diterima = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tblorderjual_header oh $where AND oh.status='1'");
                    $r_diterima = mysqli_fetch_array($sum_diterima);
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="summary-box">
                                <h4><i class="fa fa-arrow-down"></i> Ringkasan Penerimaan</h4>
                                <div class="row">
                                    <div class="col-sm-3">
                                        <strong>Total Transaksi:</strong> <?php echo number_format($r_sum['cnt']); ?>
                                    </div>
                                    <div class="col-sm-3">
                                        <strong>Sudah Diterima:</strong> <?php echo number_format($r_diterima['cnt']); ?>
                                    </div>
                                    <div class="col-sm-3">
                                        <strong>Total Qty:</strong> <?php echo number_format($r_sum['qty']); ?>
                                    </div>
                                    <div class="col-sm-3">
                                        <strong>Total Nilai:</strong> Rp <?php echo number_format($r_sum['total'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="success">
                                            <th class="center" width="5%">No</th>
                                            <th width="12%">No. Transaksi</th>
                                            <th width="10%">Tanggal</th>
                                            <th width="20%">Dari Cabang</th>
                                            <th width="10%">Tipe</th>
                                            <th class="center" width="8%">Qty</th>
                                            <th class="right" width="15%">Total</th>
                                            <th class="center" width="10%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT oh.*, c.nama_cabang as cabang_asal_nama
                                                FROM tblorderjual_header oh
                                                LEFT JOIN tbcabang c ON c.kode_cabang = oh.kd_cabang
                                                $where
                                                ORDER BY oh.tanggal DESC, oh.no_order DESC";
                                        $result = mysqli_query($koneksi, $sql);
                                        $no = 0;
                                        $grand_qty = 0;
                                        $grand_total = 0;

                                        if($result && mysqli_num_rows($result) > 0){
                                            while($row = mysqli_fetch_array($result)){
                                                $no++;
                                                $grand_qty += $row['total_qty'];
                                                $grand_total += $row['total_order'];

                                                $tipe = ($row['tipe_transaksi'] == 'MITRA_EKSTERNAL') ? 'Mitra' : 'Internal';
                                                $status = ($row['status'] == '1') ? 'Diterima' : 'Pending';
                                                $status_class = ($row['status'] == '1') ? 'text-success' : 'text-warning';
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no; ?></td>
                                            <td><?php echo $row['no_order']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                            <td><?php echo $row['cabang_asal_nama']; ?></td>
                                            <td><?php echo $tipe; ?></td>
                                            <td class="center"><?php echo number_format($row['total_qty'], 0); ?></td>
                                            <td class="right"><?php echo number_format($row['total_order'], 0, ',', '.'); ?></td>
                                            <td class="center <?php echo $status_class; ?>"><strong><?php echo $status; ?></strong></td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="8" class="center">Tidak ada data.</td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="warning">
                                            <th colspan="5" class="right">TOTAL:</th>
                                            <th class="center"><?php echo number_format($grand_qty, 0); ?></th>
                                            <th class="right"><?php echo number_format($grand_total, 0, ',', '.'); ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="footer no-print">
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
