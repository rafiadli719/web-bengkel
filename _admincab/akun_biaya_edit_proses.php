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
$cbopos = isset($_POST['cbopos']) ? trim($_POST['cbopos']) : '';

if ($txtkdakun === '' || $txtnamaakun === '' || $cbopos === '') {
    echo "<script>window.alert('Data Sub Akun tidak valid.');window.location=('akun_biaya.php');</script>";
    exit;
}

$stmt = mysqli_prepare($koneksi, "UPDATE tbakun SET nama_akun = ?, pos = ? WHERE no_akun = ?");
mysqli_stmt_bind_param($stmt, "sss", $txtnamaakun, $cbopos, $txtkdakun);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<script>window.alert('" . ($success ? "Data Sub Akun Berhasil diupdate!" : "Gagal mengupdate Data Sub Akun!") . "');window.location=('akun_biaya.php');</script>";
exit;
?>
