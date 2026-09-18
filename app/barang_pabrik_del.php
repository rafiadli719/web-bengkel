<?php
	session_start();
	if (empty($_SESSION['_iduser'])) {
		header("location:../index.php");
		exit;
	}
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbpabrik_barang WHERE id='$txtid'");

	echo"<script>window.alert('Data Pabrik Barang Berhasil dihapus!');window.location=('barang_pabrik.php');</script>";
?>