<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblkas_keluar_masuk 
                                WHERE kode_km='$txtid'");

	echo"<script>window.alert('Data Pengeluaran Kas Berhasil dihapus!');window.location=('kas_keluar.php');</script>";
?>