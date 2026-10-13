<?php
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (empty($_SESSION["_iduser"])) { header("location:../index.php"); exit; } // hanya user login
	include "../config/koneksi.php";

	$sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
    $ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
    $spesan=mysqli_real_escape_string($koneksi, $_GET['spesan']);
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblpembelian_detail 
                                    WHERE 
                                    id='$sid' AND status_trx='0'");

    echo"<script>window.location=('pembelian_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kdbrg&spesan=$spesan');</script>";			            
?>