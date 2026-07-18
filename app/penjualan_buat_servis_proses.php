<?php
session_start();
if (empty($_SESSION['_iduser']) || empty($_SESSION['_cabang'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = (int) $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
$notransaksi = isset($_POST['notransaksi']) ? trim($_POST['notransaksi']) : '';
$nopol = isset($_POST['nopol']) ? trim($_POST['nopol']) : '';

if ($notransaksi === '' || $nopol === '') {
    echo "<script>window.alert('Data tidak lengkap.');window.location=('penjualan.php');</script>";
    exit;
}

include "../config/koneksi.php";
include "helper-functions.php";

$hasil = buatServisDariPenjualan($koneksi, $nopol, $notransaksi, $kd_cabang, $id_user);

if (!$hasil['ok']) {
    $msg = addslashes($hasil['message']);
    if (!empty($hasil['no_service'])) {
        echo "<script>window.alert('" . $msg . "');window.location=('servis-input-router.php?snoserv=" . urlencode($hasil['no_service']) . "');</script>";
    } else {
        echo "<script>window.alert('" . $msg . "');window.location=('penjualan_buat_servis.php?notransaksi=" . urlencode($notransaksi) . "');</script>";
    }
    exit;
}

echo "<script>window.location=('servis-input-router.php?snoserv=" . urlencode($hasil['no_service']) . "&tab=items');</script>";
exit;
?>
