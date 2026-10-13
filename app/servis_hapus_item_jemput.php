<?php
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (empty($_SESSION["_iduser"])) { header("location:../index.php"); exit; } // hanya user login
    include "../config/koneksi.php";

    $sid        = (int)($_GET['sid'] ?? 0);
    $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv'] ?? '');

    $chk = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='$no_service' LIMIT 1");
    $row = mysqli_fetch_assoc($chk);
    if ($row && in_array($row['status_servis'], ['bayar', 'selesai'])) {
        echo "<script>alert('Item tidak bisa dihapus — servis sudah " . strtoupper($row['status_servis']) . ".');history.back();</script>";
        exit;
    }

    mysqli_query($koneksi, "DELETE FROM tblservis_barang WHERE id='$sid'");
    echo "<script>history.back();</script>";
?>
