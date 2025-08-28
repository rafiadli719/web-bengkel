<?php
	include "../config/koneksi.php";

        $sid = $_GET['sid'];
        $stgl = $_GET['stgl'];
        $ssup = $_GET['ssup'];
        $ssales = $_GET['ssales'];
        $spesan = $_GET['spesan'];
        $kdbrg = "";
    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblpenjualan_detail 
                                    WHERE 
                                    id='$sid'");

        echo"<script>window.location=('penjualan_add_rst.php?stgl=$stgl&ssup=$ssup&ssales=$ssales&kd=$kdbrg&spesan=$spesan');</script>";			            
?>