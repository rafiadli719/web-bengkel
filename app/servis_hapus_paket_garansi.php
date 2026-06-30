<?php
    include "../config/koneksi.php";

    $sid        = (int)($_GET['sid'] ?? 0);
    $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv'] ?? '');

    $chk = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='$no_service' LIMIT 1");
    $row = mysqli_fetch_assoc($chk);
    if ($row && in_array($row['status_servis'], ['bayar', 'selesai'])) {
        echo "<script>alert('Jasa tidak bisa dihapus — servis sudah " . strtoupper($row['status_servis']) . ".');window.location=('servis-garansi.php?snoserv=$no_service');</script>";
        exit;
    }

    mysqli_query($koneksi, "DELETE FROM tblservis_jasa WHERE id='$sid'");
    echo "<script>window.location=('servis-garansi.php?snoserv=$no_service');</script>";
?>
