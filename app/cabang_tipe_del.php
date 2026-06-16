<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbcabang_tipe WHERE id='$txtid'");

	echo"<script>window.alert('Data Tipe Cabang Berhasil dihapus!');window.location=('cabang_tipe.php');</script>";
?>