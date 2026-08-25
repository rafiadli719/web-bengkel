<?php
	include "../config/koneksi.php";
	
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
//	$txtnama= $_POST['txtnama'];
//cboprop
    
	mysqli_query($koneksi,"INSERT INTO tbl_adm 
							(kode, nama) 
							VALUES 
							('$txtkd','$txtnama')");
								
	echo"<script>window.alert('Data Kabupaten/Kota Berhasil disimpan!');
    window.location=('kab.php');</script>";
?>