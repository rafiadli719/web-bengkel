<?php
	session_start();
	if (empty($_SESSION['_iduser'])) {
		header("location:../index.php");
		exit;
	}
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbhargajual WHERE id='$txtid'");

	echo"<script>window.alert('Data Margin Harga Jual Berhasil dihapus!');
    window.location=('margin_jual.php');</script>";
?>