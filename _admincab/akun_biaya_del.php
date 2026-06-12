<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/koneksi.php";

$txtid = isset($_GET['kd']) ? trim($_GET['kd']) : '';
if ($txtid === '') {
    echo "<script>window.alert('Data sub akun tidak valid.');window.location=('akun_biaya.php');</script>";
    exit;
}

$stmt = mysqli_prepare($koneksi, "UPDATE tbakun SET status_akun = '1' WHERE no_akun = ?");
mysqli_stmt_bind_param($stmt, "s", $txtid);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<script>window.alert('" . ($success ? "Data Sub Akun Berhasil dihapus!" : "Gagal menghapus Data Sub Akun!") . "');window.location=('akun_biaya.php');</script>";
exit;
?>
