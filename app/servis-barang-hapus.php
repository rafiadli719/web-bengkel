<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    $bid = mysqli_real_escape_string($koneksi, $_GET['bid']);
    $snoserv = mysqli_real_escape_string($koneksi, $_GET['snoserv']);
    
    $chk_sv = mysqli_query($koneksi, "SELECT s.status_servis FROM tblservis_barang b JOIN tblservice s ON s.no_service=b.no_service AND s.kd_cabang=b.kd_cabang WHERE b.id='$bid' LIMIT 1");
    $row_sv = $chk_sv ? mysqli_fetch_assoc($chk_sv) : null;
    if ($row_sv && in_array($row_sv['status_servis'], ['bayar', 'selesai'])) {
        echo "<script>alert('Item tidak bisa dihapus: servis sudah " . strtoupper($row_sv['status_servis']) . ".');history.back();</script>";
        exit;
    }

    // Delete barang item
    mysqli_query($koneksi,"DELETE FROM tblservis_barang WHERE id='$bid'");
    
    echo"<script>window.alert('Item barang berhasil dihapus!');
    window.location=('servis-input-reguler.php?snoserv=$snoserv');
    </script>";
}
?>