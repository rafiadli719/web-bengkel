<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblkendaraan WHERE nopolisi='$txtid'");

	echo"<script>window.alert('Data Kendaraan Berhasil dihapus!');window.location=('kendaraan.php');</script>";
?>