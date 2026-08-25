<?php
	include "../config/koneksi.php";
	
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"INSERT INTO tbcabang_tipe 
							(cabang_tipe) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Tipe Cabang Berhasil disimpan!');window.location=('cabang_tipe.php');</script>";
?>