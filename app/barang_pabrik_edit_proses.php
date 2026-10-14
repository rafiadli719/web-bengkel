<?php
	include "../config/koneksi.php";
	require_once __DIR__ . '/_include_guard_master.php'; guardMasterWrite($koneksi, 'barang_write');
	
	$id= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"UPDATE tbpabrik_barang 
							SET pabrik_barang='$txtnama' 
							WHERE 
							id='$id'");
								
	echo"<script>window.alert('Data Pabrik Barang Berhasil disimpan!');window.location=('barang_pabrik.php');</script>";
?>