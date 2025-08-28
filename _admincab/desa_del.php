<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tbl_adm WHERE id='$txtid'");

	echo"<script>window.alert('Data Kelurahan/Desa Berhasil dihapus!');
    window.location=('desa.php');</script>";
?>