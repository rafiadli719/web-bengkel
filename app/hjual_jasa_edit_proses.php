<?php
	include "../config/koneksi.php";
	
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbhjual_jasa 
							SET nilai='$txtnama' 
                            WHERE jasa='$txtkd'");
								
	echo"<script>window.alert('Data Harga Jual Plus Jasa Berhasil diupdate!');
    window.location=('hjual_jasa.php');</script>";
?>