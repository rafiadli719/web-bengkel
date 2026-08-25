<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbstatus_harga 
							SET status='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Status Harga Jual Berhasil disimpan!');window.location=('status_harga.php');</script>";
?>