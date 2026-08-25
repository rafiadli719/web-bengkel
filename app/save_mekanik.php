<?php
	include "../config/koneksi.php";
	    
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
	$txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat']);    
	$txtkota= mysqli_real_escape_string($koneksi, $_POST['txtkota']);
	$txtprop= mysqli_real_escape_string($koneksi, $_POST['txtprop']);
	$txttlp= mysqli_real_escape_string($koneksi, $_POST['txttlp']); 
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);
	$txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);    
    
	mysqli_query($koneksi,"INSERT INTO tblmekanik 
                        (nomekanik, nama, alamat, kota, provinsi, notelepon, note, keahlian) 
                        VALUES 
                        ('$txtkd','$txtnama','$txtalamat','$txtkota','$txtprop','$txttlp',
                        '$txtnote','$cbolevel')");
								
	echo"<script>window.alert('Data Mekanik Berhasil disimpan!');
    window.location=('mekanik.php');</script>";
?>