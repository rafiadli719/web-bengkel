<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblitemjenis WHERE id='$txtid'");

	echo"<script>window.alert('Data Kategori Barang Berhasil dihapus!');window.location=('barang_kategori.php');</script>";
?>