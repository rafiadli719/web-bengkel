<?php
	include "../config/koneksi.php";

	$txtid= $_POST['txtid'];
	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
	$txtfld3= $_POST['txtfld3'];
	$txtfld4= $_POST['txtfld4'];	
	$cbojk= $_POST['cbojk'];	
				
	mysqli_query($koneksi,"UPDATE tbemp_family SET 
							nama='$txtfld1', id_jk='$cbojk', alamat='$txtfld2', notlp='$txtfld3', hubungan='$txtfld4' 
							where id='$txtid'");

	
	$next = 'location:pegawai_keluarga.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>