<?php
	include "../config/koneksi.php";

    $sid = $_GET['sid'];
    $kdwo = $_GET['kdwo'];
    $kdbrg="";
    $kdjasa="";
            
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbworkorderdetail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('paket_editd.php?kdwo=$kdwo&kd=$kdbrg&kdjasa=$kdjasa');</script>";			            
?>