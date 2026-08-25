<?php
	include "../config/koneksi.php";
	    
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);        
	$txtmarginpersen= mysqli_real_escape_string($koneksi, $_POST['txtmarginpersen']);
	$txtmarginplus= mysqli_real_escape_string($koneksi, $_POST['txtmarginplus']);    
	$txtbulat= mysqli_real_escape_string($koneksi, $_POST['txtbulat']);
    
	mysqli_query($koneksi,"INSERT INTO tbhargajual 
                        (jenis, margin, marginplus, bulat) 
                        VALUES 
                        ('$cbolevel','$txtmarginpersen','$txtmarginplus','$txtbulat')");
								
	echo"<script>window.alert('Data Margin Harga Jual Berhasil disimpan!');
    window.location=('margin_jual.php');</script>";
?>