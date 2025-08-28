<?php
	include "../config/koneksi.php";
	
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbmekanik_level 
							(mekanik_level) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Level Mekanik Berhasil disimpan!');window.location=('mekanik_level.php');</script>";
?>