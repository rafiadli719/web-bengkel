<?php
	session_start();
	if (empty($_SESSION['_iduser'])) {
		header("location:../index.php");
		exit;
	}
	include "../config/koneksi.php";
	require_once __DIR__ . '/_include_guard_master.php'; guardMasterWrite($koneksi, 'barang_write');

	$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);
	$modal=mysqli_query($koneksi,"Delete FROM tbrakbarang WHERE id='$txtid'");

	echo"<script>window.alert('Data Rak Barang Berhasil dihapus!');window.location=('barang_rak.php');</script>";
?>