<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbcabang WHERE kode_cabang='$txtid'");

	echo"<script>window.alert('Data Cabang Berhasil dihapus!');window.location=('cabang.php');</script>";
?>