<?php
	include "../config/koneksi.php";

	$txtid= $_POST['txtid'];
	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
				
	mysqli_query($koneksi,"UPDATE tbemp_tunjangan 
							SET nama_tunjangan='$txtfld1', nilai_tunjangan='$txtfld2' 
							WHERE id='$txtid'");
	
	$next = 'location:pegawai_jabatan.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>