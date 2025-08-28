<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblitemsatuan WHERE id='$txtid'");

	echo"<script>window.alert('Data Satuan Barang Berhasil dihapus!');window.location=('barang_satuan.php');</script>";
?>