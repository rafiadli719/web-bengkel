<?php
	include "../config/koneksi.php";
	    
	$txtkdakun= $_POST['txtkdakun'];
    $txtnamaakun= $_POST['txtnamaakun'];
    $cbopos= $_POST['cbopos'];
    
	mysqli_query($koneksi,"UPDATE tbakun 
                            SET nama_akun='$txtnamaakun', pos='$cbopos' 
                            WHERE no_akun='$txtkdakun'");
								
	echo"<script>window.alert('Data Sub Akun Berhasil diupdate!');
    window.location=('akun_biaya.php');</script>";
?>