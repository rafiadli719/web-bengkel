<?php
	include "../config/koneksi.php";

	$txtid= $_POST['txtid'];
	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
	$txtfld3= $_POST['txtfld3'];
	
	mysqli_query($koneksi,"UPDATE tbemp_training 
							SET fld1='$txtfld1', fld2='$txtfld2', fld3='$txtfld3' WHERE id='$txtid'");
	
	$next = 'location:pegawai_training.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>