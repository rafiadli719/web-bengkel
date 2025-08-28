<?php
	include "../config/koneksi.php";
	
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
	$txtdiskon= $_POST['txtdiskon'];

	mysqli_query($koneksi,"INSERT INTO tblpelanggangrup 
							(kgrup, grup, diskon) 
							VALUES 
							('$txtkd','$txtnama','$txtdiskon')");
								
	echo"<script>window.alert('Data Kategori Pelanggan Berhasil disimpan!');
    window.location=('pelanggan_kategori.php');</script>";
?>