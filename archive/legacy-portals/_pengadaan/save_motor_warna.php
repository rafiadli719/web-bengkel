<?php
	include "../config/koneksi.php";
	
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbwarna 
							(warna) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Warna Motor Berhasil disimpan!');
    window.location=('motor_warna.php');</script>";
?>