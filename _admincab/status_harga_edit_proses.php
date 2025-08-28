<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tbstatus_harga 
							SET status='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Status Harga Jual Berhasil disimpan!');window.location=('status_harga.php');</script>";
?>