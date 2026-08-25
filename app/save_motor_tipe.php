<?php
	include "../config/koneksi.php";
	    
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $cbomerek= mysqli_real_escape_string($koneksi, $_POST['cbomerek']);
    $cbokat= mysqli_real_escape_string($koneksi, $_POST['cbokat']);
    
	mysqli_query($koneksi,"INSERT INTO tbtipe_motor 
                        (tipe, kode_pabrik, kode_kategori) 
                        VALUES 
                        ('$txtnama','$cbomerek','$cbokat')");
								
	echo"<script>window.alert('Data Tipe Motor Berhasil disimpan!');
    window.location=('motor_tipe.php');</script>";
?>