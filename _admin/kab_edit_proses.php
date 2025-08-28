<?php
	include "../config/koneksi.php";

	$id= $_POST['txtid'];		
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
    
	mysqli_query($koneksi,"UPDATE tbl_adm 
							SET kode='$txtkd', nama='$txtnama' 
                            WHERE id='$id'");
                            								
	echo"<script>window.alert('Data Kabupaten/Kota Berhasil disimpan!');
    window.location=('kab.php');</script>";
?>