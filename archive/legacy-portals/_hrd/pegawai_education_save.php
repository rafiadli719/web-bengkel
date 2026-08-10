<?php
	include "../config/koneksi.php";

	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
	$txtfld3= $_POST['txtfld3'];
	$txtfld4= $_POST['txtfld4'];	
	$txtfld5= $_POST['txtfld5'];				
				
	mysqli_query($koneksi,"INSERT INTO tbemp_education 
							(nip, fld1, fld2, fld3, fld4, fld5) 
							VALUES 
							('$txtnip','$txtfld1','$txtfld2','$txtfld3','$txtfld4','$txtfld5')");
	
	$next = 'location:pegawai_education.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>