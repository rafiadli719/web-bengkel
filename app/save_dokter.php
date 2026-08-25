<?php
	include "../config/koneksi.php";
	
	$cbodivisi= mysqli_real_escape_string($koneksi, $_POST['cbodivisi']);
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"INSERT INTO tbdokter 
							(kode_dokter, nama_dokter, kode_spesialisasi) 
							VALUES 
							('$txtkd','$txtnama','$cbodivisi')");
								
	echo"<script>window.alert('Data Dokter Berhasil disimpan!');window.location=('dokter.php');</script>";
?>