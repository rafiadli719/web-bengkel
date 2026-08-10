<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbhargajual WHERE id='$txtid'");

	echo"<script>window.alert('Data Margin Harga Jual Berhasil dihapus!');
    window.location=('margin_jual.php');</script>";
?>