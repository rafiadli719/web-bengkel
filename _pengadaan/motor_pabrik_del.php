<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbpabrik_motor WHERE id='$txtid'");

	echo"<script>window.alert('Data Pabrik Motor Berhasil dihapus!');
    window.location=('motor_pabrik.php');</script>";
?>