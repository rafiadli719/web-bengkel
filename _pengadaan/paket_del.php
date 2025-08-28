<?php
	include "../config/koneksi.php";

	$txtid = $_GET['kd'];
	$modal=mysqli_query($koneksi,"Update 
                                    tbworkorderheader 
                                    SET status='1' 
                                    WHERE kode_wo='$txtid'");

	echo"<script>window.alert('Data Work Order Berhasil dihapus!');
    window.location=('paket.php');</script>";
?>