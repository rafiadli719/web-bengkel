<?php
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (empty($_SESSION["_iduser"])) { header("location:../index.php"); exit; } // hanya user login
	include "../config/koneksi.php";

	$sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
    $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
    $kdbrg="";
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbitem_keluar_detail 
                                    WHERE 
                                    id='$sid' AND status_trx='0'");

    echo"<script>window.location=('stok_keluar_add_rst.php?stgl=$stgl&kd=$kdbrg');</script>";			            
?>