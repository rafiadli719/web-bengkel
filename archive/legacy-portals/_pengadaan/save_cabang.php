<?php
	include "../config/koneksi.php";
	    
	$txtkd= $_POST['txtkd'];    
	$txtnama= $_POST['txtnama'];
    $cbolevel= $_POST['cbolevel'];

    
	mysqli_query($koneksi,"INSERT INTO tbcabang 
                        (kode_cabang, nama_cabang, tipe_cabang) 
                        VALUES 
                        ('$txtkd','$txtnama','$cbolevel')");
								
	echo"<script>window.alert('Data Cabang Berhasil disimpan!');
    window.location=('cabang.php');</script>";
?>