<?php
require __DIR__ . '/koneksi_komplain.php';

if (!cekPermissionKomplain('komplain_dashboard')) {
    http_response_code(403);
    die('Tidak punya akses dashboard komplain.');
}

$perCabangKategori = $koneksi_komplain->query(
    "SELECT kode_cabang, kode_kategori, COUNT(*) AS jumlah FROM tblkomplain GROUP BY kode_cabang, kode_kategori"
)->fetchAll(PDO::FETCH_ASSOC);

$resolusiCabang = $koneksi_komplain->query(
    "SELECT kode_cabang,
       SUM(status IN ('Selesai','Ditutup - Ditolak')) / COUNT(*) AS resolution_rate,
       AVG(DATEDIFF(updated_at, tanggal_lapor)) AS rata_rata_hari
     FROM tblkomplain GROUP BY kode_cabang"
)->fetchAll(PDO::FETCH_ASSOC);

$approvalRatePic = $koneksi_komplain->query(
    "SELECT pic_kode_karyawan,
       SUM(keputusan_kepala_cabang = 'Setuju') / NULLIF(COUNT(keputusan_kepala_cabang), 0) AS approval_rate
     FROM tblkomplain WHERE keputusan_kepala_cabang IS NOT NULL GROUP BY pic_kode_karyawan"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Komplain Manajemen — Fit Motor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Dashboard Komplain — Lintas Cabang</h4>

  <h6>Jumlah Komplain per Cabang & Kategori</h6>
  <table class="table table-bordered bg-white mb-4">
    <thead><tr><th>Cabang</th><th>Kategori</th><th>Jumlah</th></tr></thead>
    <tbody>
    <?php if (empty($perCabangKategori)): ?><tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
    <?php foreach ($perCabangKategori as $r): ?>
      <tr><td><?= htmlspecialchars($r['kode_cabang']) ?></td><td><?= htmlspecialchars($r['kode_kategori']) ?></td><td><?= (int)$r['jumlah'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h6>Resolution Rate & Rata-rata Hari Penyelesaian per Cabang</h6>
  <table class="table table-bordered bg-white mb-4">
    <thead><tr><th>Cabang</th><th>Resolution Rate</th><th>Rata-rata Hari</th></tr></thead>
    <tbody>
    <?php if (empty($resolusiCabang)): ?><tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
    <?php foreach ($resolusiCabang as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['kode_cabang']) ?></td>
        <td><?= number_format(((float)$r['resolution_rate']) * 100, 1) ?>%</td>
        <td><?= $r['rata_rata_hari'] !== null ? number_format((float)$r['rata_rata_hari'], 1) : '-' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h6>Approval Rate per PIC Kepala Cabang</h6>
  <table class="table table-bordered bg-white">
    <thead><tr><th>Kode Karyawan (PIC)</th><th>Approval Rate</th></tr></thead>
    <tbody>
    <?php if (empty($approvalRatePic)): ?><tr><td colspan="2" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
    <?php foreach ($approvalRatePic as $r): ?>
      <tr><td><?= htmlspecialchars($r['pic_kode_karyawan']) ?></td><td><?= number_format(((float)$r['approval_rate']) * 100, 1) ?>%</td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
