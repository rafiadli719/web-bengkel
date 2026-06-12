<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: akun_kas.php");
    exit;
}

include "../config/koneksi.php";

$txtid = isset($_POST['txtid']) ? (int) $_POST['txtid'] : 0;
$txtkd = isset($_POST['txtkd']) ? trim($_POST['txtkd']) : '';
$txtnama = isset($_POST['txtnama']) ? trim($_POST['txtnama']) : '';

if ($txtid <= 0 || $txtkd === '' || $txtnama === '') {
    echo "<script>window.alert('Data Akun Kas tidak valid.');window.location=('akun_kas.php');</script>";
    exit;
}

$stmt = mysqli_prepare($koneksi, "UPDATE tblakunkas SET kodeakun = ?, namaakun = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ssi", $txtkd, $txtnama, $txtid);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "<script>window.alert('" . ($success ? "Data Akun Kas Berhasil disimpan!" : "Gagal menyimpan Data Akun Kas!") . "');window.location=('akun_kas.php');</script>";
exit;
?>
