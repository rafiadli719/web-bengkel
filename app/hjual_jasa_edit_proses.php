<?php
	include "../config/koneksi.php";
	
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];

	mysqli_query($koneksi,"UPDATE tbhjual_jasa 
							SET nilai='$txtnama' 
                            WHERE jasa='$txtkd'");
								
	echo"<script>window.alert('Data Harga Jual Plus Jasa Berhasil diupdate!');
    window.location=('hjual_jasa.php');</script>";
?>