<?php
require __DIR__ . '/koneksi_komplain.php';

if (!cekPermissionKomplain('komplain_input')) {
    http_response_code(403);
    die('Tidak punya akses input komplain.');
}

$stmtKategori = $koneksi_komplain->prepare("SELECT kode_kategori, nama_kategori FROM tblkomplain_kategori WHERE is_active = 'active' ORDER BY nama_kategori");
$stmtKategori->execute();
$kategoriList = $stmtKategori->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Input Komplain — Fit Motor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Input Komplain Pelanggan</h4>
  <div class="card"><div class="card-body">
    <form id="formKomplain">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nama Pelanggan</label>
          <input type="text" class="form-control" name="nama_pelanggan" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">No HP</label>
          <input type="text" class="form-control" name="no_hp" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nopol</label>
          <input type="text" class="form-control text-uppercase" name="nopol" id="nopolInput" required>
          <div id="riwayatNopol" class="form-text"></div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Kategori Komplain</label>
          <select class="form-select" name="kode_kategori" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($kategoriList as $k): ?>
            <option value="<?= htmlspecialchars($k['kode_kategori']) ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Channel Lapor</label>
          <select class="form-select" name="channel_lapor" required>
            <option value="">-- Pilih --</option>
            <option>Telepon</option>
            <option>WA</option>
            <option>Datang langsung</option>
            <option>Lainnya</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">No Service Asli <span class="text-danger">*</span></label>
          <select class="form-select" name="no_service_asli" id="noServiceAsliSelect" required>
            <option value="">-- Isi Nopol dulu --</option>
          </select>
          <div class="form-text">Service yang jadi dasar komplain ini. Wajib — dipakai kalau nanti komplain berujung REWORK (biar masuk garansi, bukan servis reguler/jemput).</div>
        </div>
        <div class="col-12">
          <label class="form-label">Detail Keluhan</label>
          <textarea class="form-control" name="detail_keluhan" rows="3" required></textarea>
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-3">Simpan Komplain</button>
      <div id="hasilSubmit" class="mt-2"></div>
    </form>
  </div></div>
</div>
<script>
document.getElementById('nopolInput').addEventListener('blur', function () {
    var nopol = this.value.trim();
    if (!nopol) return;
    fetch('ajax_cek_riwayat_nopol.php?nopol=' + encodeURIComponent(nopol))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var el = document.getElementById('riwayatNopol');
            if (!data.riwayat || data.riwayat.length === 0) {
                el.textContent = 'Belum ada riwayat komplain untuk nopol ini.';
                return;
            }
            el.innerHTML = 'Riwayat: ' + data.riwayat.map(function (r) {
                return r.no_komplain + ' (' + r.kode_kategori + ', ' + r.status + ')';
            }).join(', ');
        });

    var sel = document.getElementById('noServiceAsliSelect');
    sel.innerHTML = '<option value="">Memuat...</option>';
    fetch('ajax_cari_service_nopol.php?nopol=' + encodeURIComponent(nopol))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var list = data.servis || [];
            if (list.length === 0) {
                sel.innerHTML = '<option value="">-- Tidak ada service untuk nopol ini --</option>';
                return;
            }
            function escHtml(v) {
                return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }
            sel.innerHTML = '<option value="">-- Pilih Service --</option>' + list.map(function (s) {
                var ket = (s.keterangan || '').substring(0, 40);
                return '<option value="' + escHtml(s.no_service) + '">' + escHtml(s.no_service) + ' (' + escHtml(s.tanggal) + ') - ' + escHtml(ket) + '</option>';
            }).join('');
        });
});

document.getElementById('formKomplain').addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('ajax_submit_komplain.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var el = document.getElementById('hasilSubmit');
            if (data.success) {
                el.innerHTML = '<div class="alert alert-success">Tersimpan: ' + data.no_komplain + '</div>';
                e.target.reset();
            } else {
                el.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        });
});
</script>
</body>
</html>
