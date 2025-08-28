<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Delete FROM tblsupplier WHERE nosupplier='$txtid'");
	$modal=mysqli_query($koneksi,"DELETE FROM tblsupplier_spart WHERE nosupplier='$txtid'");
    
	echo"<script>window.alert('Data Supplier Berhasil dihapus!');window.location=('supplier.php');</script>";
?>