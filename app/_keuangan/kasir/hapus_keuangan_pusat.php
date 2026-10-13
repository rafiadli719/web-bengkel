<?php
// Hapus satu transaksi keuangan pusat (pemasukan/pengeluaran pusat). Dipanggil dari tombol hapus
// di keuangan_pusat.php (sudah ada konfirmasi JS). Izin sama dengan halaman asal: kasir_admin.
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');
require_once __DIR__ . '/includes/keuangan_pusat_helpers.php';

$id    = (int)($_GET['id'] ?? 0);
$tabel = kpTabel($_GET['jenis'] ?? '');
if ($id <= 0 || $tabel === null) {
    die('Parameter tidak valid.');
}
$stmt = mysqli_prepare($koneksi, "DELETE FROM `$tabel` WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$hapus = mysqli_stmt_affected_rows($stmt);
header('Location: keuangan_pusat.php?tab=history' . ($hapus > 0 ? '&msg=hapus_ok' : '&msg=hapus_gagal'));
exit;
