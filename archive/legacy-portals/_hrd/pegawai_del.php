<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbpegawai WHERE nip='$txtid'");
	header('location:pegawai.php');
?>