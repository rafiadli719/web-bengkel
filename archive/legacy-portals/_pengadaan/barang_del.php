<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblitem WHERE noitem='$txtid'");

	echo"<script>window.alert('Data Barang Berhasil dihapus!');window.location=('barang.php');</script>";
?>