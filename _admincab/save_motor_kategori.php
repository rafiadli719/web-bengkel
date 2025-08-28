<?php
	include "../config/koneksi.php";
	
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbkategori_motor 
							(kategori) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Kategori Motor Berhasil disimpan!');
    window.location=('motor_kategori.php');</script>";
?>