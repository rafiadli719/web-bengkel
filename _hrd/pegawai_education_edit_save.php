<?php
	include "../config/koneksi.php";

	$txtid= $_POST['txtid'];
	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
	$txtfld3= $_POST['txtfld3'];
	$txtfld4= $_POST['txtfld4'];	
	$txtfld5= $_POST['txtfld5'];				
				
	mysqli_query($koneksi,"UPDATE tbemp_education 
							SET fld1='$txtfld1', fld2='$txtfld2', fld3='$txtfld3', fld4='$txtfld4', fld5='$txtfld5' 
							WHERE id='$txtid'");
	
	$next = 'location:pegawai_education.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>