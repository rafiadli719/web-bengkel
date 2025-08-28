<?php
	include "../config/koneksi.php";
	
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
    
	mysqli_query($koneksi,"INSERT INTO tbl_adm 
							(kode, nama) 
							VALUES 
							('$txtkd','$txtnama')");
								
	echo"<script>window.alert('Data Propinsi Berhasil disimpan!');
    window.location=('prop.php');</script>";
?>