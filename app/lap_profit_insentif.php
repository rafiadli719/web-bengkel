<?php
/**
 * lap_profit_insentif.php
 * Laporan Profit & Insentif dari tbinsentif_jual_servis (data Access)
 */
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}
$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$cari_kd   = mysqli_query($koneksi, "SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
$tm_cari   = mysqli_fetch_array($cari_kd);
$_nama     = $tm_cari['nama_user'];
$foto_user = $tm_cari['foto_user'] ?: 'file_upload/avatar.png';

$cari_kd     = mysqli_query($koneksi, "SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$tm_cari     = mysqli_fetch_array($cari_kd);
$nama_cabang = $tm_cari['nama_cabang'];

date_default_timezone_set('Asia/Jakarta');

// Daftar siklus yang tersedia di tabel
$res_siklus = mysqli_query($koneksi,
    "SELECT DISTINCT siklus FROM tbinsentif_jual_servis WHERE siklus != '' ORDER BY siklus DESC LIMIT 36"
);
$list_siklus = [];
if ($res_siklus) {
    while ($r = mysqli_fetch_assoc($res_siklus)) $list_siklus[] = $r['siklus'];
}

// Daftar cabang (kolom sts = nama cabang dari Access)
$res_cab = mysqli_query($koneksi,
    "SELECT DISTINCT sts FROM tbinsentif_jual_servis WHERE sts != '' ORDER BY sts"
);
$list_sts = [];
if ($res_cab) {
    while ($r = mysqli_fetch_assoc($res_cab)) $list_sts[] = $r['sts'];
}

// Filter dari form
$f_siklus_dari   = $_POST['siklus_dari']   ?? (isset($list_siklus[5]) ? $list_siklus[5] : ($list_siklus[0] ?? date('Y-m')));
$f_siklus_sampai = $_POST['siklus_sampai'] ?? ($list_siklus[0] ?? date('Y-m'));
$f_tipe          = $_POST['tipe']          ?? '';
$f_sts           = $_POST['sts']           ?? '';
$f_mekanik       = mysqli_real_escape_string($koneksi, trim($_POST['mekanik'] ?? ''));
$do_search       = isset($_POST['btnrst']);

$rows_siklus  = [];
$rows_mekanik = [];
$rows_tipe    = [];
$grand = ['jual' => 0, 'hpp' => 0, 'laba' => 0, 'margin' => 0];

if ($do_search) {
    $where = "siklus >= '" . mysqli_real_escape_string($koneksi, $f_siklus_dari)   . "'"
           . " AND siklus <= '" . mysqli_real_escape_string($koneksi, $f_siklus_sampai) . "'";
    if ($f_tipe  !== '') $where .= " AND tipe = '"    . mysqli_real_escape_string($koneksi, $f_tipe)  . "'";
    if ($f_sts   !== '') $where .= " AND sts  = '"    . mysqli_real_escape_string($koneksi, $f_sts)   . "'";
    if ($f_mekanik !== '') $where .= " AND mekanik LIKE '%" . $f_mekanik . "%'";

    // Per siklus
    $res = mysqli_query($koneksi,
        "SELECT siklus,
                SUM(jualnet) AS total_jual,
                SUM(tothpp)  AS total_hpp,
                SUM(laba)    AS total_laba,
                COUNT(*)     AS total_baris
         FROM tbinsentif_jual_servis
         WHERE $where GROUP BY siklus ORDER BY siklus ASC"
    );
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $r['margin']  = $r['total_jual'] > 0 ? round($r['total_laba'] / $r['total_jual'] * 100, 1) : 0;
            $rows_siklus[] = $r;
            $grand['jual'] += $r['total_jual'];
            $grand['hpp']  += $r['total_hpp'];
            $grand['laba'] += $r['total_laba'];
        }
    }
    $grand['margin'] = $grand['jual'] > 0 ? round($grand['laba'] / $grand['jual'] * 100, 1) : 0;

    // Per mekanik (top 20 by laba)
    $res = mysqli_query($koneksi,
        "SELECT mekanik,
                SUM(jualnet) AS total_jual,
                SUM(tothpp)  AS total_hpp,
                SUM(laba)    AS total_laba,
                COUNT(DISTINCT trx) AS total_trx
         FROM tbinsentif_jual_servis
         WHERE $where GROUP BY mekanik ORDER BY total_laba DESC LIMIT 20"
    );
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $r['margin'] = $r['total_jual'] > 0 ? round($r['total_laba'] / $r['total_jual'] * 100, 1) : 0;
            $rows_mekanik[] = $r;
        }
    }

    // Per tipe + kategori
    $res = mysqli_query($koneksi,
        "SELECT tipe, kategori,
                SUM(jualnet) AS total_jual,
                SUM(tothpp)  AS total_hpp,
                SUM(laba)    AS total_laba,
                COUNT(*)     AS total_baris
         FROM tbinsentif_jual_servis
         WHERE $where GROUP BY tipe, kategori ORDER BY tipe, kategori"
    );
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $r['margin'] = $r['total_jual'] > 0 ? round($r['total_laba'] / $r['total_jual'] * 100, 1) : 0;
            $rows_tipe[] = $r;
        }
    }
}

function fmt($n)    { return number_format((float)$n, 0, ',', '.'); }
function fmtPct($n) { return number_format((float)$n, 1, ',', '.') . '%'; }
function clsLaba($n){ return ((float)$n >= 0) ? 'text-success' : 'text-danger'; }
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
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
    <style>
        .stat-card { background:#fff; border:1px solid #ddd; border-radius:6px; padding:15px; text-align:center; }
        .stat-card .val { font-size:22px; font-weight:bold; margin:5px 0; }
        .stat-card .lbl { color:#888; font-size:12px; }
        .tbl-compact td, .tbl-compact th { padding:5px 8px; font-size:12px; }
        .no-data { text-align:center; color:#aaa; padding:30px; font-style:italic; }
    </style>
</head>
<body class="no-skin">
    <?php include "_include_navbar.php"; ?>
    <div class="main-container ace-save-state" id="main-container">
        <script>try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script>try{ace.settings.loadState('sidebar')}catch(e){}</script>
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
                        <li><a href="lap_profit_penjualan.php">Laporan Profit</a></li>
                        <li class="active">Profit Insentif (Access)</li>
                    </ul>
                </div>
                <div class="page-content">
                    <div class="page-header">
                        <h1>Laporan Profit &amp; Insentif
                            <small>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                Data Access (INSENTIF_JUAL_SERVIS) — <?php echo htmlspecialchars($nama_cabang); ?>
                            </small>
                        </h1>
                    </div>

                    <?php if (empty($list_siklus)): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        Tabel <code>tbinsentif_jual_servis</code> masih kosong.
                        Jalankan dulu migrasi: <code>python "E:\BENGKEL 2.0\migrate_insentif.py"</code>
                        atau upload via <a href="access-sync.php">Access Sync</a>.
                    </div>
                    <?php endif; ?>

                    <!-- Filter -->
                    <div class="widget-box" style="margin-bottom:15px;">
                        <div class="widget-header widget-header-blue">
                            <h4 class="widget-title"><i class="fa fa-filter"></i> Filter Laporan</h4>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <form method="POST" class="form-inline">
                                    <div class="form-group" style="margin-right:10px;">
                                        <label>Siklus Dari&nbsp;</label>
                                        <select name="siklus_dari" class="form-control input-sm" style="width:130px;">
                                            <?php foreach ($list_siklus as $sk): ?>
                                            <option value="<?php echo $sk; ?>" <?php echo ($sk === $f_siklus_dari) ? 'selected' : ''; ?>>
                                                <?php echo $sk; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-right:10px;">
                                        <label>s/d&nbsp;</label>
                                        <select name="siklus_sampai" class="form-control input-sm" style="width:130px;">
                                            <?php foreach ($list_siklus as $sk): ?>
                                            <option value="<?php echo $sk; ?>" <?php echo ($sk === $f_siklus_sampai) ? 'selected' : ''; ?>>
                                                <?php echo $sk; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-right:10px;">
                                        <label>Tipe&nbsp;</label>
                                        <select name="tipe" class="form-control input-sm">
                                            <option value="">Semua</option>
                                            <option value="PENJUALAN" <?php echo ($f_tipe === 'PENJUALAN') ? 'selected' : ''; ?>>Penjualan</option>
                                            <option value="SERVIS"    <?php echo ($f_tipe === 'SERVIS')    ? 'selected' : ''; ?>>Servis</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-right:10px;">
                                        <label>Cabang&nbsp;</label>
                                        <select name="sts" class="form-control input-sm">
                                            <option value="">Semua</option>
                                            <?php foreach ($list_sts as $s): ?>
                                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($s === $f_sts) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-right:10px;">
                                        <label>Mekanik&nbsp;</label>
                                        <input type="text" name="mekanik" class="form-control input-sm"
                                            value="<?php echo htmlspecialchars($f_mekanik); ?>"
                                            placeholder="Nama mekanik" style="width:130px;">
                                    </div>
                                    <button type="submit" name="btnrst" class="btn btn-primary btn-sm">
                                        <i class="fa fa-search"></i> Tampilkan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($do_search): ?>

                    <!-- Grand Total -->
                    <div class="row" style="margin-bottom:15px;">
                        <div class="col-sm-3">
                            <div class="stat-card">
                                <div class="lbl">Total Penjualan</div>
                                <div class="val text-primary">Rp <?php echo fmt($grand['jual']); ?></div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="stat-card">
                                <div class="lbl">Total HPP</div>
                                <div class="val text-warning">Rp <?php echo fmt($grand['hpp']); ?></div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="stat-card">
                                <div class="lbl">Total Laba</div>
                                <div class="val <?php echo clsLaba($grand['laba']); ?>">
                                    Rp <?php echo fmt($grand['laba']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="stat-card">
                                <div class="lbl">Margin Rata-rata</div>
                                <div class="val <?php echo clsLaba($grand['laba']); ?>">
                                    <?php echo fmtPct($grand['margin']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Per Siklus -->
                        <div class="col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-calendar"></i> Per Siklus (Bulanan)</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <?php if (empty($rows_siklus)): ?>
                                        <div class="no-data">Tidak ada data untuk filter ini.</div>
                                        <?php else: ?>
                                        <table class="table table-striped table-bordered tbl-compact">
                                            <thead><tr>
                                                <th>Siklus</th>
                                                <th align="right">Jual</th>
                                                <th align="right">HPP</th>
                                                <th align="right">Laba</th>
                                                <th class="center">Margin</th>
                                            </tr></thead>
                                            <tbody>
                                            <?php foreach ($rows_siklus as $r): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['siklus']); ?></td>
                                                <td align="right"><?php echo fmt($r['total_jual']); ?></td>
                                                <td align="right"><?php echo fmt($r['total_hpp']); ?></td>
                                                <td align="right" class="<?php echo clsLaba($r['total_laba']); ?>">
                                                    <strong><?php echo fmt($r['total_laba']); ?></strong>
                                                </td>
                                                <td class="center <?php echo clsLaba($r['total_laba']); ?>">
                                                    <?php echo fmtPct($r['margin']); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                            <tfoot><tr style="background:#f5f5f5;">
                                                <th>Total</th>
                                                <th align="right"><?php echo fmt($grand['jual']); ?></th>
                                                <th align="right"><?php echo fmt($grand['hpp']); ?></th>
                                                <th align="right" class="<?php echo clsLaba($grand['laba']); ?>">
                                                    <strong><?php echo fmt($grand['laba']); ?></strong>
                                                </th>
                                                <th class="center <?php echo clsLaba($grand['laba']); ?>">
                                                    <?php echo fmtPct($grand['margin']); ?>
                                                </th>
                                            </tr></tfoot>
                                        </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Per Tipe + Kategori -->
                        <div class="col-sm-6">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-pie-chart"></i> Per Tipe &amp; Kategori</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <?php if (empty($rows_tipe)): ?>
                                        <div class="no-data">Tidak ada data.</div>
                                        <?php else: ?>
                                        <table class="table table-striped table-bordered tbl-compact">
                                            <thead><tr>
                                                <th>Tipe</th>
                                                <th>Kategori</th>
                                                <th align="right">Jual</th>
                                                <th align="right">Laba</th>
                                                <th class="center">Margin</th>
                                            </tr></thead>
                                            <tbody>
                                            <?php foreach ($rows_tipe as $r): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['tipe']); ?></td>
                                                <td><?php echo htmlspecialchars($r['kategori']); ?></td>
                                                <td align="right"><?php echo fmt($r['total_jual']); ?></td>
                                                <td align="right" class="<?php echo clsLaba($r['total_laba']); ?>">
                                                    <strong><?php echo fmt($r['total_laba']); ?></strong>
                                                </td>
                                                <td class="center <?php echo clsLaba($r['total_laba']); ?>">
                                                    <?php echo fmtPct($r['margin']); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Per Mekanik -->
                    <div class="widget-box">
                        <div class="widget-header">
                            <h4 class="widget-title"><i class="fa fa-user"></i> Top 20 Mekanik / Advisor (by Laba)</h4>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main no-padding">
                                <?php if (empty($rows_mekanik)): ?>
                                <div class="no-data">Tidak ada data.</div>
                                <?php else: ?>
                                <table class="table table-striped table-bordered tbl-compact">
                                    <thead><tr>
                                        <th>#</th>
                                        <th>Mekanik</th>
                                        <th align="right">Trx</th>
                                        <th align="right">Total Jual</th>
                                        <th align="right">Total HPP</th>
                                        <th align="right">Total Laba</th>
                                        <th class="center">Margin</th>
                                    </tr></thead>
                                    <tbody>
                                    <?php foreach ($rows_mekanik as $i => $r): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($r['mekanik']); ?></strong></td>
                                        <td align="right"><?php echo fmt($r['total_trx']); ?></td>
                                        <td align="right"><?php echo fmt($r['total_jual']); ?></td>
                                        <td align="right"><?php echo fmt($r['total_hpp']); ?></td>
                                        <td align="right" class="<?php echo clsLaba($r['total_laba']); ?>">
                                            <strong><?php echo fmt($r['total_laba']); ?></strong>
                                        </td>
                                        <td class="center <?php echo clsLaba($r['total_laba']); ?>">
                                            <?php echo fmtPct($r['margin']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php endif; // $do_search ?>

                </div><!-- /.page-content -->
            </div>
        </div><!-- /.main-content -->
        <div class="footer">
            <div class="footer-inner"><div class="footer-content">
                <?php include "../lib/footer.php"; ?>
            </div></div>
        </div>
    </div><!-- /.main-container -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
