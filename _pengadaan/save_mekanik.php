<?php
	include "../config/koneksi.php";
	    
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
	$txtalamat= $_POST['txtalamat'];    
	$txtkota= $_POST['txtkota'];
	$txtprop= $_POST['txtprop'];
	$txttlp= $_POST['txttlp']; 
    $cbolevel= $_POST['cbolevel'];
	$txtnote= $_POST['txtnote'];    
    
	mysqli_query($koneksi,"INSERT INTO tblmekanik 
                        (nomekanik, nama, alamat, kota, provinsi, notelepon, note, keahlian) 
                        VALUES 
                        ('$txtkd','$txtnama','$txtalamat','$txtkota','$txtprop','$txttlp',
                        '$txtnote','$cbolevel')");
								
	echo"<script>window.alert('Data Mekanik Berhasil disimpan!');
    window.location=('mekanik.php');</script>";
?>