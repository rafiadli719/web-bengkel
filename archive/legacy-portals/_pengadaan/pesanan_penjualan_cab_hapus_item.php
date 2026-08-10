<?php
	include "../config/koneksi.php";

        $sid = $_GET['sid'];
        $stgl = $_GET['stgl'];
        $cbocabang = $_GET['ssup'];
        $kd = $_GET['kd'];
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblorderjual_detail 
                                    WHERE 
                                    id='$sid'");

            echo"<script>window.location=('pesanan_penjualan_cab_add_rst.php?stgl=$stgl&ssup=$cbocabang&kd=$kd');</script>";			            
?>