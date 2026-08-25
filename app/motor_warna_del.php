<?php
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbwarna WHERE id='$txtid'");

	echo"<script>window.alert('Data Warna Motor Berhasil dihapus!');
    window.location=('motor_warna.php');</script>";
?>