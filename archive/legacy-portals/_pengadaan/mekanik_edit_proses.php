<?php
	include "../config/koneksi.php";
	    
	$txtid= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];
	$txtalamat= $_POST['txtalamat'];    
	$txtkota= $_POST['txtkota'];
	$txtprop= $_POST['txtprop'];
	$txttlp= $_POST['txttlp']; 
    $cbolevel= $_POST['cbolevel'];
	$txtnote= $_POST['txtnote'];    
    
	mysqli_query($koneksi,"UPDATE tblmekanik 
                        SET nama='$txtnama', alamat='$txtalamat', kota='$txtkota', 
                        provinsi='$txtprop', notelepon='$txttlp', note='$txtnote', 
                        keahlian='$cbolevel' 
                        WHERE nomekanik='$txtid'");
								
	echo"<script>window.alert('Data Mekanik Berhasil disimpan!');
    window.location=('mekanik.php');</script>";
?>