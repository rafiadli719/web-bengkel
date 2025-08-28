<?php
	include "../config/koneksi.php";
	
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbstatus_harga 
							(status) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Status Harga Jual Berhasil disimpan!');window.location=('status_harga.php');</script>";
?>