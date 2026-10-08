<?php
require __DIR__ . '/koneksi_inv.php';
require_once __DIR__ . '/../lib/InventoryCorrection.php';

$bisaAjukan = cekPermissionInv('inv_koreksi');
$bisaSetuju = cekPermissionInv('inv_approve');
$bisaFinance = cekPermissionInv('inv_finance');
if (!$bisaAjukan && !$bisaSetuju) {
    http_response_code(403);
    die('Tidak punya akses koreksi persediaan.');
}

$svc = new InventoryService($koneksi);
$kor = new InventoryCorrection($svc, $koneksi);
$boleh = lokasiBolehInv($koneksi, $kode_cabang_aktif);
$pesan = '';
$pesanErr = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCekInv();
    $aksi = (string) ($_POST['aksi'] ?? '');
    try {
        if ($aksi === 'ajukan' && $bisaAjukan) {
            $lok = (string) ($_POST['lokasi'] ?? '');
            if (!isset($boleh[$svc->resolveLocation($lok)])) {
                throw new InventoryException('Lokasi di luar akses Anda', 'lokasi_dilarang');
            }
            $jenis = (string) ($_POST['jenis'] ?? '');
            $item = trim((string) ($_POST['item'] ?? ''));
            $alasan = trim((string) ($_POST['alasan'] ?? ''));
            if ($jenis === 'REVALUASI' && !$bisaFinance) {
                throw new InventoryException('Revaluasi hanya untuk Finance HQ', 'dilarang');
            }
            switch ($jenis) {
                case 'ADJUST':
                    $payload = ['item' => $item, 'qty' => (string) ($_POST['qty'] ?? '')];
                    break;
                case 'CORR_HARGA':
                    $payload = ['item' => $item, 'ref_type' => 'PEMBELIAN', 'ref_no' => trim((string) ($_POST['ref_no'] ?? '')),
                                'harga_baru' => (string) ($_POST['harga_baru'] ?? '')];
                    break;
                case 'REVALUASI':
                    $payload = ['item' => $item, 'average_baru' => (string) ($_POST['average_baru'] ?? '')];
                    break;
                case 'SCRAP':
                    $payload = ['item' => $item, 'qty' => (string) ($_POST['qty'] ?? ''), 'jenis' => (string) ($_POST['jenis_scrap'] ?? 'RUSAK'),
                                'berita_acara' => trim((string) ($_POST['berita_acara'] ?? ''))];
                    break;
                default:
                    throw new InventoryException('Jenis koreksi tidak dikenal', 'jenis_invalid');
            }
            $id = $kor->submit($jenis, $lok, $payload, $alasan, $kode_karyawan_aktif, trim((string) ($_POST['ref_asal'] ?? '')));
            $pesan = "Pengajuan #$id dikirim. Menunggu persetujuan orang lain.";
        } elseif (($aksi === 'setuju' || $aksi === 'tolak') && $bisaSetuju) {
            $id = (int) ($_POST['id'] ?? 0);
            $req = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT type, kd_cabang FROM inv_correction_request WHERE id = $id"));
            if (!$req) {
                throw new InventoryException('Pengajuan tidak ada', 'request_tidak_ada');
            }
            if ($req['type'] === 'REVALUASI' && !$bisaFinance) {
                throw new InventoryException('Revaluasi harus disetujui Finance HQ', 'dilarang');
            }
            if (!isset($boleh[$req['kd_cabang']])) {
                throw new InventoryException('Lokasi di luar akses Anda', 'lokasi_dilarang');
            }
            $catatan = trim((string) ($_POST['catatan'] ?? ''));
            if ($aksi === 'setuju') {
                $kor->approve($id, $kode_karyawan_aktif, $catatan);
                $pesan = "Pengajuan #$id disetujui dan diterapkan.";
            } else {
                $kor->reject($id, $kode_karyawan_aktif, $catatan);
                $pesan = "Pengajuan #$id ditolak.";
            }
        }
    } catch (InventoryException $e) {
        $pesan = $e->getMessage();
        $pesanErr = true;
    }
}

$siap = mysqli_num_rows(mysqli_query($koneksi, "SHOW TABLES LIKE 'inv_correction_request'")) > 0;
$antrian = []; $riwayat = [];
if ($siap) {
    $in = "'" . implode("','", array_map(function ($k) use ($koneksi) { return mysqli_real_escape_string($koneksi, $k); }, array_keys($boleh))) . "'";
    if ($boleh) {
        $r = mysqli_query($koneksi, "SELECT * FROM inv_correction_request WHERE status = 'pending' AND kd_cabang IN ($in) ORDER BY id");
        while ($x = mysqli_fetch_assoc($r)) $antrian[] = $x;
        $r = mysqli_query($koneksi, "SELECT * FROM inv_correction_request WHERE status <> 'pending' AND kd_cabang IN ($in) ORDER BY id DESC LIMIT 30");
        while ($x = mysqli_fetch_assoc($r)) $riwayat[] = $x;
    }
}

$pageTitle = 'Koreksi Persediaan (Maker-Checker)';
include __DIR__ . '/_ace_header.php';
?>
<?php if (!$siap): ?>
    <div class="alert alert-danger">Jalankan migrasi <code>db/migrations/2026-10-08_run_inv_koreksi.php</code> dulu.</div>
<?php else: ?>
<?php if ($pesan !== ''): ?><div class="alert <?= $pesanErr ? 'alert-danger' : 'alert-success' ?>"><?= einv($pesan) ?></div><?php endif; ?>

<div class="alert alert-info">
    Transaksi yang sudah tercatat <strong>tidak boleh diedit atau dihapus</strong>. Kesalahan diperbaiki lewat koreksi ke depan di sini,
    dengan alasan wajib, tanggal koreksi hari ini, dan persetujuan orang lain (pengaju tidak boleh menyetujui sendiri).
</div>

<?php if ($bisaAjukan): ?>
<h4>Ajukan koreksi</h4>
<form method="post" class="form-horizontal" style="max-width:640px">
    <input type="hidden" name="csrf" value="<?= einv(csrfTokenInv()) ?>">
    <input type="hidden" name="aksi" value="ajukan">
    <div class="form-group"><label class="col-sm-3 control-label">Jenis</label><div class="col-sm-9">
        <select name="jenis" class="form-control" id="jenis" onchange="tampilJenis()">
            <option value="ADJUST">Stock Adjustment (+/-)</option>
            <option value="CORR_HARGA">Koreksi harga pembelian</option>
            <option value="SCRAP">Barang hilang / rusak / scrap</option>
            <?php if ($bisaFinance): ?><option value="REVALUASI">Revaluasi persediaan (Finance HQ)</option><?php endif; ?>
        </select></div></div>
    <div class="form-group"><label class="col-sm-3 control-label">Lokasi</label><div class="col-sm-9">
        <select name="lokasi" class="form-control"><?php foreach ($boleh as $kd => $nm): ?><option value="<?= einv($kd) ?>"><?= einv($kd) ?></option><?php endforeach; ?></select></div></div>
    <div class="form-group"><label class="col-sm-3 control-label">No item</label><div class="col-sm-9"><input name="item" class="form-control" required></div></div>
    <div class="form-group j j-ADJUST j-SCRAP"><label class="col-sm-3 control-label">Qty</label><div class="col-sm-9"><input name="qty" class="form-control" placeholder="ADJUST: +/- ; SCRAP: positif"></div></div>
    <div class="form-group j j-CORR_HARGA"><label class="col-sm-3 control-label">No pembelian asal</label><div class="col-sm-9"><input name="ref_no" class="form-control"></div></div>
    <div class="form-group j j-CORR_HARGA"><label class="col-sm-3 control-label">Harga satuan benar</label><div class="col-sm-9"><input name="harga_baru" class="form-control"></div></div>
    <div class="form-group j j-REVALUASI"><label class="col-sm-3 control-label">Average baru</label><div class="col-sm-9"><input name="average_baru" class="form-control"></div></div>
    <div class="form-group j j-SCRAP"><label class="col-sm-3 control-label">Jenis scrap</label><div class="col-sm-9">
        <select name="jenis_scrap" class="form-control"><option>RUSAK</option><option>HILANG</option><option>SCRAP</option><option>INTERNAL</option></select></div></div>
    <div class="form-group j j-SCRAP"><label class="col-sm-3 control-label">Berita acara</label><div class="col-sm-9"><input name="berita_acara" class="form-control"></div></div>
    <div class="form-group"><label class="col-sm-3 control-label">Referensi transaksi</label><div class="col-sm-9"><input name="ref_asal" class="form-control"></div></div>
    <div class="form-group"><label class="col-sm-3 control-label">Alasan (wajib)</label><div class="col-sm-9"><textarea name="alasan" class="form-control" rows="2" required minlength="5"></textarea></div></div>
    <div class="form-group"><div class="col-sm-offset-3 col-sm-9"><button class="btn btn-primary" type="submit">Kirim pengajuan</button></div></div>
</form>
<script>
function tampilJenis(){var j=document.getElementById('jenis').value;document.querySelectorAll('.j').forEach(function(e){e.style.display=e.classList.contains('j-'+j)?'':'none';});}
tampilJenis();
</script>
<?php endif; ?>

<h4>Antrian persetujuan</h4>
<table class="table table-condensed table-bordered">
    <thead><tr><th>#</th><th>Jenis</th><th>Lokasi</th><th>Detail</th><th>Alasan</th><th>Pengaju</th><th>Waktu</th><?php if ($bisaSetuju): ?><th>Keputusan</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($antrian as $a): ?>
        <tr><td><?= (int) $a['id'] ?></td><td><?= einv($a['type']) ?></td><td><?= einv($a['kd_cabang']) ?></td>
            <td><code><?= einv($a['payload_json']) ?></code></td><td><?= einv($a['reason']) ?></td><td><?= einv($a['maker']) ?></td><td><?= einv($a['created_at']) ?></td>
            <?php if ($bisaSetuju): ?><td>
                <?php if ($a['maker'] === $kode_karyawan_aktif): ?><span class="text-muted">Menunggu orang lain</span>
                <?php else: ?>
                <form method="post" class="form-inline">
                    <input type="hidden" name="csrf" value="<?= einv(csrfTokenInv()) ?>"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <input name="catatan" class="form-control input-sm" placeholder="Catatan" required minlength="5">
                    <button name="aksi" value="setuju" class="btn btn-sm btn-success" type="submit">Setujui</button>
                    <button name="aksi" value="tolak" class="btn btn-sm btn-danger" type="submit">Tolak</button>
                </form>
                <?php endif; ?></td><?php endif; ?></tr>
    <?php endforeach; ?>
    <?php if (!$antrian): ?><tr><td colspan="8" class="text-center text-muted">Tidak ada pengajuan menunggu.</td></tr><?php endif; ?>
    </tbody>
</table>

<h4>Riwayat</h4>
<table class="table table-condensed table-bordered">
    <thead><tr><th>#</th><th>Jenis</th><th>Lokasi</th><th>Status</th><th>Pengaju</th><th>Penyetuju</th><th>Catatan</th><th>Waktu keputusan</th></tr></thead><tbody>
    <?php foreach ($riwayat as $a): ?>
        <tr><td><?= (int) $a['id'] ?></td><td><?= einv($a['type']) ?></td><td><?= einv($a['kd_cabang']) ?></td><td><?= einv($a['status']) ?></td>
            <td><?= einv($a['maker']) ?></td><td><?= einv($a['checker']) ?></td><td><?= einv($a['decision_note']) ?></td><td><?= einv($a['decided_at']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$riwayat): ?><tr><td colspan="8" class="text-center text-muted">Belum ada.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php endif; ?>
<?php include __DIR__ . '/_ace_footer.php'; ?>
</body>
</html>
