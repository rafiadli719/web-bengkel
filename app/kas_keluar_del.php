<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } // kd_cabang dari sesi (Fase K)
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tblkas_keluar_masuk 
                                WHERE kode_km='$txtid' AND kd_cabang='{$_SESSION['_cabang']}'");

	echo"<script>window.alert('Data Pengeluaran Kas Berhasil dihapus!');window.location=('kas_keluar.php');</script>";
?>