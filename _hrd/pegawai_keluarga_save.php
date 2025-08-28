<?php
	include "../config/koneksi.php";

	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
	$txtfld3= $_POST['txtfld3'];
	$txtfld4= $_POST['txtfld4'];	
	$cbojk= $_POST['cbojk'];	
				
	mysqli_query($koneksi,"INSERT INTO tbemp_family (nip, nama, id_jk, alamat, notlp, hubungan) VALUES ('$txtnip','$txtfld1','$cbojk','$txtfld2','$txtfld3','$txtfld4')");
	
	$next = 'location:pegawai_keluarga.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>