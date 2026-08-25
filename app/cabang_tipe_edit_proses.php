<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbcabang_tipe 
							SET cabang_tipe='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Tipe Cabang Berhasil disimpan!');window.location=('cabang_tipe.php');</script>";
?>