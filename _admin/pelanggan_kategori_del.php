<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblpelanggangrup WHERE kgrup='$txtid'");

	echo"<script>window.alert('Data Kategori Pelanggan Berhasil dihapus!');
    window.location=('pelanggan_kategori.php');</script>";
?>