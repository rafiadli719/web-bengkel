<?php
	include "../config/koneksi.php";
	
	$id= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tbkeping 
							SET keping='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Nominal Rupiah Berhasil disimpan!');
    window.location=('keping.php');</script>";
?>