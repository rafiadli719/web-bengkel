<?php
	include "../config/koneksi.php";

    $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $txtnamawo = mysqli_real_escape_string($koneksi, $_GET['snamawo']);
    $txtketwo = mysqli_real_escape_string($koneksi, $_GET['sketwo']);
    $kdbrg="";
    $kdjasa="";
            
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbworkorderdetail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('paket_add_rst.php?snamawo=$txtnamawo&sketwo=$txtketwo&kd=$kdbrg&kdjasa=$kdjasa');</script>";			            
?>