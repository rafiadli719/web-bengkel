<?php
	include "../config/koneksi.php";
	    
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $cbomerek= mysqli_real_escape_string($koneksi, $_POST['cbomerek']);
    $cbokat= mysqli_real_escape_string($koneksi, $_POST['cbokat']);
    
	mysqli_query($koneksi,"UPDATE tbtipe_motor 
                        SET tipe='$txtnama', kode_pabrik='$cbomerek', 
                        kode_kategori='$cbokat' 
                        WHERE kode_tipe='$id'");
								
	echo"<script>window.alert('Data Tipe Motor Berhasil disimpan!');
    window.location=('motor_tipe.php');</script>";
?>