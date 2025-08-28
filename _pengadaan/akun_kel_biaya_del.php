<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Update 
                                    tbakun 
                                    SET status_akun='1' 
                                    WHERE no_akun='$txtid'");

	echo"<script>window.alert('Data Kelompok Akun Berhasil dihapus!');window.location=('akun_biaya.php');</script>";
?>