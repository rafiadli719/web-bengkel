<?php
	include "../config/koneksi.php";

	$sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbitem_masuk_detail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('stok_masuk_add_rst.php?stgl=$stgl&kd=$kdbrg');</script>";			            
?>