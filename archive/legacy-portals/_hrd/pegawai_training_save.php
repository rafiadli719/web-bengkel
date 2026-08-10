<?php
	include "../config/koneksi.php";

	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
	$txtfld3= $_POST['txtfld3'];			
				
	mysqli_query($koneksi,"INSERT INTO tbemp_training 
							(nip, fld1, fld2, fld3) 
							VALUES 
							('$txtnip','$txtfld1','$txtfld2','$txtfld3')");
	
	$next = 'location:pegawai_training.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>