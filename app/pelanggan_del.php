<?php
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tblpelanggan WHERE nopelanggan='$txtid'");

	echo"<script>window.alert('Data Pelanggan Berhasil dihapus!');window.location=('pelanggan.php');</script>";
?>