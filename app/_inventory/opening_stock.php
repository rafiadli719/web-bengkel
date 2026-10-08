<?php
require __DIR__ . '/koneksi_inv.php';
wajibPermissionInv('inv_opening');

$svc = new InventoryService($koneksi);

/**
 * Saran biaya awal (D-02): pembelian riil terakhir -> acuan HPP Access -> hargapokok master -> 0 (isi manual).
 * Hasil: [biaya, sumber].
 */
function saranBiayaInv($koneksi, string $item, array $cacheAcuan): array {
    $st = mysqli_prepare($koneksi,
        "SELECT d.total, d.quantity, d.harga_pokok FROM tblpembelian_detail d
         WHERE d.no_item = ? AND d.no_transaksi <> '' AND d.quantity > 0 ORDER BY d.id DESC LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $item);
    mysqli_stmt_execute($st);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if ($r && ((float) $r['total'] > 0 || (float) $r['harga_pokok'] > 0)) {
        $b = (float) $r['total'] > 0 ? (float) $r['total'] / (float) $r['quantity'] : (float) $r['harga_pokok'];
        return [number_format($b, 2, '.', ''), 'pembelian_terakhir'];
    }
    if (isset($cacheAcuan[$item]) && (float) $cacheAcuan[$item] > 0) {
        return [number_format((float) $cacheAcuan[$item], 2, '.', ''), 'acuan_hpp_access'];
    }
    return ['', 'manual'];
}

// ---- unduh template terisi saran biaya ----
if (isset($_GET['template'])) {
    $lok = $svc->resolveLocation((string) $_GET['template']);
    if (!isset(lokasiBolehInv($koneksi, $kode_cabang_aktif)[$lok])) {
        http_response_code(403);
        die('Lokasi di luar akses Anda.');
    }
    $acuan = [];
    $ra = mysqli_query($koneksi, "SELECT g.no_item, g.acuan_hpp FROM gabung_hpp_acuan g
        JOIN (SELECT no_item, MAX(siklus) s FROM gabung_hpp_acuan GROUP BY no_item) m ON m.no_item = g.no_item AND m.s = g.siklus
        WHERE g.acuan_hpp > 0");
    while ($x = mysqli_fetch_assoc($ra)) {
        $acuan[$x['no_item']] = $x['acuan_hpp'];
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="opening_stock_' . $lok . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['kd_cabang', 'no_item', 'nama_item', 'qty_fisik', 'unit_cost', 'sumber_biaya']);
    $ri = mysqli_query($koneksi, "SELECT noitem, namaitem, hargapokok FROM tblitem WHERE statusproduk <> '0' OR statusproduk IS NULL ORDER BY noitem");
    while ($it = mysqli_fetch_assoc($ri)) {
        [$biaya, $sumber] = saranBiayaInv($koneksi, $it['noitem'], $acuan);
        if ($biaya === '' && (float) $it['hargapokok'] > 0) {
            $biaya = number_format((float) $it['hargapokok'], 2, '.', '');
            $sumber = 'master_hargapokok';
        }
        fputcsv($out, [$lok, $it['noitem'], $it['namaitem'], '', $biaya, $sumber]);
    }
    fclose($out);
    exit;
}

$pesan = '';
$pesanErr = false;
$hasil = null;      // hasil validasi
$dimuat = null;

function validasiCsvInv($koneksi, InventoryService $svc, string $path, array $lokasiBoleh): array {
    $fh = fopen($path, 'r');
    $hdr = $fh ? fgetcsv($fh) : false;
    if (!$hdr) {
        return ['error_fatal' => 'File kosong atau tidak bisa dibaca.', 'baris' => [], 'valid' => [], 'error' => []];
    }
    $hdr = array_map(function ($h) { return strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))); }, $hdr);
    foreach (['kd_cabang', 'no_item', 'qty_fisik', 'unit_cost'] as $w) {
        if (!in_array($w, $hdr, true)) {
            return ['error_fatal' => "Kolom wajib '$w' tidak ada. Kolom wajib: kd_cabang, no_item, qty_fisik, unit_cost.", 'baris' => [], 'valid' => [], 'error' => []];
        }
    }
    $ada = [];
    $ri = mysqli_query($koneksi, "SELECT noitem FROM tblitem");
    while ($x = mysqli_fetch_assoc($ri)) {
        $ada[$x['noitem']] = true;
    }
    $valid = []; $error = []; $lihat = []; $n = 0; $nol = 0;
    while (($row = fgetcsv($fh)) !== false) {
        $n++;
        if (count(array_filter($row, 'strlen')) === 0) {
            continue;
        }
        $r = [];
        foreach ($hdr as $i => $h) {
            $r[$h] = isset($row[$i]) ? trim($row[$i]) : '';
        }
        $err = [];
        try {
            $lok = $svc->resolveLocation($r['kd_cabang']);
            if (!isset($lokasiBoleh[$lok])) {
                $err[] = "lokasi $lok di luar akses Anda";
            }
        } catch (InventoryException $e) {
            $lok = '';
            $err[] = 'lokasi tidak dikenal';
        }
        if ($r['no_item'] === '' || !isset($ada[$r['no_item']])) {
            $err[] = 'item tidak ada di master';
        }
        if ($r['qty_fisik'] === '') {
            $nol++;     // qty kosong = belum dihitung, dilewati
            continue;
        }
        if (!preg_match('/^\d+(\.\d{1,3})?$/', $r['qty_fisik'])) {
            $err[] = 'qty_fisik harus angka >= 0 (maks 3 desimal)';
        }
        $cost = str_replace(',', '.', $r['unit_cost']);
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $cost)) {
            $err[] = 'unit_cost harus angka >= 0 (maks 2 desimal)';
        }
        $kunci = $lok . '|' . $r['no_item'];
        if ($lok !== '' && isset($lihat[$kunci])) {
            $err[] = 'duplikat lokasi+item (baris ' . $lihat[$kunci] . ')';
        }
        $lihat[$kunci] = $n + 1;
        if ($err) {
            $error[] = ['baris' => $n + 1, 'item' => $r['no_item'], 'pesan' => implode('; ', $err)];
            continue;
        }
        if ((float) $r['qty_fisik'] == 0.0) {
            $nol++;
            continue;
        }
        $valid[] = ['lokasi' => $lok, 'item' => $r['no_item'], 'qty' => $r['qty_fisik'], 'cost' => $cost, 'baris' => $n + 1];
    }
    fclose($fh);
    return ['error_fatal' => '', 'baris' => $n, 'valid' => $valid, 'error' => $error, 'dilewati' => $nol];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCekInv();
    $aksi = $_POST['aksi'] ?? '';
    $boleh = lokasiBolehInv($koneksi, $kode_cabang_aktif);
    if ($aksi === 'validasi' && isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $hasil = validasiCsvInv($koneksi, $svc, $_FILES['csv']['tmp_name'], $boleh);
        if ($hasil['error_fatal'] === '' && !$hasil['error'] && $hasil['valid']) {
            $_SESSION['_inv_opening'] = ['rows' => $hasil['valid'], 'token' => bin2hex(random_bytes(8))];
        } else {
            unset($_SESSION['_inv_opening']);
        }
    } elseif ($aksi === 'muat') {
        $sesi = $_SESSION['_inv_opening'] ?? null;
        if (!$sesi || !hash_equals($sesi['token'], (string) ($_POST['token'] ?? ''))) {
            $pesan = 'Data validasi kedaluwarsa. Unggah ulang file.';
            $pesanErr = true;
        } else {
            $lokasiUnik = array_values(array_unique(array_column($sesi['rows'], 'lokasi')));
            // opening hanya boleh sekali per lokasi; koreksi berikutnya lewat Stock Adjustment
            foreach ($lokasiUnik as $lk) {
                $c = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE kd_cabang = ? AND trx_type = 'OPENING'");
                mysqli_stmt_bind_param($c, 's', $lk);
                mysqli_stmt_execute($c);
                $jml = (int) mysqli_fetch_row(mysqli_stmt_get_result($c))[0];
                mysqli_stmt_close($c);
                if ($jml > 0) {
                    $pesan = "Opening stock $lk sudah pernah dimuat. Koreksi lewat Stock Adjustment.";
                    $pesanErr = true;
                    break;
                }
            }
            if (!$pesanErr) {
                $batch = 'OPEN-' . date('Ymd-His');
                mysqli_begin_transaction($koneksi);
                try {
                    foreach ($sesi['rows'] as $i => $row) {
                        $svc->postIn($row['lokasi'], $row['item'], $row['qty'], $row['cost'], 'OPENING', $batch, [
                            'trx_type' => 'OPENING', 'ref_line' => $i, 'user' => $kode_karyawan_aktif,
                            'keterangan' => 'Opening stock opname fisik', 'manage_tx' => false,
                        ]);
                    }
                    $svc->audit($kode_karyawan_aktif, 'opening_stock', $batch, 'Muat opening stock', null,
                        ['lokasi' => $lokasiUnik, 'baris' => count($sesi['rows'])]);
                    mysqli_commit($koneksi);
                    unset($_SESSION['_inv_opening']);
                    $dimuat = ['batch' => $batch, 'baris' => count($sesi['rows']), 'lokasi' => $lokasiUnik];
                } catch (Throwable $e) {
                    mysqli_rollback($koneksi);
                    $pesan = 'Pemuatan dibatalkan seluruhnya: ' . $e->getMessage();
                    $pesanErr = true;
                }
            }
        }
    }
}

$boleh = lokasiBolehInv($koneksi, $kode_cabang_aktif);
$pageTitle = 'Opening Stock Moving Average';
include __DIR__ . '/_ace_header.php';
?>
<?php if ($pesan !== ''): ?><div class="alert <?= $pesanErr ? 'alert-danger' : 'alert-success' ?>"><?= einv($pesan) ?></div><?php endif; ?>
<?php if ($dimuat): ?>
    <div class="alert alert-success">Opening stock dimuat: <?= (int) $dimuat['baris'] ?> baris, lokasi <?= einv(implode(', ', $dimuat['lokasi'])) ?>, batch <code><?= einv($dimuat['batch']) ?></code>.</div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>Saldo awal dari stok opname fisik (D-03).</strong> Unduh template per lokasi (biaya awal sudah disarankan: pembelian terakhir,
    lalu acuan HPP Access, lalu harga pokok master), isi kolom <code>qty_fisik</code> dari hasil hitung fisik, unggah, periksa hasil validasi,
    baru muat. Baris dengan <code>qty_fisik</code> kosong atau 0 dilewati. Opening hanya bisa dimuat satu kali per lokasi
    dan semuanya atau tidak sama sekali.
</div>

<h4>1. Unduh template</h4>
<p>
<?php foreach ($boleh as $kd => $nm): ?>
    <a class="btn btn-sm btn-default" href="?template=<?= urlencode($kd) ?>"><i class="fa fa-download"></i> <?= einv($kd) ?></a>
<?php endforeach; ?>
</p>

<h4>2. Unggah dan validasi</h4>
<form method="post" enctype="multipart/form-data" class="form-inline">
    <input type="hidden" name="csrf" value="<?= einv(csrfTokenInv()) ?>">
    <input type="hidden" name="aksi" value="validasi">
    <input type="file" name="csv" accept=".csv,text/csv" required>
    <button type="submit" class="btn btn-primary btn-sm">Validasi</button>
</form>

<?php if ($hasil): ?>
    <hr>
    <?php if ($hasil['error_fatal'] !== ''): ?>
        <div class="alert alert-danger"><?= einv($hasil['error_fatal']) ?></div>
    <?php else: ?>
        <p>Baris dibaca: <strong><?= (int) $hasil['baris'] ?></strong> · siap dimuat: <strong><?= count($hasil['valid']) ?></strong> ·
           dilewati (qty kosong/0): <strong><?= (int) $hasil['dilewati'] ?></strong> · error: <strong><?= count($hasil['error']) ?></strong></p>
        <?php if ($hasil['error']): ?>
            <div class="alert alert-danger">Perbaiki error berikut lalu unggah ulang. Tidak ada yang dimuat.</div>
            <table class="table table-condensed table-bordered"><thead><tr><th>Baris</th><th>Item</th><th>Masalah</th></tr></thead><tbody>
            <?php foreach (array_slice($hasil['error'], 0, 200) as $er): ?>
                <tr><td><?= (int) $er['baris'] ?></td><td><?= einv($er['item']) ?></td><td><?= einv($er['pesan']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php elseif ($hasil['valid']): ?>
            <?php
            $totNilai = 0;
            foreach ($hasil['valid'] as $v) { $totNilai += (float) $v['qty'] * (float) $v['cost']; }
            ?>
            <div class="alert alert-success">Validasi lolos. Total nilai persediaan awal: <strong>Rp <?= number_format($totNilai, 2, ',', '.') ?></strong>.</div>
            <form method="post" onsubmit="return confirm('Muat opening stock? Ini tidak bisa diulang untuk lokasi yang sama.');">
                <input type="hidden" name="csrf" value="<?= einv(csrfTokenInv()) ?>">
                <input type="hidden" name="aksi" value="muat">
                <input type="hidden" name="token" value="<?= einv($_SESSION['_inv_opening']['token'] ?? '') ?>">
                <button type="submit" class="btn btn-danger">3. Muat opening stock</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Tidak ada baris dengan qty &gt; 0.</div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
