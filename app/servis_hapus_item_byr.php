<?php
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (empty($_SESSION["_iduser"])) { header("location:../index.php"); exit; } // hanya user login
	include "../config/koneksi.php";

        $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
        $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv']);
    $chk_sv = mysqli_query($koneksi, "SELECT s.status_servis FROM tblservis_barang b JOIN tblservice s ON s.no_service=b.no_service AND s.kd_cabang=b.kd_cabang WHERE b.id='$sid' LIMIT 1");
    $row_sv = $chk_sv ? mysqli_fetch_assoc($chk_sv) : null;
    if ($row_sv && in_array($row_sv['status_servis'], ['bayar', 'selesai'])) {
        echo "<script>alert('Item tidak bisa dihapus: servis sudah " . strtoupper($row_sv['status_servis']) . ".');history.back();</script>";
        exit;
    }

    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_barang 
                                    WHERE 
                                    id='$sid'");

            $kdbrg="";
            $kdjasa="";
            echo"<script>window.location=('servis-reguler-byr.php?snoserv=$no_service&kd=$kdbrg&kdjasa=$kdjasa');</script>";        
?>