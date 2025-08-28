<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tbwarna 
							SET warna='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Warna Motor Berhasil disimpan!');
    window.location=('motor_warna.php');</script>";
?>