<?php
	include "../config/koneksi.php";
	
	$cbodivisi= $_POST['cbodivisi'];
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"INSERT INTO tbdokter 
							(kode_dokter, nama_dokter, kode_spesialisasi) 
							VALUES 
							('$txtkd','$txtnama','$cbodivisi')");
								
	echo"<script>window.alert('Data Dokter Berhasil disimpan!');window.location=('dokter.php');</script>";
?>