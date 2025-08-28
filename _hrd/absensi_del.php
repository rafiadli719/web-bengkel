<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$cari_kd=mysqli_query($koneksi,"SELECT DATE_FORMAT(tgl,'%d/%m/%Y') AS tanggal_absen FROM tbabsensi WHERE id='$txtid'");			
	$tm_cari=mysqli_fetch_array($cari_kd);
	$txttgl=$tm_cari['tanggal_absen'];
		
	$modal=mysqli_query($koneksi,"Delete FROM tbabsensi WHERE id='$txtid'");

	$next = 'location:absensi_daftar_next.php?id-date-picker-1=';
	$awal = $next.$txttgl;
	header($awal);
?>