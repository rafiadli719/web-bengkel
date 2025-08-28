<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tblitemjenis 
							SET jenis='$txtkd', namajenis='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Kategori Barang Berhasil disimpan!');window.location=('barang_kategori.php');</script>";
?>