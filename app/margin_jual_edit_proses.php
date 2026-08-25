<?php
	include "../config/koneksi.php";
	    
    $txtid= mysqli_real_escape_string($koneksi, $_POST['txtid']);        
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);        
	$txtmarginpersen= mysqli_real_escape_string($koneksi, $_POST['txtmarginpersen']);
	$txtmarginplus= mysqli_real_escape_string($koneksi, $_POST['txtmarginplus']);    
	$txtbulat= mysqli_real_escape_string($koneksi, $_POST['txtbulat']);
    
	mysqli_query($koneksi,"UPDATE tbhargajual 
                        SET jenis='$cbolevel', margin='$txtmarginpersen', 
                        marginplus='$txtmarginplus', bulat='$txtbulat' 
                        WHERE id='$txtid'");
								
	echo"<script>window.alert('Data Margin Harga Jual Berhasil disimpan!');
    window.location=('margin_jual.php');</script>";
?>