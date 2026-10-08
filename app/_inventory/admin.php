<?php
require __DIR__ . '/koneksi_inv.php';
wajibPermissionInv('inv_admin');

$svc = new InventoryService($koneksi);
$pesan = '';
$pesanErr = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCekInv();
    if (($_POST['aksi'] ?? '') === 'set_mode') {
        $lok = (string) ($_POST['lokasi'] ?? '');
        $mode = (string) ($_POST['mode'] ?? '');
        // live sengaja tidak ditawarkan: baru boleh setelah opening stock + cutover (planning F5-06)
        if (!in_array($mode, ['off', 'shadow'], true)) {
            $pesan = 'Mode tidak valid. Hanya off atau shadow yang bisa dipilih dari halaman ini.';
            $pesanErr = true;
        } else {
            try {
                $svc->setMode($lok, $mode, $kode_karyawan_aktif);
                $pesan = "Mode $lok diubah ke $mode.";
            } catch (InventoryException $e) {
                $pesan = $e->getMessage();
                $pesanErr = true;
            }
        }
    }
}

$cabang = [];
$r = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY kode_cabang");
while ($x = mysqli_fetch_assoc($r)) {
    $cabang[] = $x;
}

$tabelAda = mysqli_query($koneksi, "SHOW TABLES LIKE 'inv_config'");
$siap = $tabelAda && mysqli_num_rows($tabelAda) > 0;

$rekonKode = (string) ($_GET['rekon'] ?? '');
$rekon = null;
if ($siap && $rekonKode !== '') {
    try {
        $rekon = $svc->reconcile($rekonKode);
    } catch (InventoryException $e) {
        $pesan = $e->getMessage();
        $pesanErr = true;
    }
}

$pageTitle = 'Persediaan Moving Average — Pengaturan';
include __DIR__ . '/_ace_header.php';
?>
<?php if (!$siap): ?>
    <div class="alert alert-danger">Tabel <code>inv_*</code> belum ada. Jalankan migrasi <code>db/migrations/2026-10-08_run_inv_core.php</code> dulu.</div>
<?php else: ?>
<?php if ($pesan !== ''): ?>
    <div class="alert <?= $pesanErr ? 'alert-danger' : 'alert-success' ?>"><?= einv($pesan) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>Mode per cabang.</strong>
    <code>off</code> = tidak ada pencatatan. <code>shadow</code> = setiap transaksi stok dicatat paralel ke ledger Moving Average,
    alur lama tidak berubah dan tidak pernah terganggu. Mode <code>live</code> (penegakan stok minus, HPP resmi) baru dibuka saat cutover
    setelah opening stock dimuat.
</div>

<table class="table table-bordered table-striped">
    <thead><tr><th>Cabang</th><th>Mode</th><th>Baseline tbstok</th><th>Item di ledger</th><th>Ubah mode</th><th>Rekonsiliasi</th></tr></thead>
    <tbody>
    <?php foreach ($cabang as $c):
        $kd = $c['kode_cabang'];
        $mode = $svc->getMode($kd);
        $base = $svc->getConfig($kd, 'shadow_since_tbstok_id', '-');
        $rr = mysqli_query($koneksi, "SELECT COUNT(*) n FROM inv_balance WHERE kd_cabang = '" . mysqli_real_escape_string($koneksi, $kd) . "'");
        $nItem = $rr ? (int) mysqli_fetch_assoc($rr)['n'] : 0;
    ?>
        <tr>
            <td><?= einv($kd) ?> <small class="text-muted"><?= einv($c['nama_cabang']) ?></small></td>
            <td><span class="label <?= $mode === 'off' ? 'label-default' : ($mode === 'shadow' ? 'label-warning' : 'label-success') ?>"><?= einv($mode) ?></span></td>
            <td><?= einv($base) ?></td>
            <td><?= $nItem ?></td>
            <td>
                <?php if ($mode !== 'live'): ?>
                <form method="post" class="form-inline" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= einv(csrfTokenInv()) ?>">
                    <input type="hidden" name="aksi" value="set_mode">
                    <input type="hidden" name="lokasi" value="<?= einv($kd) ?>">
                    <select name="mode" class="form-control input-sm">
                        <option value="off" <?= $mode === 'off' ? 'selected' : '' ?>>off</option>
                        <option value="shadow" <?= $mode === 'shadow' ? 'selected' : '' ?>>shadow</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </form>
                <?php else: ?>-<?php endif; ?>
            </td>
            <td><a class="btn btn-sm btn-default" href="?rekon=<?= urlencode($kd) ?>">Lihat selisih</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($rekon): ?>
    <h4>Rekonsiliasi <?= einv($rekon['lokasi']) ?> <small>sejak tbstok id &gt; <?= (int) $rekon['baseline_tbstok_id'] ?></small></h4>
    <p>
        Item bergerak di jalur lama: <strong><?= (int) $rekon['item_tbstok'] ?></strong> ·
        di ledger baru: <strong><?= (int) $rekon['item_inv'] ?></strong> ·
        item selisih: <strong><?= (int) $rekon['item_selisih'] ?></strong> ·
        transaksi shadow ditolak/gagal: <strong><?= (int) $rekon['shadow_gagal'] ?></strong>
    </p>
    <p class="text-muted">Selisih muncul wajar bila transaksi keluar ditolak (saldo awal ledger belum dimuat lewat Opening Stock)
        atau transaksi lama dibatalkan setelah shadow mencatat.</p>
    <?php if (!$rekon['selisih']): ?>
        <div class="alert alert-success">Tidak ada selisih.</div>
    <?php else: ?>
        <table class="table table-condensed table-bordered">
            <thead><tr><th>Item</th><th class="text-right">Jalur lama (tbstok)</th><th class="text-right">Ledger baru</th><th class="text-right">Selisih</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($rekon['selisih'], 0, 100) as $s): ?>
                <tr><td><?= einv($s['no_item']) ?></td><td class="text-right"><?= einv($s['tbstok']) ?></td>
                    <td class="text-right"><?= einv($s['inv']) ?></td><td class="text-right"><?= einv($s['selisih']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($rekon['selisih']) > 100): ?><p class="text-muted">Menampilkan 100 selisih terbesar dari <?= count($rekon['selisih']) ?>.</p><?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<h4>Penolakan shadow terakhir</h4>
<?php
$rg = mysqli_query($koneksi, "SELECT created_at, ref, reason FROM inv_audit_log WHERE action IN ('shadow_gagal','shadow_void_gagal') ORDER BY id DESC LIMIT 20");
if ($rg && mysqli_num_rows($rg) > 0): ?>
    <table class="table table-condensed table-bordered">
        <thead><tr><th>Waktu</th><th>Referensi</th><th>Alasan</th></tr></thead><tbody>
        <?php while ($g = mysqli_fetch_assoc($rg)): ?>
            <tr><td><?= einv($g['created_at']) ?></td><td><?= einv($g['ref']) ?></td><td><?= einv($g['reason']) ?></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="text-muted">Belum ada.</p>
<?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
