<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tbrakbarang 
							SET rak_barang='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Rak Barang Berhasil disimpan!');window.location=('barang_rak.php');</script>";
?>