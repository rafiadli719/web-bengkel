<?php
	include "../config/koneksi.php";
	    
    $txtid= $_POST['txtid'];        
    $cbolevel= $_POST['cbolevel'];        
	$txtmarginpersen= $_POST['txtmarginpersen'];
	$txtmarginplus= $_POST['txtmarginplus'];    
	$txtbulat= $_POST['txtbulat'];
    
	mysqli_query($koneksi,"UPDATE tbhargajual 
                        SET jenis='$cbolevel', margin='$txtmarginpersen', 
                        marginplus='$txtmarginplus', bulat='$txtbulat' 
                        WHERE id='$txtid'");
								
	echo"<script>window.alert('Data Margin Harga Jual Berhasil disimpan!');
    window.location=('margin_jual.php');</script>";
?>