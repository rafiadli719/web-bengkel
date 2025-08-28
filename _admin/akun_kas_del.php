<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblakunkas WHERE id='$txtid'");

	echo"<script>window.alert('Data Akun Kas Berhasil dihapus!');
    window.location=('akun_kas.php');</script>";
?>