<?php
	include "../config/koneksi.php";
	    
	$txtnama= $_POST['txtnama'];
    $cbomerek= $_POST['cbomerek'];
    $cbokat= $_POST['cbokat'];
    
	mysqli_query($koneksi,"INSERT INTO tbtipe_motor 
                        (tipe, kode_pabrik, kode_kategori) 
                        VALUES 
                        ('$txtnama','$cbomerek','$cbokat')");
								
	echo"<script>window.alert('Data Tipe Motor Berhasil disimpan!');
    window.location=('motor_tipe.php');</script>";
?>