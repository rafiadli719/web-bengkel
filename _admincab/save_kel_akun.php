<?php
	include "../config/koneksi.php";
	    
	$txtkdakun= $_POST['txtkdakun'];
    $txtnamaakun= $_POST['txtnamaakun'];
    
	mysqli_query($koneksi,"INSERT INTO tbakun 
                            (no_akun, nama_akun, pos, status_akun, akun_induk, no_akun_induk) 
                            VALUES 
                            ('$txtkdakun','$txtnamaakun','','0','YA','')");
								
	echo"<script>window.alert('Data Kelompok Akun Berhasil disimpan!');
    window.location=('akun_biaya.php');</script>";
?>