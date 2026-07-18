<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";
include_once "../lib/rbac.php";

error_reporting(E_ALL);
ini_set('display_errors', 0);
if(!isset($koneksi) || !$koneksi){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    die('DB connection failed: '.mysqli_connect_error());
}
@mysqli_set_charset($koneksi, 'utf8mb4');
if(function_exists('mysqli_report')){ @mysqli_report(MYSQLI_REPORT_OFF); }

$cari_kd = mysqli_query($koneksi, "SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi,$id_user)."'");
$tm_cari = $cari_kd ? mysqli_fetch_array($cari_kd) : null;
$_nama = $tm_cari && isset($tm_cari['nama_user']) ? $tm_cari['nama_user'] : 'admin';
$lvl_akses = $tm_cari && isset($tm_cari['user_akses']) ? $tm_cari['user_akses'] : '';
$foto_user = $tm_cari && isset($tm_cari['foto_user']) ? $tm_cari['foto_user'] : '';
if($foto_user=='') { $foto_user="file_upload/avatar.png"; }
$is_admin = ($lvl_akses == '1');

$can_merge_approve = $is_admin;
if(function_exists('rbac_has')){
    $can_merge_approve = $can_merge_approve || rbac_has('issue_approve_supervisor') || rbac_has('issue_approve_owner') || rbac_has('issue_merge_customer_approve');
}

if(!$can_merge_approve){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(403);
    die('Halaman ini khusus user yang punya hak menyetujui penggabungan pelanggan.');
}

$message = '';

function get_pelanggan_preview($koneksi, $nopelanggan){
    $esc = mysqli_real_escape_string($koneksi, $nopelanggan);
    $q = mysqli_query($koneksi, "SELECT nopelanggan, namapelanggan, no_wa, notlp FROM tblpelanggan WHERE nopelanggan='{$esc}'");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    $qc = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tblservice WHERE no_pelanggan='{$esc}'");
    $cnt = $qc ? (int)mysqli_fetch_assoc($qc)['c'] : 0;
    if($row){ $row['jumlah_servis'] = $cnt; }
    return $row;
}

// Approve & Eksekusi
if(isset($_POST['btnapprove']) && !empty($_POST['id'])){
    $id = (int)$_POST['id'];
    $qm = mysqli_query($koneksi, "SELECT * FROM customer_merge_log WHERE id={$id} AND status='diajukan'");
    $m = $qm ? mysqli_fetch_assoc($qm) : null;
    if(!$m){
        $message = 'Data merge tidak ditemukan atau sudah diproses.';
    } else {
        $source = $m['nopelanggan_source'];
        $target = $m['nopelanggan_target'];
        $confirm_target = isset($_POST['confirm_target']) ? trim((string)$_POST['confirm_target']) : '';
        if($confirm_target !== $target){
            $message = 'Konfirmasi gagal. Kode pelanggan yang dipertahankan tidak cocok.';
        } else {
        $source_esc = mysqli_real_escape_string($koneksi, $source);
        $target_esc = mysqli_real_escape_string($koneksi, $target);

        mysqli_begin_transaction($koneksi);
        try {
            $qs = mysqli_query($koneksi, "SELECT * FROM tblpelanggan WHERE nopelanggan IN ('{$source_esc}','{$target_esc}')");
            $snapshot = [];
            while($row = mysqli_fetch_assoc($qs)){ $snapshot[$row['nopelanggan']] = $row; }

            $stmt = mysqli_prepare($koneksi, "UPDATE customer_merge_log SET snapshot_before_json=?, status='disetujui', disetujui_oleh=? WHERE id=?");
            $snap_json = json_encode($snapshot);
            mysqli_stmt_bind_param($stmt, "sii", $snap_json, $id_user, $id);
            if(!mysqli_stmt_execute($stmt)){ throw new Exception('Gagal simpan snapshot'); }
            mysqli_stmt_close($stmt);

            if(!mysqli_query($koneksi, "UPDATE tblservice SET no_pelanggan='{$target_esc}' WHERE no_pelanggan='{$source_esc}'")){
                throw new Exception('Gagal re-point tblservice');
            }

            if(!mysqli_query($koneksi, "INSERT INTO customer_alias (nopelanggan_lama, nopelanggan_baru) VALUES ('{$source_esc}','{$target_esc}') ON DUPLICATE KEY UPDATE nopelanggan_baru='{$target_esc}'")){
                throw new Exception('Gagal insert customer_alias');
            }

            $tanda = " [MERGED ke {$target}]";
            if(!mysqli_query($koneksi, "UPDATE tblpelanggan SET note=CONCAT(note,'".mysqli_real_escape_string($koneksi,$tanda)."') WHERE nopelanggan='{$source_esc}'")){
                throw new Exception('Gagal tandai pelanggan source');
            }

            if(!mysqli_query($koneksi, "UPDATE customer_merge_log SET status='dieksekusi', executed_at=NOW() WHERE id={$id}")){
                throw new Exception('Gagal update status merge_log');
            }

            if(!empty($m['id_issue'])){
                $issue_esc = mysqli_real_escape_string($koneksi, $m['id_issue']);
                mysqli_query($koneksi, "UPDATE tbl_issue SET status='resolved', solusi='Merge dieksekusi: {$source_esc} -> {$target_esc}' WHERE id_issue='{$issue_esc}'");
                $stmt2 = mysqli_prepare($koneksi, "INSERT INTO tbl_issue_progress_log (id_issue, oleh, catatan, status_before, status_after) VALUES (?, ?, ?, 'waiting_approval', 'resolved')");
                $catatan2 = "Merge disetujui & dieksekusi: {$source} -> {$target}";
                mysqli_stmt_bind_param($stmt2, "sis", $m['id_issue'], $id_user, $catatan2);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            mysqli_commit($koneksi);
            $message = "Merge {$source} -> {$target} berhasil dieksekusi.";
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $message = 'Gagal eksekusi merge: '.$e->getMessage();
        }
        }
    }
}

// Tolak
if(isset($_POST['btnreject']) && !empty($_POST['id'])){
    $id = (int)$_POST['id'];
    mysqli_query($koneksi, "UPDATE customer_merge_log SET status='ditolak', disetujui_oleh={$id_user} WHERE id={$id} AND status='diajukan'");
    $qm = mysqli_query($koneksi, "SELECT id_issue, nopelanggan_source, nopelanggan_target FROM customer_merge_log WHERE id={$id}");
    $m = $qm ? mysqli_fetch_assoc($qm) : null;
    if($m && !empty($m['id_issue'])){
        $issue_esc = mysqli_real_escape_string($koneksi, $m['id_issue']);
        mysqli_query($koneksi, "UPDATE tbl_issue SET status='rejected' WHERE id_issue='{$issue_esc}'");
        $stmt2 = mysqli_prepare($koneksi, "INSERT INTO tbl_issue_progress_log (id_issue, oleh, catatan, status_before, status_after) VALUES (?, ?, 'Merge ditolak', 'waiting_approval', 'rejected')");
        mysqli_stmt_bind_param($stmt2, "si", $m['id_issue'], $id_user);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
    $message = 'Pengajuan merge ditolak.';
}

$pending = [];
$qp = mysqli_query($koneksi, "SELECT * FROM customer_merge_log WHERE status='diajukan' ORDER BY created_at ASC");
while($row = mysqli_fetch_assoc($qp)){
    $row['source_info'] = get_pelanggan_preview($koneksi, $row['nopelanggan_source']);
    $row['target_info'] = get_pelanggan_preview($koneksi, $row['nopelanggan_target']);
    $pending[] = $row;
}

$riwayat = [];
$qr = mysqli_query($koneksi, "SELECT * FROM customer_merge_log WHERE status IN ('dieksekusi','ditolak') ORDER BY created_at DESC LIMIT 50");
while($row = mysqli_fetch_assoc($qr)){ $riwayat[] = $row; }
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
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <script src="assets/js/ace-extra.min.js"></script>
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
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i><?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small><?php echo htmlspecialchars($_nama); ?></span>
                        <i class="ace-icon fa fa-caret-down"></i>
                    </a>
                    <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                        <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i>Change Password</a></li>
                        <li><a href="profile.php"><i class="ace-icon fa fa-user"></i>Profile</a></li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
        <?php include "menu_dashboard.php"; ?>
        <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>
    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                    <li class="active">Persetujuan Gabung Pelanggan</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="row">
                    <div class="col-xs-12">
                        <?php if($message!=''){ echo '<div class="alert alert-info">'.htmlspecialchars($message).'</div>'; } ?>
                        <div class="alert alert-warning">
                            <strong>Perhatian:</strong> halaman ini dipakai untuk menyetujui gabung pelanggan dobel. Akun sumber akan dilebur ke akun target, dan riwayat transaksi akan ikut pindah. Perubahan ini tidak punya tombol batal otomatis.
                        </div>
                        <div class="widget-box">
                            <div class="widget-header widget-header-blue widget-header-flat">
                                <h4 class="widget-title lighter"><i class="ace-icon fa fa-compress"></i> Pengajuan Gabung yang Menunggu Persetujuan (<?php echo count($pending); ?>)</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                <?php if(count($pending)==0){ ?>
                                    <div class="alert alert-success">Tidak ada pengajuan gabung pelanggan yang menunggu persetujuan.</div>
                                <?php } else { foreach($pending as $m){ ?>
                                    <div class="well">
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <strong>Akun Sumber / Akan Dilebur: <?php echo htmlspecialchars($m['nopelanggan_source']); ?></strong><br />
                                                <?php if($m['source_info']){ ?>
                                                Nama: <?php echo htmlspecialchars($m['source_info']['namapelanggan']); ?><br />
                                                No WA: <?php echo htmlspecialchars($m['source_info']['no_wa'] ?? '-'); ?><br />
                                                Jumlah Servis: <?php echo (int)$m['source_info']['jumlah_servis']; ?>
                                                <?php } else { echo '<span class="text-danger">Data tidak ditemukan</span>'; } ?>
                                            </div>
                                            <div class="col-sm-2 text-center" style="padding-top:20px;"><i class="fa fa-arrow-right fa-2x"></i></div>
                                            <div class="col-sm-5">
                                                <strong>Akun Target / Dipertahankan: <?php echo htmlspecialchars($m['nopelanggan_target']); ?></strong><br />
                                                <?php if($m['target_info']){ ?>
                                                Nama: <?php echo htmlspecialchars($m['target_info']['namapelanggan']); ?><br />
                                                No WA: <?php echo htmlspecialchars($m['target_info']['no_wa'] ?? '-'); ?><br />
                                                Jumlah Servis: <?php echo (int)$m['target_info']['jumlah_servis']; ?>
                                                <?php } else { echo '<span class="text-danger">Data tidak ditemukan</span>'; } ?>
                                            </div>
                                        </div>
                                        <hr style="margin:10px 0;" />
                                        <p><strong>Alasan:</strong> <?php echo nl2br(htmlspecialchars($m['alasan'])); ?></p>
                                        <?php if(!empty($m['id_issue'])){ ?>
                                        <p><small>Ref. Tiket: <a href="issue_add.php?id_issue=<?php echo urlencode($m['id_issue']); ?>"><?php echo htmlspecialchars($m['id_issue']); ?></a></small></p>
                                        <?php } ?>
                                        <form method="post" action="customer_merge_approve.php" style="display:inline;" onsubmit="return confirm('Yakin gabung pelanggan <?php echo htmlspecialchars($m['nopelanggan_source']); ?> ke <?php echo htmlspecialchars($m['nopelanggan_target']); ?>? Riwayat transaksi akan dipindahkan dan perubahan ini tidak bisa dibatalkan otomatis.');">
                                            <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>" />
                                            <div class="form-group" style="max-width:340px;margin-bottom:10px;">
                                                <label>Konfirmasi akun target</label>
                                                <input type="text" name="confirm_target" class="form-control" value="<?php echo htmlspecialchars($m['nopelanggan_target']); ?>" readonly required />
                                                <small class="text-muted">Kode target dikunci otomatis agar tidak perlu diketik ulang.</small>
                                            </div>
                                            <button type="submit" name="btnapprove" class="btn btn-success"><i class="fa fa-check"></i> Setujui & Gabungkan</button>
                                            <button type="submit" name="btnreject" class="btn btn-danger"><i class="fa fa-times"></i> Tolak Pengajuan</button>
                                        </form>
                                    </div>
                                <?php } } ?>
                                </div>
                            </div>
                        </div>

                        <div class="widget-box">
                            <div class="widget-header widget-header-blue widget-header-flat">
                                <h4 class="widget-title lighter"><i class="ace-icon fa fa-history"></i> Riwayat Pengajuan Gabung</h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main no-padding">
                                    <table class="table table-bordered table-striped">
                                        <thead><tr><th>Tanggal</th><th>Source</th><th>Target</th><th>Status</th><th>Dieksekusi</th></tr></thead>
                                        <tbody>
                                        <?php if(count($riwayat)>0){ foreach($riwayat as $r){ ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($r['nopelanggan_source']); ?></td>
                                                <td><?php echo htmlspecialchars($r['nopelanggan_target']); ?></td>
                                                <td><span class="label <?php echo $r['status']=='dieksekusi'?'label-success':'label-danger'; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                                <td><?php echo htmlspecialchars($r['executed_at'] ?? '-'); ?></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="5" class="text-center">Belum ada riwayat</td></tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
