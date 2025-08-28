<?php
	include "../config/koneksi.php";

	$txtnip= $_POST['txtnip'];
	$txtidsal= $_POST['txtidsal'];
	$txtjml= $_POST['txtjml'];
			
	mysqli_query($koneksi,"INSERT INTO tbpegawai_salary (nip, id_salary, jumlah) values ('$txtnip','$txtidsal','$txtjml')");

	$next = 'location:pegawai_gaji.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>