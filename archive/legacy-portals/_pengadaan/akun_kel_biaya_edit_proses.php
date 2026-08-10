<?php
	include "../config/koneksi.php";
	    
	$txtkdakun= $_POST['txtkdakun'];
    $txtnamaakun= $_POST['txtnamaakun'];
    
	mysqli_query($koneksi,"UPDATE tbakun 
                            SET nama_akun='$txtnamaakun' 
                            WHERE no_akun='$txtkdakun'");
								
	echo"<script>window.alert('Data Kelompok Akun Berhasil diupdate!');
    window.location=('akun_biaya.php');</script>";
?>