<?php
session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
$id_user   = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";
include_once "../lib/rbac.php";
rbac_require_any(['laporan_menu_read', 'lap_servis_read']);
include "helper-functions.php";

include_once "includes/report-default-range.php";
$_default_range = app_report_default_range($koneksi, 'tblservice', 'tanggal', "status_servis='bayar'");
$tgl_dari   = $_POST['tgl_dari']   ?? $_default_range['from_ymd'];
$tgl_sampai = $_POST['tgl_sampai'] ?? $_default_range['to_ymd'];
$filter_mk  = $_POST['filter_mk']  ?? '';
$show       = true;

$tgl_dari_esc   = mysqli_real_escape_string($koneksi, $tgl_dari);
$tgl_sampai_esc = mysqli_real_escape_string($koneksi, $tgl_sampai);

// Daftar mekanik untuk filter — gabung tblmekanik (kode lama 001, MK001, dll.)
// dengan tbuser_karyawan (karyawan web baru) agar semua kode terakomodasi
$mk_list = [];
$q_mk = mysqli_query($koneksi, "SELECT nomekanik AS kode, nama AS nama_tampil FROM tblmekanik WHERE status='aktif' AND nama NOT IN ('-','')
    UNION ALL
    SELECT kode_karyawan AS kode, nama_lengkap AS nama_tampil FROM tbuser_karyawan WHERE kode_posisi IN ('MK','KM') AND nama_lengkap NOT IN ('-','') AND kode_karyawan NOT IN (SELECT nomekanik FROM tblmekanik)
    ORDER BY nama_tampil ASC");
while ($m = mysqli_fetch_assoc($q_mk)) $mk_list[] = $m;

// ── Data komisi ──────────────────────────────────────────────
// Formula audit Access T-05:
//   Komisi jasa   = subtotal_jasa × 20% / jumlah_mekanik_aktif
//   Komisi barang = laba_barang   × 5%  / jumlah_mekanik_aktif
//   Laba barang   = SUM(total_baris - qty × hargapokok)
$rows_komisi = [];
if ($show) {
    $where_mk = $filter_mk ? "AND mek.kode_mek = '".mysqli_real_escape_string($koneksi, $filter_mk)."'" : '';

    // Pecah 4 slot mekanik jadi baris (no_service, kode_mek) dulu,
    // lalu JOIN sekali ke tblservice dan subquery laba_barang — menghindari lb dihitung 4x.
    $sql = "
    SELECT
        mek.kode_mek,
        COUNT(DISTINCT mek.no_service)                                         AS jml_service,
        SUM(mek.subtotal_jasa * 0.20 / GREATEST(mek.jml_mk, 1))              AS komisi_jasa,
        SUM(mek.laba_barang   * 0.05 / GREATEST(mek.jml_mk, 1))              AS komisi_barang
    FROM (
        SELECT s.no_service, slot.kode_mek, s.subtotal_jasa,
            COALESCE(lb.laba_barang, 0) AS laba_barang,
            ((s.mekanik1 IS NOT NULL AND s.mekanik1<>'') + (s.mekanik2 IS NOT NULL AND s.mekanik2<>'') +
             (s.mekanik3 IS NOT NULL AND s.mekanik3<>'') + (s.mekanik4 IS NOT NULL AND s.mekanik4<>'')) AS jml_mk
        FROM tblservice s
        JOIN (
            SELECT no_service, mekanik1 AS kode_mek FROM tblservice
                WHERE tanggal BETWEEN '$tgl_dari_esc' AND '$tgl_sampai_esc'
                  AND status_servis = 'bayar' AND mekanik1 IS NOT NULL AND mekanik1 <> ''
            UNION ALL
            SELECT no_service, mekanik2 FROM tblservice
                WHERE tanggal BETWEEN '$tgl_dari_esc' AND '$tgl_sampai_esc'
                  AND status_servis = 'bayar' AND mekanik2 IS NOT NULL AND mekanik2 <> ''
            UNION ALL
            SELECT no_service, mekanik3 FROM tblservice
                WHERE tanggal BETWEEN '$tgl_dari_esc' AND '$tgl_sampai_esc'
                  AND status_servis = 'bayar' AND mekanik3 IS NOT NULL AND mekanik3 <> ''
            UNION ALL
            SELECT no_service, mekanik4 FROM tblservice
                WHERE tanggal BETWEEN '$tgl_dari_esc' AND '$tgl_sampai_esc'
                  AND status_servis = 'bayar' AND mekanik4 IS NOT NULL AND mekanik4 <> ''
        ) slot ON slot.no_service = s.no_service
        LEFT JOIN (
            SELECT sb.no_service,
                   SUM(GREATEST(sb.total - sb.quantity * COALESCE(ti.hargapokok,0), 0)) AS laba_barang
            FROM tblservis_barang sb
            LEFT JOIN tblitem ti ON ti.noitem = sb.no_item
            GROUP BY sb.no_service
        ) lb ON lb.no_service = s.no_service
        WHERE s.tanggal BETWEEN '$tgl_dari_esc' AND '$tgl_sampai_esc'
          AND s.status_servis = 'bayar'
    ) mek
    WHERE 1=1 $where_mk
    GROUP BY mek.kode_mek
    ORDER BY (komisi_jasa + komisi_barang) DESC
    ";

    $q = mysqli_query($koneksi, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $r['nama_mekanik'] = getMekanikNama($koneksi, $r['kode_mek']);
            $r['total_komisi'] = $r['komisi_jasa'] + $r['komisi_barang'];
            $rows_komisi[] = $r;
        }
    }
}

$grand_jasa   = array_sum(array_column($rows_komisi, 'komisi_jasa'));
$grand_barang = array_sum(array_column($rows_komisi, 'komisi_barang'));
$grand_total  = $grand_jasa + $grand_barang;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Komisi Mekanik</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
<style>
body { padding: 20px; font-size: 13px; }
.info-formula { background:#fff8e1; border-left:4px solid #ffc107; padding:10px 15px; margin-bottom:15px; font-size:12px; }
.tbl-komisi th { background:#2c3e50; color:#fff; }
.tbl-komisi tfoot td { background:#ecf0f1; font-weight:bold; }
.badge-jasa   { background:#27ae60; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
.badge-barang { background:#2980b9; color:#fff; padding:2px 7px; border-radius:3px; font-size:11px; }
@media print { .no-print { display:none; } }
</style>
</head>
<body>
<h4><i class="fa fa-money"></i> Laporan Komisi Mekanik</h4>

<div class="info-formula">
    <strong>Formula (referensi Access):</strong>&nbsp;
    <span class="badge-jasa">Komisi Jasa</span> = Subtotal Jasa &times; 20% &divide; Jumlah Mekanik &nbsp;|&nbsp;
    <span class="badge-barang">Komisi Barang</span> = Laba Barang &times; 5% &divide; Jumlah Mekanik &nbsp;|&nbsp;
    <em>Laba Barang = Total Jual Barang &minus; (Qty &times; HPP)</em>
</div>

<form method="POST" class="no-print" style="margin-bottom:15px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
    <div class="form-group">
        <label class="control-label">Dari</label>
        <input type="date" name="tgl_dari" class="form-control input-sm" value="<?= htmlspecialchars($tgl_dari) ?>">
    </div>
    <div class="form-group">
        <label class="control-label">s/d</label>
        <input type="date" name="tgl_sampai" class="form-control input-sm" value="<?= htmlspecialchars($tgl_sampai) ?>">
    </div>
    <div class="form-group">
        <label class="control-label">Mekanik</label>
        <select name="filter_mk" class="form-control input-sm">
            <option value="">— Semua —</option>
            <?php foreach ($mk_list as $m): ?>
            <option value="<?= htmlspecialchars($m['kode']) ?>" <?= ($filter_mk === $m['kode'] ? 'selected' : '') ?>>
                [<?= htmlspecialchars($m['kode']) ?>] <?= htmlspecialchars($m['nama_tampil']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" name="btnCari" class="btn btn-primary btn-sm">
        <i class="fa fa-search"></i> Tampilkan
    </button>
    <?php if ($show && !empty($rows_komisi)): ?>
    <button type="button" class="btn btn-default btn-sm" onclick="window.print()">
        <i class="fa fa-print"></i> Print
    </button>
    <?php endif; ?>
</form>

<?php if ($show): ?>
<?php if (empty($rows_komisi)): ?>
    <div class="alert alert-info">Tidak ada data komisi pada periode yang dipilih.</div>
<?php else: ?>
<table class="table table-bordered table-condensed table-hover tbl-komisi">
    <thead>
        <tr>
            <th style="width:40px">#</th>
            <th>Kode</th>
            <th>Nama Mekanik</th>
            <th class="text-center">Jml Servis</th>
            <th class="text-right"><span class="badge-jasa">Komisi Jasa (20%)</span></th>
            <th class="text-right"><span class="badge-barang">Komisi Barang (5%)</span></th>
            <th class="text-right">Total Komisi</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows_komisi as $i => $r): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><code><?= htmlspecialchars($r['kode_mek']) ?></code></td>
        <td><?= htmlspecialchars($r['nama_mekanik'] ?: $r['kode_mek']) ?></td>
        <td class="text-center"><?= number_format($r['jml_service']) ?></td>
        <td class="text-right">Rp <?= number_format($r['komisi_jasa'], 0, ',', '.') ?></td>
        <td class="text-right">Rp <?= number_format($r['komisi_barang'], 0, ',', '.') ?></td>
        <td class="text-right"><strong>Rp <?= number_format($r['total_komisi'], 0, ',', '.') ?></strong></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-right">TOTAL</td>
            <td class="text-right">Rp <?= number_format($grand_jasa, 0, ',', '.') ?></td>
            <td class="text-right">Rp <?= number_format($grand_barang, 0, ',', '.') ?></td>
            <td class="text-right">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
        </tr>
    </tfoot>
</table>
<small class="text-muted">
    Periode: <?= htmlspecialchars($tgl_dari) ?> s/d <?= htmlspecialchars($tgl_sampai) ?> |
    <?= count($rows_komisi) ?> mekanik | Generated: <?= date('d/m/Y H:i') ?>
</small>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
