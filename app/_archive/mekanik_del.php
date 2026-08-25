<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblmekanik WHERE nomekanik='$txtid'");

	echo"<script>window.alert('Data Mekanik Berhasil dihapus!');window.location=('mekanik.php');</script>";
?>