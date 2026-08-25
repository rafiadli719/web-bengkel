<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbkeping 
							SET keping='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Nominal Rupiah Berhasil disimpan!');
    window.location=('keping.php');</script>";
?>