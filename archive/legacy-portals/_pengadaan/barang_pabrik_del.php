<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbpabrik_barang WHERE id='$txtid'");

	echo"<script>window.alert('Data Pabrik Barang Berhasil dihapus!');window.location=('barang_pabrik.php');</script>";
?>