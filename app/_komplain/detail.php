<?php
require __DIR__ . '/koneksi_komplain.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $koneksi_komplain->prepare("SELECT * FROM tblkomplain WHERE id = :id");
$stmt->execute([':id' => $id]);
$komplain = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$komplain) {
    die('Komplain tidak ditemukan.');
}

$isManajemen = cekPermissionKomplain('komplain_view_all');
if (!$isManajemen && $komplain['kode_cabang'] !== $kode_cabang_aktif) {
    http_response_code(403);
    die('Tidak punya akses ke data cabang lain.');
}

$stmtLog = $koneksi_komplain->prepare("SELECT * FROM tblkomplain_log WHERE komplain_id = :id ORDER BY created_at ASC");
$stmtLog->execute([':id' => $id]);
$logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Komplain <?= htmlspecialchars($komplain['no_komplain']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4>Detail Komplain <?= htmlspecialchars($komplain['no_komplain']) ?></h4>
  <div class="card mb-3"><div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Pelanggan</dt><dd class="col-sm-9"><?= htmlspecialchars($komplain['nama_pelanggan']) ?> (<?= htmlspecialchars($komplain['no_hp']) ?>)</dd>
      <dt class="col-sm-3">Nopol</dt><dd class="col-sm-9"><?= htmlspecialchars($komplain['nopol']) ?></dd>
      <dt class="col-sm-3">Cabang</dt><dd class="col-sm-9"><?= htmlspecialchars($komplain['kode_cabang']) ?></dd>
      <dt class="col-sm-3">Kategori</dt><dd class="col-sm-9"><?= htmlspecialchars($komplain['kode_kategori']) ?></dd>
      <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-info"><?= htmlspecialchars($komplain['status']) ?></span></dd>
      <dt class="col-sm-3">Detail Keluhan</dt><dd class="col-sm-9"><?= nl2br(htmlspecialchars($komplain['detail_keluhan'])) ?></dd>
      <?php if ($komplain['jenis_usulan']): ?>
      <dt class="col-sm-3">Usulan</dt><dd class="col-sm-9"><?= htmlspecialchars($komplain['jenis_usulan']) ?> — <?= htmlspecialchars($komplain['mekanik_pelaksana_kode'] ?? $komplain['alasan_usulan'] ?? '-') ?></dd>
      <?php endif; ?>
      <?php if ($komplain['tindak_lanjut_penanganan']): ?>
      <dt class="col-sm-3">Tindak Lanjut</dt><dd class="col-sm-9"><?= nl2br(htmlspecialchars($komplain['tindak_lanjut_penanganan'])) ?></dd>
      <?php endif; ?>
    </dl>
  </div></div>

  <h6>Timeline</h6>
  <ul class="list-group">
    <?php foreach ($logs as $log): ?>
    <li class="list-group-item">
      <strong><?= htmlspecialchars($log['created_at']) ?></strong> —
      <?= htmlspecialchars($log['aksi']) ?> oleh <?= htmlspecialchars($log['kode_karyawan_pelaku']) ?>
      (<?= htmlspecialchars($log['status_sebelum'] ?? '-') ?> → <?= htmlspecialchars($log['status_sesudah']) ?>)
      <?php if ($log['keterangan']): ?><div class="text-muted small"><?= htmlspecialchars($log['keterangan']) ?></div><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
</body>
</html>
