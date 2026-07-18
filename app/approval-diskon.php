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
    rbac_require_any(array('diskon_approval_approve'));

    $cari_kd = mysqli_query($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $_nama = $tm_cari['nama_user'] ?? '';
    $foto_user = $tm_cari['foto_user'] ?? '';
    if ($foto_user == '') { $foto_user = "file_upload/avatar.png"; }

    $msg = '';

    // Handler approve/reject
    if (isset($_POST['btnaction'])) {
        $id_req = (int)($_POST['id_req'] ?? 0);
        $action = $_POST['action'] ?? '';
        $alasan = mysqli_real_escape_string($koneksi, trim($_POST['alasan'] ?? ''));

        $chk = mysqli_query($koneksi, "SELECT status FROM tb_approval_diskon WHERE id=$id_req LIMIT 1");
        $row_chk = $chk ? mysqli_fetch_assoc($chk) : null;

        if (!$row_chk) {
            $msg = "Request tidak ditemukan.";
        } elseif ($row_chk['status'] !== 'pending') {
            $msg = "Request ini sudah diproses sebelumnya (status: " . $row_chk['status'] . ").";
        } elseif ($action === 'approve') {
            mysqli_query($koneksi, "UPDATE tb_approval_diskon SET status='approved', id_user_supervisor='$id_user', tanggal_approval=NOW(), alasan=CONCAT(COALESCE(alasan,''), ' | Approved: $alasan') WHERE id=$id_req");
            $msg = "Diskon disetujui. Kasir bisa lanjut proses pembayaran.";
        } elseif ($action === 'reject') {
            mysqli_query($koneksi, "UPDATE tb_approval_diskon SET status='rejected', id_user_supervisor='$id_user', tanggal_approval=NOW(), alasan=CONCAT(COALESCE(alasan,''), ' | Rejected: $alasan') WHERE id=$id_req");
            $msg = "Diskon ditolak.";
        }
    }

    $q_pending = mysqli_query($koneksi, "SELECT ad.*, s.no_pelanggan, p.namapelanggan, u.nama_user AS nama_cs
        FROM tb_approval_diskon ad
        LEFT JOIN tblservice s ON ad.no_service = s.no_service AND ad.kd_cabang = s.kd_cabang
        LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
        LEFT JOIN tbuser u ON ad.id_user_cs = u.id
        WHERE ad.status = 'pending'
        ORDER BY ad.tanggal_request ASC");

    $q_riwayat = mysqli_query($koneksi, "SELECT ad.*, s.no_pelanggan, p.namapelanggan, u.nama_user AS nama_cs
        FROM tb_approval_diskon ad
        LEFT JOIN tblservice s ON ad.no_service = s.no_service AND ad.kd_cabang = s.kd_cabang
        LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
        LEFT JOIN tbuser u ON ad.id_user_cs = u.id
        WHERE ad.status != 'pending'
        ORDER BY ad.tanggal_approval DESC
        LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="description" content="Approval Diskon Manual">

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
                        <li class="active">Approval Diskon</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1>
                            Approval Diskon Manual
                            <small><i class="ace-icon fa fa-angle-double-right"></i> Diskon invoice di luar SOP (F2-C)</small>
                        </h1>
                    </div>

                    <?php if (!empty($msg)): ?>
                    <div class="alert alert-info"><i class="ace-icon fa fa-info-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xs-12">
                            <h4>Menunggu Approval</h4>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>No. Service</th>
                                        <th>Pelanggan</th>
                                        <th>CS/Kasir</th>
                                        <th>Persen</th>
                                        <th>Nominal</th>
                                        <th>Tgl Request</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($q_pending && mysqli_num_rows($q_pending) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($q_pending)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['no_service']); ?></td>
                                            <td><?php echo htmlspecialchars($r['namapelanggan'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($r['nama_cs'] ?? '-'); ?></td>
                                            <td><?php echo number_format((float)$r['persen_diskon'], 2); ?>%</td>
                                            <td>Rp <?php echo number_format((float)$r['nominal_diskon'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($r['tanggal_request']); ?></td>
                                            <td class="action-buttons">
                                                <form method="post" style="display:inline-block" onsubmit="return confirm('Setujui diskon ini?');">
                                                    <input type="hidden" name="id_req" value="<?php echo (int)$r['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="text" name="alasan" placeholder="Catatan (opsional)" class="form-control input-sm" style="display:inline-block;width:140px;">
                                                    <button type="submit" name="btnaction" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Setujui</button>
                                                </form>
                                                <form method="post" style="display:inline-block" onsubmit="return confirm('Tolak diskon ini?');">
                                                    <input type="hidden" name="id_req" value="<?php echo (int)$r['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" name="btnaction" class="btn btn-xs btn-danger"><i class="fa fa-times"></i> Tolak</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">Tidak ada request yang menunggu approval.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <h4>Riwayat (50 terakhir)</h4>
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>No. Service</th>
                                        <th>Pelanggan</th>
                                        <th>Persen</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                        <th>Diproses Oleh</th>
                                        <th>Tgl Approval</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($q_riwayat && mysqli_num_rows($q_riwayat) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($q_riwayat)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['no_service']); ?></td>
                                            <td><?php echo htmlspecialchars($r['namapelanggan'] ?? '-'); ?></td>
                                            <td><?php echo number_format((float)$r['persen_diskon'], 2); ?>%</td>
                                            <td>Rp <?php echo number_format((float)$r['nominal_diskon'], 0, ',', '.'); ?></td>
                                            <td><span class="label <?php echo $r['status']==='approved' ? 'label-success' : 'label-danger'; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars($r['id_user_supervisor'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($r['tanggal_approval'] ?? '-'); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">Belum ada riwayat.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
<?php } ?>
