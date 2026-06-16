<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblsales WHERE nosales='$txtid'");

	echo"<script>window.alert('Data Sales Berhasil dihapus!');window.location=('sales.php');</script>";
?>