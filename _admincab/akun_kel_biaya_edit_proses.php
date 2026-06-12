<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: akun_biaya.php");
    exit;
}

include "../config/koneksi.php";

$txtkdakun = isset($_POST['txtkdakun']) ? trim($_POST['txtkdakun']) : '';
$txtnamaakun = isset($_POST['txtnamaakun']) ? trim($_POST['txtnamaakun']) : '';

if ($txtkdakun === '' || $txtnamaakun === '') {
    echo "<script>window.alert('Data Kelompok Akun tidak valid.');window.location=('akun_biaya.php');</script>";
    exit;
}

$stmt = mysqli_prepare($koneksi, "UPDATE tbakun SET nama_akun = ? WHERE no_akun = ?");
mysqli_stmt_bind_param($stmt, "ss", $txtnamaakun, $txtkdakun);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<script>window.alert('" . ($success ? "Data Kelompok Akun Berhasil diupdate!" : "Gagal mengupdate Data Kelompok Akun!") . "');window.location=('akun_biaya.php');</script>";
exit;
?>
