<?php
	include "../config/koneksi.php";
	
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tblitemjenis 
							(jenis, namajenis) 
							VALUES 
							('$txtkd','$txtnama')");
								
	echo"<script>window.alert('Data Kategori Barang Berhasil disimpan!');window.location=('barang_kategori.php');</script>";
?>