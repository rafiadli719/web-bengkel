<?php
	include "../config/koneksi.php";
	    
	$id= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];
    $cbomerek= $_POST['cbomerek'];
    $cbokat= $_POST['cbokat'];
    
	mysqli_query($koneksi,"UPDATE tbtipe_motor 
                        SET tipe='$txtnama', kode_pabrik='$cbomerek', 
                        kode_kategori='$cbokat' 
                        WHERE kode_tipe='$id'");
								
	echo"<script>window.alert('Data Tipe Motor Berhasil disimpan!');
    window.location=('motor_tipe.php');</script>";
?>