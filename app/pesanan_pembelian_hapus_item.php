<?php
	include "../config/koneksi.php";

	$sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
    $ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblorder_detail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('pesanan_pembelian_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kdbrg');</script>";			            
?>