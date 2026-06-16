<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tbcabang_tipe 
							SET cabang_tipe='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Tipe Cabang Berhasil disimpan!');window.location=('cabang_tipe.php');</script>";
?>