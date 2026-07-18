<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    include "../config/koneksi.php";
    include_once "../lib/rbac.php";
    rbac_require_any(array('servis_menu_read', 'service_read'));

    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'] ?? '';
    $foto_user = $tm_cari['foto_user'] ?? '';
    if ($foto_user == '') { $foto_user = "file_upload/avatar.png"; }

    // F2-A: Laporan DP — masuk dan offset/batal tampil 2 baris terpisah (Q9, jawaban 2026-07-04)
    include_once "includes/report-default-range.php";
    $_default_range = app_report_default_range($koneksi, 'tb_dp_servis', 'tanggal_dp');
    $tgl_dari = $_GET['tgl_dari'] ?? $_default_range['from_ymd'];
    $tgl_sampai = $_GET['tgl_sampai'] ?? $_default_range['to_ymd'];
    $td = mysqli_real_escape_string($koneksi, $tgl_dari);
    $ts = mysqli_real_escape_string($koneksi, $tgl_sampai);

    $is_admin_dp = rbac_has('all');
    $svc_cabang_cond_dp = $is_admin_dp ? '' : " AND s.kd_cabang = '$kd_cabang'";
    $where_cabang_dp = $is_admin_dp ? '' : " AND EXISTS (SELECT 1 FROM tblservice sx WHERE sx.no_service = dp.no_service AND sx.kd_cabang = '$kd_cabang')";
    $q = mysqli_query($koneksi, "SELECT dp.*, s.no_pelanggan, p.namapelanggan
        FROM tb_dp_servis dp
        LEFT JOIN tblservice s ON dp.no_service = s.no_service$svc_cabang_cond_dp
        LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
        WHERE dp.tanggal_dp BETWEEN '$td' AND '$ts'$where_cabang_dp
        ORDER BY dp.tanggal_dp DESC, dp.id DESC");

    $baris = [];
    $total_masuk = 0;
    $total_keluar = 0;
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $baris[] = [
                'tanggal' => $r['tanggal_dp'],
                'no_dp' => $r['no_dp'],
                'no_service' => $r['no_service'],
                'pelanggan' => $r['namapelanggan'] ?? '-',
                'jenis' => 'DP Masuk',
                'jumlah' => (float)$r['jumlah_dp'],
            ];
            $total_masuk += (float)$r['jumlah_dp'];
            if ($r['status'] === 'offset') {
                $baris[] = [
                    'tanggal' => $r['tanggal_offset'],
                    'no_dp' => $r['no_dp'],
                    'no_service' => $r['no_service'],
                    'pelanggan' => $r['namapelanggan'] ?? '-',
                    'jenis' => 'DP Offset (dipakai pelunasan)',
                    'jumlah' => -(float)$r['jumlah_dp'],
                ];
                $total_keluar += (float)$r['jumlah_dp'];
            } elseif ($r['status'] === 'batal') {
                $baris[] = [
                    'tanggal' => $r['tanggal_offset'],
                    'no_dp' => $r['no_dp'],
                    'no_service' => $r['no_service'],
                    'pelanggan' => $r['namapelanggan'] ?? '-',
                    'jenis' => 'DP Dikembalikan (batal)',
                    'jumlah' => -(float)$r['jumlah_dp'],
                ];
                $total_keluar += (float)$r['jumlah_dp'];
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Laporan DP Servis">

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css">
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css">
    <link rel="stylesheet" href="assets/css/ace.min.css" id="main-ace-style">
    <link rel="stylesheet" href="assets/css/ace-skins.min.css">
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css">
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
                    <small><?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>
            <div class="navbar-buttons navbar-header pull-right">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profile">
                            <span class="user-info"><small>Welcome,</small> <?php echo htmlspecialchars($_nama); ?></span>
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
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                        <li class="active">Laporan DP Servis</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Laporan DP Servis
                            <small><i class="ace-icon fa fa-angle-double-right"></i> DP masuk &amp; offset tampil terpisah (F2-A)</small>
                        </h1>
                    </div>

                    <form method="get" class="form-inline" style="margin-bottom:12px;">
                        <label>Dari: <input type="date" name="tgl_dari" value="<?php echo htmlspecialchars($tgl_dari); ?>" class="form-control input-sm"></label>
                        &nbsp;
                        <label>Sampai: <input type="date" name="tgl_sampai" value="<?php echo htmlspecialchars($tgl_sampai); ?>" class="form-control input-sm"></label>
                        &nbsp;
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter"></i> Filter</button>
                    </form>

                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-xs-6">
                            <div class="alert alert-success">Total DP Masuk: <b>Rp <?php echo number_format($total_masuk,0,',','.'); ?></b></div>
                        </div>
                        <div class="col-xs-6">
                            <div class="alert alert-warning">Total DP Keluar (Offset/Batal): <b>Rp <?php echo number_format($total_keluar,0,',','.'); ?></b></div>
                        </div>
                    </div>

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. DP</th>
                                <th>No. Service</th>
                                <th>Pelanggan</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($baris)): ?>
                                <?php foreach ($baris as $b): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($b['tanggal'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($b['no_dp']); ?></td>
                                    <td><?php echo htmlspecialchars($b['no_service']); ?></td>
                                    <td><?php echo htmlspecialchars($b['pelanggan']); ?></td>
                                    <td><?php echo htmlspecialchars($b['jenis']); ?></td>
                                    <td style="color:<?php echo $b['jumlah'] < 0 ? '#c0392b' : '#27ae60'; ?>">
                                        <?php echo ($b['jumlah'] < 0 ? '-' : ''); ?>Rp <?php echo number_format(abs($b['jumlah']),0,',','.'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">Tidak ada data DP pada rentang tanggal ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
<?php } ?>
