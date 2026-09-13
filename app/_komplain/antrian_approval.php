<?php
require __DIR__ . '/koneksi_komplain.php';

$bolehApproveRework = cekPermissionKomplain('komplain_approve_rework');
$bolehCloseNonRework = cekPermissionKomplain('komplain_close_nonrework');
if (!$bolehApproveRework && !$bolehCloseNonRework) {
    http_response_code(403);
    die('Tidak punya akses antrian approval.');
}

$rework = [];
if ($bolehApproveRework) {
    $stmt = $koneksi_komplain->prepare(
        "SELECT *, DATEDIFF(NOW(), tanggal_lapor) AS aging_hari FROM tblkomplain
         WHERE kode_cabang = :cabang AND status = 'Diajukan' ORDER BY tanggal_lapor ASC"
    );
    $stmt->execute([':cabang' => $kode_cabang_aktif]);
    $rework = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$nonRework = [];
if ($bolehCloseNonRework) {
    $stmt2 = $koneksi_komplain->prepare(
        "SELECT k.*, DATEDIFF(NOW(), k.tanggal_lapor) AS aging_hari FROM tblkomplain k
         JOIN tblkomplain_kategori kat ON kat.kode_kategori = k.kode_kategori
         WHERE k.kode_cabang = :cabang AND k.status = 'Open' AND kat.pic_role = 'KEPALA_CABANG'
         ORDER BY k.tanggal_lapor ASC"
    );
    $stmt2->execute([':cabang' => $kode_cabang_aktif]);
    $nonRework = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Antrian Approval Komplain — Fit Motor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Antrian Approval Komplain — <?= htmlspecialchars($kode_cabang_aktif ?? '-') ?></h4>

  <?php if ($bolehApproveRework): ?>
  <h6 class="mt-4">Rework — Menunggu Keputusan</h6>
  <table class="table table-bordered bg-white">
    <thead><tr><th>No Komplain</th><th>Nopol</th><th>Usulan</th><th>Aging (hari)</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php if (empty($rework)): ?><tr><td colspan="5" class="text-center text-muted">Kosong.</td></tr><?php endif; ?>
    <?php foreach ($rework as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['no_komplain']) ?></td>
        <td><?= htmlspecialchars($k['nopol']) ?></td>
        <td><?= htmlspecialchars($k['jenis_usulan'] ?? '-') ?></td>
        <td><?= (int)$k['aging_hari'] ?></td>
        <td>
          <button class="btn btn-sm btn-success" onclick="keputusanRework(<?= (int)$k['id'] ?>,'Setuju')">Setuju</button>
          <button class="btn btn-sm btn-warning" onclick="keputusanRework(<?= (int)$k['id'] ?>,'Minta Revisi')">Minta Revisi</button>
          <button class="btn btn-sm btn-secondary" onclick="keputusanRework(<?= (int)$k['id'] ?>,'No-show')">No-show</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($bolehCloseNonRework): ?>
  <h6 class="mt-4">Non-Rework — Perlu Tindak Lanjut</h6>
  <table class="table table-bordered bg-white">
    <thead><tr><th>No Komplain</th><th>Nopol</th><th>Kategori</th><th>Aging (hari)</th><th>Tindak Lanjut</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php if (empty($nonRework)): ?><tr><td colspan="6" class="text-center text-muted">Kosong.</td></tr><?php endif; ?>
    <?php foreach ($nonRework as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['no_komplain']) ?></td>
        <td><?= htmlspecialchars($k['nopol']) ?></td>
        <td><?= htmlspecialchars($k['kode_kategori']) ?></td>
        <td><?= (int)$k['aging_hari'] ?></td>
        <td><input type="text" class="form-control form-control-sm" id="tl_<?= (int)$k['id'] ?>"></td>
        <td><button class="btn btn-sm btn-primary" onclick="closeNonRework(<?= (int)$k['id'] ?>)">Selesai</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<script>
function keputusanRework(id, keputusan) {
    var fd = new FormData();
    fd.append('komplain_id', id);
    fd.append('keputusan', keputusan);
    fetch('ajax_keputusan_rework.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) { alert(data.message); if (data.success) location.reload(); });
}
function closeNonRework(id) {
    var tl = document.getElementById('tl_' + id).value;
    var fd = new FormData();
    fd.append('komplain_id', id);
    fd.append('tindak_lanjut_penanganan', tl);
    fetch('ajax_close_nonrework.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) { alert(data.message); if (data.success) location.reload(); });
}
</script>
</body>
</html>
