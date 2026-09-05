<?php
// Sumber: web_kasir/hapus_pemasukan1.php — hapus 1 baris pemasukan milik
// kasir sendiri (guard kode_karyawan match). Gerbang asli cuma cek session
// login (semua role) -> kasir_operate (Task 10, sama kayak pemasukan.php).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$kode_karyawan = $kode_karyawan_aktif;

// Get ID from URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die("ID pemasukan tidak ditemukan.");
}

$sql = "SELECT * FROM pemasukan_kasir_closing_kasir WHERE id = :id AND kode_karyawan = :kode_karyawan";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
$stmt->execute();
$pemasukan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pemasukan) {
    die("Data pemasukan tidak ditemukan.");
}

// Assign the kode_transaksi from the fetched data
$kode_transaksi = $pemasukan['kode_transaksi'];

// Delete the pemasukan data based on ID and kode_karyawan
$sql = "DELETE FROM pemasukan_kasir_closing_kasir WHERE id = :id AND kode_karyawan = :kode_karyawan";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);

if ($stmt->execute()) {
    echo "<script>alert('Data pemasukan berhasil dihapus.'); window.location.href='edit_pemasukan1.php?kode_transaksi=$kode_transaksi';</script>";
} else {
    echo "<script>alert('Terjadi kesalahan saat menghapus data.'); window.location.href='index_kasir.php';</script>";
}
