<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbtipe_motor WHERE kode_tipe='$txtid'");

	echo"<script>window.alert('Data Tipe Motor Berhasil dihapus!');
    window.location=('motor_tipe.php');</script>";
?>