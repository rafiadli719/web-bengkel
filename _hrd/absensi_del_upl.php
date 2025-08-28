<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$txtidupl = $_GET['idupl'];
    
	$cari_kd=mysqli_query($koneksi,"SELECT DATE_FORMAT(tgl,'%d/%m/%Y') AS tanggal_absen FROM tbabsensi WHERE id='$txtid'");			
	$tm_cari=mysqli_fetch_array($cari_kd);
	$txttgl=$tm_cari['tanggal_absen'];
		
	$modal=mysqli_query($koneksi,"Delete FROM tbabsensi WHERE id='$txtid'");

	$next = 'location:absensi_upload_rst_view_edit.php?kd=';
	$awal = $next.$txtidupl;
	header($awal);
?>