<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$cari_kd=mysqli_query($koneksi,"SELECT nip FROM tbemp_education WHERE id='$txtid'");
	$tm_cari=mysqli_fetch_array($cari_kd);	
	$txtnip=$tm_cari['nip'];

	$modal=mysqli_query($koneksi,"Delete FROM tbemp_education WHERE id='$txtid'");
	$next = 'location:pegawai_education.php?kd=';
	$awal = $next.$txtnip;
	header($awal);
?>