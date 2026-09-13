<?php
require __DIR__ . '/koneksi_komplain.php';

if (!cekPermissionKomplain('komplain_review')) {
    http_response_code(403);
    die('Tidak punya akses meninjau usulan REWORK.');
}

$stmt = $koneksi_komplain->prepare(
    "SELECT * FROM tblkomplain WHERE pic_kode_karyawan = :pic AND status IN ('Open','Eskalasi Manajemen') ORDER BY tanggal_lapor ASC"
);
$stmt->execute([':pic' => $kode_karyawan_aktif]);
$antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Antrian Rework — Fit Motor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Antrian & Usulan Rework — <?= htmlspecialchars($kode_karyawan_aktif) ?></h4>
  <table class="table table-bordered bg-white">
    <thead><tr><th>No Komplain</th><th>Nopol</th><th>Keluhan</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php if (empty($antrian)): ?>
      <tr><td colspan="5" class="text-center text-muted">Tidak ada antrian rework.</td></tr>
    <?php endif; ?>
    <?php foreach ($antrian as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['no_komplain']) ?></td>
        <td><?= htmlspecialchars($k['nopol']) ?></td>
        <td><?= htmlspecialchars($k['detail_keluhan']) ?></td>
        <td><?= htmlspecialchars($k['status']) ?></td>
        <td>
          <button class="btn btn-sm btn-primary" onclick="bukaUsulan(<?= (int)$k['id'] ?>)">Susun Usulan</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal" id="modalUsulan" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUsulan">
        <div class="modal-header"><h5 class="modal-title">Usulan Rework</h5></div>
        <div class="modal-body">
          <input type="hidden" name="komplain_id" id="komplainIdInput">
          <div class="mb-3">
            <label class="form-label">Jenis Usulan</label>
            <select class="form-select" name="jenis_usulan" id="jenisUsulan" required>
              <option value="Terima">Terima</option>
              <option value="Tolak">Tolak</option>
            </select>
          </div>
          <div class="mb-3" id="fieldTerima">
            <label class="form-label">Mekanik Pelaksana</label>
            <input type="text" class="form-control" name="mekanik_pelaksana_kode">
            <label class="form-label mt-2">Rencana Tanggal Kedatangan</label>
            <input type="date" class="form-control" name="rencana_tanggal_kedatangan">
          </div>
          <div class="mb-3" id="fieldTolak" style="display:none">
            <label class="form-label">Alasan Tolak</label>
            <textarea class="form-control" name="alasan_usulan"></textarea>
          </div>
          <div id="hasilUsulan"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="tutupModal()">Tutup</button>
          <button type="submit" class="btn btn-primary">Simpan Usulan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function bukaUsulan(id) {
    document.getElementById('komplainIdInput').value = id;
    document.getElementById('modalUsulan').style.display = 'block';
    document.getElementById('modalUsulan').classList.add('show');
}
function tutupModal() {
    document.getElementById('modalUsulan').style.display = 'none';
    document.getElementById('modalUsulan').classList.remove('show');
}
document.getElementById('jenisUsulan').addEventListener('change', function () {
    document.getElementById('fieldTerima').style.display = this.value === 'Terima' ? 'block' : 'none';
    document.getElementById('fieldTolak').style.display = this.value === 'Tolak' ? 'block' : 'none';
});
document.getElementById('formUsulan').addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('ajax_submit_usulan.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var el = document.getElementById('hasilUsulan');
            if (data.success) {
                location.reload();
            } else {
                el.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        });
});
</script>
</body>
</html>
