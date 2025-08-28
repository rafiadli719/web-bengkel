<?php
	include "../config/koneksi.php";

	$txtnip= $_POST['txtnip'];
	$txtfld1= $_POST['txtfld1'];
	$txtfld2= $_POST['txtfld2'];
				
	mysqli_query($koneksi,"INSERT INTO tbemp_tunjangan 
						(nip, nama_tunjangan, nilai_tunjangan) 
						VALUES 
						('$txtnip','$txtfld1','$txtfld2')");
	
	$next = 'location:pegawai_jabatan.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>