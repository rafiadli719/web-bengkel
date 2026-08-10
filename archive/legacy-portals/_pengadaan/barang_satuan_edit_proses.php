<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tblitemsatuan 
							SET satuan='$txtkd', namasatuan='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Satuan Barang Berhasil disimpan!');window.location=('barang_satuan.php');</script>";
?>