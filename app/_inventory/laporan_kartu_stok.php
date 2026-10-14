<?php
require __DIR__ . '/koneksi_inv.php';
wajibPermissionInv('inv_laporan');

$boleh = lokasiBolehInv($koneksi, $kode_cabang_aktif);
$lok = (string) ($_GET['lokasi'] ?? (array_key_first($boleh) ?? ''));
$item = trim((string) ($_GET['item'] ?? ''));
$dari = (string) ($_GET['dari'] ?? date('Y-m-d', strtotime('-30 days')));
$sampai = (string) ($_GET['sampai'] ?? date('Y-m-d'));
$tglOk = function ($t) { return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $t); };
if (!$tglOk($dari)) $dari = date('Y-m-d', strtotime('-30 days'));
if (!$tglOk($sampai)) $sampai = date('Y-m-d');

$rows = []; $saldo = null; $hist = [];
if ($item !== '' && isset($boleh[$lok])) {
    $st = mysqli_prepare($koneksi,
        "SELECT * FROM inv_ledger WHERE kd_cabang = ? AND no_item = ? AND transaction_date BETWEEN ? AND ? ORDER BY id DESC LIMIT 500");
    mysqli_stmt_bind_param($st, 'ssss', $lok, $item, $dari, $sampai);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($x = mysqli_fetch_assoc($res)) $rows[] = $x;
    mysqli_stmt_close($st);

    $st = mysqli_prepare($koneksi, "SELECT * FROM inv_balance WHERE kd_cabang = ? AND no_item = ?");
    mysqli_stmt_bind_param($st, 'ss', $lok, $item);
    mysqli_stmt_execute($st);
    $saldo = mysqli_fetch_assoc(mysqli_stmt_get_result($st)) ?: null;
    mysqli_stmt_close($st);

    $st = mysqli_prepare($koneksi, "SELECT * FROM inv_avg_history WHERE kd_cabang = ? AND no_item = ? ORDER BY id DESC LIMIT 50");
    mysqli_stmt_bind_param($st, 'ss', $lok, $item);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($x = mysqli_fetch_assoc($res)) $hist[] = $x;
    mysqli_stmt_close($st);
}
$rp = function ($v) { return number_format((float) $v, 2, ',', '.'); };
$qt = function ($v) { return rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ','); };

$pageTitle = 'Kartu Stok Bernilai (Rata-rata Bergerak)';
include __DIR__ . '/_ace_header.php';
?>
<form method="get" class="form-inline" style="margin-bottom:15px">
    <select name="lokasi" class="form-control input-sm">
        <?php foreach ($boleh as $kd => $nm): ?><option value="<?= einv($kd) ?>" <?= $kd === $lok ? 'selected' : '' ?>><?= einv($kd) ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="item" value="<?= einv($item) ?>" placeholder="No item" class="form-control input-sm" required>
    <input type="date" name="dari" value="<?= einv($dari) ?>" class="form-control input-sm">
    <input type="date" name="sampai" value="<?= einv($sampai) ?>" class="form-control input-sm">
    <button class="btn btn-sm btn-primary" type="submit">Tampilkan</button>
</form>

<?php if ($item === ''): ?>
    <p class="text-muted">Pilih lokasi dan isi nomor barang.</p>
<?php else: ?>
    <?php if ($saldo): ?>
        <div class="alert alert-info">
            Saldo <strong><?= einv($lok) ?></strong> · <strong><?= einv($item) ?></strong>:
            qty <strong><?= $qt($saldo['qty_on_hand']) ?></strong> ·
            average <strong>Rp <?= $rp($saldo['average_cost']) ?></strong> ·
            nilai persediaan <strong>Rp <?= $rp($saldo['total_value']) ?></strong>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Item ini belum punya saldo di <?= einv($lok) ?> pada buku stok Rata-rata Bergerak.</div>
    <?php endif; ?>

    <table class="table table-condensed table-bordered table-striped">
        <thead><tr>
            <th>Tgl transaksi</th><th>Tgl koreksi</th><th>Jenis</th><th>Referensi</th>
            <th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Biaya satuan</th>
            <th class="text-right">Nilai +/-</th><th class="text-right">Rata-rata sebelum</th><th class="text-right">Rata-rata sesudah</th>
            <th class="text-right">Jumlah akhir</th><th class="text-right">Nilai akhir</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= einv($r['transaction_date']) ?></td>
                <td><?= einv($r['correction_date'] ?? '') ?></td>
                <td><?= einv($r['trx_type']) ?></td>
                <td><?= einv($r['ref_no']) ?></td>
                <td class="text-right"><?= (float) $r['qty_in'] > 0 ? $qt($r['qty_in']) : '' ?></td>
                <td class="text-right"><?= (float) $r['qty_out'] > 0 ? $qt($r['qty_out']) : '' ?></td>
                <td class="text-right"><?= $rp($r['unit_cost']) ?></td>
                <td class="text-right"><?= $rp($r['value_delta']) ?></td>
                <td class="text-right"><?= $rp($r['avg_before']) ?></td>
                <td class="text-right"><?= $rp($r['avg_after']) ?></td>
                <td class="text-right"><?= $qt($r['qty_after']) ?></td>
                <td class="text-right"><?= $rp($r['value_after']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="12" class="text-center text-muted">Tidak ada pergerakan pada rentang ini.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if (count($rows) >= 500): ?><p class="text-muted">Menampilkan 500 baris terbaru. Persempit rentang tanggal.</p><?php endif; ?>

    <h4>Riwayat perubahan rata-rata</h4>
    <table class="table table-condensed table-bordered">
        <thead><tr><th>Waktu</th><th>Pemicu</th><th>Referensi</th><th class="text-right">Sebelum</th><th class="text-right">Sesudah</th><th>Koreksi</th></tr></thead>
        <tbody>
        <?php foreach ($hist as $h): ?>
            <tr><td><?= einv($h['changed_at']) ?></td><td><?= einv($h['trigger_type']) ?></td><td><?= einv($h['trigger_ref']) ?></td>
                <td class="text-right"><?= $rp($h['avg_before']) ?></td><td class="text-right"><?= $rp($h['avg_after']) ?></td>
                <td><?= (int) $h['is_correction'] ? 'ya' : '' ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$hist): ?><tr><td colspan="6" class="text-center text-muted">Belum ada perubahan average.</td></tr><?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
