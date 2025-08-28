<?php
	include "../config/koneksi.php";
	
	$txtkd= $_POST['txtkd'];
	$txtnama= strtoupper($_POST['txtnama']);
	$txtdiskon= $_POST['txtdiskon'];

	mysqli_query($koneksi,"UPDATE tblpelanggangrup 
							SET grup='$txtnama', diskon='$txtdiskon' 
							WHERE kgrup='$txtkd'");
								
	echo"<script>window.alert('Data Kategori Pelanggan Berhasil disimpan!');
    window.location=('pelanggan_kategori.php');</script>";
?>