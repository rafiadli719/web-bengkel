<?php
	include "../config/koneksi.php";

    $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $kdwo = mysqli_real_escape_string($koneksi, $_GET['kdwo']);
    $kdbrg="";
    $kdjasa="";
            
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbworkorderdetail 
                                    WHERE 
                                    id='$sid'");

    echo"<script>window.location=('paket_editd.php?kdwo=$kdwo&kd=$kdbrg&kdjasa=$kdjasa');</script>";			            
?>