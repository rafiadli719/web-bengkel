<?php
	include "../config/koneksi.php";
	
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbrakbarang 
							(rak_barang) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Rak Barang Berhasil disimpan!');window.location=('barang_rak.php');</script>";
?>