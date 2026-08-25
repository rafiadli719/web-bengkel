<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbpabrik_motor 
							SET merek='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Pabrik Motor Berhasil disimpan!');
    window.location=('motor_pabrik.php');</script>";
?>