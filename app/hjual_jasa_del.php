<?php
	include "../config/koneksi.php";

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbhjual_jasa WHERE jasa='$txtid'");

	echo"<script>window.alert('Data Harga Jual Plus Jasa Berhasil dihapus!');
    window.location=('hjual_jasa.php');</script>";
?>