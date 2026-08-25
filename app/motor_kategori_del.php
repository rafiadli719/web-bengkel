<?php
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbkategori_motor WHERE id='$txtid'");

	echo"<script>window.alert('Data Kategori Motor Berhasil dihapus!');
    window.location=('motor_kategori.php');</script>";
?>