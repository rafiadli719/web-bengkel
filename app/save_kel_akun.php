<?php
	include "../config/koneksi.php";
	    
	$txtkdakun= mysqli_real_escape_string($koneksi, $_POST['txtkdakun']);
    $txtnamaakun= mysqli_real_escape_string($koneksi, $_POST['txtnamaakun']);
    
	mysqli_query($koneksi,"INSERT INTO tbakun 
                            (no_akun, nama_akun, pos, status_akun, akun_induk, no_akun_induk) 
                            VALUES 
                            ('$txtkdakun','$txtnamaakun','','0','YA','')");
								
	echo"<script>window.alert('Data Kelompok Akun Berhasil disimpan!');
    window.location=('akun_biaya.php');</script>";
?>