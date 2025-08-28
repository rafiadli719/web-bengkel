<?php
	include "../config/koneksi.php";

    $sid = $_GET['sid'];
    $txtnamawo = $_GET['snamawo'];
    $txtketwo = $_GET['sketwo'];
    $kdbrg="";
    $kdjasa="";
            
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbworkorderdetail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('paket_add_rst.php?snamawo=$txtnamawo&sketwo=$txtketwo&kd=$kdbrg&kdjasa=$kdjasa');</script>";			            
?>