<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbwarna 
							SET warna='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Warna Motor Berhasil disimpan!');
    window.location=('motor_warna.php');</script>";
?>