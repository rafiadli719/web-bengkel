<?php
	include "../config/koneksi.php";
	
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbpabrik_barang 
							(pabrik_barang) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Pabrik Barang Berhasil disimpan!');window.location=('barang_pabrik.php');</script>";
?>