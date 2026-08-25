<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbkategori_motor 
							SET kategori='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Kategori Motor Berhasil disimpan!');
    window.location=('motor_kategori.php');</script>";
?>