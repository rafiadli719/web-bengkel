<?php
	include "../config/koneksi.php";

    $sid = $_GET['sid'];
    $no_service = $_GET['snoserv'];
    
    $modal=mysqli_query($koneksi,"Delete FROM tbservis_pengerjaan WHERE id='$sid'");

    $kdbrg="";
    $kdjasa="";
    echo"<script>window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$kdbrg&kdjasa=$kdjasa');</script>";        
?>