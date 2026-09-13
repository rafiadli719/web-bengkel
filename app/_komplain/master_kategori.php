<?php
require __DIR__ . '/koneksi_komplain.php';

if (!cekPermissionKomplain('komplain_master_kategori')) {
    http_response_code(403);
    die('Tidak punya akses master kategori komplain.');
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah') {
        $stmt = $koneksi_komplain->prepare(
            "INSERT INTO tblkomplain_kategori (kode_kategori, nama_kategori, pic_role, jenis_penyelesaian) VALUES (:kode, :nama, :pic, :jenis)"
        );
        $stmt->execute([
            ':kode' => trim($_POST['kode_kategori'] ?? ''),
            ':nama' => trim($_POST['nama_kategori'] ?? ''),
            ':pic' => $_POST['pic_role'] ?? 'KEPALA_CABANG',
            ':jenis' => trim($_POST['jenis_penyelesaian'] ?? ''),
        ]);
        $pesan = 'Kategori ditambahkan.';
    } elseif ($aksi === 'edit') {
        $stmt = $koneksi_komplain->prepare(
            "UPDATE tblkomplain_kategori SET nama_kategori = :nama, pic_role = :pic, jenis_penyelesaian = :jenis WHERE id = :id"
        );
        $stmt->execute([
            ':nama' => trim($_POST['nama_kategori'] ?? ''),
            ':pic' => $_POST['pic_role'] ?? 'KEPALA_CABANG',
            ':jenis' => trim($_POST['jenis_penyelesaian'] ?? ''),
            ':id' => (int)($_POST['id'] ?? 0),
        ]);
        $pesan = 'Kategori diperbarui.';
    } elseif ($aksi === 'nonaktifkan') {
        $stmt = $koneksi_komplain->prepare("UPDATE tblkomplain_kategori SET is_active = 'inactive' WHERE id = :id");
        $stmt->execute([':id' => (int)($_POST['id'] ?? 0)]);
        $pesan = 'Kategori dinonaktifkan.';
    } elseif ($aksi === 'aktifkan') {
        $stmt = $koneksi_komplain->prepare("UPDATE tblkomplain_kategori SET is_active = 'active' WHERE id = :id");
        $stmt->execute([':id' => (int)($_POST['id'] ?? 0)]);
        $pesan = 'Kategori diaktifkan kembali.';
    }
}

$list = $koneksi_komplain->query("SELECT * FROM tblkomplain_kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Master Kategori Komplain — Fit Motor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Master Kategori Komplain</h4>
  <?php if ($pesan): ?><div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>

  <div class="card mb-4"><div class="card-body">
    <h6>Tambah Kategori</h6>
    <form method="post" class="row g-2">
      <input type="hidden" name="aksi" value="tambah">
      <div class="col-md-2"><input class="form-control" name="kode_kategori" placeholder="Kode (mis. LAINNYA2)" required></div>
      <div class="col-md-3"><input class="form-control" name="nama_kategori" placeholder="Nama Kategori" required></div>
      <div class="col-md-3">
        <select class="form-select" name="pic_role">
          <option value="KEPALA_MEKANIK">Kepala Mekanik</option>
          <option value="KEPALA_CABANG">Kepala Cabang</option>
        </select>
      </div>
      <div class="col-md-3"><input class="form-control" name="jenis_penyelesaian" placeholder="Jenis Penyelesaian" required></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Tambah</button></div>
    </form>
  </div></div>

  <table class="table table-bordered bg-white">
    <thead><tr><th>Kode</th><th>Nama</th><th>PIC Role</th><th>Jenis Penyelesaian</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($list as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['kode_kategori']) ?></td>
        <td><?= htmlspecialchars($k['nama_kategori']) ?></td>
        <td><?= htmlspecialchars($k['pic_role']) ?></td>
        <td><?= htmlspecialchars($k['jenis_penyelesaian']) ?></td>
        <td><?= htmlspecialchars($k['is_active']) ?></td>
        <td>
          <form method="post" class="d-inline">
            <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
            <input type="hidden" name="aksi" value="<?= $k['is_active'] === 'active' ? 'nonaktifkan' : 'aktifkan' ?>">
            <button class="btn btn-sm <?= $k['is_active'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
              <?= $k['is_active'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
