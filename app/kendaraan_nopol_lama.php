<?php
// Catat nopol lama (plat sebelumnya) sebuah kendaraan ke kendaraan_plat_history. Nopol lama tidak boleh
// dipakai mendaftar kendaraan/pelanggan baru (lihat save_kendaraan.php), dan tampil di daftar di bawah.
session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
include "../config/koneksi.php";
require_once "_include_menu_rbac.php";
$id_user = (int)$_SESSION['_iduser'];
requirePermission($koneksi, $id_user, 'kendaraan_read');

$kd_cabang = $_SESSION['_cabang'];
$q = mysqli_query($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
$u = mysqli_fetch_array($q);
$_nama = $u['nama_user'] ?? '';
$foto_user = $u['foto_user'] ?: 'file_upload/avatar.png';

$pesan = ''; $tipe_pesan = 'success';
$nopol_aktif = trim($_POST['nopol_aktif'] ?? ($_GET['nopol'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nopol_lama = strtoupper(trim($_POST['nopol_lama'] ?? ''));
    $tgl_akhir  = trim($_POST['tanggal_akhir'] ?? '');
    $st = mysqli_prepare($koneksi, "SELECT id_kendaraan FROM tblkendaraan WHERE nopolisi = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $nopol_aktif);
    mysqli_stmt_execute($st);
    $k = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    if (!$k) {
        $pesan = 'Nopol aktif tidak ditemukan di data kendaraan.'; $tipe_pesan = 'danger';
    } elseif ($nopol_lama === '' || $nopol_lama === strtoupper($nopol_aktif)) {
        $pesan = 'Isi nopol lama yang berbeda dari nopol aktif.'; $tipe_pesan = 'danger';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_akhir)) {
        $pesan = 'Tanggal berhenti dipakai tidak valid.'; $tipe_pesan = 'danger';
    } else {
        $st2 = mysqli_prepare($koneksi, "SELECT id FROM kendaraan_plat_history WHERE nopolisi = ? LIMIT 1");
        mysqli_stmt_bind_param($st2, 's', $nopol_lama);
        mysqli_stmt_execute($st2);
        $ada_h = mysqli_fetch_assoc(mysqli_stmt_get_result($st2));
        $st3 = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ? LIMIT 1");
        mysqli_stmt_bind_param($st3, 's', $nopol_lama);
        mysqli_stmt_execute($st3);
        $ada_k = mysqli_fetch_assoc(mysqli_stmt_get_result($st3));
        if ($ada_h || $ada_k) {
            $pesan = 'Nopol lama itu sudah tercatat (sebagai kendaraan aktif atau riwayat).'; $tipe_pesan = 'danger';
        } else {
            $idk = (int)$k['id_kendaraan'];
            $alasan = 'ganti_plat';
            $ins = mysqli_prepare($koneksi, "INSERT INTO kendaraan_plat_history (id_kendaraan, nopolisi, tanggal_mulai, tanggal_akhir, is_current, alasan, diinput_oleh) VALUES (?, ?, ?, ?, 0, ?, ?)");
            $mulai = $tgl_akhir;
            mysqli_stmt_bind_param($ins, 'issssi', $idk, $nopol_lama, $mulai, $tgl_akhir, $alasan, $id_user);
            mysqli_stmt_execute($ins);
            $pesan = 'Nopol lama ' . htmlspecialchars($nopol_lama) . ' dicatat untuk kendaraan ' . htmlspecialchars($nopol_aktif) . '.';
        }
    }
}

$riwayat = [];
if ($nopol_aktif !== '') {
    $st4 = mysqli_prepare($koneksi, "SELECT h.nopolisi, h.tanggal_mulai, h.tanggal_akhir, h.is_current, h.alasan
                                     FROM kendaraan_plat_history h JOIN tblkendaraan k ON k.id_kendaraan = h.id_kendaraan
                                     WHERE k.nopolisi = ? ORDER BY h.is_current DESC, h.tanggal_akhir DESC");
    mysqli_stmt_bind_param($st4, 's', $nopol_aktif);
    mysqli_stmt_execute($st4);
    $rr = mysqli_stmt_get_result($st4);
    while ($x = mysqli_fetch_assoc($rr)) { $riwayat[] = $x; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <div class="navbar-header pull-left"><a href="index.php" class="navbar-brand"><small><?php include "../lib/logo.php"; ?> FIT MOTOR</small></a></div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation"><ul class="nav ace-nav">
            <li class="light-blue dropdown-modal"><a data-toggle="dropdown" href="#" class="dropdown-toggle">
                <img class="nav-user-photo" src="../<?php echo htmlspecialchars($foto_user); ?>" alt="" />
                <span class="user-info"><small>Welcome,</small> <?php echo htmlspecialchars($_nama); ?></span></a></li>
        </ul></div>
    </div>
</div>
<div class="main-container ace-save-state" id="main-container">
    <div id="sidebar" class="sidebar responsive ace-save-state">
        <?php include "menu_dashboard.php"; ?>
    </div>
    <div class="main-content"><div class="main-content-inner">
        <div class="breadcrumbs ace-save-state"><ul class="breadcrumb">
            <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
            <li><a href="kendaraan.php">Kendaraan</a></li><li class="active">Nopol Lama</li></ul></div>
        <div class="page-content">
            <div class="page-header"><h1>Catat Nopol Lama <small><i class="ace-icon fa fa-angle-double-right"></i> riwayat plat kendaraan</small></h1></div>
            <div class="row"><div class="col-xs-12 col-sm-7">
                <?php if ($pesan !== ''): ?><div class="alert alert-<?php echo $tipe_pesan; ?>"><?php echo $pesan; ?></div><?php endif; ?>
                <form method="post" class="form-horizontal">
                    <div class="form-group"><label class="col-sm-4 control-label" for="nopol_aktif">Nopol aktif</label>
                        <div class="col-sm-8"><input class="form-control" id="nopol_aktif" name="nopol_aktif" required
                             value="<?php echo htmlspecialchars($nopol_aktif); ?>" placeholder="mis. G 1234 AB" /></div></div>
                    <div class="form-group"><label class="col-sm-4 control-label" for="nopol_lama">Nopol lama</label>
                        <div class="col-sm-8"><input class="form-control" id="nopol_lama" name="nopol_lama" required placeholder="plat sebelumnya" /></div></div>
                    <div class="form-group"><label class="col-sm-4 control-label" for="tanggal_akhir">Berhenti dipakai</label>
                        <div class="col-sm-8"><input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo date('Y-m-d'); ?>" /></div></div>
                    <div class="col-sm-offset-4 col-sm-8">
                        <button class="btn btn-primary" type="submit"><i class="ace-icon fa fa-save"></i> Simpan nopol lama</button>
                        <a class="btn btn-default" href="kendaraan.php">Kembali</a>
                    </div>
                </form>
            </div></div>
            <?php if ($nopol_aktif !== ''): ?>
            <div class="space-12"></div>
            <h4>Riwayat nopol kendaraan <?php echo htmlspecialchars($nopol_aktif); ?></h4>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Nopol</th><th>Mulai</th><th>Berhenti</th><th>Status</th><th>Alasan</th></tr></thead>
                <tbody>
                <?php if (!$riwayat): ?><tr><td colspan="5" class="text-muted">Belum ada riwayat tercatat.</td></tr><?php endif; ?>
                <?php foreach ($riwayat as $h): ?>
                    <tr><td><?php echo htmlspecialchars($h['nopolisi']); ?></td><td><?php echo htmlspecialchars((string)$h['tanggal_mulai']); ?></td>
                        <td><?php echo htmlspecialchars((string)($h['tanggal_akhir'] ?: '-')); ?></td>
                        <td><?php echo $h['is_current'] ? '<span class="label label-success">Aktif</span>' : '<span class="label label-default">Lama</span>'; ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $h['alasan'])); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div></div>
</div>
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
</body>
</html>
