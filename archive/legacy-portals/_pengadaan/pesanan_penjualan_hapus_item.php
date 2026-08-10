<?php
	include "../config/koneksi.php";

        $sid = $_GET['sid'];
        $stgl = $_GET['stgl'];
        $ssup = $_GET['ssup'];
        $ssales = $_GET['ssales'];
        $kd = $_GET['kd'];
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblorderjual_detail 
                                    WHERE 
                                    id='$sid'");

            echo"<script>window.location=('pesanan_penjualan_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kd&ssales=$ssales');</script>";			            
?>