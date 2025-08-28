<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbstatus_harga WHERE id='$txtid'");

	echo"<script>window.alert('Data Status Harga Jual Berhasil dihapus!');window.location=('status_harga.php');</script>";
?>