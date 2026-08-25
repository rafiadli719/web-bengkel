<?php
	include "../config/koneksi.php";
	
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"INSERT INTO tbpabrik_motor 
							(merek) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data pabrik Motor Berhasil disimpan!');
    window.location=('motor_pabrik.php');</script>";
?>