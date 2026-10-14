<?php
require __DIR__ . '/koneksi_inv.php';
wajibPermissionInv('inv_laporan');

$boleh = lokasiBolehInv($koneksi, $kode_cabang_aktif);
$in = $boleh ? "'" . implode("','", array_map(function ($k) use ($koneksi) { return mysqli_real_escape_string($koneksi, $k); }, array_keys($boleh))) . "'" : "''";
$periode = (string) ($_GET['periode'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $periode)) $periode = date('Y-m');
$rp = function ($v) { return number_format((float) $v, 2, ',', '.'); };
$qt = function ($v) { return rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ','); };
$q = function ($sql) use ($koneksi) { $o = []; $r = @mysqli_query($koneksi, $sql); if ($r) while ($x = mysqli_fetch_assoc($r)) $o[] = $x; return $o; };
$siap = mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'inv_transit'")) > 0;

$aging = (int) (mysqli_fetch_row(mysqli_query($koneksi, "SELECT COALESCE((SELECT config_value FROM inv_config WHERE kd_cabang='*' AND config_key='transit_aging_hari'),3)"))[0]);
$transit = $siap ? $q("SELECT *, DATEDIFF(NOW(), shipped_at) umur FROM inv_transit WHERE status='in_transit' AND (kd_asal IN ($in) OR kd_tujuan IN ($in)) ORDER BY shipped_at") : [];
$ppv = $siap ? $q("SELECT p.*, i.namaitem FROM inv_ppv_log p LEFT JOIN tblitem i ON i.noitem=p.no_item WHERE p.period='" . mysqli_real_escape_string($koneksi, $periode) . "' AND p.kd_cabang IN ($in) ORDER BY p.id DESC") : [];
$klaim = $siap ? $q("SELECT c.*, i.namaitem FROM inv_claim_receivable c LEFT JOIN tblitem i ON i.noitem=c.no_item WHERE c.kd_cabang IN ($in) ORDER BY FIELD(c.status,'pending','settled','rejected'), c.id DESC LIMIT 200") : [];
// HPP keluar per periode (penjualan + servis) dan total persediaan sekarang + transit = neraca persediaan (F5-03)
$hpp = $q("SELECT kd_cabang, ref_type, SUM(-value_delta) hpp FROM inv_ledger WHERE trx_type NOT LIKE 'VOID\\_%' AND qty_out > 0 AND ref_type IN ('PENJUALAN','SERVIS')
          AND DATE_FORMAT(transaction_date,'%Y-%m')='" . mysqli_real_escape_string($koneksi, $periode) . "' AND kd_cabang IN ($in) GROUP BY kd_cabang, ref_type ORDER BY kd_cabang");
$persediaan = (float) (mysqli_fetch_row(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_value),0) FROM inv_balance WHERE kd_cabang IN ($in)"))[0]);
$nilaiTransit = 0.0; foreach ($transit as $t) $nilaiTransit += (float) $t['value'];
$totPpv = 0.0; foreach ($ppv as $p) $totPpv += (float) $p['total_variance'];
$totKlaim = 0.0; foreach ($klaim as $c) if ($c['status'] === 'pending') $totKlaim += (float) $c['value'];

$pageTitle = 'Laporan Persediaan: Transit, PPV, Klaim, HPP';
include __DIR__ . '/_ace_header.php';
?>
<form method="get" class="form-inline" style="margin-bottom:15px">
    <label>Periode </label> <input type="month" name="periode" value="<?= einv($periode) ?>" class="form-control input-sm">
    <button class="btn btn-sm btn-primary" type="submit">Tampilkan</button>
</form>
<?php if (!$siap): ?><div class="alert alert-danger">Jalankan migrasi <code>2026-10-08_run_inv_koreksi.php</code> dulu.</div><?php endif; ?>

<div class="alert alert-info">
    Total persediaan (saldo): <strong>Rp <?= $rp($persediaan) ?></strong> · barang dalam perjalanan: <strong>Rp <?= $rp($nilaiTransit) ?></strong> ·
    <strong>neraca persediaan: Rp <?= $rp($persediaan + $nilaiTransit) ?></strong> ·
    selisih harga pembelian periode ini: <strong>Rp <?= $rp($totPpv) ?></strong> · piutang klaim supplier (pending): <strong>Rp <?= $rp($totKlaim) ?></strong>
</div>

<h4>HPP periode <?= einv($periode) ?></h4>
<table class="table table-condensed table-bordered"><thead><tr><th>Lokasi</th><th>Sumber</th><th class="text-right">HPP (Rata-rata Bergerak)</th></tr></thead><tbody>
<?php foreach ($hpp as $h): ?><tr><td><?= einv($h['kd_cabang']) ?></td><td><?= einv($h['ref_type']) ?></td><td class="text-right"><?= $rp($h['hpp']) ?></td></tr><?php endforeach; ?>
<?php if (!$hpp): ?><tr><td colspan="3" class="text-center text-muted">Belum ada HPP tercatat pada periode ini.</td></tr><?php endif; ?>
</tbody></table>

<h4>Barang dalam perjalanan <small>(peringatan bila lebih dari <?= $aging ?> hari)</small></h4>
<table class="table table-condensed table-bordered"><thead><tr><th>No transfer</th><th>Item</th><th>Asal</th><th>Tujuan</th><th class="text-right">Jumlah</th><th class="text-right">Nilai</th><th>Dikirim</th><th>Umur</th></tr></thead><tbody>
<?php foreach ($transit as $t): $lewat = (int) $t['umur'] > $aging; ?>
    <tr class="<?= $lewat ? 'danger' : '' ?>"><td><?= einv($t['no_transfer']) ?></td><td><?= einv($t['no_item']) ?></td><td><?= einv($t['kd_asal']) ?></td><td><?= einv($t['kd_tujuan']) ?></td>
        <td class="text-right"><?= $qt($t['qty']) ?></td><td class="text-right"><?= $rp($t['value']) ?></td><td><?= einv($t['shipped_at']) ?></td>
        <td><?= (int) $t['umur'] ?> hari <?= $lewat ? '<span class="label label-danger">lewat batas</span>' : '' ?></td></tr>
<?php endforeach; ?>
<?php if (!$transit): ?><tr><td colspan="8" class="text-center text-muted">Tidak ada barang dalam perjalanan.</td></tr><?php endif; ?>
</tbody></table>

<h4>Selisih Harga Pembelian <?= einv($periode) ?></h4>
<table class="table table-condensed table-bordered"><thead><tr><th>Lokasi</th><th>Item</th><th>Nama</th><th>Ref koreksi</th><th class="text-right">Jumlah terjual</th><th class="text-right">Selisih/unit</th><th class="text-right">Total</th></tr></thead><tbody>
<?php foreach ($ppv as $p): ?><tr><td><?= einv($p['kd_cabang']) ?></td><td><?= einv($p['no_item']) ?></td><td><?= einv($p['namaitem']) ?></td><td><?= einv($p['correction_ref']) ?></td>
    <td class="text-right"><?= $qt($p['qty_affected']) ?></td><td class="text-right"><?= $rp($p['price_difference']) ?></td><td class="text-right"><?= $rp($p['total_variance']) ?></td></tr><?php endforeach; ?>
<?php if (!$ppv): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada.</td></tr><?php endif; ?>
</tbody></table>

<h4>Piutang Klaim Supplier</h4>
<table class="table table-condensed table-bordered"><thead><tr><th>No klaim</th><th>Lokasi</th><th>Item</th><th>Nama</th><th>Supplier</th><th class="text-right">Jumlah</th><th class="text-right">Nilai</th><th>Status</th></tr></thead><tbody>
<?php foreach ($klaim as $c): ?><tr><td><?= einv($c['no_klaim']) ?></td><td><?= einv($c['kd_cabang']) ?></td><td><?= einv($c['no_item']) ?></td><td><?= einv($c['namaitem']) ?></td><td><?= einv($c['supplier_id']) ?></td>
    <td class="text-right"><?= $qt($c['qty']) ?></td><td class="text-right"><?= $rp($c['value']) ?></td><td><?= einv($c['status']) ?></td></tr><?php endforeach; ?>
<?php if (!$klaim): ?><tr><td colspan="8" class="text-center text-muted">Tidak ada.</td></tr><?php endif; ?>
</tbody></table>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
