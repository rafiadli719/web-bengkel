<?php
	include "../config/koneksi.php";
	
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"INSERT INTO tbkeping 
							(keping) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Nominal Rupiah Berhasil disimpan!');
    window.location=('keping.php');</script>";
?>