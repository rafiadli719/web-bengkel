<?php
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbkeping WHERE id='$txtid'");

	echo"<script>window.alert('Data Nominal Rupiah Berhasil dihapus!');
    window.location=('keping.php');</script>";
?>