<?php
	include "../config/koneksi.php";

	$sid = $_GET['sid'];
    $stgl = $_GET['stgl'];
    $ssup = $_GET['ssup'];
    $spesan=$_GET['spesan'];
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblpembelian_detail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('pembelian_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kdbrg&spesan=$spesan');</script>";			            
?>