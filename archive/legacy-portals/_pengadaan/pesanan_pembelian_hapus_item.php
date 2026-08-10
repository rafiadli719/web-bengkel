<?php
	include "../config/koneksi.php";

	$sid = $_GET['sid'];
    $stgl = $_GET['stgl'];
    $ssup = $_GET['ssup'];
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblorder_detail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('pesanan_pembelian_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kdbrg');</script>";			            
?>