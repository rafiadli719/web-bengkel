<?php
	include "../config/koneksi.php";
	    
    $cbolevel= $_POST['cbolevel'];        
	$txtmarginpersen= $_POST['txtmarginpersen'];
	$txtmarginplus= $_POST['txtmarginplus'];    
	$txtbulat= $_POST['txtbulat'];
    
	mysqli_query($koneksi,"INSERT INTO tbhargajual 
                        (jenis, margin, marginplus, bulat) 
                        VALUES 
                        ('$cbolevel','$txtmarginpersen','$txtmarginplus','$txtbulat')");
								
	echo"<script>window.alert('Data Margin Harga Jual Berhasil disimpan!');
    window.location=('margin_jual.php');</script>";
?>