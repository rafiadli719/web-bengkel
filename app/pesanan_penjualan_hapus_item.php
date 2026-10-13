<?php
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (empty($_SESSION["_iduser"])) { header("location:../index.php"); exit; } // hanya user login
	include "../config/koneksi.php";

        $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
        $stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
        $ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
        $ssales = mysqli_real_escape_string($koneksi, $_GET['ssales']);
        $kd = mysqli_real_escape_string($koneksi, $_GET['kd']);
    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tblorderjual_detail 
                                    WHERE 
                                    id='$sid' AND status_trx='0'");

            echo"<script>window.location=('pesanan_penjualan_add_rst.php?stgl=$stgl&ssup=$ssup&kd=$kd&ssales=$ssales');</script>";			            
?>