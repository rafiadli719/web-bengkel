<?php
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbrakbarang WHERE id='$txtid'");

	echo"<script>window.alert('Data Rak Barang Berhasil dihapus!');window.location=('barang_rak.php');</script>";
?>