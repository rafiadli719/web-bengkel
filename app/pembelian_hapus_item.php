<?php
	include "../config/koneksi.php";

	$sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
    $ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
    $spesan=mysqli_real_escape_string($koneksi, $_GET['spesan']);
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblpembelian_detail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('pembelian_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kdbrg&spesan=$spesan');</script>";			            
?>