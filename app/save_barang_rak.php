<?php
	session_start();
	if (empty($_SESSION['_iduser'])) {
		header("location:../index.php");
		exit;
	}
	include "../config/koneksi.php";
	require_once __DIR__ . '/_include_guard_master.php'; guardMasterWrite($koneksi, 'barang_write');

	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);

	mysqli_query($koneksi,"INSERT INTO tbrakbarang 
							(rak_barang) 
							VALUES 
							('$txtnama')");
								
	echo"<script>window.alert('Data Rak Barang Berhasil disimpan!');window.location=('barang_rak.php');</script>";
?>