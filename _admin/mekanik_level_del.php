<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbmekanik_level WHERE id='$txtid'");

	echo"<script>window.alert('Data Level Mekanik Berhasil dihapus!');window.location=('mekanik_level.php');</script>";
?>