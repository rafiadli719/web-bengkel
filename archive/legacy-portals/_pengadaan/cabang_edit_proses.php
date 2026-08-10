<?php
	include "../config/koneksi.php";
	    
	$txtid= $_POST['txtid'];
	$txtnama= $_POST['txtnama'];
    $cbolevel= $_POST['cbolevel'];

	mysqli_query($koneksi,"UPDATE tbcabang 
                        SET nama_cabang='$txtnama', tipe_cabang='$cbolevel' 
                        WHERE kode_cabang='$txtid'");
								
	echo"<script>window.alert('Data Cabang Berhasil disimpan!');
    window.location=('cabang.php');</script>";
?>