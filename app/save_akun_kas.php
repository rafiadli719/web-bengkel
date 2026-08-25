<?php
	include "../config/koneksi.php";
	
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    
	mysqli_query($koneksi,"INSERT INTO tblakunkas 
							(kodeakun, namaakun) 
							VALUES 
							('$txtkd','$txtnama')");
								
	echo"<script>window.alert('Data Akun Kas Berhasil disimpan!');window.location=('akun_kas.php');</script>";
?>