<?php
session_start();
if(empty($_SESSION['_iduser'])){ header("location:../index.php"); exit; }

$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$noretur = isset($_GET['noretur']) ? mysqli_real_escape_string($koneksi, $_GET['noretur']) : '';
if($noretur == '') { header("location:retur_penjualan_rst.php?_key=&_cari="); exit; }

$cek = mysqli_query($koneksi, "SELECT status_retur FROM tblretur_penjualan_header
                                WHERE noretur='$noretur' AND kd_cabang='$kd_cabang'");
if(mysqli_num_rows($cek) == 0) { header("location:retur_penjualan_rst.php?_key=&_cari="); exit; }
$row = mysqli_fetch_assoc($cek);
if($row['status_retur'] != '0') {
    header("location:retur_penjualan_detail.php?noretur=".urlencode($noretur));
    exit;
}

mysqli_query($koneksi, "UPDATE tblretur_penjualan_header SET status_retur='1' WHERE noretur='$noretur' AND kd_cabang='$kd_cabang'");
header("location:retur_penjualan_detail.php?noretur=".urlencode($noretur)."&approved=1");
exit;
?>
