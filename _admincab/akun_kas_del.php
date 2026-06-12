<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/koneksi.php";

$txtid = isset($_GET['kd']) ? (int) $_GET['kd'] : 0;
if ($txtid <= 0) {
    echo "<script>window.alert('Data akun kas tidak valid.');window.location=('akun_kas.php');</script>";
    exit;
}

$stmt = mysqli_prepare($koneksi, "DELETE FROM tblakunkas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $txtid);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<script>window.alert('" . ($success ? "Data Akun Kas Berhasil dihapus!" : "Gagal menghapus Data Akun Kas!") . "');window.location=('akun_kas.php');</script>";
exit;
?>
