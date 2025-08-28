<?php
	include "../config/koneksi.php";
	    
	$txtkdakun_induk= $_POST['txtkdakun_induk'];    
	$txtkdakun= $_POST['txtkdakun'];
    $txtnamaakun= $_POST['txtnamaakun'];
    $cbopos= $_POST['cbopos'];
    
	mysqli_query($koneksi,"INSERT INTO tbakun 
                            (no_akun, nama_akun, pos, status_akun, akun_induk, no_akun_induk) 
                            VALUES 
                            ('$txtkdakun','$txtnamaakun','$cbopos','0','TIDAK','$txtkdakun_induk')");
								
	echo"<script>window.alert('Data Sub Akun Berhasil disimpan!');
    window.location=('akun_biaya.php');</script>";
?>