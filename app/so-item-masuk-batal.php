<?php
	include "../config/koneksi.php";

	$suser = mysqli_real_escape_string($koneksi, $_GET['suser']);
    $scabang = mysqli_real_escape_string($koneksi, $_GET['scabang']);
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbitem_masuk_detail 
                                    WHERE 
                                    user='$suser' and kd_cabang='$scabang' and 
                                    status_trx='0'");

	echo"<script>window.location=('so-item-masuk.php');</script>";
?>