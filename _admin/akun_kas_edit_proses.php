<?php
	include "../config/koneksi.php";
	
	$txtid= $_POST['txtid'];
    $txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
    
	mysqli_query($koneksi,"UPDATE tblakunkas 
							SET kodeakun='$txtkd', namaakun='$txtnama' 
							WHERE id='$txtid'");
								
	echo"<script>window.alert('Data Akun Kas Berhasil disimpan!');window.location=('akun_kas.php');</script>";
?>