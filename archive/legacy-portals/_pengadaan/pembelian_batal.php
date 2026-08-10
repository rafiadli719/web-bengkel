<?php
	include "../config/koneksi.php";

	$suser = $_GET['suser'];
    $scabang = $_GET['scabang'];
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblpembelian_detail 
                                    WHERE 
                                    user='$suser' and kd_cabang='$scabang' and 
                                    status_trx='0'");

	echo"<script>window.location=('pembelian_add.php');</script>";
?>