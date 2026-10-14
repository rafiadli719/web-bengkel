<?php
// Laporan selisih stok opname (G14): item masuk = selisih lebih, item keluar = selisih kurang.
// Sumber: transaksi Penyesuaian Stok Otomatis (header note 'Penyesuaian stok opname').
session_start();
if (empty($_SESSION['_iduser'])) {
    header('location:../index.php');
    exit;
}
$id_user = (int) $_SESSION['_iduser'];
$kd_cabang = (string) ($_SESSION['_cabang'] ?? '');
include "../config/koneksi.php";
require_once __DIR__ . '/_include_menu_rbac.php';
requirePermission($koneksi, $id_user, 'lap_stok_masuk_read');
$izin_user = getUserPermissions($koneksi, $id_user);
$lihat_semua = hasRbacPermission($izin_user, 'inv_view_all');

$stmt = mysqli_prepare($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$_nama = $u['nama_user'] ?? '';
$foto_user = ($u['foto_user'] ?? '') ?: 'file_upload/avatar.png';

date_default_timezone_set('Asia/Jakarta');
function so_tgl_ymd($dmy, $fallback)
{
    if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', (string) $dmy, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
        return "$m[3]-$m[2]-$m[1]";
    }
    return $fallback;
}

$dari_ymd = so_tgl_ymd($_GET['dari'] ?? '', date('Y-m-01'));
$sampai_ymd = so_tgl_ymd($_GET['sampai'] ?? '', date('Y-m-d'));
$jenis = in_array($_GET['jenis'] ?? '', ['lebih', 'kurang'], true) ? $_GET['jenis'] : '';
$cab_pilih = $lihat_semua ? trim((string) ($_GET['cabang'] ?? '')) : $kd_cabang;

$cabangList = [];
if ($lihat_semua) {
    $r = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY kode_cabang");
    while ($x = mysqli_fetch_assoc($r)) {
        $cabangList[$x['kode_cabang']] = $x['nama_cabang'];
    }
}

$bagian = [];
$param = [];
$tipeParam = '';
foreach ([['lebih', 'tbitem_masuk_header', 'tbitem_masuk_detail', 'Lebih'], ['kurang', 'tbitem_keluar_header', 'tbitem_keluar_detail', 'Kurang']] as $b) {
    if ($jenis !== '' && $jenis !== $b[0]) {
        continue;
    }
    $sql = "SELECT h.tanggal, h.no_transaksi, '{$b[3]}' AS jenis, h.kd_cabang, i.rakbarang, d.no_item, i.namaitem,
                   d.stok_sistem, d.quantity AS selisih, h.user
            FROM {$b[1]} h
            JOIN {$b[2]} d ON d.no_transaksi = h.no_transaksi AND d.kd_cabang = h.kd_cabang
            LEFT JOIN tblitem i ON i.noitem = d.no_item
            WHERE h.note LIKE 'Penyesuaian stok opname%' AND h.tanggal >= ? AND h.tanggal <= ?";
    $param[] = $dari_ymd;
    $param[] = $sampai_ymd;
    $tipeParam .= 'ss';
    if ($cab_pilih !== '') {
        $sql .= " AND h.kd_cabang = ?";
        $param[] = $cab_pilih;
        $tipeParam .= 's';
    }
    $bagian[] = $sql;
}
$rows = [];
$totLebih = 0;
$totKurang = 0;
if ($bagian) {
    $st = mysqli_prepare($koneksi, implode(' UNION ALL ', $bagian) . " ORDER BY tanggal, no_transaksi, rakbarang, namaitem");
    mysqli_stmt_bind_param($st, $tipeParam, ...$param);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($x = mysqli_fetch_assoc($res)) {
        $x['fisik'] = (int) $x['stok_sistem'] + ($x['jenis'] === 'Lebih' ? 1 : -1) * (int) $x['selisih'];
        if ($x['jenis'] === 'Lebih') {
            $totLebih += (int) $x['selisih'];
        } else {
            $totKurang += (int) $x['selisih'];
        }
        $rows[] = $x;
    }
}

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="selisih_opname_' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['tanggal', 'no_transaksi', 'jenis', 'cabang', 'rak', 'no_item', 'nama', 'stok_sistem', 'selisih', 'stok_fisik', 'user']);
    foreach ($rows as $r) {
        fputcsv($o, [$r['tanggal'], $r['no_transaksi'], $r['jenis'], $r['kd_cabang'], $r['rakbarang'], $r['no_item'], $r['namaitem'], $r['stok_sistem'], $r['selisih'], $r['fisik'], $r['user']]);
    }
    fclose($o);
    exit;
}

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
$dariDmy = date('d/m/Y', strtotime($dari_ymd));
$sampaiDmy = date('d/m/Y', strtotime($sampai_ymd));
?>
<!DOCTYPE html>
<html lang="id">
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
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
<div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
        <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
            <span class="sr-only">Toggle sidebar</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
        </button>
        <div class="navbar-header pull-left">
            <a href="index.php" class="navbar-brand"><small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small></a>
        </div>
        <div class="navbar-buttons navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">
                <li class="light-blue dropdown-modal">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <img class="nav-user-photo" src="../<?= e($foto_user) ?>" alt="User Profil" />
                        <span class="user-info"><small>Welcome,</small> <?= e($_nama) ?></span>
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
            <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
        </div>
    </div>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li><i class="ace-icon fa fa-home home-icon"></i> <a href="index.php">Home</a></li>
                    <li><a href="#">Laporan</a></li>
                    <li class="active">Selisih Stok Opname</li>
                </ul>
            </div>
            <div class="page-content">
                <div class="page-header"><h1>Laporan Selisih Stok Opname <small><i class="ace-icon fa fa-angle-double-right"></i> lebih = item masuk, kurang = item keluar</small></h1></div>

                <form method="get" class="form-inline" style="margin-bottom:15px">
                    <input class="form-control date-picker" name="dari" value="<?= e($dariDmy) ?>" data-date-format="dd/mm/yyyy" autocomplete="off" style="width:120px"> s/d
                    <input class="form-control date-picker" name="sampai" value="<?= e($sampaiDmy) ?>" data-date-format="dd/mm/yyyy" autocomplete="off" style="width:120px">
                    <select name="jenis" class="form-control">
                        <option value="">Lebih dan kurang</option>
                        <option value="lebih" <?= $jenis === 'lebih' ? 'selected' : '' ?>>Selisih lebih (masuk)</option>
                        <option value="kurang" <?= $jenis === 'kurang' ? 'selected' : '' ?>>Selisih kurang (keluar)</option>
                    </select>
                    <?php if ($lihat_semua): ?>
                    <select name="cabang" class="form-control">
                        <option value="">Semua cabang</option>
                        <?php foreach ($cabangList as $kd => $nm): ?><option value="<?= e($kd) ?>" <?= $kd === $cab_pilih ? 'selected' : '' ?>><?= e($kd) ?> - <?= e($nm) ?></option><?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="submit">Tampilkan</button>
                    <button class="btn btn-default" type="submit" name="csv" value="1"><i class="fa fa-download"></i> CSV</button>
                </form>

                <div class="alert alert-info">
                    Total selisih lebih: <strong><?= (int) $totLebih ?></strong> unit ·
                    total selisih kurang: <strong><?= (int) $totKurang ?></strong> unit ·
                    baris: <strong><?= count($rows) ?></strong>
                </div>

                <table class="table table-bordered table-striped table-condensed">
                    <thead><tr><th>Tanggal</th><th>No. Transaksi</th><th>Jenis</th><th>Cabang</th><th>Rak</th><th>No. Item</th><th>Nama</th>
                        <th class="text-right">Sistem</th><th class="text-right">Selisih</th><th class="text-right">Fisik</th><th>User</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($r['tanggal']))) ?></td>
                            <td><?= e($r['no_transaksi']) ?></td>
                            <td><span class="label label-<?= $r['jenis'] === 'Lebih' ? 'success' : 'danger' ?>"><?= e($r['jenis']) ?></span></td>
                            <td><?= e($r['kd_cabang']) ?></td>
                            <td><?= e($r['rakbarang']) ?></td>
                            <td><?= e($r['no_item']) ?></td>
                            <td><?= e($r['namaitem']) ?></td>
                            <td class="text-right"><?= (int) $r['stok_sistem'] ?></td>
                            <td class="text-right"><?= ($r['jenis'] === 'Lebih' ? '+' : '-') . (int) $r['selisih'] ?></td>
                            <td class="text-right"><?= (int) $r['fisik'] ?></td>
                            <td><?= e($r['user']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="11" class="text-center text-muted">Tidak ada penyesuaian stok opname pada rentang ini.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer"><div class="footer-inner"><div class="footer-content"><?php include "../lib/footer.php"; ?></div></div></div>
    <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"><i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i></a>
</div>

<script src="assets/js/jquery-2.1.4.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/bootstrap-datepicker.min.js"></script>
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>
<script>jQuery(function($){ $('.date-picker').datepicker({autoclose:true, todayHighlight:true}); });</script>
</body>
</html>
