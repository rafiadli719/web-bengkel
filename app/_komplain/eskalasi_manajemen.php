<?php
require __DIR__ . '/koneksi_komplain.php';

if (!cekPermissionKomplain('komplain_eskalasi_keputusan')) {
    http_response_code(403);
    die('Tidak punya akses keputusan eskalasi.');
}

$stmt = $koneksi_komplain->prepare(
    "SELECT *, DATEDIFF(NOW(), tanggal_lapor) AS aging_hari FROM tblkomplain WHERE status = 'Eskalasi Manajemen' ORDER BY tanggal_lapor ASC"
);
$stmt->execute();
$daftar = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Eskalasi Manajemen — Fit Motor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Eskalasi Manajemen (Revisi ke-3)</h4>
  <table class="table table-bordered bg-white">
    <thead><tr><th>No Komplain</th><th>Cabang</th><th>Nopol</th><th>Jumlah Revisi</th><th>Aging (hari)</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php if (empty($daftar)): ?><tr><td colspan="6" class="text-center text-muted">Tidak ada kasus eskalasi.</td></tr><?php endif; ?>
    <?php foreach ($daftar as $k): ?>
      <tr>
        <td><a href="detail.php?id=<?= (int)$k['id'] ?>"><?= htmlspecialchars($k['no_komplain']) ?></a></td>
        <td><?= htmlspecialchars($k['kode_cabang']) ?></td>
        <td><?= htmlspecialchars($k['nopol']) ?></td>
        <td><?= (int)$k['jumlah_revisi'] ?></td>
        <td><?= (int)$k['aging_hari'] ?></td>
        <td>
          <button class="btn btn-sm btn-success" onclick="keputusan(<?= (int)$k['id'] ?>,'Dijadwalkan')">Dijadwalkan</button>
          <button class="btn btn-sm btn-danger" onclick="keputusan(<?= (int)$k['id'] ?>,'Ditutup - Ditolak')">Tutup - Ditolak</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
function keputusan(id, keputusanFinal) {
    var fd = new FormData();
    fd.append('komplain_id', id);
    fd.append('keputusan_final', keputusanFinal);
    fetch('ajax_keputusan_eskalasi.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) { alert(data.message); if (data.success) location.reload(); });
}
</script>
</body>
</html>
