<?php
	include "../config/koneksi.php";

	$sid = $_GET['sid'];
    $stgl = $_GET['stgl'];
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbitem_masuk_detail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('stok_masuk_add_rst.php?stgl=$stgl&kd=$kdbrg');</script>";			            
?>