<?php
	include "../config/koneksi.php";
	
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"INSERT INTO tbhjual_jasa 
							(jasa, nilai) 
							VALUES 
							('$txtkd','$txtnama')");
								
	echo"<script>window.alert('Data Harga Jual Plus Jasa Berhasil disimpan!');
    window.location=('hjual_jasa.php');</script>";
?>