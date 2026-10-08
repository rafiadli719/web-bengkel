<?php
require __DIR__ . '/koneksi_inv.php';
wajibPermissionInv('inv_laporan');

$boleh = lokasiBolehInv($koneksi, $kode_cabang_aktif);
$lok = (string) ($_GET['lokasi'] ?? '');       // kosong = semua lokasi yang boleh
$cari = trim((string) ($_GET['cari'] ?? ''));
$lokPakai = $lok !== '' ? [$lok => true] : $boleh;
$lokPakai = array_intersect_key($lokPakai, $boleh);

$rows = []; $totNilai = 0.0; $totItem = 0;
if ($lokPakai) {
    $in = "'" . implode("','", array_map(function ($k) use ($koneksi) { return mysqli_real_escape_string($koneksi, $k); }, array_keys($lokPakai))) . "'";
    $like = '%' . $cari . '%';
    $st = mysqli_prepare($koneksi,
        "SELECT b.kd_cabang, b.no_item, i.namaitem, b.qty_on_hand, b.average_cost, b.total_value
         FROM inv_balance b LEFT JOIN tblitem i ON i.noitem = b.no_item
         WHERE b.kd_cabang IN ($in) AND b.qty_on_hand > 0 AND (b.no_item LIKE ? OR i.namaitem LIKE ?)
         ORDER BY b.total_value DESC LIMIT 2000");
    mysqli_stmt_bind_param($st, 'ss', $like, $like);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($x = mysqli_fetch_assoc($res)) {
        $rows[] = $x;
        $totNilai += (float) $x['total_value'];
    }
    mysqli_stmt_close($st);
    $totItem = count($rows);
}

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="valuasi_persediaan_' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['lokasi', 'no_item', 'nama', 'qty', 'average', 'nilai']);
    foreach ($rows as $r) {
        fputcsv($o, [$r['kd_cabang'], $r['no_item'], $r['namaitem'], $r['qty_on_hand'], $r['average_cost'], $r['total_value']]);
    }
    fclose($o);
    exit;
}

$rp = function ($v) { return number_format((float) $v, 2, ',', '.'); };
$qt = function ($v) { return rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ','); };
$pageTitle = 'Valuasi Persediaan (Moving Average)';
include __DIR__ . '/_ace_header.php';
?>
<form method="get" class="form-inline" style="margin-bottom:15px">
    <select name="lokasi" class="form-control input-sm">
        <option value="">Semua lokasi</option>
        <?php foreach ($boleh as $kd => $nm): ?><option value="<?= einv($kd) ?>" <?= $kd === $lok ? 'selected' : '' ?>><?= einv($kd) ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="cari" value="<?= einv($cari) ?>" placeholder="Cari no item / nama" class="form-control input-sm">
    <button class="btn btn-sm btn-primary" type="submit">Tampilkan</button>
    <button class="btn btn-sm btn-default" type="submit" name="csv" value="1"><i class="fa fa-download"></i> CSV</button>
</form>

<div class="alert alert-info">
    Total nilai persediaan: <strong>Rp <?= $rp($totNilai) ?></strong> · item bersaldo: <strong><?= (int) $totItem ?></strong>
    <?php if ($totItem >= 2000): ?>· <em>menampilkan 2000 nilai terbesar</em><?php endif; ?>
</div>

<table class="table table-condensed table-bordered table-striped">
    <thead><tr><th>Lokasi</th><th>No item</th><th>Nama</th><th class="text-right">Qty</th><th class="text-right">Average</th><th class="text-right">Nilai</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= einv($r['kd_cabang']) ?></td>
            <td><a href="laporan_kartu_stok.php?lokasi=<?= urlencode($r['kd_cabang']) ?>&item=<?= urlencode($r['no_item']) ?>"><?= einv($r['no_item']) ?></a></td>
            <td><?= einv($r['namaitem']) ?></td>
            <td class="text-right"><?= $qt($r['qty_on_hand']) ?></td>
            <td class="text-right"><?= $rp($r['average_cost']) ?></td>
            <td class="text-right"><?= $rp($r['total_value']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">Belum ada saldo di ledger Moving Average. Muat opening stock atau aktifkan shadow.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
