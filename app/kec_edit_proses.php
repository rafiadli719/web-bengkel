<?php
	include "../config/koneksi.php";
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    
	mysqli_query($koneksi,"UPDATE tbl_adm 
							SET kode='$txtkd', nama='$txtnama' 
                            WHERE id='$id'");
                            								
	echo"<script>window.alert('Data Kecamatan Berhasil disimpan!');
    window.location=('kec.php');</script>";
?>