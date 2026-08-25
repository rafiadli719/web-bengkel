<?php
	include "../config/koneksi.php";

        $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
        $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
        $ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
        $ssales = mysqli_real_escape_string($koneksi, $_GET['ssales']);
        $spesan = mysqli_real_escape_string($koneksi, $_GET['spesan']);
        $kdbrg = "";
    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblpenjualan_detail 
                                    WHERE 
                                    id='$sid'");

        echo"<script>window.location=('penjualan_add_rst.php?stgl=$stgl&ssup=$ssup&ssales=$ssales&kd=$kdbrg&spesan=$spesan');</script>";			            
?>