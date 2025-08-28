<?php
	include "../config/koneksi.php";
	
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tblitemsatuan 
							(satuan, namasatuan) 
							VALUES 
							('$txtkd','$txtnama')");
								
	echo"<script>window.alert('Data Satuan Barang Berhasil disimpan!');
    window.location=('barang_satuan.php');</script>";
?>