<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbmekanik_level 
							SET mekanik_level='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Level Mekanik Berhasil disimpan!');window.location=('mekanik_level.php');</script>";
?>