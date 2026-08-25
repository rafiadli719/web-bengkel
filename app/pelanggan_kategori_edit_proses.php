<?php
	include "../config/koneksi.php";
	
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
	$txtdiskon= mysqli_real_escape_string($koneksi, $_POST['txtdiskon']);

	mysqli_query($koneksi,"UPDATE tblpelanggangrup 
							SET grup='$txtnama', diskon='$txtdiskon' 
							WHERE kgrup='$txtkd'");
								
	echo"<script>window.alert('Data Kategori Pelanggan Berhasil disimpan!');
    window.location=('pelanggan_kategori.php');</script>";
?>